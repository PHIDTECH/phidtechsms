@extends('layouts.modern-dashboard')

@section('title', 'Verify Your Email Address')

@section('content')
<div class="flex items-center justify-center min-h-[calc(100vh-200px)] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="flex justify-center">
                <i class="fas fa-envelope-open text-4xl text-blue-600 mb-4"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ __('Verify Your Email Address') }}</h2>
            <p class="text-gray-600">{{ __('Check your inbox for the verification link') }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            @if (session('resent'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                    {{ __('A fresh verification link has been sent to your email address.') }}
                </div>
            @endif

            <div class="text-center space-y-4">
                <p class="text-gray-600">
                    {{ __('Before proceeding, please check your email for a verification link.') }}
                </p>
                
                <p class="text-gray-600">
                    {{ __('If you did not receive the email') }},
                    <form class="inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="text-blue-600 hover:text-blue-500 font-medium underline bg-transparent border-none cursor-pointer">
                            {{ __('click here to request another') }}
                        </button>.
                    </form>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
