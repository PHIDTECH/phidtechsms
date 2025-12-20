<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Bryceandy\Selcom\Facades\Selcom;

class PaymentController extends Controller
{
    /**
     * Display the payment top-up page
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $statusFilter = strtolower($request->query('status', 'all'));

        $statusMap = [
            'all' => null,
            'pending' => 'pending',
            'paid' => 'completed',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'failed' => 'failed',
        ];

        if (!array_key_exists($statusFilter, $statusMap)) {
            $statusFilter = 'all';
        }

        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        $paymentsQuery = Payment::query()
            ->with(['user:id,name,email,phone'])
            ->when(!$isAdmin, fn($query) => $query->where('user_id', $user->id))
            ->orderBy('created_at', 'desc');

        if ($statusMap[$statusFilter]) {
            $paymentsQuery->where('status', $statusMap[$statusFilter]);
        }

        $payments = $paymentsQuery->paginate(10)->withQueryString();

        $statusCountsRaw = Payment::query()
            ->when(!$isAdmin, fn($query) => $query->where('user_id', $user->id))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusCounts = [
            'all' => $statusCountsRaw->sum(),
            'pending' => $statusCountsRaw->get('pending', 0),
            'paid' => $statusCountsRaw->get('completed', 0),
            'cancelled' => $statusCountsRaw->get('cancelled', 0),
            'failed' => $statusCountsRaw->get('failed', 0),
        ];

        return view('payments.index', [
            'user' => $user,
            'payments' => $payments,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Create a new payment request
     */
    public function create(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000|max:100000000',
            'payment_method' => 'required|in:selcom,mobile_money,card',
            'phone' => 'nullable|string'
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        
        // Calculate credits (TZS 30 per SMS)
        $credits = floor($amount / 30);
        
        try {
            DB::beginTransaction();
            
            // Create payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'credits' => $credits,
                'currency' => 'TZS',
                'payment_method' => 'selcom',
                'status' => 'pending',
                'reference' => 'PAY_' . time() . '_' . $user->id,
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            // Normalize MSISDN for Selcom (expects 255XXXXXXXXX)
            $phone = $user->phone_number ?? ($request->input('phone') ?? '');
            if ($phone) {
                $digits = preg_replace('/\D+/', '', $phone);
                if (str_starts_with($digits, '0')) {
                    $digits = '255' . substr($digits, 1);
                } elseif (!str_starts_with($digits, '255') && str_starts_with($phone, '+255')) {
                    $digits = substr($digits, 1);
                }
                $phone = $digits;
            }
            if (empty($phone) || !str_starts_with($phone, '255')) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Valid phone (255XXXXXXXXX) is required for Selcom checkout.'
                ], 422);
            }

            // Build Selcom checkout payload (package method is `checkout`)
            $host = $request->getSchemeAndHttpHost();
            $payload = [
                'name' => $user->name ?: 'Customer',
                'email' => $user->email ?: ('user'.$user->id.'@phidsms.local'),
                'phone' => $phone ?: ($user->phone_number ?: ''),
                'amount' => (string) $amount,
                'currency' => 'TZS',
                'transaction_id' => $payment->reference,
                'redirect_url' => base64_encode($host.'/payments/success'),
                'cancel_url' => base64_encode($host.'/payments/cancel'),
                'webhook' => base64_encode($host.'/payments/webhook'),
            ];

            // If phone is empty, omit it to avoid sending an invalid value
            if (empty($payload['phone'])) {
                $payload['phone'] = $phone;
            }

            // Non-AJAX form submission: let the package handle the redirect response
            if (!$request->expectsJson() && !$request->ajax()) {
                DB::commit();
                return Selcom::checkout($payload);
            }

            // For AJAX/JSON callers, avoid redirection and return the URL
            $selcomData = Selcom::checkout(array_merge($payload, ['no_redirection' => true]));

            $paymentUrl = null;
            if (is_array($selcomData)) {
                if (isset($selcomData['payment_gateway_url'])) {
                    $paymentUrl = $selcomData['payment_gateway_url'];
                } elseif (isset($selcomData['data'][0]['payment_gateway_url'])) {
                    $paymentUrl = $selcomData['data'][0]['payment_gateway_url'];
                } elseif (isset($selcomData['data']['payment_gateway_url'])) {
                    $paymentUrl = $selcomData['data']['payment_gateway_url'];
                }
            }

            if (is_string($paymentUrl) && !preg_match('/^https?:\/\//i', $paymentUrl)) {
                $decoded = base64_decode($paymentUrl, true);
                if ($decoded !== false && preg_match('/^https?:\/\//i', $decoded)) {
                    $paymentUrl = $decoded;
                }
            }

            if ($paymentUrl) {
                $payment->update([
                    'gateway_response' => $selcomData
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'payment_id' => $payment->id,
                    'credits' => $credits
                ]);
            }

            // Fallback: call Selcom create-order directly (live)
            $vendor = (string) config('selcom.vendor');
            $apiKey = (string) config('selcom.key');
            $secret = (string) config('selcom.secret');
            $isLive = (bool) config('selcom.live');
            $baseUrl = $isLive ? 'https://apigw.selcommobile.com' : 'https://apigwtest.selcommobile.com';

            $orderId = $payment->reference;
            $redirectUrl = $host.'/payments/success';
            $cancelUrl   = $host.'/payments/cancel';
            $webhookUrl  = $host.'/payments/webhook';

            $directPayload = [
                'vendor'          => $vendor,
                'order_id'        => $orderId,
                'buyer_email'     => $payload['email'],
                'buyer_name'      => $payload['name'],
                'buyer_phone'     => $payload['phone'] ?? '',
                'amount'          => (int) $amount,
                'currency'        => 'TZS',
                'payment_methods' => 'ALL',
                'redirect_url'    => base64_encode($redirectUrl),
                'cancel_url'      => base64_encode($cancelUrl),
                'webhook'         => base64_encode($webhookUrl),
                'no_of_items'     => 1,
            ];

            $signedFields = [
                'vendor','order_id','buyer_email','buyer_name','buyer_phone',
                'amount','currency','payment_methods','redirect_url','cancel_url','webhook','no_of_items'
            ];

            $qsParts = [];
            foreach ($signedFields as $k) {
                if ($k === 'redirect_url') {
                    $qsParts[] = $k . '=' . base64_encode($redirectUrl);
                } elseif ($k === 'cancel_url') {
                    $qsParts[] = $k . '=' . base64_encode($cancelUrl);
                } elseif ($k === 'webhook') {
                    $qsParts[] = $k . '=' . base64_encode($webhookUrl);
                } else {
                    $qsParts[] = $k . '=' . ($directPayload[$k] ?? '');
                }
            }

            $timestamp = now()->format('Y-m-d\TH:i:sP');
            $toSign    = 'timestamp=' . $timestamp . '&' . implode('&', $qsParts);
            $digest    = base64_encode(hash_hmac('sha256', $toSign, $secret, true));

            $headers = [
                'Content-Type: application/json',
                'Authorization: SELCOM ' . base64_encode($apiKey),
                'Digest-Method: HS256',
                'Digest: ' . $digest,
                'Timestamp: ' . $timestamp,
                'Signed-Fields: ' . implode(',', $signedFields),
            ];

            $endpoint = rtrim($baseUrl, '/') . '/v1/checkout/create-order';

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($directPayload),
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 30,
            ]);
            $responseBody = curl_exec($ch);
            $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr      = curl_error($ch);
            curl_close($ch);

            $jsonResp = $responseBody ? json_decode($responseBody, true) : null;
            $pgB64 = null;
            if (is_array($jsonResp)) {
                $data = $jsonResp['data'] ?? null;
                if (is_array($data)) {
                    if (isset($data[0]['payment_gateway_url'])) {
                        $pgB64 = $data[0]['payment_gateway_url'];
                    } elseif (isset($data['payment_gateway_url'])) {
                        $pgB64 = $data['payment_gateway_url'];
                    }
                }
            }

            if ($httpCode >= 200 && $httpCode < 300 && is_string($pgB64)) {
                $decodedUrl = base64_decode($pgB64, true);
                $finalUrl = (is_string($decodedUrl) && preg_match('/^https?:\/\//i', $decodedUrl)) ? $decodedUrl : $pgB64;

                $payment->update([
                    'gateway_response' => $jsonResp,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'payment_url' => $finalUrl,
                    'payment_id' => $payment->id,
                    'credits' => $credits,
                    'fallback' => true,
                ]);
            }

            DB::rollBack();
            \Log::error('Selcom checkout failed (package+fallback)', [
                'http' => $httpCode ?? null,
                'error' => $curlErr ?? null,
                'response' => $jsonResp ?? $responseBody,
                'endpoint' => $endpoint,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment. Please try again.'
            ], 500);

            DB::rollBack();
            Log::error('Selcom checkout failed or unexpected response', ['response' => $selcomData ?? null]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'amount' => $amount,
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your payment. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['payment' => 'An error occurred while processing your payment. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Handle Selcom webhook notifications
     */
    public function webhook(Request $request)
    {
        Log::info('Selcom webhook received', $request->all());

        // Optional: Verify Selcom HS256 signature using configured secret
        try {
            $digest    = $request->header('Digest');
            $method    = $request->header('Digest-Method');
            $timestamp = $request->header('Timestamp') ?? $request->header('timestamp');
            $signed    = $request->header('Signed-Fields') ?? $request->header('signed-fields');

            if ($digest && $timestamp && $signed && strtoupper((string) $method) === 'HS256') {
                $signedFields = array_filter(array_map('trim', explode(',', $signed)));
                $qsParts = [];
                foreach ($signedFields as $field) {
                    $value = $request->input($field);
                    // Normalize arrays to JSON strings if ever present
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    // Use empty string for missing keys to keep order consistency
                    if ($value === null) {
                        $value = '';
                    }
                    $qsParts[] = $field . '=' . $value;
                }

                $toSign = 'timestamp=' . $timestamp . '&' . implode('&', $qsParts);
                $secret = config('selcom.secret');
                $computed = base64_encode(hash_hmac('sha256', $toSign, (string) $secret, true));

                if (!hash_equals((string) $digest, (string) $computed)) {
                    Log::warning('Selcom webhook signature mismatch', [
                        'expected' => $digest,
                        'computed' => $computed,
                        'timestamp' => $timestamp,
                        'signed_fields' => $signedFields,
                    ]);
                    return response('Invalid signature', 401);
                }
            } else {
                Log::info('Selcom webhook without signature or non-HS256 method', [
                    'has_digest' => (bool) $digest,
                    'has_timestamp' => (bool) $timestamp,
                    'has_signed_fields' => (bool) $signed,
                    'digest_method' => $method,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Error verifying Selcom webhook signature', [
                'message' => $e->getMessage(),
            ]);
            // Do not block processing due to verification error
        }

        try {
            // Verify webhook signature if needed
            $orderId = $request->input('order_id');
            $status = $request->input('payment_status');
            $transactionId = $request->input('transid');

            if (!$orderId) {
                Log::warning('Webhook missing order_id');
                return response('Missing order_id', 400);
            }

            $payment = Payment::where('reference', $orderId)->first();
            
            if (!$payment) {
                Log::warning('Payment not found for order_id: ' . $orderId);
                return response('Payment not found', 404);
            }

            DB::beginTransaction();

            // Update payment status based on webhook
            switch (strtolower($status)) {
                case 'completed':
                case 'success':
                    $payment->update([
                        'status' => 'completed',
                        'gateway_transaction_id' => $transactionId,
                        'completed_at' => now(),
                        'webhook_data' => $request->all()
                    ]);

                    // Process SMS purchase through BeemSmsService (deduct from admin, credit to user)
                    $beemService = new \App\Services\BeemSmsService();
                    $result = $beemService->processSMSPurchase(
                        $payment->user_id,
                        $payment->credits,
                        $payment->reference
                    );

                    if ($result['success']) {
                        Log::info('Payment and SMS transfer completed', [
                            'payment_id' => $payment->id,
                            'user_id' => $payment->user_id,
                            'credits_transferred' => $payment->credits,
                            'user_new_balance' => $result['user_new_balance'],
                            'admin_new_balance' => $result['admin_new_balance']
                        ]);
                        $user = User::find($payment->user_id);
                        if ($user && !empty($user->email)) {
                            try {
                                $html = '<h2>SMS Credits Purchased</h2>'
                                    . '<p>Your payment has been completed. ' . e((string) $payment->credits) . ' SMS credits added to your account.</p>'
                                    . '<p><a href="' . route('wallet.index') . '" style="display:inline-block;background:#4f46e5;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">View Dashboard</a></p>';
                                \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($user) {
                                    $m->to($user->email)->subject('RodLine SMS: Credits Added');
                                });
                            } catch (\Throwable $e) {}
                        }
                    } else {
                        Log::warning('SMS transfer via admin balance failed, applying direct credit fallback', [
                            'payment_id' => $payment->id,
                            'user_id' => $payment->user_id,
                            'error' => $result['error'] ?? 'unknown'
                        ]);

                        // Fallback: directly credit user and record wallet transaction
                        $user = User::find($payment->user_id);
                        if ($user) {
                            $before = (int) ($user->sms_credits ?? 0);
                            $after = $before + (int) $payment->credits;
                            $user->update(['sms_credits' => $after]);

                            WalletTransaction::create([
                                'user_id' => $user->id,
                                'type' => 'topup',
                                'amount' => (int) $payment->credits * 30,
                                'sms_credits' => (int) $payment->credits,
                                'balance_before' => $before,
                                'balance_after' => $after,
                                'description' => 'SMS credits purchase via Selcom (Order ' . $orderId . ')',
                                'reference' => $payment->reference,
                                'payment_method' => 'selcom',
                                'payment_reference' => $transactionId,
                                'status' => 'completed',
                                'processed_at' => now(),
                                'metadata' => [
                                    'webhook' => true,
                                    'fallback_credit' => true
                                ]
                            ]);

                            Log::info('Direct credit fallback applied', [
                                'user_id' => $user->id,
                                'credited' => $payment->credits,
                                'new_balance' => $after
                            ]);
                            if (!empty($user->email)) {
                                try {
                                    $html = '<h2>SMS Credits Purchased</h2>'
                                        . '<p>Your payment has been completed. ' . e((string) $payment->credits) . ' SMS credits added to your account.</p>'
                                        . '<p><a href="' . route('wallet.index') . '" style="display:inline-block;background:#4f46e5;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">View Dashboard</a></p>';
                                    \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($user) {
                                        $m->to($user->email)->subject('RodLine SMS: Credits Added');
                                    });
                                } catch (\Throwable $e) {}
                            }
                        } else {
                            Log::error('Fallback credit failed: user not found', [
                                'user_id' => $payment->user_id,
                                'payment_id' => $payment->id
                            ]);
                        }
                    }
                    break;

                case 'failed':
                case 'cancelled':
                    $payment->update([
                        'status' => 'failed',
                        'gateway_transaction_id' => $transactionId,
                        'webhook_data' => $request->all()
                    ]);
                    break;

                default:
                    $payment->update([
                        'webhook_data' => $request->all()
                    ]);
                    Log::info('Unknown payment status: ' . $status);
            }

            DB::commit();
            return response('OK', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('Error processing webhook', 500);
        }
    }

    /**
     * Handle successful payment redirect
     */
    public function success(Request $request)
    {
        $orderId = $request->input('order_id');
        $payment = null;
        
        if ($orderId) {
            $payment = Payment::where('reference', $orderId)->first();
        }

        if ($payment && strtolower($payment->status) !== 'completed') {
            $payment->update([
                'status' => 'completed',
                'gateway_transaction_id' => $request->input('transid'),
                'completed_at' => now(),
            ]);
            $payment->refresh();
        }

        // Ensure credits are applied if not already recorded
        if ($payment) {
            $user = User::find($payment->user_id);
            if ($user) {
                $existing = WalletTransaction::where('reference', $payment->reference)
                    ->where('user_id', $user->id)
                    ->where('type', 'topup')
                    ->first();
                if (!$existing) {
                    $before = (int) ($user->sms_credits ?? 0);
                    $after = $before + (int) $payment->credits;
                    $user->update(['sms_credits' => $after]);
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'type' => 'topup',
                        'amount' => (int) $payment->credits * 30,
                        'sms_credits' => (int) $payment->credits,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'description' => 'SMS credits purchase via Selcom (Order ' . $orderId . ')',
                        'reference' => $payment->reference,
                        'payment_method' => 'selcom',
                        'payment_reference' => $payment->gateway_transaction_id,
                        'status' => 'completed',
                        'processed_at' => now(),
                        'metadata' => [
                            'success_fallback' => true
                        ]
                    ]);
                    Log::info('Success redirect fallback credited user', [
                        'user_id' => $user->id,
                        'credited' => $payment->credits,
                        'new_balance' => $after
                    ]);
                    if (!empty($user->email)) {
                        try {
                            $html = '<h2>SMS Credits Purchased</h2>'
                                . '<p>Your payment has been completed. ' . e((string) $payment->credits) . ' SMS credits added to your account.</p>'
                                . '<p><a href="' . route('wallet.index') . '" style="display:inline-block;background:#4f46e5;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none">View Dashboard</a></p>';
                            \Illuminate\Support\Facades\Mail::html($html, function ($m) use ($user) {
                                $m->to($user->email)->subject('RodLine SMS: Credits Added');
                            });
                        } catch (\Throwable $e) {}
                    }
                }
            }
        }

        return view('payments.success', compact('payment'));
    }

    /**
     * Handle cancelled payment redirect
     */
    public function cancel(Request $request)
    {
        $orderId = $request->input('order_id');
        $payment = null;
        
        if ($orderId) {
            $payment = Payment::where('reference', $orderId)
                ->where('status', 'pending')
                ->first();
                
            if ($payment) {
                $payment->update(['status' => 'cancelled']);
            }
        }

        return view('payments.cancel', compact('payment'));
    }

    /**
     * Get payment status (for AJAX polling)
     */
    public function status($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json([
            'status' => $payment->status,
            'amount' => $payment->amount,
            'credits' => $payment->credits,
            'created_at' => $payment->created_at->format('Y-m-d H:i:s')
        ]);
    }
}
