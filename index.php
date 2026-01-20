<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = 'Home';

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        die("Database connection failed. Please check your database configuration.");
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Ensure seller_id column exists in services table
try {
    $conn->query("SELECT seller_id FROM services LIMIT 1");
} catch (PDOException $e) {
    try {
        $conn->query("ALTER TABLE services ADD COLUMN seller_id INT DEFAULT NULL");
    } catch (PDOException $e2) {
        error_log("Failed to add seller_id column: " . $e2->getMessage());
    }
}

// Ensure seller_profiles table exists
try {
    $conn->query("SELECT id FROM seller_profiles LIMIT 1");
} catch (PDOException $e) {
    try {
        // Create seller_profiles table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS seller_profiles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNIQUE NOT NULL,
            business_name VARCHAR(100),
            bio TEXT,
            skills TEXT,
            portfolio_url VARCHAR(255),
            experience_years INT DEFAULT 0,
            education VARCHAR(255),
            certifications TEXT,
            id_type ENUM('national_id', 'passport', 'driving_license') DEFAULT 'national_id',
            id_number VARCHAR(50),
            id_document_path VARCHAR(255),
            profile_photo VARCHAR(255),
            tagline VARCHAR(200) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            rating DECIMAL(3,2) DEFAULT 0.00,
            total_reviews INT DEFAULT 0,
            total_earnings DECIMAL(15,2) DEFAULT 0.00,
            available_balance DECIMAL(15,2) DEFAULT 0.00,
            pending_balance DECIMAL(15,2) DEFAULT 0.00,
            total_completed_tasks INT DEFAULT 0,
            status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
            rejection_reason TEXT,
            approved_by INT,
            approved_at DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } catch (PDOException $e2) {
        error_log("Failed to create seller_profiles table: " . $e2->getMessage());
    }
}

