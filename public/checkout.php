<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
if (class_exists('Dotenv\\Dotenv')) {
    try {
        Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    } catch (Throwable $e) {}
}
// Simple Selcom Checkout integration script for Phidtech SMS
// Lives in public/ so it can be called at /checkout.php

// ---------- CONFIG LOADING ----------
// Allow values from a local PHP .env file (variables like $SELCOM_VENDOR),
// then fall back to environment variables, and finally to defaults.
$localEnvPath = __DIR__.'/.env';
if (file_exists($localEnvPath)) {
    // This .env should be a PHP file that sets variables, e.g. $SELCOM_VENDOR = '...';
    include $localEnvPath;
}

$SELCOM_VENDOR     = ($_ENV['SELCOM_VENDOR_ID'] ?? getenv('SELCOM_VENDOR_ID'))     ?: (isset($SELCOM_VENDOR) ? $SELCOM_VENDOR : 'SHOP203');
$SELCOM_API_KEY    = ($_ENV['SELCOM_API_KEY'] ?? getenv('SELCOM_API_KEY'))    ?: (isset($SELCOM_API_KEY) ? $SELCOM_API_KEY : 'your_api_key');
$SELCOM_API_SECRET = ($_ENV['SELCOM_API_SECRET'] ?? getenv('SELCOM_API_SECRET')) ?: (isset($SELCOM_API_SECRET) ? $SELCOM_API_SECRET : 'your_api_secret');

// IMPORTANT: Make sure there are NO quotes/backticks/spaces around the base URL
// Example live:    https://apigw.selcommobile.com
// Example sandbox: https://apigwtest.selcommobile.com
$SELCOM_IS_LIVE    = ($_ENV['SELCOM_IS_LIVE'] ?? getenv('SELCOM_IS_LIVE')) ?: (isset($SELCOM_IS_LIVE) ? $SELCOM_IS_LIVE : true);
$SELCOM_IS_LIVE    = filter_var($SELCOM_IS_LIVE, FILTER_VALIDATE_BOOLEAN);
$SELCOM_BASE_URL   = $SELCOM_IS_LIVE ? 'https://apigw.selcommobile.com' : 'https://apigwtest.selcommobile.com';

// ---------- APP URL ----------
$scheme   = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'));
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$APP_URL  = $scheme . '://' . $host;
// App branding
$APP_NAME = ($_ENV['APP_NAME'] ?? getenv('APP_NAME')) ?: 'RodLine SMS';
// Derive order prefix from app name (alphanumerics only)
$orderPrefix = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $APP_NAME));

// ---------- RATE ----------
$rate = 30; // TZS per SMS

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $title = 'Buy SMS Credits';
    $csrf = '';
    $cfgError = (empty($SELCOM_VENDOR) || $SELCOM_API_KEY === 'your_api_key' || $SELCOM_API_SECRET === 'your_api_secret');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>'.$title.'</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-gray-50 min-h-screen"><div class="max-w-md mx-auto px-4 py-8"><div class="bg-white rounded-2xl shadow-xl overflow-hidden"><div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-5"><h1 class="text-white text-lg font-semibold">'.$title.'</h1><p class="text-indigo-100 text-sm">Secure payment powered by Selcom</p></div><div class="p-6 space-y-4">'
        .($cfgError ? '<div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">Configuration required: set SELCOM_VENDOR, SELCOM_API_KEY, SELCOM_API_SECRET in .env</div>' : '')
        .'<form id="checkoutForm" class="space-y-4" method="post" action="/checkout.php">'
            .'<div><label class="block text-sm font-medium text-gray-700">Sender ID</label><input name="senderId" type="text" required class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Your Sender ID"></div>'
            .'<div><label class="block text-sm font-medium text-gray-700">Email</label><input name="email" type="email" required class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="you@example.com"></div>'
            .'<div><label class="block text-sm font-medium text-gray-700">Phone</label><input name="phone" type="tel" required class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="255XXXXXXXXX"></div>'
            .'<div><label class="block text-sm font-medium text-gray-700">SMS Quantity</label><input name="smsQuantity" id="smsQty" type="number" min="1" value="50" required class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"></div>'
            .'<div class="flex items-center justify-between bg-gray-50 border rounded-xl px-4 py-3"><span class="text-sm text-gray-600">Rate</span><span class="text-sm font-semibold text-gray-900">TZS '.number_format($rate).'/SMS</span></div>'
            .'<div class="flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3"><span class="text-sm text-indigo-700">Total</span><span id="totalAmount" class="text-lg font-bold text-indigo-700">TZS '.number_format(50*$rate).'</span></div>'
            .'<button type="submit" id="payBtn" class="w-full inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-white font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Proceed to Pay</button>'
        .'</form>'
        .'<div id="errorBox" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>'
        .'</div></div><p class="text-center text-xs text-gray-400 mt-4">&copy; '.date('Y').' '.htmlspecialchars($APP_NAME).'</p></div>'
        .'<script>const qty=document.getElementById("smsQty");const total=document.getElementById("totalAmount");const form=document.getElementById("checkoutForm");const btn=document.getElementById("payBtn");const err=document.getElementById("errorBox");function upd(){const q=parseInt(qty.value||"0");const amt=Math.max(1,q)*'+$rate+';total.textContent="TZS "+amt.toLocaleString();}qty.addEventListener("input",upd);form.addEventListener("submit",async function(e){e.preventDefault();err.classList.add("hidden");btn.disabled=true;btn.textContent="Processing...";try{const fd=new FormData(form);const res=await fetch(form.action,{method:"POST",body:fd});const data=await res.json();if(data&&data.success&&data.redirect){window.location.href=data.redirect;return}throw new Error((data&&data.error)||"Payment failed");}catch(ex){err.textContent=ex.message;err.classList.remove("hidden");btn.disabled=false;btn.textContent="Proceed to Pay";} });upd();</script></body></html>';
    exit;
}

