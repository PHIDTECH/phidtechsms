@extends('layouts.app')

@section('content')
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-6">Contact Import - Mapping</h1>

        <div id="importMappingSheet" x-data="importMappingSheet" x-init="init()">
            <div class="overflow-x-auto rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <template x-for="header in headers" :key="header">
                                <th class="whitespace-nowrap px-4 py-2 text-left font-medium text-gray-900">
                                    <div x-text="header"></div>
                                    <select x-model="map[header]" @change="updateMapping(header, $event.target.value)" class="mt-1 w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
                                        <option value="">Ignore column</option>
                                        <template x-for="option in mappingOptions" :key="option.value">
                                            <option :value="option.value" x-text="option.label"></option>
                                        </template>
                                    </select>
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(row, rowIndex) in rows" :key="rowIndex">
                            <tr>
                                <template x-for="header in headers" :key="header">
                                    <td class="whitespace-nowrap px-4 py-2 text-gray-700" x-text="row[header] || ''"></td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <div class="text-xs text-gray-500" x-show="summary">
                    <span class="font-semibold text-gray-700">Summary:</span>
                    <span x-text="`Valid ${summary.valid} · Invalid ${summary.invalid}`"></span>
                </div>
                <div class="flex gap-3">
                    <button @click="submitMapping" class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('importMappingSheet', () => ({
                headers: [],
                rows: [],
                map: {},
                importId: null,
                summary: null,
                routes: {
                    mapping: @json(route('contacts.import.mapping')),
                },
                mappingOptions: [
                    { value: 'phone', label: 'Phone (required)' },
                    { value: 'additional_phone', label: 'Additional Phone' },
                    { value: 'title', label: 'Title' },
                    { value: 'first_name', label: 'First Name' },
                    { value: 'last_name', label: 'Last Name' },
                    { value: 'gender', label: 'Gender' },
                    { value: 'dob', label: 'Date of Birth (yyyy-mm-dd)' },
                    { value: 'email', label: 'Email' },
                    { value: 'address', label: 'Address' },
                    { value: 'city', label: 'City' },
                    { value: 'country', label: 'Country' },
                    { value: 'area', label: 'Area' },
                ],
                init() {
                    const previewData = window.__importPreview || @json(session('import_preview'));
                    if (previewData) {
                        this.applyPreview(previewData);
                    }
                    window.addEventListener('import:preview-ready', (e) => {
                        this.applyPreview(e.detail);
                    });
                },
                applyPreview(payload) {
                    const { headers = [], rows = [] } = payload || {};
                    this.importId = payload?.import_id || null;
                    this.headers = Array.isArray(headers) ? headers : [];
                    this.rows = Array.isArray(rows) ? rows.slice(0, 10) : [];
                    this.map = {};
                    // Heuristic: bring phone-like columns to the top
                    const scored = this.headers.map(h => ({
                        header: h,
                        score: this.phoneLikelihood(h, this.rows)
                    }));
                    scored.sort((a, b) => b.score - a.score);
                    this.headers = scored.map(s => s.header);
                    // Initialize mapping: first detected phone → phone, second → additional_phone
                    this.headers.forEach((header, idx) => {
                        if (idx === 0 && scored[idx].score > 0) this.map[header] = 'phone';
                        else if (idx === 1 && scored[idx].score > 0) this.map[header] = 'additional_phone';
                        else this.map[header] = '';
                    });
                },
                phoneLikelihood(header, rows) {
                    let hits = 0, total = 0;
                    const rx = /^(\+?255|0)?\d{9,}$/;
                    rows.forEach(r => {
                        if (Object.prototype.hasOwnProperty.call(r, header)) {
                            const v = (r[header] ?? '').toString().trim();
                            if (v.length) {
                                total++;
                                if (rx.test(v.replace(/\s|-/g, ''))) hits++;
                            }
                        }
                    });
                    return total ? hits / total : 0;
                },
                updateMapping(header, value) {
                    this.map[header] = value;
                },
                async submitMapping() {
                    if (!this.importId) {
                        return this.toast('Missing import session. Please re-upload the file.', 'error');
                    }
                    const phoneColumn = Object.entries(this.map).find(([, v]) => v === 'phone')?.[0];
                    if (!phoneColumn) {
                        return this.toast('Please map a column to Phone (required).', 'warning');
                    }
                    try {
                        const res = await fetch(this.routes.mapping, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({
                                import_id: this.importId,
                                column_mapping: this.toServerMapping(this.map),
                            })
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            const msg = data?.errors ? Object.values(data.errors).flat()[0] : (data?.message || 'Unable to process mapping');
                            throw new Error(msg);
                        }
                        const valid = data?.data?.valid_count ?? 0;
                        const invalid = data?.data?.invalid_count ?? 0;
                        this.summary = { valid, invalid };
                        this.toast(`Mapping saved. Valid ${valid} · Invalid ${invalid}.`, 'success');
                        window.dispatchEvent(new CustomEvent('import:mapping-saved', { detail: data?.data }));
                    } catch (e) {
                        this.toast(e.message || 'Failed to submit mapping.', 'error');
                    }
                },
                toServerMapping(obj) {
                    const m = {};
                    Object.entries(obj).forEach(([k, v]) => { if (v) m[v] = k; });
                    return m;
                },
                toast(message, type = 'success') {
                    const el = document.createElement('div');
                    el.className = `fixed bottom-6 right-6 z-50 rounded-lg px-4 py-2 text-sm shadow ${type==='error'?'bg-red-600 text-white':type==='warning'?'bg-yellow-500 text-white':'bg-blue-600 text-white'}`;
                    el.textContent = message;
                    document.body.appendChild(el);
                    setTimeout(() => { el.classList.add('opacity-0'); setTimeout(() => el.remove(), 250); }, 3000);
                }
            }));
        });
    </script>
@endsection
