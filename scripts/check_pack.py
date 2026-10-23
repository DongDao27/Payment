#!/usr/bin/env python3
"""Document consistency only; never claims to test the application."""
import csv
import json
import re
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
def load(name):
    return json.loads((ROOT/name).read_text(encoding="utf-8"))
def main():
    required = ["README.md","AGENTS.md","START-HERE.md","docs/PRD.md",
        "docs/ARCHITECTURE.md","docs/DATABASE.md","docs/DOMAIN-RULES.md",
        "docs/API.md","docs/openapi.json","docs/TEST-PLAN.md","docs/test-cases.csv",
        "docs/TASKS.json","docs/STATUS.json","docs/HANDOFF.md","docs/PROGRESS.md",
        "docs/DECISIONS.md","docs/FIGMA-HANDOFF.md","docs/OPERATIONS.md",
        "docs/SECURITY.md","docs/KNOWN-LIMITATIONS.md",
        "docs/TRACEABILITY.md","docs/SOURCES.md","docs/CHAY-WEB-VA-LARAVEL.md","docs/VERIFICATION.md","docs/MD-CLEANUP-MANIFEST.json",
        "templates/backend.env.example","templates/frontend.env.example"]
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
    with (ROOT/"docs/test-cases.csv").open(encoding="utf-8",newline="") as f:
        cases=list(csv.DictReader(f))
    assert len({c["case_id"] for c in cases})==len(cases)
    for c in cases:assert c["task_id"] in tasks and c["scenario"] and c["expected"]
    for tid in tasks:
        if tid!="B00":assert any(c["task_id"]==tid for c in cases),tid
    markdown = [ROOT/"README.md", ROOT/"AGENTS.md", ROOT/"START-HERE.md"]
    markdown += list((ROOT/"docs").rglob("*.md")) + list((ROOT/"prompts").rglob("*.md"))
    for file in markdown:
        assert file.read_text(encoding="utf-8").count(chr(96)*3)%2==0,str(file)
    for env in (ROOT/"templates").glob("*.env.example"):
        for line in env.read_text().splitlines():
            if line.startswith(("VNPAY_HASH_SECRET=","ADMIN_PASSWORD=","DB_PASSWORD=")):
                assert not line.split("=",1)[1],str(env)
    print(f"PASS — docs only: {len(required)} required files; {len(tasks)} tasks; "
          f"{len(functions)} functions; {len(spec['paths'])} API paths; {len(cases)} test scenarios.")
    print("Application implementation/integration tests: NOT RUN.")
if __name__=="__main__":main()
