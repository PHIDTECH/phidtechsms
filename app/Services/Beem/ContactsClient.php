<?php

namespace App\Services\Beem;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ContactsClient
{
    private Client $http;

    private array $headers;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => config('services.beem_contacts.base'),
            'timeout' => 10,
        ]);

        $this->headers = [
            'api_key' => config('services.beem_contacts.key'),
            'secret_key' => config('services.beem_contacts.secret'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function call(string $method, string $uri, array $opts = []): array
    {
        $opts['headers'] = array_merge($this->headers, Arr::get($opts, 'headers', []));

        if (isset($opts['json'])) {
            $opts['body'] = json_encode($opts['json']);
            unset($opts['json']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $response = $this->http->request($method, ltrim($uri, '/'), $opts);

                return json_decode((string) $response->getBody(), true) ?? [];
            } catch (RequestException $e) {
                $status = (int) ($e->getResponse()?->getStatusCode() ?? $e->getCode());

                if (!in_array($status, [429, 500, 502, 503, 504], true) || $attempt === 2) {
                    Log::warning('BeemContacts error', [
                        'uri' => $uri,
                        'code' => $status,
                        'error' => $e->getMessage(),
                    ]);

                    throw $e;
                }

                usleep((int) (pow(2, $attempt) * 200000));
            }
        }

        return [];
    }

    public function addressbooksList(?string $q = null): array
    {
        $qs = $q ? '?q=' . urlencode($q) : '';

        return $this->call('GET', "/address-books{$qs}");
    }

    public function addressbookCreate(array $payload): array
    {
        return $this->call('POST', '/address-books', ['json' => $payload]);
    }

    public function addressbookUpdate(string $id, array $payload): array
    {
        return $this->call('PUT', "/address-books/{$id}", ['json' => $payload]);
    }

    public function addressbookDelete(string $id): array
    {
        return $this->call('DELETE', "/address-books/{$id}");
    }

    public function contactsList(string $addressbookId, ?string $q = null): array
    {
        $qs = http_build_query(array_filter([
            'addressbook_id' => $addressbookId,
            'q' => $q,
        ]));

        $suffix = $qs ? "?{$qs}" : '';

        return $this->call('GET', "/contacts{$suffix}");
    }

    public function contactCreate(array $payload): array
    {
        return $this->call('POST', '/contacts', ['json' => $payload]);
    }

    public function contactUpdate(string $contactId, array $payload): array
    {
        return $this->call('PUT', "/contacts/{$contactId}", ['json' => $payload]);
    }

    public function contactsDelete(array $payload): array
    {
        return $this->call('DELETE', '/contacts', ['json' => $payload]);
    }
}
