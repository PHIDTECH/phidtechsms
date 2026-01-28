<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\SmsMessage;
use App\Models\Campaign;
use Exception;

class SmsService
{
    private $apiKey;
    private $secretKey;
    private $baseUrl;
    private $webhookUrl;

    public function __construct()
    {
        $this->apiKey = config('services.beem.api_key');
        $this->secretKey = config('services.beem.secret_key');
        $this->baseUrl = config('services.beem.base_url', 'https://apisms.beem.africa/v1');
        $urlBase = env('WEBHOOK_URL', config('app.url'));
        if (!preg_match('#^https?://#', (string) $urlBase)) {
            $urlBase = 'https://' . ltrim((string) $urlBase, '/');
        }
        $this->webhookUrl = rtrim($urlBase, '/') . '/webhooks/beem/dlr';
    }

    /**
     * Send a single SMS message
     */
    public function sendSms(string $recipient, string $message, string $senderId = null, array $options = []): array
    {
        try {
            $encoding = $this->isUnicode($message) ? 1 : 0;
            $payload = [
                'source_addr' => $this->normalizeSenderId($senderId ?: config('services.beem.default_sender_id', 'Phidtech')),
                'encoding' => $encoding,
                'message' => $message,
                'recipients' => [
                    [
                        'recipient_id' => 1,
                        'dest_addr' => $this->formatPhoneNumber($recipient)
                    ]
                ],
                'dlr_url' => $this->webhookUrl,
                'dlr_level' => 2, // Final delivery report
            ];

            // Add optional parameters
            if (isset($options['schedule_time'])) {
                $payload['schedule_time'] = $options['schedule_time'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('SMS sent successfully via Beem', [
                    'recipient' => $recipient,
                    'sender_id' => $senderId,
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
                    'recipient' => $recipient,
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
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Send bulk SMS messages
     */
    public function sendBulkSms(array $recipients, string $message, string $senderId = null, array $options = []): array
    {
        try {
            // Prepare recipients array
            $recipientList = [];
            foreach ($recipients as $index => $recipient) {
                $recipientList[] = [
                    'recipient_id' => $index + 1,
                    'dest_addr' => $this->formatPhoneNumber($recipient)
                ];
            }

            $encoding = $this->isUnicode($message) ? 1 : 0;
            $payload = [
                'source_addr' => $this->normalizeSenderId($senderId ?: config('services.beem.default_sender_id', 'Phidtech')),
                'encoding' => $encoding,
                'message' => $message,
                'recipients' => $recipientList,
                'dlr_url' => $this->webhookUrl,
                'dlr_level' => 2, // Final delivery report
            ];

            // Add optional parameters
            if (isset($options['schedule_time'])) {
                $payload['schedule_time'] = $options['schedule_time'];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->baseUrl . '/send', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Bulk SMS sent successfully via Beem', [
                    'recipient_count' => count($recipients),
                    'sender_id' => $senderId,
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
                
                Log::error('Failed to send bulk SMS via Beem', [
                    'recipient_count' => count($recipients),
                    'error' => $error,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => isset($error['message']) ? $error['message'] : ($error['detail'] ?? 'Unknown error'),
                    'error_code' => $error['code'] ?? $response->status(),
                    'raw' => $response->body()
                ];
            }
        } catch (Exception $e) {
            Log::error('Exception while sending bulk SMS', [
                'recipient_count' => count($recipients),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => 'EXCEPTION'
            ];
        }
    }

    /**
     * Check account balance
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/balance');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'balance' => $data['balance'] ?? 0,
                    'currency' => $data['currency'] ?? 'USD'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch balance'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to E.164 format
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-digit characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If number starts with 0, replace with country code (255 for Tanzania)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '255' . substr($phoneNumber, 1);
        }
        
        // If number doesn't start with +, add it - REMOVED for Beem API compatibility
        // Beem API expects format 255xxxxxxxxx without + prefix
        // if (substr($phoneNumber, 0, 1) !== '+') {
        //     $phoneNumber = '+' . $phoneNumber;
        // }
        
        return $phoneNumber;
    }

    public function canSend(): bool
    {
        return !empty($this->apiKey) && !empty($this->secretKey) && !empty($this->baseUrl);
    }

    /**
     * Calculate SMS parts based on message length
     */
    public function calculateSmsParts(string $message): int
    {
        $length = strlen($message);
        
        if ($length <= 160) {
            return 1;
        } elseif ($length <= 306) {
            return 2;
        } else {
            return ceil($length / 153);
        }
    }

    /**
     * Calculate SMS cost based on parts
     */
    public function calculateSmsCost(string $message, int $recipientCount = 1): float
    {
        $parts = $this->calculateSmsParts($message);
        $costPerPart = config('services.sms.cost_per_part', 30); // TZS 30 per part
        
        return $parts * $recipientCount * $costPerPart;
    }

    /**
     * Validate sender ID format
     */
    public function validateSenderId(string $senderId): bool
    {
        // Beem allows letters, numbers, spaces, hyphens (-), and dots (.) up to 11 chars
        return preg_match('/^[a-zA-Z0-9\s\-\.]{3,11}$/', $senderId) === 1;
    }

    private function isUnicode(string $message): bool
    {
        return preg_match('/[^\x00-\x7F]/', $message) === 1;
    }

    private function normalizeSenderId(string $senderId): string
    {
        $senderId = trim($senderId);
        $senderId = preg_replace('/\s+/', ' ', $senderId);
        return $senderId;
    }
}
