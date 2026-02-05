<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Phidtech SMS</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        
        .content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Mobile Menu Overlay -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>
    
    <!-- Sidebar -->
    <div id="sidebar" class="fixed left-0 top-0 h-full w-64 bg-gradient-to-b from-[#6144f2] to-[#4b37c9] text-white z-50 sidebar-transition transform -translate-x-full lg:translate-x-0">
        <!-- Logo Section -->
        <div class="p-6 border-b border-purple-500">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                    <i class="fas fa-sms text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">Phidtech SMS</h1>
                    <p class="text-purple-300 text-sm">Admin Panel</p>
                </div>
            </div>
        </div>
        
        <!-- User Profile -->
        <div class="p-6 border-b border-purple-500">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-[#6144f2] rounded-full flex items-center justify-center">
                    <i class="fas fa-user-shield text-white text-lg"></i>
                </div>
                <div>
                    <p class="font-semibold">{{ Auth::user()->name }}</p>
                    <p class="text-purple-300 text-sm">{{ Auth::user()->email }}</p>
                    <span class="inline-block px-2 py-1 bg-yellow-500 text-yellow-900 text-xs rounded-full font-semibold mt-1">Admin</span>
                </div>
            </div>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 p-4">
            <ul class="space-y-2">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="dashboard">
                        <i class="fas fa-tachometer-alt w-5"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="users">
                        <i class="fas fa-users w-5"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.sms.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="send-message">
                        <i class="fas fa-paper-plane w-5"></i>
                        <span>Send Message</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.campaigns.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="campaigns">
                        <i class="fas fa-bullhorn w-5"></i>
                        <span>Campaigns</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.payments.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="payments">
                        <i class="fas fa-credit-card w-5"></i>
                        <span>Payments</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.sender-ids.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="sender-ids">
                        <i class="fas fa-id-card w-5"></i>
                        <span>Sender IDs</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-[#5a3ad1] transition-colors" data-section="reports">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.settings.index') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-purple-700 transition-colors" data-section="settings">
                        <i class="fas fa-cog w-5"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.settings.api') }}" class="nav-link flex items-center space-x-3 p-3 rounded-lg hover:bg-purple-700 transition-colors" data-section="api">
                        <i class="fas fa-code w-5"></i>
                        <span>API Management</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Logout Section -->
        <div class="p-4 border-t border-purple-700">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-purple-700 transition-colors text-left">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Main Content -->
    <div id="main-content" class="content-transition lg:ml-64">
        <!-- Top Header -->
            <header class="bg-white shadow-sm border-b border-gray-200 p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-gray-600"></i>
                        </button>
                        <h2 id="page-title" class="text-xl font-semibold text-[#6144f2]">Admin Dashboard</h2>
                    </div>
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex items-center space-x-2 text-sm text-gray-600">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"></span>
                    </div>
                    <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-shield text-white text-sm"></i>
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
        
        <!-- Page Content -->
        <main class="p-6">
            @yield('content')
        </main>
    </div>
    
    <!-- JavaScript -->
    <script>
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');
        
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            mobileOverlay.classList.toggle('hidden');
        });
        
        mobileOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            mobileOverlay.classList.add('hidden');
        });
        
        // Navigation functionality
        const navLinks = document.querySelectorAll('.nav-link');
        const pageTitle = document.getElementById('page-title');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Remove active class from all links
                navLinks.forEach(l => l.classList.remove('bg-purple-700'));
                
                // Add active class to clicked link
                link.classList.add('bg-purple-700');
                
                // Update page title
                const section = link.dataset.section;
                if (section) {
                    const sectionNames = {
                        'dashboard': 'Admin Dashboard',
                        'users': 'User Management',
                        'send-message': 'Send Message',
                        'campaigns': 'Campaign Management',
                        'payments': 'Payment Management',
                        'sender-ids': 'Sender ID Management',
                        'reports': 'Reports & Analytics',
                        'settings': 'System Settings',
                        'api': 'API Management'
                    };
                    pageTitle.textContent = sectionNames[section] || 'Admin Dashboard';
                }
                
                // Close mobile menu
                if (window.innerWidth < 1024) {
                    sidebar.classList.add('-translate-x-full');
                    mobileOverlay.classList.add('hidden');
                }
            });
        });
        
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour12: false,
                hour: '2-digit',
                minute: '2-digit'
            });
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Update time every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Set active navigation based on current page
        window.addEventListener('load', () => {
            const currentPath = window.location.pathname;
            navLinks.forEach(link => {
                if (link.href === window.location.href) {
                    link.classList.add('bg-purple-700');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
