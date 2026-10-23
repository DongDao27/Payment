import http from 'node:http';
import { performance } from 'node:perf_hooks';
import { writeFile } from 'node:fs/promises';

const target = new URL(process.argv[2] || 'http://localhost:18081/api/v1/storefront');
if (!['localhost', '127.0.0.1', '[::1]'].includes(target.hostname) || target.protocol !== 'http:' || target.pathname !== '/api/v1/storefront') throw new Error('Smoke test is restricted to the local public snapshot.');
const concurrency = Math.min(100, Math.max(1, Number(process.argv[3] || 20)));
const total = Math.min(2000, Math.max(concurrency, Number(process.argv[4] || 200)));
if (!Number.isInteger(total) || !Number.isInteger(concurrency)) throw new Error('Use integer concurrency and request counts.');
const agent = new http.Agent({ keepAlive: true, maxSockets: concurrency });
const durations = []; let failed = 0, next = 0, cookies = 0;
function get() { return new Promise(resolve => { const start = performance.now(); const req = http.get(target, { agent }, res => { let body=''; res.on('data', data => body+=data); res.on('end', () => { durations.push(performance.now()-start); if(res.headers['set-cookie']) cookies++; try { if (res.statusCode !== 200 || !JSON.parse(body).data.new) failed++; } catch { failed++; } resolve(); }); }); req.setTimeout(15000, () => req.destroy(new Error('timeout'))); req.on('error', () => { failed++; resolve(); }); }); }
await get(); durations.length = 0; // warm up separately
const started = performance.now();
await Promise.all(Array.from({ length: concurrency }, async () => { while (next++ < total) await get(); }));
const elapsed = performance.now()-started; agent.destroy(); durations.sort((a,b)=>a-b);
const percentile = p => Number((durations[Math.min(durations.length-1, Math.ceil(durations.length*p)-1)] || 0).toFixed(2));
const result = { at: new Date().toISOString(), target:target.origin+target.pathname, environment:'local PHP development server; not production capacity proof', concurrency, requests:total, failed, cookies, elapsed_ms:Math.round(elapsed), requests_per_second:Number((total*1000/elapsed).toFixed(2)), p50_ms:percentile(.5), p95_ms:percentile(.95), p99_ms:percentile(.99), ten_thousand_verified:false };
console.log(JSON.stringify(result));
await writeFile(new URL('../../docs/qa/DUI09-load-smoke.json', import.meta.url), JSON.stringify(result,null,2)+'\n');
if (failed || cookies) process.exitCode=1;
