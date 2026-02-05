@extends('layouts.admin-modern-dashboard')

@section('title', 'Admin - Send Message')

@section('content')
<div class="animate-fade-in-up">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-envelope text-purple-600 mr-3"></i>
                    Messaging Service
                </h1>
                <p class="text-gray-600">Send SMS notifications to users</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('admin.sms.compose') }}" 
                   class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 inline-flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Compose Message
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- SMS Balance -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">SMS Balance</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['sms_balance']) }}</p>
                    <a href="{{ route('wallet.topup') }}" class="text-xs text-blue-600 hover:underline mt-1 inline-block">
                        Buy More SMS →
                    </a>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-envelope text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Delivered This Month -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Delivered This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['delivered_this_month']) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Sent This Month -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Sent This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['sent_this_month']) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-paper-plane text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Failed This Month -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Failed This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['failed_this_month']) }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Send SMS -->
        <a href="{{ route('admin.sms.compose') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer group">
            <div class="flex items-center">
                <div class="p-4 bg-purple-100 rounded-xl group-hover:bg-purple-200 transition-colors">
                    <i class="fas fa-comment-dots text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Send SMS</h3>
                    <p class="text-sm text-gray-600">Send messages to users</p>
                </div>
            </div>
        </a>

        <!-- Buy SMS -->
        <a href="{{ route('wallet.topup') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer group">
            <div class="flex items-center">
                <div class="p-4 bg-green-100 rounded-xl group-hover:bg-green-200 transition-colors">
                    <i class="fas fa-coins text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Buy SMS</h3>
                    <p class="text-sm text-gray-600">Purchase SMS packages</p>
                </div>
            </div>
        </a>

        <!-- Sender IDs -->
        <a href="{{ route('admin.sender-ids.index') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow cursor-pointer group">
            <div class="flex items-center">
                <div class="p-4 bg-blue-100 rounded-xl group-hover:bg-blue-200 transition-colors">
                    <i class="fas fa-id-card text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900">Sender IDs</h3>
                    <p class="text-sm text-gray-600">Manage your sender names</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Sender ID Alert -->
    @if(!$hasSenderId)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-8">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-400 mr-3"></i>
            <div>
                <h4 class="font-semibold text-yellow-800">No Sender ID Configured</h4>
                <p class="text-sm text-yellow-700">You need an approved Sender ID to send SMS messages. <a href="{{ route('admin.sender-ids.index') }}" class="underline font-medium">Request one now</a></p>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Messages -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Recent Messages</h3>
                <a href="{{ route('admin.sms.history') }}" class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                    View All →
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recipient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recentMessages as $message)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $message->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $message->phone }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <div class="max-w-xs truncate">{{ $message->message_content }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'queued' => 'bg-gray-100 text-gray-800',
                                    'sent' => 'bg-blue-100 text-blue-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$message->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($message->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                <p>No messages sent yet. <a href="{{ route('admin.sms.compose') }}" class="text-purple-600 hover:underline">Send your first message</a></p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
