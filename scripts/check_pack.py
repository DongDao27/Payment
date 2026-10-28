#!/usr/bin/env python3
"""Document consistency only; never claims to test the application."""
import json
import re
from pathlib import Path
from urllib.parse import unquote
ROOT = Path(__file__).resolve().parents[1]
def load(name):
    return json.loads((ROOT/name).read_text(encoding="utf-8"))
def main():
    required = ["README.md","AGENTS.md","START-HERE.md","docs/PRD.md",
        "docs/DATABASE.md","docs/DOMAIN-RULES.md",
        "docs/API.md","docs/openapi.json",
        "docs/TASKS.json","docs/STATUS.json","docs/HANDOFF.md","docs/PROGRESS.md",
        "docs/DECISIONS.md","docs/CHAY-WEB-VA-LARAVEL.md","docs/CLEANUP-2026-10-28.json",
        "backend/.env.example","backend/.env.testing.example","frontend/.env.example",
        "deploy/.env.example","deploy/.env.backend.example"]
    for name in required: assert (ROOT/name).is_file(),name
    doc=load("docs/TASKS.json")
    tasks={t["id"]:t for t in doc["tasks"]}
    assert len(tasks)==len(doc["tasks"])
    visiting,done=set(),set()
    def visit(tid):
        assert tid in tasks and tid not in visiting,tid
        if tid in done: return
        visiting.add(tid)
        t=tasks[tid]
        assert t["status"] in doc["status_values"]
        assert t["acceptance"] and t["required_checks"]
        for dep in t["depends_on"]: visit(dep)
        if t["status"]=="done":
            assert t["evidence"],tid
            assert all(tasks[d]["status"]=="done" for d in t["depends_on"])
        visiting.remove(tid);done.add(tid)
    for tid in tasks: visit(tid)
    status=load("docs/STATUS.json")
    for k in ("current_task_id","next_task_id"):
        if status.get(k): assert status[k] in tasks
    if status["backend_gate"]!="passed":
        if status["frontend_gate"] == "authorized_scoped":
            assert status.get("frontend_authorization", {}).get("decision") == "D34"
            assert "## D34" in (ROOT/"docs/DECISIONS.md").read_text(encoding="utf-8")
        else:
            assert status["frontend_gate"]=="locked"
            assert all(t["status"]=="todo" for t in tasks.values() if t["phase"]=="FRONTEND")
    functions={f"F{i:02}" for i in range(1,23)}
    mapped={f for t in tasks.values() if t["phase"]=="BACKEND" for f in t["functions"]}
    assert functions<=mapped
    spec=load("docs/openapi.json")
    assert spec["openapi"]=="3.0.3"
    def refs(node):
        if isinstance(node,dict):
            if "$ref" in node:
                target=node["$ref"];assert target.startswith("#/")
                obj=spec
                for part in target[2:].split("/"):
                    obj=obj[part.replace("~1","/").replace("~0","~")]
                assert obj is not None
            for value in node.values():refs(value)
        elif isinstance(node,list):
            for value in node:refs(value)
    refs(spec)
    opids=[]
    for path,ops in spec["paths"].items():
        for method,op in ops.items():
            if "operationId" in op:opids.append(op["operationId"])
            assert op.get("responses"),(method,path)
            params={p["name"] for p in op.get("parameters",[]) if p["in"]=="path"}
            assert params==set(re.findall(r"\{([^}]+)\}",path)),path
    assert len(opids)==len(set(opids))
    # Executable tests are retained; the redundant manual case spreadsheet was archived.
    for suite, pattern in (("backend/tests", "*Test.php"), ("frontend/tests", "*.test.ts")):
        assert list((ROOT/suite).rglob(pattern)),suite
    for name in ("auth-cookie-smoke.php", "ops-smoke.php"):
        assert (ROOT/"scripts"/name).is_file(),name
        assert name in (ROOT/".github/workflows/backend.yml").read_text(encoding="utf-8"),name
    archived=set()
    for manifest in ("docs/CLEANUP-2026-10-24.json","docs/CLEANUP-2026-10-28.json"):
        cleanup=load(manifest)
        members={entry["path"] for entry in cleanup["files"]}
        assert len(members)==cleanup["file_count"]
        assert all(len(entry["sha256"])==64 for entry in cleanup["files"])
        archived.update(members)
    markdown = [ROOT/"README.md", ROOT/"AGENTS.md", ROOT/"START-HERE.md"]
    markdown += list((ROOT/"docs").rglob("*.md"))
    markdown += [ROOT/"frontend/AGENTS.md",ROOT/"deploy/README.md",ROOT/"thu-thanh-figma-clicon/HUONG-DAN.md"]
    links=0
    for file in markdown:
        content=file.read_text(encoding="utf-8")
        assert content.count(chr(96)*3)%2==0,str(file)
        for target in re.findall(r"!?\[[^\]]*\]\(([^)]+)\)",content):
            if target.startswith(("http:","https:","mailto:","tel:","#")):
                continue
            target=unquote(target.split("#",1)[0].strip("<>"))
            path=(file.parent/target).resolve()
            # Historical checkpoint citations can resolve through the cleanup manifest.
            historical=file.name in ("HANDOFF.md","PROGRESS.md","DECISIONS.md")
            archived_target=path.is_relative_to(ROOT) and path.relative_to(ROOT).as_posix() in archived
            assert path.exists() or (historical and archived_target),(str(file),target)
            links+=1
    for env in (ROOT/"backend/.env.example", ROOT/"frontend/.env.example", ROOT/"deploy/.env.backend.example"):
        for line in env.read_text().splitlines():
            if line.startswith(("VNPAY_HASH_SECRET=","ADMIN_PASSWORD=","DB_PASSWORD=")):
                assert not line.split("=",1)[1],str(env)
    print(f"PASS — docs only: {len(required)} required files; {len(tasks)} tasks; "
          f"{len(functions)} functions; {len(spec['paths'])} API paths; {links} local links; executable test suites retained.")
    print("Application implementation/integration tests: NOT RUN.")
if __name__=="__main__":main()
