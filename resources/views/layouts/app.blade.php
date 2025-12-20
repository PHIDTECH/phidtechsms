<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Phidsms') }}</title>

    <!-- Fonts: use system stack; removed external Google Fonts -->
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    @yield('styles')
    @if (!file_exists(public_path('build/manifest.json')))
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    
    <!-- Loading and Animation Styles -->
    <style>
        /* Page Loading Overlay */
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #6144f2;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }
        
        .page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .loader-content {
            text-align: center;
            color: white;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Page Transition */
        .page-content {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .page-content.loaded {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Navigation Link Animations */
        .nav-link {
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.15);
            transition: left 0.5s;
        }
        
        .nav-link:hover::before {
            left: 100%;
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Loading bar */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0;
            height: 3px;
            background: #6144f2;
            z-index: 9998;
            transition: width 0.3s ease;
        }
        
        /* Form animations */
        .form-group {
            position: relative;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Button ripple effect */
        .btn-ripple {
            position: relative;
            overflow: hidden;
        }
        
        .btn-ripple::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-ripple:active::after {
            width: 300px;
            height: 300px;
        }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Noto Sans, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol';
        }
    </style>

    <!-- Scripts -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @endif
</head>
<body class="bg-gray-50">
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <div class="spinner"></div>
            <h3 class="text-xl font-semibold mb-2">Loading...</h3>
            <p class="text-sm opacity-80">Please wait while we prepare your content</p>
        </div>
    </div>
    
    <!-- Loading Bar -->
    <div class="loading-bar" id="loadingBar"></div>
    
    <div id="app" class="page-content">
        <nav id="appNav" class="bg-white shadow-lg border-b border-gray-200 backdrop-blur-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="flex items-center text-gray-900 hover:text-primary-600 transition-all duration-200 group">
                            <img src="{{ asset('logo.png') }}" alt="Phidsms" class="w-10 h-10" />
                        </a>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500" aria-controls="mobile-menu" aria-expanded="false">
                            <span class="sr-only">Open main menu</span>
                            <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Desktop navigation -->
                    <div class="hidden md:flex md:items-center md:space-x-2">
                        @auth
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('dashboard') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-tachometer-alt text-blue-500 group-hover:text-blue-600"></i>
                                    <span>Dashboard</span>
                                </a>
                                <a href="{{ route('campaigns.index') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-[#6144f2] hover:bg-[#6144f2] hover:bg-opacity-10 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-bullhorn text-[#6144f2] group-hover:text-[#6144f2]"></i>
                                    <span>Campaigns</span>
                                </a>
                                <a href="{{ route('wallet.index') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-[#6144f2] hover:bg-[#6144f2] hover:bg-opacity-10 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-wallet text-[#6144f2] group-hover:text-[#6144f2]"></i>
                                    <span>Buy SMS</span>
                                </a>
                                <a href="{{ route('sender-ids.index') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-orange-600 hover:bg-orange-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-id-card text-orange-500 group-hover:text-orange-600"></i>
                                    <span>Sender IDs</span>
                                </a>
                                <a href="{{ route('reports.index') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-chart-bar text-indigo-500 group-hover:text-indigo-600"></i>
                                    <span>Reports</span>
                                </a>
                                <a href="{{ route('user.api-keys.index') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-green-600 hover:bg-green-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-key text-green-500 group-hover:text-green-600"></i>
                                    <span>API Keys</span>
                                </a>
                                <a href="{{ url('/docs/api') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-purple-600 hover:bg-purple-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-book text-purple-500 group-hover:text-purple-600"></i>
                                    <span>API Docs</span>
                                </a>
                            </div>
                        @endauth

                        <!-- Authentication Links -->
                        @guest
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('home') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-[#6144f2] hover:bg-[#6144f2] hover:bg-opacity-10 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-home text-[#6144f2]"></i>
                                    <span>Home</span>
                                </a>
                                <a href="{{ url('/#pricing') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-indigo-600 hover:bg-indigo-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-tags text-indigo-500 group-hover:text-indigo-600"></i>
                                    <span>Pricing</span>
                                </a>
                                <a href="{{ url('/docs/api') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-purple-600 hover:bg-purple-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-book text-purple-500 group-hover:text-purple-600"></i>
                                    <span>API Docs</span>
                                </a>
                                <a href="{{ url('/#contact') }}" class="nav-link flex items-center space-x-2 text-gray-700 hover:text-green-600 hover:bg-green-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group">
                                    <i class="fas fa-phone text-green-500 group-hover:text-green-600"></i>
                                    <span>Contact</span>
                                </a>
                                <a href="{{ route('login') }}" class="flex items-center space-x-1 text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>{{ __('Login') }}</span>
                                </a>
                                <a href="{{ route('register') }}" class="flex items-center space-x-1 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-user-plus"></i>
                                    <span>{{ __('Register') }}</span>
                                </a>
                            </div>
                        @else
                            <div class="relative">
                                <button type="button" class="user-menu-button flex items-center space-x-3 text-gray-700 hover:text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 group" aria-expanded="false">
                                    <div class="w-8 h-8 bg-[#6144f2] rounded-full flex items-center justify-center shadow-lg">
                                        <i class="fas fa-user text-white text-sm"></i>
                                    </div>
                                    <div class="flex flex-col items-start">
                                        <span class="font-medium">{{ Auth::user()->name }}</span>
                                        @if(Auth::user()->sms_credits > 0)
                                            <span class="bg-[#6144f2] text-white text-xs px-2 py-0.5 rounded-full font-medium shadow-sm">{{ number_format(Auth::user()->sms_credits) }} SMS</span>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 transition-transform duration-200 group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <div class="user-menu absolute right-0 mt-2 w-64 rounded-xl shadow-2xl bg-white ring-1 ring-gray-200 hidden" role="menu">
                                    <div class="py-2">
                                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 rounded-t-xl">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-10 h-10 bg-[#6144f2] rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                                                    <p class="text-xs text-gray-600 flex items-center">
                                                        <i class="fas fa-phone mr-1 text-[#6144f2]"></i>{{ Auth::user()->phone }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('profile.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                                    <i class="fas fa-user-cog mr-3 text-blue-500"></i>Profile Settings
                                </a>
                                <a href="{{ route('profile.security') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                                    <i class="fas fa-shield-alt mr-3 text-green-500"></i>Security
                                </a>
                                <a href="#" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                                            <i class="fas fa-credit-card mr-3 text-purple-500"></i>Buy SMS
                                        </a>
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <a href="{{ route('logout') }}" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-all duration-200 rounded-b-xl"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt mr-3"></i>{{ __('Logout') }}
                                </a>
                            </div>
                        </div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                            </div>
                        @endguest
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div class="md:hidden mobile-menu hidden" id="mobile-menu">
                        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg.white border-t border-gray-200">
                    @auth
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="{{ route('campaigns.index') }}" class="flex items-center space-x-2 text-gray-700 hover:text-[#6144f2] hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-bullhorn"></i>
                            <span>Campaigns</span>
                        </a>
                        <a href="{{ route('wallet.index') }}" class="flex items-center space-x-2 text-gray-700 hover:text-[#6144f2] hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-wallet"></i>
                            <span>Buy SMS</span>
                        </a>
                        <a href="{{ route('sender-ids.index') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-id-card"></i>
                            <span>Sender IDs</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                        <a href="{{ route('user.api-keys.index') }}" class="flex items-center space-x-2 text-gray-700 hover:text-green-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-key"></i>
                            <span>API Keys</span>
                        </a>
                        <a href="{{ url('/docs/api') }}" class="flex items-center space-x-2 text-gray-700 hover:text-purple-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-book"></i>
                            <span>API Docs</span>
                        </a>
                        <div class="border-t border-gray-200 pt-4">
                            <div class="px-3 py-2">
                                <div class="text-sm font-medium text-gray-500">{{ Auth::user()->name }}</div>
                                <div class="text-sm text-gray-400">{{ Auth::user()->phone }}</div>
                            </div>
                            <a href="{{ route('logout') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors"
                                   onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>{{ __('Logout') }}</span>
                                </a>
                                <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                        </div>
                    @else
                        <a href="{{ route('home') }}" class="flex items-center space-x-2 text-gray-700 hover:text-[#6144f2] hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                        <a href="{{ url('/#pricing') }}" class="flex items-center space-x-2 text-gray-700 hover:text-indigo-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-tags"></i>
                            <span>Pricing</span>
                        </a>
                        <a href="{{ url('/docs/api') }}" class="flex items-center space-x-2 text-gray-700 hover:text-purple-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-book"></i>
                            <span>API Docs</span>
                        </a>
                        <a href="{{ url('/#contact') }}" class="flex items-center space-x-2 text-gray-700 hover:text-green-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-phone"></i>
                            <span>Contact</span>
                        </a>
                        <a href="{{ route('login') }}" class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>{{ __('Login') }}</span>
                        </a>
                        <a href="{{ route('register') }}" class="flex items-center space-x-2 bg-primary-600 hover:bg-primary-700 text-white block px-3 py-2 rounded-md text-base font-medium transition-colors">
                            <i class="fas fa-user-plus"></i>
                            <span>{{ __('Register') }}</span>
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="py-6">
            @yield('content')
        </main>
    </div>
    
    <!-- Custom Scripts -->
    @yield('scripts')
    
    <script>
        // Page Loading and Animation System
        document.addEventListener('DOMContentLoaded', function() {
            const pageLoader = document.getElementById('pageLoader');
            const loadingBar = document.getElementById('loadingBar');
            const pageContent = document.querySelector('.page-content');
            
            // Hide loader and show content with animation
            function hideLoader() {
                if (pageLoader) {
                    pageLoader.classList.add('hidden');
                    setTimeout(() => {
                        pageLoader.style.display = 'none';
                    }, 500);
                }
                
                if (pageContent) {
                    pageContent.classList.add('loaded');
                }
            }
            
            // Show loading bar progress
            function showLoadingProgress() {
                if (loadingBar) {
                    loadingBar.style.width = '30%';
                    setTimeout(() => {
                        loadingBar.style.width = '60%';
                    }, 200);
                    setTimeout(() => {
                        loadingBar.style.width = '90%';
                    }, 400);
                    setTimeout(() => {
                        loadingBar.style.width = '100%';
                        setTimeout(() => {
                            loadingBar.style.width = '0';
                        }, 200);
                    }, 600);
                }
            }
            
            // Initialize page loading
            showLoadingProgress();
            
            // Hide loader after content is ready
            setTimeout(hideLoader, 800);
            
            // Navigation link animations with loading
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                    
                    // Show loading bar for navigation
                    if (loadingBar) {
                        loadingBar.style.width = '100%';
                    }
                });
            });
            
            // Add ripple effect styles
            const style = document.createElement('style');
            style.textContent = `
                .ripple {
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.3);
                    transform: scale(0);
                    animation: ripple-animation 0.6s linear;
                    pointer-events: none;
                }
                
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Mobile menu toggle
            const mobileMenuButton = document.querySelector('.mobile-menu-button');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            // User menu toggle
            const userMenuButton = document.querySelector('.user-menu-button');
            const userMenu = document.querySelector('.user-menu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function() {
                    userMenu.classList.toggle('hidden');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            
            // Add button ripple effects to all buttons
            const buttons = document.querySelectorAll('button, .btn, [role="button"]');
            buttons.forEach(button => {
                button.classList.add('btn-ripple');
            });
            
            // Add form input animations
            const formInputs = document.querySelectorAll('input, textarea, select');
            formInputs.forEach(input => {
                input.classList.add('form-input');
                
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
            
            // Smooth scroll for anchor links
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            anchorLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
            
            // Add intersection observer for fade-in animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in-up');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observe elements for animation
            const animateElements = document.querySelectorAll('.card, .stat-card, .feature-card');
            animateElements.forEach(el => {
                observer.observe(el);
            });
        });
        
        // Page visibility change handling
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden
                document.title = '💤 ' + document.title.replace('💤 ', '');
            } else {
                // Page is visible
                document.title = document.title.replace('💤 ', '');
            }
        });
    </script>
</body>
</html>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="32x32">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
