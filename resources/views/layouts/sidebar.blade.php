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

    <!-- Custom Styles -->
    @yield('styles')
    
    <!-- Responsive Sidebar Styles -->
    <style>
        /* Mobile sidebar styles */
        @media (max-width: 1023px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.show {
                transform: translateX(0);
            }
        }
        
        /* Prevent horizontal scroll */
        body {
            overflow-x: hidden;
        }
        
        /* Main content responsive margins */
        .main-content {
            margin-left: 0;
        }
        
        @media (min-width: 1024px) {
            .main-content {
                margin-left: 18rem; /* 288px = w-72 */
            }
        }
        
        /* Smooth transitions */
        #sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        /* Ensure content doesn't overflow */
        .sidebar-item {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 640px) {
            .main-content {
                padding: 1rem;
            }
            
            header {
                padding: 0.75rem 1rem;
            }
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Enhanced Sidebar -->
        <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-[#6144f2] lg:static lg:inset-0 shadow-2xl border-r border-purple-600">
            <!-- Enhanced Logo Section -->
            <div class="flex items-center justify-center h-20 px-6 bg-[#5a3ad1] border-b border-purple-500 shadow-lg">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="w-12 h-12 bg-[#6144f2] rounded-2xl flex items-center justify-center shadow-xl transform rotate-3">
                            <i class="fas fa-sms text-white text-xl"></i>
                        </div>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-gray-800 animate-pulse"></div>
                    </div>
                    <div>
                         <h1 class="text-xl font-black text-white tracking-tight">{{ config('app.name', 'Phidsms') }}</h1>
                         <p class="text-xs text-gray-300 font-medium">Professional SMS Platform</p>
                     </div>
                </div>
            </div>

            <!-- Enhanced Navigation -->
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                @auth
                    @if(Auth::user()->isAdmin())
                        <!-- Admin Panel Only -->
                        <div class="space-y-1">
                            <div class="px-3 py-2 mb-4">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center">
                                    <i class="fas fa-crown mr-2 text-yellow-500"></i>
                                    Admin Panel
                                </h3>
                            </div>
                            
                            <a href="{{ route('admin.dashboard') }}" class="sidebar-item group {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <span class="font-semibold">Dashboard</span>
                            </a>
                            
                            <a href="{{ route('admin.users.index') }}" class="sidebar-item group {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <span class="font-semibold">Manage Clients</span>
                            </a>
                            
                            <a href="{{ route('admin.payments.index') }}" class="sidebar-item group {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                                <span class="font-semibold">Manage Purchases</span>
                            </a>
                            
                            <a href="{{ route('admin.sender-ids.index') }}" class="sidebar-item group {{ request()->routeIs('admin.sender-ids.*') ? 'active' : '' }}">
                                <span class="font-semibold">Sender Names</span>
                            </a>
                            
                            <a href="{{ route('admin.reports.index') }}" class="sidebar-item group {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                                <span class="font-semibold">Admin Reports</span>
                            </a>
                            
                            <a href="{{ route('admin.sms.transactions') }}" class="sidebar-item group {{ request()->routeIs('admin.sms.*') ? 'active' : '' }}">
                                <span class="font-semibold">SMS Balance</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-3 p-3 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span class="font-semibold">Logout</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <!-- Main Navigation for Regular Users -->
                        <div class="space-y-1">
                            <div class="px-3 py-2 mb-4">
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Main Menu</h3>
                            </div>
                            
                            <a href="{{ route('dashboard') }}" class="sidebar-item group {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                 <span class="font-semibold">Dashboard</span>
                             </a>
                            
                            <a href="{{ route('campaigns.index') }}" class="sidebar-item group {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Campaigns</span>
                             </a>
                            
                            <a href="{{ route('wallet.index') }}" class="sidebar-item group {{ request()->routeIs('wallet.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Top Up SMS</span>
                             </a>
                            
                            <a href="{{ route('payments.index') }}" class="sidebar-item group {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Payments</span>
                             </a>
                            
                            <a href="{{ route('sender-ids.index') }}" class="sidebar-item group {{ request()->routeIs('sender-ids.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Sender IDs</span>
                             </a>
                            
                            <a href="{{ route('sms-templates.index') }}" class="sidebar-item group {{ request()->routeIs('sms-templates.*') ? 'active' : '' }}">
                                 <span class="font-semibold">SMS Templates</span>
                             </a>
                            
                            <a href="{{ route('contacts.index') }}" class="sidebar-item group {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Manage Contacts</span>
                             </a>
                            
                            <a href="{{ route('contacts.import.index') }}" class="sidebar-item group {{ request()->routeIs('contacts.import.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Import Contacts</span>
                             </a>
                            
                            <a href="{{ route('reports.index') }}" class="sidebar-item group {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                 <span class="font-semibold">Reports</span>
                             </a>

                            <a href="{{ route('user.api-keys.index') }}" class="sidebar-item group {{ request()->routeIs('user.api-keys.*') ? 'active' : '' }}">
                                 <span class="font-semibold">API Keys</span>
                             </a>
                            <a href="/docs/api" class="sidebar-item group {{ request()->is('docs/api') ? 'active' : '' }}">
                                 <span class="font-semibold">API Docs</span>
                             </a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                                @csrf
                                <button type="submit" class="w-full flex items-center space-x-3 p-3 rounded-lg text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span class="font-semibold">Logout</span>
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth
            </nav>

            <!-- User Profile -->
            @auth
                <div class="p-4 border-t border-purple-500 bg-[#5a3ad1]">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="relative">
                            <div class="w-12 h-12 bg-[#6144f2] rounded-full flex items-center justify-center shadow-lg">
                                <i class="fas fa-user text-white text-lg"></i>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-[#5a3ad1]"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-300 truncate flex items-center">
                                <i class="fas fa-phone mr-1 text-[#6144f2]"></i>{{ Auth::user()->phone }}
                            </p>
                        </div>
                        <div class="relative">
                            <button id="userMenuButton" class="p-2 text-gray-300 hover:text-white hover:bg-purple-600 rounded-lg focus:outline-none transition-all duration-200">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div id="userMenu" class="hidden absolute bottom-full right-0 mb-2 w-52 bg-white rounded-xl shadow-2xl py-2 z-50 border border-gray-200">
                                <a href="{{ route('profile.index') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-user-cog mr-3 text-blue-500"></i>Profile Settings
                        </a>
                        <a href="{{ route('profile.security') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-shield-alt mr-3 text-green-500"></i>Security
                        </a>
                                <a href="{{ route('wallet.topup') }}" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                                    <i class="fas fa-credit-card mr-3 text-purple-500"></i>Buy SMS
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                            <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors duration-200 rounded-b-xl">
                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                        </a>
                            </div>
                        </div>
                    </div>
                    @if(Auth::user()->sms_credits > 0)
                        <div class="px-3 py-2 bg-[#6144f2] rounded-xl shadow-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-white flex items-center">
                                    <i class="fas fa-sms mr-2"></i>SMS Credits
                                </span>
                                <span class="text-sm font-bold text-white">{{ number_format(Auth::user()->sms_credits) }} SMS</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endauth
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden main-content">
            <!-- Top Header -->
            <header class="bg-white shadow-lg border-b border-gray-200 backdrop-blur-sm">
                <div class="flex items-center justify-between px-4 sm:px-6 py-4">
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <button id="sidebarToggle" class="lg:hidden p-3 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg focus:outline-none transition-all duration-200 touch-manipulation">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                    <div class="flex items-center">
                            <a href="{{ route('dashboard') }}" class="flex items-center">
                                <img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-8 w-auto sm:h-10" />
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4">
                        <!-- Notifications -->
                        <div class="relative">
                            <button class="p-3 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg focus:outline-none transition-all duration-200 relative touch-manipulation">
                                <i class="fas fa-bell text-lg sm:text-xl"></i>
                                <span class="absolute -top-1 -right-1 bg-[#F23700] text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold shadow-lg">3</span>
                            </button>
                        </div>
                        <!-- Quick Actions -->
                        <a href="{{ route('campaigns.create') }}" class="bg-[#6144f2] hover:bg-[#5338d8] text-white px-3 sm:px-6 py-3 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 flex items-center touch-manipulation">
                            <i class="fas fa-paper-plane mr-1 sm:mr-2"></i><span class="hidden sm:inline">Send SMS</span><span class="sm:hidden">Send SMS</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center px-3 py-2 rounded-lg text-xs sm:text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition-all duration-200">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Logout Form -->
    @auth
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    @endauth

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>

    <!-- Custom Scripts -->
    <script>
        // Enhanced mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('hidden');
            
            // Prevent body scroll when sidebar is open
            if (sidebar.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.remove('show');
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
        });
        
        // Close sidebar on window resize if desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                
                sidebar.classList.remove('show');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        });
        
        // Close sidebar when clicking on sidebar links (mobile)
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth < 1024) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    sidebar.classList.remove('show');
                    overlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        });

        // User menu toggle
        document.getElementById('userMenuButton')?.addEventListener('click', function() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        });

        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const button = document.getElementById('userMenuButton');
            const menu = document.getElementById('userMenu');
            
            if (button && menu && !button.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>

    <!-- jQuery -->
    
    @yield('scripts')
</body>
</html>

