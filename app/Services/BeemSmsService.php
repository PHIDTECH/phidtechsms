<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\User;
use App\Models\SmsTransaction;
use Exception;
use Illuminate\Http\Client\RequestException;

class BeemSmsService
{
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct($apiKey = null, $secretKey = null)
    {
        // Always load base URL from settings first
        $beemSettings = Setting::getBeemSettings();
        $this->baseUrl = $beemSettings['base_url'] ?? null;
        
        // Prefer explicit args for credentials
        if ($apiKey && $secretKey) {
            $this->apiKey = $apiKey;
            $this->secretKey = $secretKey;
        } else {
            // 1) Try database-backed settings
            $this->apiKey = $beemSettings['api_key'] ?? null;
            $this->secretKey = $beemSettings['secret_key'] ?? null;

            // 2) Fallback to config/services (env) if DB values are missing
            if (!$this->apiKey || !$this->secretKey) {
                $this->apiKey = $this->apiKey ?: config('services.beem.api_key');
                $this->secretKey = $this->secretKey ?: config('services.beem.secret_key');
            }
        }
        
        // Fallback base URL to config
        if (!$this->baseUrl) {
            $this->baseUrl = config('services.beem.base_url');
        }

        // Final default to official base URL
        if (!$this->baseUrl) {
            $this->baseUrl = 'https://apisms.beem.africa/v1';
        }
    }

    /**
     * List SMS templates from Beem
     */
    public function listSmsTemplates(int $page = 1)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/sms-templates', [ 'page' => $page ]);

            if ($response->successful()) {
                $data = $response->json();
                return [ 'success' => true, 'data' => $data['data'] ?? [], 'pagination' => $data['pagination'] ?? null ];
            }

