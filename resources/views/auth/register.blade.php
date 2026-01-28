<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up with Email</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-100">
    <!-- Header -->
    <header class="bg-white/90 backdrop-blur border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="{{ url('/') }}" class="flex items-center">
<img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-10 w-auto">
                        </a>
                    </div>
                </div>
                
                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-3">
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Sign In</a>
                        <a href="{{ route('email.login') }}" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Phone Login</a>
                        <a href="{{ route('email.register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">Create Account</a>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-600 hover:text-indigo-600 focus:outline-none focus:text-indigo-600" id="mobile-menu-button">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
                <a href="{{ route('login') }}" class="text-gray-700 hover:text-indigo-600 block px-3 py-2 rounded-md text-base font-medium">Sign In</a>
                <a href="{{ route('email.login') }}" class="text-gray-700 hover:text-indigo-600 block px-3 py-2 rounded-md text-base font-medium">Phone Login</a>
                <a href="{{ route('email.register') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white block px-3 py-2 rounded-md text-base font-medium">Create Account</a>
            </div>
        </div>
    </header>

    <div class="min-h-screen flex flex-col sm:flex-row">
        <!-- Left side illustration -->
        <div class="hidden sm:flex sm:w-1/2 items-center justify-center p-8 bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1568216681201-1edacc6c8048?q=80&w=1633&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="text-center bg-black/40 backdrop-blur-sm rounded-2xl p-8 shadow-lg relative z-10">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/10 text-white shadow-lg mb-6">
                    <!-- user icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4" />
                        <path d="M6 20c0-3.314 2.686-6 6-6s6 2.686 6 6" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-white">Join Phidtech SMS</h2>
                <p class="mt-2 text-white/90">Create your account with email and start sending reliable bulk SMS.</p>
            </div>
        </div>

        <!-- Right side form -->
        <div class="w-full sm:w-1/2 flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md bg-white/90 backdrop-blur rounded-2xl border border-gray-200 shadow-xl p-8">
                <div class="mb-6">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create your account</h1>
                    <p class="mt-1 text-sm text-gray-600">Sign up with your email and password</p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <!-- Name Fields -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1">First name</label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required autocomplete="given-name"
                                   placeholder="John"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200" />
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1">Last name</label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required autocomplete="family-name"
                                   placeholder="Doe"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200" />
                        </div>
                    </div>
                    <input type="hidden" name="name" id="full_name" />

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                               placeholder="you@example.com"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('email') border-red-300 focus:border-red-500 focus:ring-red-200 @enderror" />
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone (optional) -->
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Phone number (optional)</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel"
                               placeholder="+255700000000"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('phone') border-red-300 focus:border-red-500 focus:ring-red-200 @enderror" />
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                   placeholder="Create a password"
                                   class="w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200 @error('password') border-red-300 focus:border-red-500 focus:ring-red-200 @enderror" />
                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 px-4 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Toggle password visibility">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                               placeholder="Repeat password"
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200" />
                    </div>

                    <!-- Terms -->
                    <div class="flex items-start">
                        <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded" required>
                        <label for="terms" class="ml-2 block text-sm text-gray-700">I agree to the Terms & Privacy Policy</label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Create Account</button>
                </form>

                <p class="mt-4 text-sm text-gray-600">Already have an account?
                    <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Sign in with email</a>
                </p>
                <p class="text-xs text-gray-500 mt-2">We value your privacy and secure your data</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <a href="{{ url('/') }}" class="flex items-center">
<img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-8 w-auto mr-3">
<span class="text-xl font-bold">Phidsms</span>
                        </a>
                    </div>
                    <p class="text-gray-300 mb-4 leading-relaxed">
                        Professional SMS platform for businesses in Tanzania. Send bulk messages, manage campaigns, and track delivery with ease.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Features</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Contact</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">API Documentation</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">
© {{ date('Y') }} Phidtech SMS. All rights reserved. Made with ❤️ in Tanzania.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Compose full name on submit
        document.querySelector('form')?.addEventListener('submit', function() {
            const first = document.getElementById('first_name').value.trim();
            const last = document.getElementById('last_name').value.trim();
            document.getElementById('full_name').value = [first, last].filter(Boolean).join(' ');
        });
        // Password visibility toggle
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        toggleBtn?.addEventListener('click', () => {
            const isPwd = passwordInput.type === 'password';
            passwordInput.type = isPwd ? 'text' : 'password';
        });

        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        mobileMenuButton?.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
