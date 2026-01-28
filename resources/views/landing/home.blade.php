<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phidsms - Bulk SMS Solutions</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" sizes="32x32">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      body { box-sizing: border-box; }
      .gradient-bg { background: #6144f2; }
      .feature-card { transition: all 0.3s ease; }
      .feature-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
      .stat-number { font-size: 2rem; font-weight: 600; line-height: 1; }
      .section-title { font-size: 1.75rem; font-weight: 600; line-height: 1.2; }
      .pricing-card { transition: all 0.3s ease; border: 2px solid transparent; }
      .pricing-card:hover { transform: scale(1.05); border-color: #6144f2; }
      .nav-link { transition: color 0.2s ease; }
      .nav-link:hover { color: #6144f2; }
      @keyframes float { 0%, 100% { transform: translateY(0px);} 50% { transform: translateY(-20px);} }
      .floating { animation: float 3s ease-in-out infinite; }
      .testimonial-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    </style>
  </head>
  <body class="bg-white">
   <nav class="w-full bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-3">
     <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <img src="{{ asset('logo.png') }}" alt="Phidsms" class="h-10 w-auto" />
      </div>
      <div class="flex items-center gap-2 w-full md:w-auto ml-4">
        <a href="{{ route('email.login') }}" class="w-full md:w-auto px-5 py-2 font-semibold rounded-lg border hover:bg-gray-100 transition">Login</a>
        <a href="{{ route('email.register') }}" class="w-full md:w-auto px-5 py-2 font-semibold rounded-lg text-white transition" style="background:#6144f2">Register</a>
      </div>
     </div>
    </div>
   </nav>
    <section id="home" class="relative overflow-hidden gradient-bg text-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:py-20">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-bold mb-6 leading-tight">Send Unlimited Bulk SMS and Promotions to Potential Customers</h1>
                    <p class="text-xl mb-8 opacity-90">Connect your business with the right audience and keep them updated with your latest promotions and offers.</p>
                    <div class="flex flex-nowrap items-center gap-3">
                        <a href="{{ route('wallet.topup') }}" class="px-6 py-2 bg-white text-base font-semibold rounded-lg hover:bg-gray-100 transition shadow border text-gray-900"><i class="fas fa-sms"></i> Purchase SMS</a>
                        <a href="{{ url('/docs/api') }}" class="px-6 py-2 bg-transparent border border-white text-white text-base font-semibold rounded-lg hover:bg-white transition"><i class="fas fa-book"></i> API Docs</a>
                    </div>
                    <div class="mt-8 flex space-x-6">
                        <div><i class="fas fa-bolt mb-2"></i><div class="text-sm">Instant</div></div>
                        <div><i class="fas fa-chart-line mb-2"></i><div class="text-sm">Unlimited</div></div>
                        <div><i class="fas fa-globe mb-2"></i><div class="text-sm">Simple API</div></div>
                        <div><i class="fas fa-tachometer-alt mb-2"></i><div class="text-sm">Real-time</div></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full bg-white py-12">
        <div class="max-w-7xl mx-auto px-6">
     <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div><div class="stat-number" style="color:#6144f2;">99.9%</div><div class="text-gray-200 md:text-gray-600 font-semibold mt-2">Delivery Rate</div></div>
                <div><div class="stat-number" style="color:#6144f2;">&lt;1s</div><div class="text-gray-200 md:text-gray-600 font-semibold mt-2">Delivery Time</div></div>
                <div><div class="stat-number" style="color:#6144f2;">24/7</div><div class="text-gray-200 md:text-gray-600 font-semibold mt-2">Support</div></div>
                <div><div class="stat-number" style="color:#6144f2;">100%</div><div class="text-gray-200 md:text-gray-600 font-semibold mt-2">Secure</div></div>
            </div>
        </div>
    </section>

    <section id="features" class="w-full py-12">
        <div class="max-w-7xl mx-auto px-6">
                    <h2 class="section-title text-center mb-4 font-semibold">Why Phidsms</h2>
            <p class="text-center text-gray-600 text-xl mb-16 max-w-2xl mx-auto">Everything you need to connect with your customers effectively</p>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg"><i class="fas fa-upload text-[#6144f2] w-8 h-8 mb-4"></i><h3 class="text-lg font-semibold mb-3">Unlimited Bulk SMS</h3><p class="text-gray-600">Send unlimited messages to your entire customer base without restrictions</p></div>
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg"><i class="fas fa-check-circle text-[#6144f2] w-8 h-8 mb-4"></i><h3 class="text-lg font-semibold mb-3">Instant Delivery</h3><p class="text-gray-600">Messages delivered in seconds with real-time delivery reports</p></div>
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg"><i class="fas fa-code text-[#6144f2] w-8 h-8 mb-4"></i><h3 class="text-lg font-semibold mb-3">Simple API</h3><p class="text-gray-600">Easy integration with comprehensive documentation and support</p></div>
                <div class="feature-card bg-white p-8 rounded-2xl shadow-lg"><i class="fas fa-chart-line text-[#6144f2] w-8 h-8 mb-4"></i><h3 class="text-lg font-semibold mb-3">Affordable &amp; Scalable</h3><p class="text-gray-600">Cost-effective plans that grow with your business needs</p></div>
            </div>
        </div>
    </section>

    <section id="about" class="mx-auto max-w-7xl px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">About Us</h2>
                <p class="mt-3 text-gray-600">At Phidtechsms, we specialize in providing top-quality bulk SMS solutions tailored to meet the communication needs of modern businesses. We understand the power of direct, timely, and impactful messaging, which is why we strive to offer an efficient platform for sending unlimited promotional and transactional SMS to your target audience.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900">Business Promotions & Offers</h3>
                    <p class="mt-1 text-sm text-gray-600">Send advertisements for promotions and special offers.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900">Event Invitations</h3>
                    <p class="mt-1 text-sm text-gray-600">Invite guests for weddings, birthdays, and meetings.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900">Urgent Notifications</h3>
                    <p class="mt-1 text-sm text-gray-600">Quick updates for employees and groups.</p>
                </div>
                <div class="rounded-xl border border-gray-200 p-4">
                    <h3 class="font-semibold text-gray-900">Reminder Notifications</h3>
                    <p class="mt-1 text-sm text-gray-600">Holiday greetings, appreciation, and appointment reminders.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-12">
            <h2 class="text-2xl font-bold text-gray-900">Services</h2>
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 p-5"><h3 class="font-semibold text-gray-900">Business Promotions & Offers</h3><p class="mt-1 text-sm text-gray-600">Send business advertisements for promotions and special offers.</p></div>
                <div class="rounded-xl border border-gray-200 p-5"><h3 class="font-semibold text-gray-900">Website Order Automation</h3><p class="mt-1 text-sm text-gray-600">Automate website orders and send order confirmations.</p></div>
                <div class="rounded-xl border border-gray-200 p-5"><h3 class="font-semibold text-gray-900">Event Invitations</h3><p class="mt-1 text-sm text-gray-600">Send invitations to your guests for any event.</p></div>
                <div class="rounded-xl border border-gray-200 p-5"><h3 class="font-semibold text-gray-900">Reminder Notifications</h3><p class="mt-1 text-sm text-gray-600">Send reminders and appreciation messages.</p></div>
                <div class="rounded-xl border border-gray-200 p-5"><h3 class="font-semibold text-gray-900">Urgent Notifications</h3><p class="mt-1 text-sm text-gray-600">Notify customers about urgent matters.</p></div>
                <div class="rounded-xl border border-gray-200 p-5"><h3 class="font-semibold text-gray-900">New Arrival Notifications</h3><p class="mt-1 text-sm text-gray-600">Announce new arrivals to your customers.</p></div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="mx-auto max-w-7xl px-4 py-12">
        <h2 class="text-2xl font-bold text-gray-900">How It Works</h2>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 p-5"><div class="text-xs text-gray-500">Step 1</div><div class="font-semibold text-gray-900">Register</div><p class="mt-1 text-sm text-gray-600">Create your account and log in.</p></div>
            <div class="rounded-xl border border-gray-200 p-5"><div class="text-xs text-gray-500">Step 2</div><div class="font-semibold text-gray-900">Purchase SMS</div><p class="mt-1 text-sm text-gray-600">Top up credits directly.</p></div>
            <div class="rounded-xl border border-gray-200 p-5"><div class="text-xs text-gray-500">Step 3</div><div class="font-semibold text-gray-900">Send Campaigns</div><p class="mt-1 text-sm text-gray-600">Use groups or API to send.</p></div>
            <div class="rounded-xl border border-gray-200 p-5"><div class="text-xs text-gray-500">Step 4</div><div class="font-semibold text-gray-900">Track Results</div><p class="mt-1 text-sm text-gray-600">Monitor delivery and performance.</p></div>
        </div>
    </section>

    <section id="pricing" class="bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-12">
            <h2 class="text-2xl font-bold text-gray-900">Pricing</h2>
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <div class="pricing-card bg-white p-8 rounded-2xl shadow-lg">
                    <div class="text-sm text-gray-500">Starter</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900" style="color:#6144f2;">Tsh 30</div>
                    <p class="mt-2 text-sm text-gray-600">per SMS</p>
                    <p class="mt-2 text-sm text-gray-700 font-semibold">1 - 5,000 SMS</p>
                    <a href="{{ route('wallet.topup') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2"><i class="fas fa-sms"></i> Buy SMS</a>
                </div>
                <div class="pricing-card bg-white p-8 rounded-2xl shadow-xl transform scale-105">
                    <div class="text-sm text-gray-500">Growth</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900" style="color:#6144f2;">Tsh 27</div>
                    <p class="mt-2 text-sm text-gray-600">per SMS</p>
                    <p class="mt-2 text-sm text-gray-700 font-semibold">5,001 - 10,000 SMS</p>
                    <a href="{{ route('wallet.topup') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2"><i class="fas fa-sms"></i> Buy SMS</a>
                </div>
                <div class="pricing-card bg-white p-8 rounded-2xl shadow-lg">
                    <div class="text-sm text-gray-500">Business</div>
                    <div class="mt-1 text-3xl font-bold text-gray-900" style="color:#6144f2;">Tsh 25</div>
                    <p class="mt-2 text-sm text-gray-600">per SMS</p>
                    <p class="mt-2 text-sm text-gray-700 font-semibold">10,001 - 50,000 SMS</p>
                    <a href="{{ route('wallet.topup') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2"><i class="fas fa-sms"></i> Buy SMS</a>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="mx-auto max-w-7xl px-4 py-12">
        <h2 class="text-2xl font-bold text-gray-900">What Our Customers Say</h2>
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="rounded-xl border border-gray-200 p-6 bg-white shadow-sm"><p class="text-sm text-gray-700">“Phidtech SMS made it easy to reach our customers instantly.”</p><div class="mt-3 text-xs text-gray-500">Retail Business</div></div>
            <div class="rounded-xl border border-gray-200 p-6 bg-white shadow-sm"><p class="text-sm text-gray-700">“Setup was simple and delivery reports helped us optimize.”</p><div class="mt-3 text-xs text-gray-500">Events Agency</div></div>
            <div class="rounded-xl border border-gray-200 p-6 bg-white shadow-sm"><p class="text-sm text-gray-700">“Great value and scalability for our growth.”</p><div class="mt-3 text-xs text-gray-500">E‑commerce</div></div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-gray-200 p-6">
                    <h2 class="text-2xl font-bold text-gray-900">Contact</h2>
                    <p class="mt-2 text-gray-600">Hello, contact us for the best experience of our services.</p>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-lg border border-gray-200 p-4"><div class="text-sm text-gray-500">Address</div><div class="font-semibold text-gray-900">Dar es Salaam, Tanzania</div></div>
                        <div class="rounded-lg border border-gray-200 p-4"><div class="text-sm text-gray-500">Call Us</div><div class="font-semibold text-gray-900">0682 188 544</div></div>
                        <a href="https://wa.me/255682188544" class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"><div class="text-sm text-gray-500">Chat on WhatsApp</div><div class="font-semibold text-gray-900">Click to Chat</div></a>
                        <a href="mailto:info@phidtechsms.co.tz" class="rounded-lg border border-gray-200 p-4 hover:bg-gray-50"><div class="text-sm text-gray-500">Email Us</div><div class="font-semibold text-gray-900">info@phidtechsms.co.tz</div></a>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900">Get Started</h3>
                <p class="mt-2 text-sm text-gray-600">Welcome to bulk SMS services. Automate your business with auto-reply SMS from us. We offer instant support to make sure you make it. Let’s get started today.</p>
                <div class="mt-4 space-y-2">
                    <a href="{{ route('email.register') }}" class="block w-full text-center rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2">Register</a>
                    <a href="{{ route('email.login') }}" class="block w-full text-center rounded-lg border border-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-50">Login</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-gray-300">
        <div class="mx-auto max-w-7xl px-4 py-10">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <h4 class="font-semibold text-white">Useful Links</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a class="hover:underline" href="{{ route('home') }}">Home</a></li>
                        <li><a class="hover:underline" href="{{ route('email.register') }}">Register</a></li>
                        <li><a class="hover:underline" href="{{ route('email.login') }}">Login</a></li>
                        <li><a class="hover:underline" href="#">Terms of service</a></li>
                        <li><a class="hover:underline" href="#">Privacy policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white">Our Services</h4>
                    <ul class="mt-3 space-y-2">
                        <li>Bulk SMS</li>
                        <li>SMS APIs</li>
                        <li>WordPress Plugin</li>
                        <li><a class="hover:underline" href="{{ url('/docs/api') }}">API Docs</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white">Contact Us</h4>
                    <ul class="mt-3 space-y-2">
                        <li>Dar es Salaam</li>
                        <li>Tanzania</li>
                        <li>Phone: +255 682 188 544</li>
                        <li>Email: info@phidtechsms.co.tz</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white">Purchase SMS</h4>
                    <p class="mt-3 text-sm">Top up credits and start sending right away.</p>
                    <a href="{{ route('wallet.topup') }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2"><i class="fas fa-sms"></i> Buy SMS</a>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-800 pt-6 text-sm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>© Copyright {{ date('Y') }} Phidsms All Rights Reserved</div>
                    <div>Designed by Phidtech Technologies</div>
                </div>
            </div>
        </div>
    </footer>

    <div class="fixed bottom-4 left-4 right-4 md:left-auto md:right-6 z-40">
        <div class="mx-auto md:ml-auto md:w-max rounded-2xl bg-white shadow-xl border border-gray-200 px-4 py-3 flex items-center gap-3">
            <span class="hidden md:inline text-sm text-gray-700">Ready to start?</span>
            <a href="{{ route('wallet.topup') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2"><i class="fas fa-sms"></i> Buy SMS</a>
            <a href="{{ url('/docs/api') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-gray-800 hover:bg-gray-50"><i class="fas fa-book"></i> API Docs</a>
        </div>
    </div>
  </body>
</html>
