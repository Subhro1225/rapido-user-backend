/**
 * tests/test_booking.js
 * Phase 4 — Booking endpoint test cases.
 * Runs against ../user/book_ride.php via fetch (requires active PHP session).
 */

'use strict';

const ENDPOINT = '../user/book_ride.php';

const CASES = [
    {
        id: 1,
        label: 'CASE 1 — Valid booking',
        body: { pickup_location: 'Koramangala', destination: 'Indiranagar' },
        expect: (data, status) =>
            status === 200 &&
            data.success === true &&
            typeof data.ride_id === 'number' && data.ride_id > 0 &&
            /^\d{4}$/.test(data.otp) &&
            typeof data.fare === 'number' && data.fare >= 50 &&
            typeof data.distance === 'number' &&
            data.status === 'waiting',
    },
    {
        id: 2,
        label: 'CASE 2 — Same pickup and destination',
        body: { pickup_location: 'Koramangala', destination: 'Koramangala' },
        expect: (data, status) =>
            status === 422 &&
            data.success === false,
    },
    {
        id: 3,
        label: 'CASE 3 — Empty fields',
        body: { pickup_location: '', destination: '' },
        expect: (data, status) =>
            status === 422 &&
            data.success === false &&
            Array.isArray(data.errors) && data.errors.length > 0,
    },
    {
        id: 4,
        label: 'CASE 4 — Numeric-only locations',
        body: { pickup_location: '12345', destination: '67890' },
        expect: (data, status) =>
            status === 422 &&
            data.success === false,
    },
];

// ── DOM helpers ───────────────────────────────────────────────────────────────

function renderCases() {
    const container = document.getElementById('cases');
    container.innerHTML = CASES.map(c => `
        <div class="case" id="case-${c.id}">
            <div class="case-header">
                <span class="case-title">${c.label}</span>
                <span class="badge pending" id="badge-${c.id}">PENDING</span>
            </div>
            <div class="response" id="resp-${c.id}">—</div>
        </div>
    `).join('');
}

function setResult(id, passed, responseText) {
    const badge = document.getElementById(`badge-${id}`);
    const resp  = document.getElementById(`resp-${id}`);
    badge.textContent = passed ? 'PASS' : 'FAIL';
    badge.className   = 'badge ' + (passed ? 'pass' : 'fail');
    resp.textContent  = responseText;
}

// ── Runner ────────────────────────────────────────────────────────────────────

async function runCase(c) {
    const form = new FormData();
    Object.entries(c.body).forEach(([k, v]) => form.append(k, v));

    try {
        const res  = await fetch(ENDPOINT, { method: 'POST', body: form });
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch { data = {}; }

        const passed = c.expect(data, res.status);
        setResult(c.id, passed, text);
        return passed;
    } catch (err) {
        setResult(c.id, false, 'Network error: ' + err.message);
        return false;
    }
}

async function runAll() {
    document.getElementById('summary').textContent = 'Running…';
    const results = await Promise.all(CASES.map(runCase));
    const passed  = results.filter(Boolean).length;
    document.getElementById('summary').textContent =
        `${passed}/${CASES.length} tests passed`;
}

// ── Init ──────────────────────────────────────────────────────────────────────

renderCases();
document.getElementById('run-all').addEventListener('click', runAll);
