import http from 'k6/http';
import { check, sleep } from 'k6';

const base = (__ENV.BASE_URL || 'http://localhost:8081').replace(/\/$/, '');
const highLoad = __ENV.PROFILE === '10k';
if (highLoad && __ENV.ALLOW_HIGH_LOAD !== '1') throw new Error('10k profile requires ALLOW_HIGH_LOAD=1 on infrastructure you control.');
export const options = {
  stages: highLoad ? [{ duration: '1m', target: 100 }, { duration: '2m', target: 1000 }, { duration: '3m', target: 10000 }, { duration: '5m', target: 10000 }, { duration: '1m', target: 0 }] : [{ duration: '10s', target: 20 }, { duration: '20s', target: 20 }, { duration: '5s', target: 0 }],
  thresholds: { checks: ['rate>0.99'], http_req_failed: ['rate<0.01'], http_req_duration: ['p(95)<1000', 'p(99)<2000'] },
};
export default function () {
  const response = http.get(`${base}/api/v1/storefront`, { tags: { flow: 'anonymous-home' }, timeout: '10s' });
  check(response, { 'snapshot200': r => r.status === 200, 'no cookie': r => !r.headers['Set-Cookie'], 'public': r => (r.headers['Cache-Control'] || '').includes('public'), 'not personalized': r => !r.body.includes('recipient_phone') });
  sleep(3);
}