// ---------- INPUT VALIDATION ----------
$senderId  = trim($_POST['senderId'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phoneRaw  = trim($_POST['phone'] ?? '');
$phone     = preg_replace('/\D+/', '', $phoneRaw); // digits only
$qty       = max(1, (int)($_POST['smsQuantity'] ?? 0));
$amount    = $qty * $rate;

if (!$senderId || !$email || !$phone || $amount < 1) {
    http_response_code(422);
    echo 'Missing or invalid fields.';
    exit;
}

if (empty($SELCOM_VENDOR) || $SELCOM_API_KEY === 'your_api_key' || $SELCOM_API_SECRET === 'your_api_secret') {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuration error']);
    exit;
}

// Ensure phone is E.164 with country code if missing (basic heuristic)
if (strpos($phone, '255') !== 0) {
    $phone = '255' . ltrim($phone, '0');
}

// ---------- ORDER ----------
$orderId    = $orderPrefix . time();
$redirectUrl = $APP_URL . '/payments/success';              // Laravel route exists
$cancelUrl   = $APP_URL . '/payments/cancel';               // Laravel route exists
$webhookUrl  = $APP_URL . '/payments/webhook';              // Laravel webhook route (direct)

$payload = [
    'vendor'          => $SELCOM_VENDOR,
    'order_id'        => $orderId,
    'buyer_email'     => $email,
    'buyer_name'      => $APP_NAME,
    'buyer_phone'     => $phone,
    'amount'          => $amount,
    'currency'        => 'TZS',
    'payment_methods' => 'MOBILEMONEYPULL',
    'redirect_url'    => base64_encode($redirectUrl),
    'cancel_url'      => base64_encode($cancelUrl),
    'webhook'         => base64_encode($webhookUrl),
    'no_of_items'     => 1,
    // Optional line items (uncomment if your account requires explicit items)
    // 'items' => [
    //     [
    //         'item_price' => $rate,
    //         'item_qty'   => $qty,
    //         'item_name'  => 'SMS Credits',
    //     ],
    // ],
    'billing' => [
        'firstname'        => 'RodLine',
        'lastname'         => 'SMS',
        'address_1'        => 'N/A',
        'city'             => 'Dar es Salaam',
        'state_or_region'  => 'Dar es Salaam',
        'postcode_or_pobox'=> '00000',
        'country'          => 'TZ',
        'phone'            => $phone,
    ],
];

// ---------- SIGNING ----------
$signedFields = [
    'vendor','order_id','buyer_email','buyer_name','buyer_phone',
    'amount','currency','payment_methods','redirect_url','cancel_url','webhook','no_of_items',
    'billing.firstname','billing.lastname','billing.address_1','billing.address_2','billing.city',
    'billing.state_or_region','billing.postcode_or_pobox','billing.country','billing.phone'
];

$qsParts = [];
foreach ($signedFields as $k) {
    if ($k === 'redirect_url') {
        $qsParts[] = $k . '=' . base64_encode($redirectUrl);
        continue;
    } elseif ($k === 'cancel_url') {
        $qsParts[] = $k . '=' . base64_encode($cancelUrl);
        continue;
    } elseif ($k === 'webhook') {
        $qsParts[] = $k . '=' . base64_encode($webhookUrl);
        continue;
    }
    $qsParts[] = $k . '=' . ($payload[$k] ?? '');
}

$timestamp = date('Y-m-d\TH:i:sP');
$toSign    = 'timestamp=' . $timestamp . '&' . implode('&', $qsParts);
$digest    = base64_encode(hash_hmac('sha256', $toSign, $SELCOM_API_SECRET, true));

// ---------- REQUEST ----------
$headers = [
    'Content-Type: application/json',
    'Authorization: SELCOM ' . base64_encode($SELCOM_API_KEY),
    'Digest-Method: HS256',
    'Digest: ' . $digest,
    'Timestamp: ' . $timestamp,
    'Signed-Fields: ' . implode(',', $signedFields),
];

$endpoint = rtrim($SELCOM_BASE_URL, '/') . '/v1/checkout/create-order';

// Save a minimal order record for later reconciliation (demo only)
$ordersDir = __DIR__ . '/orders';
if (!is_dir($ordersDir)) {
    @mkdir($ordersDir, 0755, true);
}
$orderFile = $ordersDir . '/' . $orderId . '.json';
@file_put_contents($orderFile, json_encode([
    'order_id' => $orderId,
    'sender_id' => $senderId,
    'email' => $email,
    'phone' => $phone,
    'quantity' => $qty,
    'amount' => $amount,
    'rate' => $rate,
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'PENDING',
], JSON_PRETTY_PRINT));

// ---------- CALL API ----------
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);
$responseBody = curl_exec($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr      = curl_error($ch);
curl_close($ch);

// ---------- HANDLE RESPONSE ----------
header('Content-Type: application/json');

if ($responseBody === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Curl error',
        'message' => $curlErr,
    ]);
    exit;
}

