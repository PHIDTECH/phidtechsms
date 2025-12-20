<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $this->middleware('auth');
        $keys = ApiKey::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')->get();
        return view('api.keys', compact('keys'));
    }

    public function create(Request $request)
    {
        $this->middleware('auth');
        $request->validate([
            'name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $key = 'rk_' . Str::random(24);
        $secret = 'rs_' . Str::random(48);
        $hash = hash_hmac('sha256', $secret, config('app.key'));

        $record = ApiKey::create([
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'key' => $key,
            'secret_hash' => $hash,
            'permissions' => ['sms.send' => true],
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'api_key' => $key,
            'api_secret' => $secret,
            'note' => 'Store the secret securely; it is shown only once.'
        ], 201);
    }

    public function update(Request $request, ApiKey $apiKey)
    {
        $this->middleware('auth');
        abort_if($apiKey->user_id !== Auth::id(), 403);
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'active' => 'nullable|boolean',
            'rate_limit_per_min' => 'nullable|integer|min:1|max:1000',
            'ip_allowlist' => 'nullable|string', // comma/space separated
        ]);

        if (isset($data['ip_allowlist'])) {
            $ips = preg_split('/[\s,;]+/', $data['ip_allowlist'], -1, PREG_SPLIT_NO_EMPTY);
            $data['ip_allowlist'] = array_values(array_unique($ips));
        } else {
            unset($data['ip_allowlist']);
        }

        $apiKey->fill($data);
        $apiKey->save();
        return back()->with('success', 'API key updated');
    }

    public function revoke(ApiKey $apiKey)
    {
        $this->middleware('auth');
        abort_if($apiKey->user_id !== Auth::id(), 403);
        $apiKey->active = false;
        $apiKey->save();
        return back()->with('success', 'API key revoked');
    }

    public function restore(ApiKey $apiKey)
    {
        $this->middleware('auth');
        abort_if($apiKey->user_id !== Auth::id(), 403);
        $apiKey->active = true;
        $apiKey->save();
        return back()->with('success', 'API key activated');
    }

    public function destroy(ApiKey $apiKey)
    {
        $this->middleware('auth');
        abort_if($apiKey->user_id !== Auth::id(), 403);
        $apiKey->delete();
        return back()->with('success', 'API key deleted');
    }
}
