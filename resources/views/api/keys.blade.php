@extends('layouts.modern-dashboard')

@section('page-title', 'API Keys')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-semibold mb-4">API Keys</h1>

    @if(session('success'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="p-4 bg-white rounded shadow mb-6">
        <div class="flex gap-2 items-end">
            <div class="flex-1">
                <label class="block text-sm text-gray-600">Name (optional)</label>
                <input id="name" type="text" class="mt-1 w-full border rounded px-3 py-2" placeholder="My Server"/>
            </div>
            <button onclick="createKey()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Generate Key</button>
        </div>
        <p class="text-xs text-gray-500 mt-2">The secret will be shown once. Copy it and store securely.</p>
    </div>

    <div id="newKeyPanel" class="hidden p-4 bg-white rounded shadow mb-6">
        <h2 class="text-lg font-semibold mb-3">New API Credentials</h2>
        <div class="space-y-3">
            <div>
                <label class="block text-xs text-gray-600 mb-1">API Key</label>
                <div class="flex gap-2">
                    <input id="newApiKey" type="text" readonly class="flex-1 border rounded px-3 py-2 font-mono text-sm" />
                    <button id="copyApiKey" class="px-3 py-2 bg-gray-800 text-white rounded">Copy</button>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-600 mb-1">API Secret</label>
                <div class="flex gap-2">
                    <input id="newApiSecret" type="text" readonly class="flex-1 border rounded px-3 py-2 font-mono text-sm" />
                    <button id="copyApiSecret" class="px-3 py-2 bg-gray-800 text-white rounded">Copy</button>
                </div>
            </div>
            <div class="text-xs text-gray-500">Store the secret now; it will not be shown again.</div>
            <div class="flex gap-2">
                <button id="doneNewKey" class="px-4 py-2 bg-indigo-600 text-white rounded">I saved it</button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-100 text-left text-sm">
            <tr>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Key</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Rate/min</th>
                <th class="px-4 py-3">Last used</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($keys as $k)
                <tr>
                    <td class="px-4 py-3">{{ $k->name ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-sm">{{ $k->key }}</td>
                    <td class="px-4 py-3">
                        @if($k->active)
                            <span class="inline-block px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">Active</span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded bg-red-100 text-red-800 text-xs">Revoked</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <form method="post" action="{{ route('user.api-keys.update', $k) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="number" min="1" max="1000" name="rate_limit_per_min" value="{{ $k->rate_limit_per_min ?? 60 }}" class="w-20 border rounded px-2 py-1"/>
                            <button class="px-2 py-1 text-xs bg-gray-800 text-white rounded">Save</button>
                        </form>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ $k->last_used_at ? $k->last_used_at->diffForHumans() : '—' }}</td>
                    <td class="px-4 py-3">
                        <form method="post" action="{{ route('user.api-keys.update', $k) }}" class="mb-2">
                            @csrf
                            <label class="text-xs text-gray-600">IP allowlist (comma/space separated)</label>
                            <div class="flex gap-2 mt-1">
                                <input type="text" name="ip_allowlist" value="{{ implode(', ', $k->ip_allowlist ?? []) }}" class="flex-1 border rounded px-2 py-1" placeholder="127.0.0.1, 41.59.0.0/16"/>
                                <button class="px-2 py-1 text-xs bg-gray-800 text-white rounded">Save</button>
                            </div>
                        </form>
                        @if($k->active)
                            <form method="post" action="{{ route('user.api-keys.revoke', $k) }}" class="inline">
                                @csrf
                                <button class="px-3 py-1 text-xs bg-red-600 text-white rounded">Revoke</button>
                            </form>
                        @else
                            <form method="post" action="{{ route('user.api-keys.restore', $k) }}" class="inline">
                                @csrf
                                <button class="px-3 py-1 text-xs bg-green-600 text-white rounded">Activate</button>
                            </form>
                        @endif
                        <form method="post" action="{{ route('user.api-keys.destroy', $k) }}" class="inline" onsubmit="return confirm('Delete this key?');">
                            @csrf
                            <button class="px-3 py-1 text-xs bg-gray-200 text-gray-800 rounded">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td class="px-4 py-6 text-gray-500" colspan="6">No API keys yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-10">
        <a href="{{ url('/docs/api') }}" class="text-indigo-600 hover:underline">View API documentation</a>
    </div>
</div>
@endsection

@section('scripts')
<script>
async function createKey() {
    const res = await fetch('{{ route('user.api-keys.create') }}', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
        body: JSON.stringify({name: document.getElementById('name').value})
    });
    const data = await res.json();
    if (data.success) {
        const panel = document.getElementById('newKeyPanel');
        const keyInput = document.getElementById('newApiKey');
        const secretInput = document.getElementById('newApiSecret');
        const copyKeyBtn = document.getElementById('copyApiKey');
        const copySecretBtn = document.getElementById('copyApiSecret');
        const doneBtn = document.getElementById('doneNewKey');
        keyInput.value = data.api_key;
        secretInput.value = data.api_secret;
        panel.classList.remove('hidden');
        copyKeyBtn.onclick = () => { navigator.clipboard.writeText(keyInput.value); };
        copySecretBtn.onclick = () => { navigator.clipboard.writeText(secretInput.value); };
        doneBtn.onclick = () => { location.reload(); };
    } else {
        const panel = document.getElementById('newKeyPanel');
        panel.classList.add('hidden');
        const msg = (data.error||'Failed to generate API key');
        const div = document.createElement('div');
        div.className = 'mb-4 p-3 rounded bg-red-100 text-red-800';
        div.textContent = msg;
        document.querySelector('.max-w-5xl').prepend(div);
    }
}
</script>
@endsection