$json = json_decode($responseBody, true);

if ($httpCode >= 200 && $httpCode < 300) {
    $paymentUrl = null;
    $data = $json['data'] ?? null;
    if (is_array($data)) {
        if (isset($data['payment_gateway_url'])) {
            $paymentUrl = $data['payment_gateway_url'];
        } elseif (isset($data[0]) && is_array($data[0]) && isset($data[0]['payment_gateway_url'])) {
            $paymentUrl = $data[0]['payment_gateway_url'];
        } elseif (isset($data['checkout_url'])) {
            $paymentUrl = $data['checkout_url'];
        } elseif (isset($data[0]) && is_array($data[0]) && isset($data[0]['checkout_url'])) {
            $paymentUrl = $data[0]['checkout_url'];
        } elseif (isset($data['payment_url'])) {
            $paymentUrl = $data['payment_url'];
        } elseif (isset($data[0]) && is_array($data[0]) && isset($data[0]['payment_url'])) {
            $paymentUrl = $data[0]['payment_url'];
        }
    } else {
        $paymentUrl = $json['checkout_url'] ?? ($json['payment_url'] ?? null);
    }

    if (is_string($paymentUrl) && !preg_match('/^https?:\/\//i', $paymentUrl)) {
        $decoded = base64_decode($paymentUrl, true);
        if ($decoded !== false && preg_match('/^https?:\/\//i', $decoded)) {
            $paymentUrl = $decoded;
        }
    }

    echo json_encode([
        'success' => true,
        'http_code' => $httpCode,
        'endpoint' => $endpoint,
        'request' => [
            'timestamp' => $timestamp,
            'signed_fields' => $signedFields,
        ],
        'response' => $json,
        'redirect' => $paymentUrl,
    ]);
    exit;
}

// Non-2xx
http_response_code($httpCode ?: 500);
echo json_encode([
    'success' => false,
    'http_code' => $httpCode,
    'endpoint' => $endpoint,
    'request' => [
        'timestamp' => $timestamp,
        'signed_fields' => $signedFields,
    ],
    'response' => $json,
    'raw' => $responseBody,
]);
exit;
