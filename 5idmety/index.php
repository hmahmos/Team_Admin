<?php 
include 'config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>منصة موريتانيا للخدمات الإلكترونية</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #0c4a6e 0%, #0f766e 50%, #166534 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse"><path d="M 50 0 L 0 0 0 50" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        
        .service-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border-radius: 16px;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        }
        
        .service-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .service-icon {
            transition: all 0.3s ease;
        }
        
        .service-card:hover .service-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .gradient-text {
            background: linear-gradient(45deg, #0c4a6e, #0f766e, #166534);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stats-counter {
            font-variant-numeric: tabular-nums;
        }
        
        .feature-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .nav-mobile {
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        .nav-mobile.active {
            transform: translateY(0);
        }

        /* Amélioration du style des boutons */
        .btn-primary {
            background: linear-gradient(135deg, #0f766e 0%, #166534 100%);
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.4);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15, 118, 110, 0.6);
        }
        
        .btn-secondary {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header amélioré -->
    <header class="bg-white shadow-lg sticky top-0 z-50 backdrop-filter backdrop-blur-lg bg-opacity-95">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <!-- Logo amélioré -->
                <div class="flex items-center">
                    <div class="h-12 w-12 bg-gradient-to-br from-teal-600 to-green-600 rounded-full flex items-center justify-center mr-3 shadow-lg">
                        <i class="fas fa-flag text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold gradient-text">منصة موريتانيا</h1>
                        <p class="text-xs text-gray-500">للخدمات الإلكترونية</p>
                    </div>
                </div>
                
                <!-- Navigation Desktop -->
                <nav class="hidden md:flex items-center space-x-6 space-x-reverse">
                    <!-- Language Switcher amélioré -->
                    <div class="relative group">
                        <button class="flex items-center hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                            <i class="fas fa-globe mr-2 text-teal-600"></i>
                            <span class="text-sm font-medium">العربية</span>
                            <i class="fas fa-chevron-down text-xs mr-1 transition-transform group-hover:rotate-180"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-32 bg-white rounded-lg shadow-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100 transition">
                                <i class="fas fa-flag mr-2"></i>Français
                            </a>
                            <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100 transition">
                                <i class="fas fa-flag mr-2"></i>English
                            </a>
                        </div>
                    </div>
                    
                    <!-- Auth Buttons améliorés -->
                    <div class="flex space-x-3 space-x-reverse">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo $_SESSION['role'] === 'citizen' ? 'citizen_dashboard.php' : 'admin_dashboard.php'; ?>" 
                               class="btn-primary px-6 py-2 text-white rounded-lg text-sm font-medium flex items-center">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                لوحة التحكم
                            </a>
                            <a href="logout.php" class="px-4 py-2 text-gray-600 hover:text-gray-800 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-sign-out-alt mr-1"></i>
                                خروج
                            </a>
                        <?php else: ?>
                            <a href="auth.php?action=login" class="px-4 py-2 text-gray-600 hover:text-gray-800 rounded-lg text-sm font-medium transition">
                                تسجيل الدخول
                            </a>
                            <a href="auth.php?action=register" class="btn-primary px-6 py-2 text-white rounded-lg text-sm font-medium">
                                إنشاء حساب
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
                
                <!-- Mobile menu button -->
                <button class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition" id="mobile-menu-btn">
                    <i class="fas fa-bars text-xl text-gray-600"></i>
                </button>
            </div>

            <!-- Mobile Navigation -->
            <div class="nav-mobile md:hidden mt-4 bg-white rounded-lg shadow-lg p-4" id="mobile-menu">
                <div class="space-y-3">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo $_SESSION['role'] === 'citizen' ? 'citizen_dashboard.php' : 'admin_dashboard.php'; ?>" 
                           class="block px-4 py-3 bg-teal-600 text-white rounded-lg text-center font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>لوحة التحكم
                        </a>
                        <a href="logout.php" class="block px-4 py-3 text-center text-gray-600 hover:bg-gray-100 rounded-lg transition">
                            تسجيل الخروج
                        </a>
                    <?php else: ?>
                        <a href="auth.php?action=login" class="block px-4 py-3 text-center text-gray-600 hover:bg-gray-100 rounded-lg transition">
                            تسجيل الدخول
                        </a>
                        <a href="auth.php?action=register" class="block px-4 py-3 bg-teal-600 text-white rounded-lg text-center font-medium">
                            إنشاء حساب
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Notifications améliorées -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="notification show bg-green-100 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg shadow-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-xl mr-3"></i>
                <span class="font-medium"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="notification show bg-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg shadow-lg" role="alert">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-xl mr-3"></i>
                <span class="font-medium"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section amélioré -->
    <section class="hero-section text-white py-20 md:py-32 relative">
        <div class="container mx-auto px-4 text-center relative z-10">
            <div class="animate__animated animate__fadeInUp">
                <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                    منصة موريتانيا
                    <span class="block text-teal-300">للخدمات الإلكترونية</span>
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto leading-relaxed opacity-90">
                    حلول رقمية متطورة وآمنة لتسهيل الوصول إلى جميع الخدمات الحكومية والمحلية 
                    <span class="text-teal-300 font-semibold">بكفاءة وسرعة</span>
                </p>
                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6 sm:space-x-reverse">
                    <?php if (isLoggedIn()): ?>
                        <a href="citizen_dashboard.php" class="btn-primary px-8 py-4 rounded-xl font-bold text-lg inline-flex items-center justify-center">
                            <i class="fas fa-rocket mr-3"></i>
                            تصفح الخدمات
                        </a>
                    <?php else: ?>
                        <a href="auth.php?action=register" class="btn-primary px-8 py-4 rounded-xl font-bold text-lg inline-flex items-center justify-center">
                            <i class="fas fa-user-plus mr-3"></i>
                            ابدأ الآن مجاناً
                        </a>
                    <?php endif; ?>
                    <a href="#services" class="btn-secondary px-8 py-4 rounded-xl font-bold text-lg inline-flex items-center justify-center">
                        <i class="fas fa-info-circle mr-3"></i>
                        تعرف على الخدمات
                    </a>
                </div>
            </div>
            
            <!-- Floating elements -->
            <div class="absolute top-10 left-10 floating-animation">
                <div class="w-20 h-20 bg-white bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
            </div>
            <div class="absolute bottom-20 right-10 floating-animation" style="animation-delay: 2s;">
                <div class="w-16 h-16 bg-white bg-opacity-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-xl"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section amélioré -->
    <section id="services" class="py-20 bg-gradient-to-br from-gray-50 to-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold gradient-text mb-6">خدماتنا الإلكترونية المتطورة</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    نوفر لك مجموعة شاملة ومتنوعة من الخدمات الإلكترونية المتطورة 
                    لتلبية جميع احتياجاتك الحكومية والمحلية بسهولة تامة
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <!-- Service Cards améliorées -->
                <div class="service-card p-8 shadow-lg hover:shadow-2xl">
                    <div class="service-icon w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-city text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-center text-gray-800 mb-4">خدمات البلديات والقرى</h3>
                    <p class="text-gray-600 text-center mb-6 leading-relaxed">
                        رخص البناء، شهادات السكن، والخدمات البلدية الأساسية
                    </p>
                    <div class="text-center">
                        <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=municipal' : 'auth.php?action=login'; ?>" 
                           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
                
                <div class="service-card p-8 shadow-lg hover:shadow-2xl">
                    <div class="service-icon w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-hands-helping text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-center text-gray-800 mb-4">خدمات الجمعيات والمنظمات</h3>
                    <p class="text-gray-600 text-center mb-6 leading-relaxed">
                        تسجيل الجمعيات، طلبات الدعم، والأنشطة التطوعية
                    </p>
                    <div class="text-center">
                        <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=social' : 'auth.php?action=login'; ?>" 
                           class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
                
                <div class="service-card p-8 shadow-lg hover:shadow-2xl">
                    <div class="service-icon w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-graduation-cap text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-center text-gray-800 mb-4">خدمات التعليم والمعاهد</h3>
                    <p class="text-gray-600 text-center mb-6 leading-relaxed">
                        التسجيل المدرسي، الشهادات، والدروس الخصوصية
                    </p>
                    <div class="text-center">
                        <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=education' : 'auth.php?action=login'; ?>" 
                           class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
                
                <div class="service-card p-8 shadow-lg hover:shadow-2xl">
                    <div class="service-icon w-20 h-20 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-bus text-white text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-center text-gray-800 mb-4">خدمات النقل والمواصلات</h3>
                    <p class="text-gray-600 text-center mb-6 leading-relaxed">
                        رخص القيادة، تراخيص النقل، وبطاقات السير
                    </p>
                    <div class="text-center">
                        <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php?service=transport' : 'auth.php?action=login'; ?>" 
                           class="inline-flex items-center px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                            <i class="fas fa-arrow-left mr-2"></i>
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-16">
                <a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php' : 'auth.php?action=register'; ?>" 
                   class="btn-primary px-10 py-4 rounded-xl text-lg font-bold inline-flex items-center">
                    <i class="fas fa-th-large mr-3"></i>
                    <?php echo isLoggedIn() ? 'عرض جميع الخدمات' : 'ابدأ استخدام الخدمات الآن'; ?>
                    <i class="fas fa-arrow-left mr-3"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section amélioré -->
    <section class="py-20 bg-gradient-to-br from-teal-600 to-green-600 text-white relative overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-6">لماذا تختار منصتنا؟</h2>
                <p class="text-xl opacity-90 max-w-2xl mx-auto">نقدم تجربة فريدة ومتميزة في الخدمات الإلكترونية</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="feature-card text-center p-8 rounded-2xl">
                    <div class="w-24 h-24 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-rocket text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">سرعة فائقة</h3>
                    <p class="text-gray-600 leading-relaxed">إنجاز المعاملات في دقائق معدودة بدلاً من ساعات الانتظار الطويلة</p>
                </div>
                
                <div class="feature-card text-center p-8 rounded-2xl">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-shield-alt text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">أمان متقدم</h3>
                    <p class="text-gray-600 leading-relaxed">تشفير عالي المستوى وحماية شاملة لبياناتك ومعاملاتك الشخصية</p>
                </div>
                
                <div class="feature-card text-center p-8 rounded-2xl">
                    <div class="w-24 h-24 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                        <i class="fas fa-headset text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4 text-gray-800">دعم 24/7</h3>
                    <p class="text-gray-600 leading-relaxed">فريق دعم متخصص ومتاح على مدار الساعة لمساعدتك في أي وقت</p>
                </div>
            </div>
        </div>
        
        <!-- Background decorations -->
        <div class="absolute top-10 left-10 w-32 h-32 bg-white bg-opacity-5 rounded-full"></div>
        <div class="absolute bottom-10 right-10 w-40 h-40 bg-white bg-opacity-5 rounded-full"></div>
    </section>

    <!-- Statistics Section amélioré -->
    <section class="py-20 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="animate__animated animate__fadeInUp">
                    <div class="stats-counter text-5xl font-bold mb-3 text-teal-400">25,000+</div>
                    <div class="text-gray-300 text-lg">مستخدم نشط</div>
                </div>
                <div class="animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                    <div class="stats-counter text-5xl font-bold mb-3 text-green-400">150,000+</div>
                    <div class="text-gray-300 text-lg">طلب مكتمل</div>
                </div>
                <div class="animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <div class="stats-counter text-5xl font-bold mb-3 text-blue-400">50+</div>
                    <div class="text-gray-300 text-lg">خدمة متاحة</div>
                </div>
                <div class="animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                    <div class="stats-counter text-5xl font-bold mb-3 text-yellow-400">99.8%</div>
                    <div class="text-gray-300 text-lg">معدل الرضا</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer amélioré -->
    <footer class="bg-gray-800 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-6">
                        <div class="h-10 w-10 bg-gradient-to-br from-teal-600 to-green-600 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-flag text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">منصة موريتانيا</h3>
                            <p class="text-sm text-gray-400">للخدمات الإلكترونية</p>
                        </div>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        المنصة الرسمية للحكومة الموريتانية لتقديم الخدمات الإلكترونية المتطورة والآمنة لجميع المواطنين.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-6">روابط سريعة</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-home text-xs mr-2"></i>الصفحة الرئيسية
                        </a></li>
                        <li><a href="<?php echo isLoggedIn() ? 'citizen_dashboard.php' : 'auth.php'; ?>" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-list text-xs mr-2"></i>جميع الخدمات
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-question-circle text-xs mr-2"></i>الأسئلة الشائعة
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-headset text-xs mr-2"></i>الدعم الفني
                        </a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-6">عن المنصة</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-info-circle text-xs mr-2"></i>من نحن
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-handshake text-xs mr-2"></i>الشركاء
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-newspaper text-xs mr-2"></i>المركز الإعلامي
                        </a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition flex items-center">
                            <i class="fas fa-briefcase text-xs mr-2"></i>الوظائف
                        </a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-6">اتصل بنا</h4>
                    <ul class="space-y-4">
                        <li class="flex items-center">
                            <div class="w-8 h-8 bg-teal-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-phone-alt text-white text-xs"></i>
                            </div>
                            <span class="text-gray-400">+222 1234 5678</span>
                        </li>
                        <li class="flex items-center">
                            <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-envelope text-white text-xs"></i>
                            </div>
                            <span class="text-gray-400">info@mauritania-services.mr</span>
                        </li>
                        <li class="flex items-center">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-map-marker-alt text-white text-xs"></i>
                            </div>
                            <span class="text-gray-400">نواكشوط، موريتانيا</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">
                    © 2024 منصة موريتانيا للخدمات الإلكترونية. جميع الحقوق محفوظة.
                </p>
                <div class="flex space-x-4 space-x-reverse">
                    <a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition">
                        <i class="fab fa-facebook-f text-white"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-sky-500 rounded-full flex items-center justify-center hover:bg-sky-600 transition">
                        <i class="fab fa-twitter text-white"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-blue-700 rounded-full flex items-center justify-center hover:bg-blue-800 transition">
                        <i class="fab fa-linkedin-in text-white"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-pink-500 rounded-full flex items-center justify-center hover:bg-pink-600 transition">
                        <i class="fab fa-instagram text-white"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Enhanced mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('active');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });
            
            // Enhanced notification auto-hide
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(function(notification) {
                setTimeout(function() {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(function() {
                        notification.remove();
                    }, 300);
                }, 5000);
            });
            
            // Smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
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
            
            // Add loading states to buttons
            document.querySelectorAll('a[href*="auth.php"], a[href*="dashboard.php"]').forEach(link => {
                link.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>جارٍ التحميل...';
                    this.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 3000);
                });
            });
        });
    </script>
</body>
</html>