            // Fallback to /public/v1 if needed
            if ($response->status() === 404 && !str_contains($this->baseUrl, '/public/')) {
                $alt = str_replace('/v1', '/public/v1', rtrim($this->baseUrl, '/'));
                $retry = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                    'Content-Type' => 'application/json',
                ])->get($alt . '/sms-templates', [ 'page' => $page ]);
                if ($retry->successful()) {
                    $data = $retry->json();
                    return [ 'success' => true, 'data' => $data['data'] ?? [], 'pagination' => $data['pagination'] ?? null ];
                }
            }

            return [ 'success' => false, 'error' => $response->json()['message'] ?? 'Failed to fetch templates', 'status_code' => $response->status() ];
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Create SMS template on Beem
     */
    public function createSmsTemplate(string $title, string $message)
    {
        try {
            $payload = [ 'sms_title' => $title, 'message' => $message ];
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/sms-templates', $payload);

            if ($response->successful()) {
                $json = $response->json();
                $tpl = $json['data'] ?? $json;
                return [ 'success' => true, 'id' => $tpl['id'] ?? null, 'data' => $tpl ];
            }

            if ($response->status() === 404 && !str_contains($this->baseUrl, '/public/')) {
                $alt = str_replace('/v1', '/public/v1', rtrim($this->baseUrl, '/'));
                $retry = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                    'Content-Type' => 'application/json',
                ])->post($alt . '/sms-templates', $payload);
                if ($retry->successful()) {
                    $json = $retry->json();
                    $tpl = $json['data'] ?? $json;
                    return [ 'success' => true, 'id' => $tpl['id'] ?? null, 'data' => $tpl ];
                }
                return [ 'success' => false, 'error' => $retry->json()['message'] ?? 'Failed to create template', 'status_code' => $retry->status() ];
            }

            return [ 'success' => false, 'error' => $response->json()['message'] ?? 'Failed to create template', 'status_code' => $response->status() ];
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Update SMS template on Beem
     */
    public function updateSmsTemplate(string $templateId, string $title, string $message)
    {
        try {
            $payload = [ 'sms_title' => $title, 'message' => $message ];
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->put($this->baseUrl . '/sms-templates/' . $templateId, $payload);

            if ($response->successful()) {
                return [ 'success' => true, 'data' => $response->json()['data'] ?? $response->json() ];
            }

            if ($response->status() === 404 && !str_contains($this->baseUrl, '/public/')) {
                $alt = str_replace('/v1', '/public/v1', rtrim($this->baseUrl, '/'));
                $retry = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                    'Content-Type' => 'application/json',
                ])->put($alt . '/sms-templates/' . $templateId, $payload);
                if ($retry->successful()) {
                    return [ 'success' => true, 'data' => $retry->json()['data'] ?? $retry->json() ];
                }
                return [ 'success' => false, 'error' => $retry->json()['message'] ?? 'Failed to update template', 'status_code' => $retry->status() ];
            }

            return [ 'success' => false, 'error' => $response->json()['message'] ?? 'Failed to update template', 'status_code' => $response->status() ];
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }

    /**
     * Delete SMS template on Beem
     */
    public function deleteSmsTemplate(string $templateId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->delete($this->baseUrl . '/sms-templates/' . $templateId);

            if ($response->successful()) {
                return [ 'success' => true, 'data' => $response->json()['data'] ?? $response->json() ];
            }

            if ($response->status() === 404 && !str_contains($this->baseUrl, '/public/')) {
                $alt = str_replace('/v1', '/public/v1', rtrim($this->baseUrl, '/'));
                $retry = Http::withHeaders([
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                    'Content-Type' => 'application/json',
                ])->delete($alt . '/sms-templates/' . $templateId);
                if ($retry->successful()) {
                    return [ 'success' => true, 'data' => $retry->json()['data'] ?? $retry->json() ];
                }
                return [ 'success' => false, 'error' => $retry->json()['message'] ?? 'Failed to delete template', 'status_code' => $retry->status() ];
            }

            return [ 'success' => false, 'error' => $response->json()['message'] ?? 'Failed to delete template', 'status_code' => $response->status() ];
        } catch (Exception $e) {
            return [ 'success' => false, 'error' => $e->getMessage() ];
        }
    }
    /**
     * Get account balance from Beem SMS API
     * Uses the REST API endpoint to retrieve current balance
     */
    public function getBalance()
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                return [
                    'success' => false,
                    'error' => 'API credentials not configured',
                    'balance' => 0,
                    'currency' => 'TZS',
                    'sms_credits' => 0
                ];
            }

            // Official Beem Africa API balance endpoint
            $endpoint = '/vendors/balance';
            $result = $this->makeApiRequest('GET', $endpoint);
            
            Log::info('Beem Balance API Request', [
                'url' => $result['url'] ?? $this->baseUrl . $endpoint,
                'success' => $result['success'],
                'status_code' => $result['status_code'] ?? null,
                'response' => $result['data'] ?? ($result['response'] ?? null)
            ]);
            
            if ($result['success'] && isset($result['data'])) {
                $balance = $this->extractBalanceFromResponse($result['data']);
                
                if ($balance !== null) {
                    return [
                        'success' => true,
                        'balance' => $balance,
                        'currency' => 'TZS',
                        'sms_credits' => $this->convertToSmsCredits($balance),
                        'endpoint_used' => $endpoint
                    ];
                }
            }
            
            // Try fallback to /public/v1 base URL if primary fails
            if (!$result['success'] || !isset($result['data'])) {
                $altBaseUrl = str_replace('/v1', '/public/v1', rtrim($this->baseUrl, '/'));
                if ($altBaseUrl !== $this->baseUrl) {
                    $altUrl = $altBaseUrl . $endpoint;
                    
                    Log::info('Beem Balance API Fallback Attempt', ['url' => $altUrl]);
                    
                    $altResult = Http::withBasicAuth($this->apiKey, $this->secretKey)
                        ->timeout(30)
                        ->withOptions(['verify' => false])
                        ->withHeaders(['Accept' => 'application/json'])
                        ->get($altUrl);
                    
                    if ($altResult->successful()) {
                        $altData = $altResult->json();
                        Log::info('Beem Balance API Fallback Response', ['data' => $altData]);
                        
                        $balance = $this->extractBalanceFromResponse($altData);
                        if ($balance !== null) {
                            return [
                                'success' => true,
                                'balance' => $balance,
                                'currency' => 'TZS',
                                'sms_credits' => $this->convertToSmsCredits($balance),
                                'endpoint_used' => $endpoint . ' (fallback)'
                            ];
                        }
                    }
                }
            }

            // If no endpoint worked, return detailed error
            $errorMsg = 'Balance endpoint not found or balance data not available';
            if (isset($result['status_code'])) {
                $errorMsg .= ' (HTTP ' . $result['status_code'] . ')';
            }
            if (isset($result['error'])) {
                $errorMsg = $result['error'];
            }
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'balance' => 0,
                'currency' => 'TZS',
                'sms_credits' => 0
            ];

        } catch (Exception $e) {
            Log::error('Beem Balance API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance' => 0,
                'currency' => 'TZS',
                'sms_credits' => 0
            ];
        }
    }

    /**
     * Get account information from Beem SMS
     */
    public function getAccountInfo()
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/account');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch account info',
                'details' => $response->json()['message'] ?? 'Unknown error'
            ];

        } catch (Exception $e) {
            Log::error('Beem SMS Account Info Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test API connection and balance endpoint
     * Tests both credential validity and balance API availability
     */
    public function testConnection()
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                return [
                    'success' => false,
                    'error' => 'API credentials not provided'
                ];
            }

            // Test balance API endpoint
            $balanceResult = $this->getBalance();
            
            if ($balanceResult['success']) {
                return [
                    'success' => true,
                    'message' => 'API connection successful and balance endpoint available',
                    'balance_available' => true,
                    'current_balance' => $balanceResult['balance'],
                    'sms_credits' => $balanceResult['sms_credits'],
                    'endpoint_used' => $balanceResult['endpoint_used'] ?? 'unknown'
                ];
            } else {
                // If balance fails, test a safe GET endpoint that doesn't require body/encoding
                $accountTest = $this->makeApiRequest('GET', '/account');

                // If the API is reachable, we will consider connection successful even if the endpoint returns an error status
                if (!empty($accountTest['success']) && $accountTest['success'] === true) {
                    return [
                        'success' => true,
                        'message' => 'API connection successful but balance endpoint not available',
                        'balance_available' => false,
                        'balance_error' => $balanceResult['error']
                    ];
                }

                // If we received an HTTP status code (even non-2xx), the API is reachable
                if (isset($accountTest['status_code'])) {
                    $status = $accountTest['status_code'];
                    $reachableMsg = in_array($status, [401, 403])
                        ? 'API reachable but credentials may be invalid or lack permission'
                        : 'API reachable but the account endpoint returned status ' . $status;

                    return [
                        'success' => true,
                        'message' => $reachableMsg,
                        'balance_available' => false,
                        'balance_error' => $balanceResult['error'],
                        'status_code' => $status
                    ];
                }

                // Otherwise, treat as a real connectivity failure
                return [
                    'success' => false,
                    'error' => 'API connection failed: ' . ($accountTest['error'] ?? 'Unknown error'),
                    'balance_available' => false
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convert balance to SMS credits (assuming 1 TZS = 1 SMS credit)
     * This can be adjusted based on actual pricing
     */
    private function convertToSmsCredits($balance)
    {
        // Assuming 1 TZS = 1 SMS credit
        // You can adjust this conversion rate as needed
        return (int) $balance;
    }

    /**
     * Sync admin SMS balance with Beem API
     * Uses the REST API to retrieve and update local balance
     */
    public function syncAdminBalance()
    {
        try {
            $balanceResult = $this->getBalance();
            
            if ($balanceResult['success']) {
                $smsCredits = $balanceResult['sms_credits'];
                $this->updateAdminBalance($smsCredits);
                
                Log::info('Admin SMS balance synchronized via API', [
                    'balance' => $balanceResult['balance'],
                    'sms_credits' => $smsCredits,
                    'endpoint' => $balanceResult['endpoint_used'] ?? 'unknown'
                ]);
                
                return [
                    'success' => true,
                    'sms_credits' => $smsCredits,
                    'balance' => $balanceResult['balance'],
                    'currency' => $balanceResult['currency'],
                    'message' => 'Balance synchronized successfully via API'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to retrieve balance from API: ' . $balanceResult['error'],
                    'sms_credits' => 0
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Admin Balance Sync Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sms_credits' => 0
            ];
        }
    }

    /**
     * Get current admin SMS balance from local storage
     */
    public function getAdminBalance()
    {
        $setting = Setting::where('key', 'admin_sms_balance')->first();
        return $setting ? (int) $setting->value : 0;
    }

    /**
     * Update admin SMS balance in local storage
     */
    private function updateAdminBalance($balance)
    {
        Setting::updateOrCreate(
            ['key' => 'admin_sms_balance'],
            [
                'value' => $balance,
                'admin_sms_balance' => $balance,
                'balance_last_synced' => now()
            ]
        );
        
        // Clear cache
        Cache::forget('admin_sms_balance');
    }

    /**
     * Deduct SMS credits from admin balance
     */
    public function deductFromAdminBalance($amount, $description = 'SMS credits deducted', $userId = null, $referenceId = null)
    {
        try {
            DB::beginTransaction();
            
            $currentBalance = $this->getAdminBalance();
            
            if ($currentBalance < $amount) {
                throw new Exception('Insufficient SMS credits in admin balance');
            }

            $newBalance = $currentBalance - $amount;
            $this->updateAdminBalance($newBalance);

            // Log deduction transaction
            SmsTransaction::create([
                'user_id' => $userId,
                'admin_id' => auth()->id(),
                'type' => SmsTransaction::TYPE_DEDUCTION,
                'amount' => $amount,
                'description' => $description,
                'reference_id' => $referenceId,
                'status' => SmsTransaction::STATUS_COMPLETED,
                'metadata' => [
                    'previous_balance' => $currentBalance,
                    'new_balance' => $newBalance
                ]
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'deducted_amount' => $amount
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SMS Deduction Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Credit SMS to user account
     */
    public function creditUserAccount($userId, $amount, $description = 'SMS credits purchased', $referenceId = null)
    {
        try {
            DB::beginTransaction();
            
            $user = User::findOrFail($userId);
            $previousBalance = $user->sms_credits ?? 0;
            $newBalance = $previousBalance + $amount;
            
            // Update user SMS credits
            $user->update(['sms_credits' => $newBalance]);

            // Log credit transaction
            SmsTransaction::create([
                'user_id' => $userId,
                'admin_id' => auth()->id(),
                'type' => SmsTransaction::TYPE_CREDIT,
                'amount' => $amount,
                'description' => $description,
                'reference_id' => $referenceId,
                'status' => SmsTransaction::STATUS_COMPLETED,
                'metadata' => [
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance
                ]
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'previous_balance' => $previousBalance,
                'new_balance' => $newBalance,
                'credited_amount' => $amount
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SMS Credit Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process SMS purchase (deduct from admin, credit to user)
     */
    public function processSMSPurchase($userId, $amount, $referenceId = null)
    {
        try {
            DB::beginTransaction();
            
            // Deduct from admin balance
            $deductResult = $this->deductFromAdminBalance(
                $amount, 
                "SMS purchase by user ID: {$userId}", 
                $userId, 
                $referenceId
            );
            
            if (!$deductResult['success']) {
                throw new Exception($deductResult['error']);
            }

            // Credit to user account
            $creditResult = $this->creditUserAccount(
                $userId, 
                $amount, 
                "SMS credits purchased", 
                $referenceId
            );
            
            if (!$creditResult['success']) {
                throw new Exception($creditResult['error']);
            }

            // Log purchase transaction
            SmsTransaction::create([
                'user_id' => $userId,
                'admin_id' => auth()->id(),
                'type' => SmsTransaction::TYPE_PURCHASE,
                'amount' => $amount,
                'description' => "SMS purchase completed",
                'reference_id' => $referenceId,
                'status' => SmsTransaction::STATUS_COMPLETED,
                'metadata' => [
                    'admin_deduction' => $deductResult,
                    'user_credit' => $creditResult
                ]
            ]);

            DB::commit();
            
            return [
                'success' => true,
                'message' => 'SMS purchase completed successfully',
                'user_new_balance' => $creditResult['new_balance'],
                'admin_new_balance' => $deductResult['new_balance'],
                'amount' => $amount
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('SMS Purchase Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get SMS transaction history
     */
    public function getTransactionHistory($userId = null, $limit = 50)
    {
        $query = SmsTransaction::with(['user', 'admin'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
            
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        return $query->get();
    }

    /**
     * Get balance summary
     */
    public function getBalanceSummary()
    {
        $adminBalance = $this->getAdminBalance();
        $lastSync = Setting::where('key', 'admin_sms_balance')->first();
        
        return [
            'admin_balance' => $adminBalance,
            'last_synced' => $lastSync ? $lastSync->balance_last_synced : null,
            'total_users' => User::where('role', 'user')->count(),
            'total_user_balance' => User::where('role', 'user')->sum('sms_credits'),
            'recent_transactions' => $this->getTransactionHistory(null, 10)
        ];
    }

    /**
     * Get last sync time as Carbon instance
     */
    public function getLastSyncTime(): ?\Carbon\Carbon
    {
        $lastSync = Setting::where('key', 'beem_balance_last_sync')->first();
        
        if (!$lastSync || !$lastSync->value) {
            return null;
        }
        
        try {
            return \Carbon\Carbon::parse($lastSync->value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Legacy method - now redirects to API-based balance retrieval
     * @deprecated Use getBalance() or syncAdminBalance() instead
     */
    public function retrieveBeemBalance()
    {
        // Redirect to new API-based method
        return $this->syncAdminBalance();
    }







    /**
     * Update last sync time
     */
    private function updateLastSyncTime()
    {
        Setting::updateOrCreate(
            ['key' => 'beem_balance_last_sync'],
            ['value' => now()->toISOString()]
        );
    }

    /**
     * Automatic balance synchronization (can be called via cron job)
     * Now uses API-based approach instead of web scraping
     */
    public function syncBalance($force = false)
    {
        try {
            // Check if sync is needed (don't sync more than once per hour unless forced)
            if (!$force) {
                $lastSync = $this->getLastSyncTime();
                if ($lastSync && $lastSync->diffInMinutes(now()) < 60) {
                    return [
                        'success' => true,
                        'message' => 'Balance sync skipped - last sync was less than 1 hour ago',
                        'last_sync' => $lastSync
                    ];
                }
            }

            // Use API-based balance retrieval instead of web scraping
            $result = $this->syncAdminBalance();
            
            if ($result['success']) {
                $this->updateLastSyncTime();
                
                // Log successful sync
                Log::info('Automatic balance sync completed via API', $result);
                
                return [
                    'success' => true,
                    'balance' => $result['balance'],
                    'sms_credits' => $result['sms_credits'],
                    'sync_time' => now(),
                    'message' => $result['message']
                ];
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Automatic Balance Sync Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make API request to Beem SMS API
     */
    private function makeApiRequest($method, $endpoint, $data = [])
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            Log::debug('Beem API Request Starting', [
                'method' => $method,
                'url' => $url,
                'has_api_key' => !empty($this->apiKey),
                'has_secret_key' => !empty($this->secretKey)
            ]);

            $client = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->timeout(30)
                ->retry(3, 1000)
                ->withOptions(['verify' => false]);

            $methodLower = strtolower($method);

            // Always accept JSON responses
            $client = $client->withHeaders(['Accept' => 'application/json']);

            // For GET requests, don't force Content-Type and pass params as query
            if ($methodLower === 'get') {
                $response = $client->get($url, $data);
            } else {
                // For POST/PUT/etc, default to JSON unless endpoint requires form encoding
                if (stripos($endpoint, '/send') !== false) {
                    // Beem send endpoint expects form encoding
                    $response = $client->asForm()->post($url, $data);
                } else {
                    // Default JSON body
                    $client = $client->withHeaders(['Content-Type' => 'application/json']);
                    if ($methodLower === 'post') {
                        $response = $client->post($url, $data);
                    } elseif ($methodLower === 'put') {
                        $response = $client->put($url, $data);
                    } elseif ($methodLower === 'delete') {
                        $response = $client->delete($url, $data);
                    } else {
                        $response = $client->{$methodLower}($url, $data);
                    }
                }
            }

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'status_code' => $response->status(),
                    'url' => $url
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API request failed with status ' . $response->status(),
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                    'url' => $url
                ];
            }
            
        } catch (RequestException $e) {
            $status = $e->response ? $e->response->status() : null;
            $body = $e->response ? $e->response->body() : null;
            $url = isset($url) ? $url : ($this->baseUrl . $endpoint);
            return [
                'success' => false,
                'error' => 'API request exception: ' . $e->getMessage(),
                'status_code' => $status,
                'response' => $body,
                'url' => $url
            ];
        } catch (Exception $e) {
            $url = isset($url) ? $url : ($this->baseUrl . $endpoint);
            return [
                'success' => false,
                'error' => 'API request exception: ' . $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Extract balance from API response data
     */
    private function extractBalanceFromResponse($data)
    {
        // Log the response for debugging
        Log::info('Beem API Response Data:', ['data' => $data]);
        
        // Beem-specific balance field names based on official API documentation
          $balanceFields = [
              'credit_balance', // Official Beem API field
              'balance',
              'account_balance', 
              'wallet_balance',
              'sms_balance',
              'credits',
              'sms_credits',
              'available_balance',
              'current_balance',
              'amount',
              'total',
              'remaining',
              'available'
          ];

        // If data is array, look for balance fields
        if (is_array($data)) {
            foreach ($balanceFields as $field) {
                if (isset($data[$field]) && is_numeric($data[$field])) {
                    return (float) $data[$field];
                }
            }
            
            // Check nested data structures
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($balanceFields as $field) {
                    if (isset($data['data'][$field]) && is_numeric($data['data'][$field])) {
                        return (float) $data['data'][$field];
                    }
                }
            }
            
            // Check account or wallet nested objects
            $nestedObjects = ['account', 'wallet', 'user', 'profile'];
            foreach ($nestedObjects as $obj) {
                if (isset($data[$obj]) && is_array($data[$obj])) {
                    foreach ($balanceFields as $field) {
                        if (isset($data[$obj][$field]) && is_numeric($data[$obj][$field])) {
                            return (float) $data[$obj][$field];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Send SMS using Beem Africa API
     */
    public function sendSms($recipients, $message, $senderId = null)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            // Prepare recipients array
            $recipientList = [];
            if (is_string($recipients)) {
                $recipients = [$recipients];
            }
            
            foreach ($recipients as $index => $recipient) {
                $recipientList[] = [
                    'recipient_id' => $index + 1,
                    'dest_addr' => $this->formatPhoneNumber($recipient)
                ];
            }

            $payload = [
                'source_addr' => $senderId ?: 'Phidtech',
                'encoding' => 0, // Plain text
                'message' => $message,
                'recipients' => $recipientList
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('SMS sent successfully via Beem', [
                    'recipients' => count($recipients),
                    'response' => $responseData
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['request_id'] ?? null,
                    'cost' => $responseData['cost'] ?? 0,
                    'response' => $responseData
                ];
            } else {
                $error = $response->json();
                Log::error('Failed to send SMS via Beem', [
                    'error' => $error,
                    'status' => $response->status()
                ]);

                return [
                    'success' => false,
                    'error' => $error['message'] ?? 'Unknown error',
                    'error_code' => $error['code'] ?? $response->status()
                ];
            }
        } catch (Exception $e) {
            Log::error('Exception while sending SMS', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for Beem API
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (strlen($phone) === 9 && substr($phone, 0, 1) === '7') {
            $phone = '255' . $phone;
        } elseif (strlen($phone) === 10 && substr($phone, 0, 2) === '07') {
            $phone = '255' . substr($phone, 1);
        }
        
        // Ensure it doesn't have + prefix
        // Beem API expects 255xxxxxxxxx
        
        return $phone;
    }

    /**
     * Get SMS delivery reports using official Beem API
     */
    /**
     * Get SMS delivery report for a specific message
     * Uses the dedicated DLR API endpoint: https://dlrapi.beem.africa/public/v1/delivery-reports
     */
    public function getDeliveryReport($destAddr, $requestId)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            $baseUrl = 'https://dlrapi.beem.africa/public/v1/delivery-reports';
            
            $queryParams = [
                'dest_addr' => $destAddr,
                'request_id' => $requestId
            ];

            $url = $baseUrl . '?' . http_build_query($queryParams);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch delivery report',
                'status_code' => $response->status(),
                'details' => $response->json()
            ];

        } catch (Exception $e) {
            Log::error('Beem Delivery Report Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all SMS templates
     */
    public function getSmsTemplates()
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/templates');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch SMS templates',
                'details' => $response->json()['message'] ?? 'Unknown error'
            ];

        } catch (Exception $e) {
            Log::error('Beem SMS Templates Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Add a new SMS template
     */
    public function addSmsTemplate($smsTitle, $message)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            $payload = [
                'sms_title' => $smsTitle,
                'message' => $message
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/templates', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to add SMS template',
                'details' => $response->json()['message'] ?? 'Unknown error'
            ];

        } catch (Exception $e) {
            Log::error('Beem Add SMS Template Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    

    /**
     * Get all registered sender names using official Beem API
     */
    public function getSenderNames($query = null, $status = null)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            $params = [];
            if ($query) {
                $params['q'] = $query;
            }
            if ($status) {
                $params['status'] = $status;
            }

            // Try multiple possible endpoints for sender names
            $endpoints = [
                'https://apisms.beem.africa/v1/sender-names',
                'https://apisms.beem.africa/public/v1/sender-names',
                rtrim($this->baseUrl, '/') . '/sender-names',
            ];

            $lastError = null;
            $lastStatusCode = null;

            foreach ($endpoints as $baseEndpoint) {
                $url = $baseEndpoint;
                if (!empty($params)) {
                    $url .= '?' . http_build_query($params);
                }

                Log::info('Beem Sender Names: Trying endpoint', ['url' => $url]);

                try {
                    $response = Http::timeout(30)
                        ->withHeaders([
                            'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ])->get($url);

                    Log::info('Beem Sender Names Response', [
                        'endpoint' => $baseEndpoint,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);

                    if ($response->successful()) {
                        $responseData = $response->json();
                        
                        // Handle different response structures
                        $data = $responseData['data'] ?? $responseData['sender_names'] ?? $responseData['items'] ?? $responseData;
                        
                        return [
                            'success' => true,
                            'data' => is_array($data) ? $data : [],
                            'pagination' => $responseData['pagination'] ?? null,
                            'endpoint_used' => $baseEndpoint
                        ];
                    }

                    $lastError = $response->json()['message'] ?? $response->body();
                    $lastStatusCode = $response->status();

                } catch (\Exception $e) {
                    Log::warning('Beem endpoint failed', ['endpoint' => $baseEndpoint, 'error' => $e->getMessage()]);
                    $lastError = $e->getMessage();
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to fetch sender names',
                'details' => $lastError ?? 'All endpoints failed',
                'status_code' => $lastStatusCode
            ];

        } catch (Exception $e) {
            Log::error('Beem Sender Names Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Request a new sender name
     */
    public function requestSenderName($senderId, $sampleContent)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                throw new Exception('Beem SMS API credentials not configured');
            }

            // Validate sender ID length
            if (strlen($senderId) > 11) {
                return [
                    'success' => false,
                    'error' => 'Sender ID should not exceed 11 characters'
                ];
            }

            // Validate sample content length
            if (strlen($sampleContent) < 15) {
                return [
                    'success' => false,
                    'error' => 'Sample content should be minimum of 15 characters'
                ];
            }

            $payload = [
                'senderid' => $senderId,
                'sample_content' => $sampleContent
            ];

            $endpoint = rtrim($this->baseUrl, '/') . '/sender-names';
            $response = Http::timeout(60)
                ->retry(2, 1000)
                ->asJson()
                ->withBasicAuth($this->apiKey, $this->secretKey)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            if ($response->status() === 404 && !str_contains($this->baseUrl, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($this->baseUrl, '/'));
                $altEndpoint = $altBase . '/sender-names';
                $response = Http::timeout(60)
                    ->retry(2, 1000)
                    ->asJson()
                    ->withBasicAuth($this->apiKey, $this->secretKey)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->post($altEndpoint, $payload);
                if ($response->successful()) {
                    return [ 'success' => true, 'data' => $response->json() ];
                }
            }

            if ($response->status() === 401 || str_contains(strtolower($response->body()), 'authorization')) {
                $response = Http::timeout(60)
                    ->retry(2, 1000)
                    ->asJson()
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpoint, $payload);
                if ($response->successful()) {
                    return [ 'success' => true, 'data' => $response->json() ];
                }
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? $response->body() ?? 'Failed to request sender name',
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('Beem Request Sender Name Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    /**
     * Validate sender name according to Beem guidelines
     */
    public function validateSenderName($senderId)
    {
        $errors = [];
        
        // Check length
        if (strlen($senderId) > 11) {
            $errors[] = 'Sender ID should not exceed 11 characters';
        }
        
        // Check for invalid special characters
        if (!preg_match('/^[a-zA-Z0-9\s\-\.]+$/', $senderId)) {
            $errors[] = 'Only letters, numbers, spaces, hyphens (-) and dots (.) are allowed';
        }
        
        // Check for common brand names (basic check)
        $forbiddenNames = ['MPESA', 'TPESA', 'TANESCO', 'PEPSI', 'VODACOM', 'AIRTEL', 'TIGO', 'HALOTEL'];
        foreach ($forbiddenNames as $forbidden) {
            if (stripos($senderId, $forbidden) !== false) {
                $errors[] = 'Sender ID should not mimic established brands like ' . $forbidden;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
     }

    /**
     * Get all address books for the authenticated user
     */
    public function getAddressBooks()
    {
        try {
            $contactsBase = config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1');
            // Ensure credentials exist
            if (!$this->apiKey || !$this->secretKey) {
                \Illuminate\Support\Facades\Log::error('Beem Get Address Books Error: Missing API credentials');
                throw new \Exception('Failed to fetch address books: Missing Beem API credentials. Please set BEEM_API_KEY and BEEM_SECRET_KEY or configure Beem settings in the app.');
            }

            $endpoint = rtrim($contactsBase, '/') . '/address-books';
            \Log::info('Beem getAddressBooks request (Basic Auth)', [ 'url' => $endpoint ]);

            // Primary attempt: Basic Auth (preferred on Beem APIs)
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([ 'Accept' => 'application/json' ])
                ->withBasicAuth($this->apiKey, $this->secretKey)
                ->get($endpoint);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/address-books';
                \Log::info('Beem getAddressBooks retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withHeaders([ 'Accept' => 'application/json' ])
                    ->withBasicAuth($this->apiKey, $this->secretKey)
                    ->get($altEndpoint);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                \Log::info('Beem getAddressBooks retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json',
                    ])
                    ->get($endpoint);
            }

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }

            \Illuminate\Support\Facades\Log::error('Beem Get Address Books Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to fetch address books: ' . ($response->json()['message'] ?? $response->body()));

        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Illuminate\Support\Facades\Log::error('Beem Get Address Books Request Error: ' . $e->getMessage());
            throw new \Exception('Network error while fetching address books: ' . $e->getMessage());
        }
    }

    /**
     * Create a new address book
     */
    public function createAddressBook($name, $description = null)
    {
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $data = [
                    'name' => $name
                ];

                if ($description) {
                    $data['description'] = $description;
                }

                // Use Beem Contacts API base and headers
                $contactsBase = str_replace('/public', '', config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1'));
                $headers = [
                    'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                    'Accept' => 'application/json'
                ];
                
                // Diagnostic logging
                \Log::info('Beem createAddressBook request (attempt ' . $attempt . ')', [
                    'base' => $contactsBase,
                    'url' => rtrim($contactsBase, '/') . '/address-books',
                    'payload' => $data,
                    'headers' => [
                        'Authorization' => 'Basic ' . substr(base64_encode($this->apiKey . ':' . $this->secretKey), 0, 6) . '***',
                        'Accept' => 'application/json'
                    ],
                ]);
                
                $endpoint = rtrim($contactsBase, '/') . '/address-books';
                $response = \Illuminate\Support\Facades\Http::timeout(60) // Increased timeout to 60 seconds
                    ->retry(2, 1000) // Retry 2 times with 1 second delay
                    ->asJson()
                    ->withHeaders($headers)
                    ->post($endpoint, $data);

                // Fallback to /public/v1 if 404
                if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                    $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                    $altEndpoint = $altBase . '/address-books';
                    \Log::info('Beem createAddressBook retrying with /public/v1', [ 'url' => $altEndpoint ]);
                    $response = \Illuminate\Support\Facades\Http::timeout(60)
                        ->asJson()
                        ->withHeaders($headers)
                        ->post($altEndpoint, $data);
                }

                // Secondary attempt: legacy header keys if server complains about authorization headers
                if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                    \Log::info('Beem createAddressBook retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                    $response = \Illuminate\Support\Facades\Http::timeout(60)
                        ->asJson()
                        ->withHeaders([
                            'api_key' => $this->apiKey,
                            'secret_key' => $this->secretKey,
                            'Accept' => 'application/json'
                        ])
                        ->post($endpoint, $data);
                }

                if ($response->successful()) {
                    $responseData = $response->json();
                    \Log::info('Beem Create Address Book Success', [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'response' => $responseData,
                    ]);
                    return ['success' => true, 'data' => $responseData['data'] ?? $responseData];
                }

                \Log::error('Beem Create Address Book Error (attempt ' . $attempt . '): ' . $response->body());
                
                // If this is not the last attempt, continue to retry
                if ($attempt < $maxRetries) {
                    \Log::info('Retrying createAddressBook in ' . $retryDelay . ' seconds...');
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
                    continue;
                }
                
                throw new \Exception('Failed to create address book after ' . $maxRetries . ' attempts: ' . $response->body());

            } catch (\Illuminate\Http\Client\RequestException $e) {
                \Log::error('Beem Create Address Book Request Error (attempt ' . $attempt . '): ' . $e->getMessage());
                
                // If this is not the last attempt and it's a timeout, retry
                if ($attempt < $maxRetries && (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'timed out'))) {
                    \Log::info('Timeout detected, retrying createAddressBook in ' . $retryDelay . ' seconds...');
                    sleep($retryDelay);
                    $retryDelay *= 2; // Exponential backoff
                    continue;
                }
                
                throw new \Exception('Network error while creating address book: ' . $e->getMessage());
            }
        }
    }

    /**
     * Create a new contact in an address book
     */
    public function createContact($contactData)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                Log::error('Beem Create Contact Error: Missing API credentials');
                throw new Exception('Failed to create contact: Missing Beem API credentials.');
            }

            $contactsBase = config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1');
            $endpoint = rtrim($contactsBase, '/') . '/contacts';
            Log::info('Beem createContact request (Basic Auth)', [ 'url' => $endpoint ]);

            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($endpoint, $contactData);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/contacts';
                Log::info('Beem createContact retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                    ->timeout(30)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->post($altEndpoint, $contactData);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                Log::info('Beem createContact retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = Http::timeout(30)
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->post($endpoint, $contactData);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                return $responseData['data'] ?? $responseData;
            }

            Log::error('Beem Create Contact Error: ' . $response->body());
            throw new Exception('Failed to create contact: ' . $response->body());

        } catch (RequestException $e) {
            Log::error('Beem Create Contact Request Error: ' . $e->getMessage());
            throw new Exception('Network error while creating contact: ' . $e->getMessage());
        }
    }

    /**
     * Get contacts from an address book
     */
    public function getContacts($addressBookId, $page = 1, $limit = 50, $includeMeta = false)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                Log::error('Beem Get Contacts Error: Missing API credentials');
                throw new Exception('Failed to fetch contacts: Missing Beem API credentials.');
            }

            $contactsBase = config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1');
            $params = [
                'addressbook_id' => $addressBookId,
                'page' => $page,
                'limit' => $limit
            ];

            $endpoint = rtrim($contactsBase, '/') . '/contacts';
            Log::info('Beem getContacts request (Basic Auth)', [ 'url' => $endpoint, 'params' => $params ]);

            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->timeout(30)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($endpoint, $params);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/contacts';
                Log::info('Beem getContacts retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                    ->timeout(30)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get($altEndpoint, $params);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                Log::info('Beem getContacts retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = Http::timeout(30)
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json'
                    ])
                    ->get($endpoint, $params);
            }

            if ($response->successful()) {
                $data = $response->json();
                if ($includeMeta) {
                    return [
                        'data' => $data['data'] ?? [],
                        'meta' => $data['meta'] ?? ($data['pagination'] ?? []),
                    ];
                }
                return $data['data'] ?? [];
            }

            Log::error('Beem Get Contacts Error: ' . $response->body());
            throw new Exception('Failed to fetch contacts: ' . $response->body());

        } catch (RequestException $e) {
            Log::error('Beem Get Contacts Request Error: ' . $e->getMessage());
            throw new Exception('Network error while fetching contacts: ' . $e->getMessage());
        }
    }

    /**
     * Update an address book
     */
    public function updateAddressBook($addressBookId, array $payload)
    {
        try {
            $contactsBase = str_replace('/public', '', config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1'));
            $headers = [
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Accept' => 'application/json'
            ];

            $endpoint = rtrim($contactsBase, '/') . '/address-books/' . $addressBookId;
            $response = Http::timeout(60)
                ->asJson()
                ->withHeaders($headers)
                ->put($endpoint, $payload);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/address-books/' . $addressBookId;
                Log::info('Beem updateAddressBook retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = Http::timeout(60)
                    ->asJson()
                    ->withHeaders($headers)
                    ->put($altEndpoint, $payload);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                Log::info('Beem updateAddressBook retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = Http::timeout(60)
                    ->asJson()
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json'
                    ])
                    ->put($endpoint, $payload);
            }

            if ($response->successful()) {
                $json = $response->json();
                return ['success' => true, 'data' => $json['data'] ?? $json];
            }

            Log::error('Beem Update Address Book Error: ' . $response->body());
            throw new Exception('Failed to update address book: ' . $response->body());

        } catch (RequestException $e) {
            Log::error('Beem Update Address Book Request Error: ' . $e->getMessage());
            throw new Exception('Network error while updating address book: ' . $e->getMessage());
        }
    }

    /**
     * Update a contact
     */
    public function updateContact($contactId, $contactData)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                Log::error('Beem Update Contact Error: Missing API credentials');
                throw new Exception('Failed to update contact: Missing Beem API credentials.');
            }

            $contactsBase = config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1');
            $endpoint = rtrim($contactsBase, '/') . '/contacts/' . $contactId;

            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->put($endpoint, $contactData);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/contacts/' . $contactId;
                Log::info('Beem updateContact retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                    ->timeout(30)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->put($altEndpoint, $contactData);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                Log::info('Beem updateContact retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = Http::timeout(30)
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->put($endpoint, $contactData);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                return $responseData['data'] ?? $responseData;
            }

            Log::error('Beem Update Contact Error: ' . $response->body());
            throw new Exception('Failed to update contact: ' . $response->body());

        } catch (RequestException $e) {
            Log::error('Beem Update Contact Request Error: ' . $e->getMessage());
            throw new Exception('Network error while updating contact: ' . $e->getMessage());
        }
    }

    /**
     * Delete a contact
     */
    public function deleteContact($contactId)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                Log::error('Beem Delete Contact Error: Missing API credentials');
                throw new Exception('Failed to delete contact: Missing Beem API credentials.');
            }

            $contactsBase = config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1');
            $endpoint = rtrim($contactsBase, '/') . '/contacts/' . $contactId;
            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->timeout(30)
                ->withHeaders(['Accept' => 'application/json'])
                ->delete($endpoint);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/contacts/' . $contactId;
                Log::info('Beem deleteContact retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                    ->timeout(30)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->delete($altEndpoint);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                Log::info('Beem deleteContact retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = Http::timeout(30)
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json'
                    ])
                    ->delete($endpoint);
            }

            if ($response->successful()) {
                return true;
            }

            Log::error('Beem Delete Contact Error: ' . $response->body());
            throw new Exception('Failed to delete contact: ' . $response->body());

        } catch (RequestException $e) {
            Log::error('Beem Delete Contact Request Error: ' . $e->getMessage());
            throw new Exception('Network error while deleting contact: ' . $e->getMessage());
        }
    }

    /**
     * Delete an address book
     */
    public function deleteAddressBook($addressBookId)
    {
        try {
            if (!$this->apiKey || !$this->secretKey) {
                Log::error('Beem Delete Address Book Error: Missing API credentials');
                throw new Exception('Failed to delete address book: Missing Beem API credentials.');
            }

            $contactsBase = config('services.beem_contacts.base', 'https://apicontacts.beem.africa/v1');
            $endpoint = rtrim($contactsBase, '/') . '/address-books/' . $addressBookId;
            $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                ->timeout(30)
                ->withHeaders(['Accept' => 'application/json'])
                ->delete($endpoint);

            // Fallback to /public/v1 if 404
            if (!$response->successful() && $response->status() === 404 && !str_contains($contactsBase, '/public/')) {
                $altBase = str_replace('/v1', '/public/v1', rtrim($contactsBase, '/'));
                $altEndpoint = $altBase . '/address-books/' . $addressBookId;
                Log::info('Beem deleteAddressBook retrying with /public/v1', [ 'url' => $altEndpoint ]);
                $response = Http::withBasicAuth($this->apiKey, $this->secretKey)
                    ->timeout(30)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->delete($altEndpoint);
            }

            // Secondary attempt: legacy header keys if server complains about authorization headers
            if (!$response->successful() && ($response->status() === 401 || str_contains(strtolower($response->body()), 'no authorization headers'))) {
                Log::info('Beem deleteAddressBook retrying with api_key/secret_key headers', [ 'url' => $endpoint ]);
                $response = Http::timeout(30)
                    ->withHeaders([
                        'api_key' => $this->apiKey,
                        'secret_key' => $this->secretKey,
                        'Accept' => 'application/json'
                    ])
                    ->delete($endpoint);
            }

            if ($response->successful()) {
                return true;
            }

            Log::error('Beem Delete Address Book Error: ' . $response->body());
            throw new Exception('Failed to delete address book: ' . $response->body());

        } catch (RequestException $e) {
            Log::error('Beem Delete Address Book Request Error: ' . $e->getMessage());
            throw new Exception('Network error while deleting address book: ' . $e->getMessage());
        }
    }
}
