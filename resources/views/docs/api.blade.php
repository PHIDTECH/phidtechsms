<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Phidsms API</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;margin:0;padding:0;background:#0b1020;color:#e6e8ee}
    .wrap{max-width:920px;margin:0 auto;padding:40px 16px}
    h1,h2{color:#fff}
    code{background:#151a30;padding:2px 6px;border-radius:6px}
    pre{background:#0f1428;border:1px solid #1e2749;padding:12px;border-radius:8px;overflow:auto}
    .card{background:#0f1428;border:1px solid #1e2749;border-radius:10px;padding:16px;margin:16px 0}
    a{color:#76a8f9}
    .pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#182041;color:#c3d3ff;font-size:12px;margin-left:6px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{border-bottom:1px solid #1e2749;padding:8px;text-align:left}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Phidsms API <span class="pill">v1</span></h1>
    <p>Base URL: <code>https://rodlinesms.co.tz/api/v1</code></p>

    <div class="card">
      <h2>Authentication</h2>
      <p>Use your API Key and Secret (create one in the dashboard at <code>/user/api-keys</code>).</p>
      <ul>
        <li>Headers: <code>X-API-KEY: rk_xxx</code>, <code>X-API-SECRET: rs_xxx</code></li>
        <li>Or Basic Auth: <code>Authorization: Basic base64(rk_xxx:rs_xxx)</code></li>
      </ul>
      <p>Requests are rate limited per key (default 60/min unless you set a custom value). Optional IP allowlist can further restrict access.</p>
    </div>

    <div class="card">
      <h2>Send SMS</h2>
      <p>POST <code>/sms/send</code></p>
      <table class="table">
        <thead><tr><th>Field</th><th>Type</th><th>Description</th></tr></thead>
        <tbody>
          <tr><td>to</td><td>string | array</td><td>Single number or list (E.164, e.g., <code>2557...</code>). Comma/space separated when string.</td></tr>
          <tr><td>message</td><td>string</td><td>Message body (1–1000 chars). GSM-7/Unicode supported.</td></tr>
          <tr><td>sender_id</td><td>string?</td><td>Optional approved sender ID (<= 11 chars).</td></tr>
        </tbody>
      </table>
<pre><code>curl -X POST https://rodlinesms.co.tz/api/v1/sms/send \
  -H "X-API-KEY: rk_XXXXXXXX" \
  -H "X-API-SECRET: rs_YYYYYYYY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": ["255712345678", "255765432109"],
    "message": "Hello from Phidsms API",
    "sender_id": "RODLINE"
  }'
</code></pre>
      <p>Response</p>
<pre><code>{
  "success": true,
  "message_id": "req_abc123",
  "cost_credits": 2,
  "remaining_credits": 998,
  "provider_response": { /* passthrough */ }
}
</code></pre>
    </div>

    <div class="card">
      <h2>Errors</h2>
      <table class="table">
        <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
        <tbody>
          <tr><td>401</td><td>Missing/invalid credentials</td></tr>
          <tr><td>402</td><td>Insufficient SMS credits</td></tr>
          <tr><td>403</td><td>IP not allowed (if allowlist set)</td></tr>
          <tr><td>429</td><td>Rate limit exceeded</td></tr>
          <tr><td>502</td><td>Upstream provider error</td></tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Notes</h2>
      <ul>
        <li>Credits are charged per recipient per message part (GSM-7: 160 chars per part, Unicode: 70; concatenated parts apply).</li>
        <li>Webhook delivery reports can be added on request.</li>
        <li>Contact support at <a href="mailto:support@rodlinesms.co.tz">support@rodlinesms.co.tz</a>.</li>
      </ul>
    </div>
  </div>
</body>
</html>

