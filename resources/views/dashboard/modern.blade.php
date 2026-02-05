<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Phidsms</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
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
</head>
<body class="bg-[#6144f2] min-h-screen">
    <style>
        .sidebar-transition{transition:transform .3s ease-in-out}
    </style>
    <div class="flex h-screen">
        <!-- Mobile menu overlay -->
        <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>
        <!-- Sidebar -->
        <div id="sidebar" class="w-64 bg-gradient-to-b from-primary-600 to-primary-800 text-white flex flex-col shadow-2xl fixed inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 sidebar-transition">
            <!-- Logo -->
            <div class="p-6 border-b border-primary-500/30">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-sms text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Phidsms</h1>
                        <p class="text-xs text-primary-200">SMS Platform</p>
                    </div>
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
                            <p class="text-xs text-primary-200 truncate">{{ Auth::user()->phone ?? Auth::user()->phone_number }}</p>
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
                            <a href="{{ route('dashboard') }}" class="group flex items-center space-x-3 p-3 rounded-xl bg-white/10 text-white shadow-lg backdrop-blur-sm border border-white/20">
                                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center group-hover:bg-white/30 transition-colors">
                                    <i class="fas fa-tachometer-alt text-sm"></i>
                                </div>
                                <span class="font-medium">Dashboard</span>
                                <div class="ml-auto w-2 h-2 bg-primary-300 rounded-full"></div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('campaigns.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-bullhorn text-sm"></i>
                                </div>
                                <span class="font-medium">Campaigns</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('wallet.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-wallet text-sm"></i>
                                </div>
                                <span class="font-medium">Buy SMS</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('payments.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-credit-card text-sm"></i>
                                </div>
                                <span class="font-medium">Payments</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sender-ids.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-id-card text-sm"></i>
                                </div>
                                <span class="font-medium">Sender IDs</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('sms-templates.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-file-alt text-sm"></i>
                                </div>
                                <span class="font-medium">Templates</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('contacts.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200 {{ request()->routeIs('contacts.index') ? 'bg-white/10 text-white' : '' }}">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-address-book text-sm"></i>
                                </div>
                                <span class="font-medium">Manage Contacts</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('contacts.import.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200 {{ request()->routeIs('contacts.import.*') ? 'bg-white/10 text-white' : '' }}">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-upload text-sm"></i>
                                </div>
                                <span class="font-medium">Import Contacts</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('reports.index') }}" class="group flex items-center space-x-3 p-3 rounded-xl hover:bg-white/10 text-primary-200 hover:text-white transition-all duration-200">
                                <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center group-hover:bg-white/20 transition-colors">
                                    <i class="fas fa-chart-bar text-sm"></i>
                                </div>
                                <span class="font-medium">Reports</span>
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
                            <p class="text-2xl font-bold text-white mt-1">{{ number_format($smsCredits ?? 0) }} SMS</p>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-sms text-white text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

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
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="md:hidden flex items-center justify-between px-4 py-3">
                    <div class="flex items-center space-x-3">
                        <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                                <img src="{{ asset('logo.png') }}" alt="Phidsms" class="w-8 h-8">
                        <span class="text-[#6144f2] font-bold text-base">Phidsms</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center space-x-2 text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
                <div class="hidden md:flex items-center justify-between px-6 py-4">
                    <div class="hidden md:flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-10 w-auto" />
                        </div>
                    </div>
                    
                    <div class="hidden md:flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="relative p-2 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors">
                                <i class="fas fa-bell text-lg"></i>
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                            </button>
                        </div>
                        
                        <!-- User Profile -->
                        <div class="flex items-center space-x-3">
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name ?? 'User' }}</p>
                                @if(Auth::user()->phone ?? Auth::user()->phone_number ?? null)
                                    <p class="text-xs text-gray-500">{{ Auth::user()->phone ?? Auth::user()->phone_number }}</p>
                                @endif
                            </div>
                            <div class="relative">
                                <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-sm font-bold text-white">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
                                </div>
                                <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                            </div>
                        </div>
                        
                        <!-- Send SMS Button -->
                        <a href="{{ route('campaigns.create') }}" class="bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white px-6 py-2.5 rounded-xl font-medium shadow-lg hover:shadow-xl transition-all duration-200 flex items-center space-x-2">
                            <i class="fas fa-paper-plane text-sm"></i>
                            <span>Send SMS</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                            @csrf
                            <button type="submit" class="inline-flex items-center space-x-2 text-red-600 bg-red-50 hover:bg-red-100 px-3 py-2 rounded-lg text-sm font-medium">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 to-gray-100 p-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 animate-fade-in">
                    <!-- Messages Sent -->
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 hover:border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Messages Sent</p>
                                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($totalSms ?? 0) }}</p>
                                
                            </div>
                            <div class="hidden md:flex w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-paper-plane text-white text-lg"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SMS Credits -->
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 hover:border-green-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">SMS Credits</p>
                                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($smsCredits ?? 4500) }}</p>
                                
                            </div>
                            <div class="hidden md:flex w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-coins text-white text-lg"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Campaigns -->
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 hover:border-primary-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Active Campaigns</p>
                                <p class="text-3xl font-bold text-gray-900 mb-2">{{ $activeCampaigns ?? 8 }}</p>
                                
                            </div>
                            <div class="hidden md:flex w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-bullhorn text-white text-lg"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Delivery Rate -->
                    <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 p-6 border border-gray-100 hover:border-orange-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Delivery Rate</p>
                                <p class="text-3xl font-bold text-gray-900 mb-2">{{ number_format($deliveryRate ?? 0, 1) }}%</p>
                                
                            </div>
                            <div class="hidden md:flex w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-chart-line text-white text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <a href="{{ route('campaigns.create') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border border-gray-100 hover:border-primary-200 group">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-paper-plane text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Send SMS</h3>
                                <p class="text-sm text-gray-500">Create new campaign</p>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border border-gray-100 hover:border-blue-200 group">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-bullhorn text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">View Campaigns</h3>
                                <p class="text-sm text-gray-500">Manage all campaigns</p>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('wallet.index') }}" class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border border-gray-100 hover:border-green-200 group">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-wallet text-white"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">Buy SMS</h3>
                                <p class="text-sm text-gray-500">Top up your credits</p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Sender IDs Section -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Your Sender IDs</h3>
                        <a href="{{ route('sender-ids.create') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">+ Apply New</a>
                    </div>
                    <div class="space-y-3">
                        @forelse(($userSenderIds ?? []) as $sid)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <span class="font-medium text-gray-800">{{ $sid->sender_name }}</span>
                                <span class="text-xs px-3 py-1 rounded-full font-medium
                                    @if($sid->status==='approved') bg-emerald-100 text-emerald-700 @elseif($sid->status==='pending') bg-amber-100 text-amber-700 @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($sid->status) }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center py-6">
                                <i class="fas fa-id-card text-gray-300 text-3xl mb-2"></i>
                                <p class="text-sm text-gray-500">No sender IDs yet. Apply for one to start sending SMS.</p>
                            </div>
                        @endforelse
                    </div>
                    <a href="{{ route('sender-ids.index') }}" class="mt-4 inline-flex items-center justify-center w-full border border-gray-200 hover:bg-gray-50 text-gray-700 px-4 py-2.5 rounded-xl font-medium transition-all">
                        View All Sender IDs
                    </a>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const mobileOverlay = document.getElementById('mobile-overlay');

            function closeMobileMenu(){
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
                mobileOverlay.classList.add('hidden');
                document.body.style.overflow='';
            }

            function openMobileMenu(){
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                mobileOverlay.classList.remove('hidden');
                document.body.style.overflow='hidden';
            }

            if(mobileMenuButton){
                mobileMenuButton.addEventListener('click', openMobileMenu);
            }
            if(mobileOverlay){
                mobileOverlay.addEventListener('click', closeMobileMenu);
            }
            document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeMobileMenu(); } });
            window.addEventListener('resize', function(){ if(window.innerWidth>=1024){ closeMobileMenu(); } });
        });
    </script>
</body>
</html>
