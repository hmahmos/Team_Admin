<?php 
include 'config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة موريتانيا للخدمات الإلكترونية</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
        }
        
        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1632&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .language-switcher:hover .language-dropdown {
            display: block;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-green-800 text-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <div class="h-10 w-10 bg-white rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-flag text-green-800"></i>
                </div>
                <h1 class="text-xl font-bold">منصة موريتانيا للخدمات</h1>
            </div>
            
            <!-- Navigation -->
            <nav class="hidden md:flex items-center space-x-6 space-x-reverse">
                <!-- Language Switcher -->
                <div class="language-switcher relative">
                    <button class="flex items-center hover:bg-green-700 px-3 py-2 rounded">
                        <span class="mr-1">العربية</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="language-dropdown hidden absolute right-0 mt-2 w-32 bg-white text-gray-800 rounded shadow-lg py-1 z-10">
                        <a href="#" class="block px-4 py-2 hover:bg-gray-100">Français</a>
                        <a href="#" class="block px-4 py-2 hover:bg-gray-100">English</a>
                    </div>
                </div>
                
                <!-- Auth Buttons -->
                <div class="flex space-x-4 space-x-reverse">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo $_SESSION['role'] === 'citizen' ? 'citizen_dashboard.php' : ($_SESSION['role'] === 'super_admin' ? 'super_admin.php' : 'admin_dashboard.php'); ?>" 
                           class="px-4 py-2 bg-white text-green-800 rounded hover:bg-gray-100 transition">
                            لوحة التحكم
                        </a>
                        <a href="logout.php" class="px-4 py-2 border border-white rounded hover:bg-white hover:text-green-800 transition">
                            تسجيل الخروج
                        </a>
                    <?php else: ?>
                        <a href="auth.php?action=login" class="px-4 py-2 border border-white rounded hover:bg-white hover:text-green-800 transition">
                            تسجيل الدخول
                        </a>
                        <a href="auth.php?action=register" class="px-4 py-2 bg-white text-green-800 rounded hover:bg-gray-100 transition">
                            إنشاء حساب
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
            
            <!-- Mobile menu button -->
            <button class="md:hidden text-white focus:outline-none" id="mobile-menu-btn">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-4 py-2 space-y-2">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo $_SESSION['role'] === 'citizen' ? 'citizen_dashboard.php' : ($_SESSION['role'] === 'super_admin' ? 'super_admin.php' : 'admin_dashboard.php'); ?>" 
                       class="block px-4 py-2 bg-green-700 rounded">لوحة التحكم</a>
                    <a href="logout.php" class="block px-4 py-2 bg-green-700 rounded">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="auth.php?action=login" class="block px-4 py-2 bg-green-700 rounded">تسجيل الدخول</a>
                    <a href="auth.php?action=register" class="block px-4 py-2 bg-green-700 rounded">إنشاء حساب</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Notifications -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="notification bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section text-white py-20 md:py-32">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-3xl md:text-5xl font-bold mb-4">منصة موريتانيا للخدمات الإلكترونية</h1>
            <p class="text-xl md:text-2xl mb-8 max-w-2xl mx-auto">حلول رقمية متطورة لتسهيل الوصول إلى جميع الخدمات الحكومية والمحلية بكفاءة وسرعة</p>
            <div class="flex justify-center space-x-4 space-x-reverse">
                <?php if (isLoggedIn()): ?>
                    <a href="citizen_dashboard.php" class="px-6 py-3 bg-white text-green-800 rounded-lg font-medium hover:bg-gray-100 transition">
                        تصفح الخدمات
                    </a>
                <?php else: ?>
                    <a href="auth.php?action=register" class="px-6 py-3 bg-white text-green-800 rounded-lg font-medium hover:bg-gray-100 transition">
                        ابدأ الآن
                    </a>
                <?php endif; ?>
                <a href="#services" class="px-6 py-3 border border-white rounded-lg font-medium hover:bg-white hover:text-green-800 transition">
                    تعرف على الخدمات
                </a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-4">خدماتنا الإلكترونية</h2>
            <p class="text-center text-gray-600 mb-12 max-w-2xl mx-auto">
                نوفر لك مجموعة شاملة من الخدمات الإلكترونية لتلبية احتياجاتك اليومية بسهولة ويسر
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                <!-- Service Card 1 -->
                <div class="service-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 transition duration-300">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                            <i class="fas fa-city text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-center text-gray-800 mb-2">خدمات البلديات والقرى</h3>
                        <p class="text-gray-600 text-center mb-4">استخراج عقود الميلاد، شهادات السكن، ورخص البناء</p>
                        <div class="text-center">
                            <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=municipality' : 'auth.php?action=login'; ?>" 
                               class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Service Card 2 -->
                <div class="service-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 transition duration-300">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                            <i class="fas fa-hands-helping text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-center text-gray-800 mb-2">خدمات الجمعيات والمنظمات</h3>
                        <p class="text-gray-600 text-center mb-4">تسجيل الجمعيات وطلبات الدعم المالي</p>
                        <div class="text-center">
                            <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=association' : 'auth.php?action=login'; ?>" 
                               class="inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Service Card 3 -->
                <div class="service-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 transition duration-300">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                            <i class="fas fa-school text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-center text-gray-800 mb-2">خدمات المدارس والمعاهد</h3>
                        <p class="text-gray-600 text-center mb-4">التسجيل المدرسي والشهادات الدراسية</p>
                        <div class="text-center">
                            <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=school' : 'auth.php?action=login'; ?>" 
                               class="inline-block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Service Card 4 -->
                <div class="service-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 transition duration-300">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                            <i class="fas fa-store text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-center text-gray-800 mb-2">خدمات الأسواق المحلية</h3>
                        <p class="text-gray-600 text-center mb-4">التراخيص التجارية وتجديد الرخص</p>
                        <div class="text-center">
                            <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=market' : 'auth.php?action=login'; ?>" 
                               class="inline-block px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Service Card 5 -->
                <div class="service-card bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 transition duration-300">
                    <div class="p-6">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                            <i class="fas fa-bus text-red-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-center text-gray-800 mb-2">خدمات النقل والمواصلات</h3>
                        <p class="text-gray-600 text-center mb-4">رخص القيادة وبطاقات السير</p>
                        <div class="text-center">
                            <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=transport' : 'auth.php?action=login'; ?>" 
                               class="inline-block px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                                عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php' : 'auth.php?action=register'; ?>" 
                   class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <?php echo isLoggedIn() ? 'عرض جميع الخدمات' : 'ابدأ استخدام الخدمات'; ?> 
                    <i class="fas fa-arrow-left mr-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">لماذا تختار منصتنا؟</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="fas fa-clock text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">توفير الوقت</h3>
                    <p class="text-gray-600">إنجاز المعاملات بسرعة ودون الحاجة للانتظار في الطوابير الطويلة</p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="fas fa-shield-alt text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">آمن وموثوق</h3>
                    <p class="text-gray-600">نضمن حماية بياناتك ومعاملاتك بأعلى معايير الأمان والخصوصية</p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="fas fa-headset text-purple-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">دعم فني متكامل</h3>
                    <p class="text-gray-600">فريق دعم فني متخصص متاح لمساعدتك على مدار الساعة</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-green-800 text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl font-bold mb-2">15,000+</div>
                    <div class="text-green-200">مستخدم مسجل</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">50,000+</div>
                    <div class="text-green-200">طلب مكتمل</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">25+</div>
                    <div class="text-green-200">خدمة متاحة</div>
                </div>
                <div>
                    <div class="text-4xl font-bold mb-2">98%</div>
                    <div class="text-green-200">معدل الرضا</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white pt-12 pb-6">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="h-8 w-8 bg-green-600 rounded-full flex items-center justify-center mr-2">
                            <i class="fas fa-flag text-white text-sm"></i>
                        </div>
                        <h3 class="text-xl font-bold">منصة موريتانيا</h3>
                    </div>
                    <p class="text-gray-400">منصة الخدمات الإلكترونية الرسمية للحكومة الموريتانية لتسهيل الوصول إلى الخدمات الحكومية والمحلية.</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">روابط سريعة</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition">الصفحة الرئيسية</a></li>
                        <li><a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php' : 'auth.php'; ?>" class="text-gray-400 hover:text-white transition">جميع الخدمات</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">الأسئلة الشائعة</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">الدعم الفني</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">عن المنصة</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition">من نحن</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">الشركاء</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">المركز الإعلامي</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">الوظائف</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">اتصل بنا</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-2 text-gray-400"></i>
                            <span class="text-gray-400">+222 1234 5678</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                            <span class="text-gray-400">info@mauritania-services.mr</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                            <span class="text-gray-400">نواكشوط، موريتانيا</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-6 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">© 2024 منصة موريتانيا للخدمات الإلكترونية. جميع الحقوق محفوظة.</p>
                <div class="flex space-x-4 space-x-reverse">
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
            
            // Language dropdown functionality
            const languageSwitcher = document.querySelector('.language-switcher');
            const languageDropdown = document.querySelector('.language-dropdown');
            
            if (languageSwitcher && languageDropdown) {
                languageSwitcher.addEventListener('click', function(e) {
                    e.stopPropagation();
                    languageDropdown.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    if (!languageDropdown.classList.contains('hidden')) {
                        languageDropdown.classList.add('hidden');
                    }
                });
            }

            // Auto-hide notifications
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(function(notification) {
                setTimeout(function() {
                    notification.style.opacity = '0';
                    setTimeout(function() {
                        notification.remove();
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