// Get featured services with seller info
$services = [];
try {
    $stmt = $conn->query("
        SELECT s.*, 
               sp.id as seller_profile_id, sp.business_name as seller_name, sp.tagline as seller_tagline, 
               sp.profile_photo as seller_photo, sp.description as seller_description,
               u.name as seller_user_name
        FROM services s
        LEFT JOIN seller_profiles sp ON s.seller_id = sp.id
        LEFT JOIN users u ON sp.user_id = u.id
        WHERE s.status = 'active' 
        ORDER BY s.base_price ASC 
        LIMIT 6
    ");
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback: get services without seller info
    try {
        $stmt = $conn->query("SELECT * FROM services WHERE status = 'active' ORDER BY base_price ASC LIMIT 6");
        $services = $stmt->fetchAll();
    } catch (PDOException $e2) {
        // If services table doesn't exist, just use empty array
        $services = [];
        error_log("Services query error: " . $e2->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section with Video Background -->
<section class="relative text-white py-16 md:py-24 overflow-hidden bg-gradient-to-br from-[#667eea] to-[#764ba2]">
    <!-- Video Background Container - Constrained Width -->
    <div class="absolute inset-0 flex items-center justify-center overflow-hidden">
        <div class="w-full max-w-6xl h-full">
            <video 
                id="hero-video"
                class="w-full h-full object-cover opacity-60"
                autoplay 
                muted 
                loop 
                playsinline
                preload="auto"
                poster="">
                <source src="<?= SITE_URL ?>/uploads/videos/hero-video.mp4" type="video/mp4">
                <source src="<?= SITE_URL ?>/uploads/videos/hero-video.webm" type="video/webm">
            </video>
        </div>
    </div>
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-[#667eea]/40 to-[#764ba2]/30"></div>
    
    <!-- Force video play script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var video = document.getElementById('hero-video');
            if (video) {
                video.muted = true;
                video.play().catch(function(error) {
                    console.log('Video autoplay failed:', error);
                });
            }
        });
    </script>
    
    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-6 leading-tight">
                    Transform Your Ideas Into Digital Reality<br>
                    <span class="text-pink-400">with PhidTech</span>
                </h1>
                <p class="text-lg text-blue-100 mb-8">
                    PhidTech Solutions delivers cutting-edge web development, mobile apps, and IT services 
                    to help your business thrive in the digital age.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="<?= SITE_URL ?>/services.php" class="bg-white text-primary px-6 py-3 rounded-lg text-base font-semibold hover:bg-gray-100 transition">
                        <i class="fas fa-rocket mr-2"></i>Explore Services
                    </a>
                    <a href="<?= SITE_URL ?>/auth/register.php" class="border-2 border-white text-white px-6 py-3 rounded-lg text-base font-semibold hover:bg-white hover:text-primary transition">
                        <i class="fas fa-user-plus mr-2"></i>Get Started
                    </a>
                </div>
            </div>
            <div class="hidden md:block">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/20 rounded-xl p-4 text-center">
                            <i class="fas fa-code text-3xl mb-3"></i>
                            <h3 class="text-sm font-semibold">Web Development</h3>
                        </div>
                        <div class="bg-white/20 rounded-xl p-4 text-center">
                            <i class="fas fa-mobile-alt text-3xl mb-3"></i>
                            <h3 class="text-sm font-semibold">Mobile Apps</h3>
                        </div>
                        <div class="bg-white/20 rounded-xl p-4 text-center">
                            <i class="fas fa-paint-brush text-3xl mb-3"></i>
                            <h3 class="text-sm font-semibold">Graphics Design</h3>
                        </div>
                        <div class="bg-white/20 rounded-xl p-4 text-center">
                            <i class="fas fa-chart-line text-3xl mb-3"></i>
                            <h3 class="text-sm font-semibold">Digital Marketing</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl font-bold text-primary">100+</div>
                <div class="text-sm text-gray-600">Projects Completed</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-primary">50+</div>
                <div class="text-sm text-gray-600">Happy Clients</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-primary">5+</div>
                <div class="text-sm text-gray-600">Years Experience</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-primary">24/7</div>
                <div class="text-sm text-gray-600">Support</div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-pink-600 mb-4">Our Services</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">
                We offer a comprehensive range of digital services to help your business succeed online.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($services as $service): ?>
            <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition overflow-hidden group">
                <!-- Seller Profile Header with Photo -->
                <a href="<?= SITE_URL ?>/seller-profile.php?id=<?= $service['seller_profile_id'] ?? 1 ?>&service=<?= $service['id'] ?>" 
                   class="block relative">
                    <!-- Background gradient -->
                    <div class="h-24 bg-gradient-to-r from-primary to-secondary"></div>
                    
                    <!-- Seller Photo - Centered -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 -bottom-10">
                        <?php if ($service['seller_photo']): ?>
                        <img src="<?= SITE_URL ?>/uploads/sellers/<?= $service['seller_photo'] ?>" 
                             class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg" alt="">
                        <?php else: ?>
                        <div class="w-20 h-20 bg-gradient-to-br from-primary to-secondary text-white rounded-full flex items-center justify-center text-2xl font-bold border-4 border-white shadow-lg">
                            <?= strtoupper(substr($service['seller_name'] ?? 'P', 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                
                <!-- Service Content -->
                <div class="pt-12 pb-5 px-5">
                    <!-- Seller Name & Badge -->
                    <div class="text-center mb-3">
                        <a href="<?= SITE_URL ?>/seller-profile.php?id=<?= $service['seller_profile_id'] ?? 1 ?>" class="hover:text-primary">
                            <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($service['seller_name'] ?? 'PhidTech') ?></h4>
                        </a>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($service['seller_tagline'] ?? 'Your Digital Solutions Partner') ?></p>
                        <span class="inline-flex items-center text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full mt-1">
                            <i class="fas fa-check-circle mr-1"></i>Verified
                        </span>
                    </div>
                    
                    <!-- Service Info -->
                    <div class="border-t pt-4">
                        <h3 class="text-lg font-bold text-pink-600 group-hover:text-pink-700 transition mb-1"><?= htmlspecialchars($service['name']) ?></h3>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?= htmlspecialchars($service['description']) ?></p>
                        
                        <!-- Price & Delivery -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-2xl font-bold text-pink-600"><?= formatCurrency($service['base_price']) ?></span>
                            </div>
                            <span class="text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i><?= $service['duration_days'] ?> days delivery
                            </span>
                        </div>
                        
                        <!-- Order Button -->
                        <a href="<?= SITE_URL ?>/order.php?service=<?= $service['id'] ?>" 
                           class="block w-full bg-primary text-white text-center py-2.5 rounded-lg font-semibold hover:bg-secondary transition">
                            Order Now <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-10">
            <a href="<?= SITE_URL ?>/services.php" class="inline-block bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:bg-secondary transition">
                View All Services
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-pink-600 mb-6">Why Choose <span class="text-pink-600">PhidTech</span>?</h2>
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-pink-600">Quality Guaranteed</h3>
                            <p class="text-sm text-gray-600">We deliver high-quality solutions that exceed expectations.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-pink-600">On-Time Delivery</h3>
                            <p class="text-sm text-gray-600">We respect deadlines and deliver projects on schedule.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-headset text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-pink-600">24/7 Support</h3>
                            <p class="text-sm text-gray-600">Our team is always available to assist you.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-dollar-sign text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-pink-600">Affordable Pricing</h3>
                            <p class="text-sm text-gray-600">Competitive prices without compromising quality.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gradient-to-br from-[#667eea] to-[#764ba2] rounded-2xl p-8 text-white">
                <h3 class="text-2xl font-bold mb-4">Ready to Start Your Project?</h3>
                <p class="text-base text-blue-100 mb-6">
                    Get in touch with us today and let's discuss how we can help bring your ideas to life.
                </p>
                <ul class="space-y-3 mb-6 text-sm">
                    <li><i class="fas fa-check mr-3"></i>Free consultation</li>
                    <li><i class="fas fa-check mr-3"></i>Custom solutions</li>
                    <li><i class="fas fa-check mr-3"></i>Flexible payment options</li>
                    <li><i class="fas fa-check mr-3"></i>Post-delivery support</li>
                </ul>
                <a href="<?= SITE_URL ?>/contact.php" class="inline-block bg-white text-primary px-6 py-3 rounded-lg text-base font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-envelope mr-3"></i>Contact Us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-4">Start Your Digital Journey Today</h2>
        <p class="text-lg text-blue-100 mb-8">
            Join hundreds of satisfied clients who have transformed their businesses with <span class="text-pink-300">PhidTech</span> Solutions.
        </p>
        <div class="flex flex-wrap justify-center gap-8">
            <a href="<?= SITE_URL ?>/auth/register.php" class="bg-yellow-400 text-gray-900 px-6 py-3 rounded-lg text-base font-semibold hover:bg-yellow-300 transition">
                <i class="fas fa-user-plus mr-3"></i>Create Account
            </a>
            <a href="<?= SITE_URL ?>/services.php" class="border-2 border-white text-white px-6 py-3 rounded-lg text-base font-semibold hover:bg-white hover:text-gray-900 transition">
                <i class="fas fa-list mr-3"></i>View Services
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
