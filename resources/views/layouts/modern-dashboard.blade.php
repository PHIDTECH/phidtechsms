<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Phidsms Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="32x32">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter','ui-sans-serif','system-ui','-apple-system','Segoe UI','Roboto','Noto Sans','Ubuntu','Cantarell','Helvetica Neue','Arial','Apple Color Emoji','Segoe UI Emoji','Segoe UI Symbol']
                    },
                    colors: {
                        primary: {
                            50: '#f3f1ff',
                            100: '#ebe5ff',
                            200: '#d9ceff',
                            300: '#bea6ff',
                            400: '#9f75ff',
                            500: '#843dff',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'pulse-slow': 'pulse 3s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    @yield('styles')
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen font-sans">
    <div class="flex h-screen">
        <!-- Mobile menu overlay -->
        <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gradient-to-b from-[#6144f2] to-[#4b37c9] text-white flex flex-col shadow-2xl fixed inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 sidebar-transition overflow-y-auto">
            <!-- Logo -->
            <div class="p-6 border-b border-primary-500/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-sms text-[#6144f2] text-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Phidsms</h1>
                            <p class="text-xs text-primary-200">SMS Platform</p>
                        </div>
                    </div>
                    <button id="close-sidebar" class="lg:hidden text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="p-6 border-b border-primary-500/30">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-sm font-bold">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                        @if(Auth::user()->phone ?? Auth::user()->phone_number ?? null)
                            <p class="text-xs text-white/80 truncate">{{ Auth::user()->phone ?? Auth::user()->phone_number }}</p>
                        @endif
                    </div>
                </div>
            </div>

            
            <!-- Navigation -->
            <nav class="flex-1 p-6 space-y-1">
                <div class="mb-6">
                    <p class="text-xs font-semibold text-primary-300 uppercase tracking-wider mb-3">Main Menu</p>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('dashboard.modern') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('dashboard.modern') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('dashboard.modern') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-tachometer-alt text-sm"></i>
                                </div>
                                <span class="font-medium">Dashboard</span>
                                @if(request()->routeIs('dashboard.modern'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('campaigns.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('campaigns.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('campaigns.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-paper-plane text-sm"></i>
                                </div>
                                <span class="font-medium">Send SMS</span>
                                @if(request()->routeIs('campaigns.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('wallet.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('wallet.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('wallet.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-wallet text-sm"></i>
                                </div>
                                <span class="font-medium">Buy SMS</span>
                                @if(request()->routeIs('wallet.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                                                <li>
                            <a href="{{ route('sender-ids.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('sender-ids.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('sender-ids.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-id-card text-sm"></i>
                                </div>
                                <span class="font-medium">Sender IDs</span>
                                @if(request()->routeIs('sender-ids.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sms-templates.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('sms-templates.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('sms-templates.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-file-alt text-sm"></i>
                                </div>
                                <span class="font-medium">Templates</span>
                                @if(request()->routeIs('sms-templates.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('contacts.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('contacts.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('contacts.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-address-book text-sm"></i>
                                </div>
                                <span class="font-medium">Manage Contacts</span>
                                @if(request()->routeIs('contacts.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('contacts.import.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('contacts.import.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('contacts.import.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-file-import text-sm"></i>
                                </div>
                                <span class="font-medium">Import Contacts</span>
                                @if(request()->routeIs('contacts.import.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('reports.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('reports.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('reports.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-chart-bar text-sm"></i>
                                </div>
                                <span class="font-medium">Reports</span>
                                @if(request()->routeIs('reports.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('user.api-keys.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl {{ request()->routeIs('user.api-keys.*') ? 'bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20' : 'hover:bg-white/10 text-primary-200 hover:text-white' }} transition-all duration-200">
                                <div class="w-8 h-8 {{ request()->routeIs('user.api-keys.*') ? 'bg-white/20' : 'bg-white/10' }} rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-key text-sm"></i>
                                </div>
                                <span class="font-medium">API Keys</span>
                                @if(request()->routeIs('user.api-keys.*'))
                                    <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                                @endif
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- SMS Credits Widget -->
            <div class="p-6 border-t border-primary-500/30">
                <div class="bg-gradient-to-r from-primary-500/20 to-primary-400/20 rounded-2xl p-4 backdrop-blur-sm border border-white/10">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium text-primary-200 uppercase tracking-wide">SMS Credits</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ number_format($smsCredits ?? (Auth::user()->sms_credits ?? 0)) }} SMS</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-sms text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Logout Section -->
            <div class="p-6 border-t border-primary-500/30">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                        </div>
                        <span class="font-medium">Logout</span>
                    </button>
                </form>
            </div>
        </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
        <!-- Top Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <!-- Mobile menu button -->
                <button id="mobile-menu-button" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <!-- Mobile Logo -->
                    <a href="{{ route('dashboard') }}" class="lg:hidden ml-3 flex items-center">
                    <img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-8 w-auto" />
                </a>

                <div class="hidden lg:block">
                    <a href="{{ route('dashboard') }}" class="flex items-center">
                        <img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-8 w-auto" />
                    </a>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <button class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6a2 2 0 012 2v8a2 2 0 01-2 2H9l-4-4V9a2 2 0 012-2z"></path>
                        </svg>
                    </button>

                    <!-- User Menu -->
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 rounded-lg p-1">
                            <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="user-menu" class="absolute right-0 mt-2 w-64 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden z-50">
                            <div class="py-2">
                                <!-- User Info -->
                                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 rounded-t-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-white text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ Auth::user()->name ?? 'User' }}</p>
                                            <p class="text-xs text-gray-600">{{ Auth::user()->email ?? Auth::user()->phone_number ?? 'No contact' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <a href="{{ route('profile.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-200">
                                    <i class="fas fa-user-cog mr-3 text-purple-500"></i>Profile Settings
                                </a>
                                <a href="{{ route('profile.security') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-200">
                                    <i class="fas fa-shield-alt mr-3 text-green-500"></i>Security
                                </a>
                                <a href="{{ route('wallet.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-all duration-200">
                                    <i class="fas fa-wallet mr-3 text-blue-500"></i>Buy SMS
                                </a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-all duration-200 rounded-b-xl text-left">
                                        <i class="fas fa-sign-out-alt mr-3"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center space-x-2 text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
            <div class="container mx-auto px-6 py-8">
                @php($approvedNotice = auth()->check() ? \Illuminate\Support\Facades\Cache::get('senderid:approved:' . auth()->id()) : null)
                @if($approvedNotice)
                    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle"></i>
                                <span>Sender ID "{{ $approvedNotice['sender'] ?? 'Your ID' }}" is now active.</span>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                @endif
                @if(auth()->check() && empty(auth()->user()->email))
                    <div class="mb-4 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Add your email to receive receipts and important notifications.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('profile.index') }}" class="inline-flex items-center px-3 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium">Add Email</a>
                                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-yellow-700 hover:text-yellow-900" aria-label="Dismiss">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>

    <!-- JavaScript for Mobile Menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const mobileOverlay = document.getElementById('mobile-overlay');
            const closeSidebar = document.getElementById('close-sidebar');

            const initMobile = function() {
                if (window.innerWidth < 1024) {
                    sidebar.classList.add('-translate-x-full');
                    sidebar.classList.remove('translate-x-0');
                    mobileOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                } else {
                    sidebar.classList.remove('-translate-x-full');
                    sidebar.classList.add('translate-x-0');
                    mobileOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            };

            initMobile();
            window.addEventListener('resize', initMobile);

            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                mobileOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });

            // Close mobile menu
            function closeMobileMenu() {
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                mobileOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }

            closeSidebar.addEventListener('click', closeMobileMenu);
            mobileOverlay.addEventListener('click', closeMobileMenu);

            // Close on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMobileMenu();
                }
            });

            // User menu dropdown functionality
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');

            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
                        userMenu.classList.add('hidden');
                    }
                });

                // Close dropdown on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>

    @yield('scripts')
</body>
</html>
