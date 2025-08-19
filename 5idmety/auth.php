<?php 
include 'config.php';

$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'login';

// Redirection si déjà connecté
if (isLoggedIn() && $action !== 'logout') {
    redirectByRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "رمز الأمان غير صحيح";
    } else {
        if ($action === 'register') {
            // معالجة التسجيل - AMÉLIORÉ
            $fullname = sanitize($_POST['fullname']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $national_id = sanitize($_POST['national_id']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // التحقق من صحة البيانات - VALIDATIONS RENFORCÉES
            if (empty($fullname) || empty($email) || empty($phone) || empty($national_id) || empty($password)) {
                $error = "جميع الحقول مطلوبة";
            } elseif (!isValidEmail($email)) {
                $error = "البريد الإلكتروني غير صحيح";
            } elseif (!isValidMauritanianPhone($phone)) {
                $error = "رقم الهاتف غير صحيح (يجب أن يبدأ بـ +222 أو 222)";
            } elseif (!isStrongPassword($password)) {
                $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل وتحتوي على أرقام وحروف";
            } elseif ($password !== $confirm_password) {
                $error = "كلمات المرور غير متطابقة";
            } elseif (strlen($national_id) < 8) {
                $error = "الرقم الوطني غير صحيح (يجب أن يكون 8 أرقام على الأقل)";
            } else {
                try {
                    // التحقق من أن المستخدم مخول للتسجيل
                    if (!isAuthorizedUser($db, $fullname, $national_id)) {
                        $error = "عذراً، لا يمكنك التسجيل. اسمك أو رقمك الوطني غير مدرج في قائمة المستخدمين المخولين. يرجى التواصل مع الإدارة.";
                    } else {
                        // التحقق من وجود البريد الإلكتروني أو الرقم الوطني
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR national_id = ?");
                        $stmt->execute([$email, $national_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            $error = "البريد الإلكتروني أو الرقم الوطني مستخدم بالفعل";
                        } else {
                            // إنشاء الحساب - SÉCURISÉ
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            $stmt = $db->prepare("
                                INSERT INTO users (fullname, email, phone, national_id, password, role, account_status, verified_identity, language_preference) 
                                VALUES (?, ?, ?, ?, ?, 'citizen', 'active', 1, 'ar')
                            ");
                            
                            if ($stmt->execute([$fullname, $email, $phone, $national_id, $hashed_password])) {
                                $user_id = $db->lastInsertId();
                                
                                // إرسال رمز التحقق للبريد الإلكتروني - AMÉLIORÉ
                                $otp_code = generateOTP();
                                if (createVerification($db, $user_id, 'email_verification', $otp_code)) {
                                    $email_message = "مرحباً $fullname،\n\n🎉 مرحباً بك في منصة موريتانيا للخدمات الإلكترونية!\n\nرمز التحقق الخاص بك هو: $otp_code\n\n⏰ هذا الرمز صالح لمدة " . OTP_EXPIRY_MINUTES . " دقائق فقط.\n\n🔒 بعد التحقق من بريدك الإلكتروني، ستحتاج إلى رمز تحقق في كل مرة تسجل دخولك للحفاظ على أمان حسابك.\n\n✅ مزايا حسابك الجديد:\n• الوصول إلى جميع الخدمات الحكومية\n• تتبع طلباتك بسهولة\n• إشعارات فورية بحالة طلباتك\n• حماية متقدمة لبياناتك\n\nإذا لم تقم بإنشاء هذا الحساب، يرجى تجاهل هذه الرسالة.\n\nشكراً لك،\nفريق منصة موريتانيا للخدمات الإلكترونية\n🇲🇷 نخدمكم بكل فخر";
                                    
                                    if (sendEmail($email, "🎉 مرحباً بك في منصة موريتانيا - رمز التحقق", $email_message)) {
                                        // تسجيل النشاط
                                        logActivity($db, $user_id, 'account_created', "تم إنشاء حساب جديد للمستخدم المخول: $fullname - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                                        
                                        $_SESSION['temp_user_id'] = $user_id;
                                        $_SESSION['temp_user_email'] = $email;
                                        $_SESSION['temp_user_name'] = $fullname;
                                        $_SESSION['registration_step'] = 'email_verification';
                                        
                                        header("Location: verify_email.php");
                                        exit();
                                    } else {
                                        $error = "حدث خطأ أثناء إرسال رمز التحقق. يرجى المحاولة لاحقاً أو التواصل مع الدعم الفني.";
                                    }
                                } else {
                                    $error = "حدث خطأ أثناء إنشاء رمز التحقق";
                                }
                            } else {
                                $error = "حدث خطأ أثناء إنشاء الحساب";
                            }
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Database error in registration: " . $e->getMessage());
                    $error = "حدث خطأ في النظام. يرجى المحاولة لاحقاً";
                }
            }
        } elseif ($action === 'login') {
            // معالجة تسجيل الدخول - AMÉLIORÉ
            $login_field = sanitize($_POST['login_field']);
            $password = $_POST['password'];
            
            if (empty($login_field) || empty($password)) {
                $error = "البريد الإلكتروني/الهاتف وكلمة المرور مطلوبان";
            } else {
                try {
                    // البحث بالبريد الإلكتروني أو رقم الهاتف
                    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
                    $stmt->execute([$login_field, $login_field]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // التحقق من حالة الحساب
                        if ($user['account_status'] === 'suspended') {
                            $error = "⚠️ حسابك معلق مؤقتاً. يرجى التواصل مع الإدارة للحصول على المساعدة.";
                        } elseif ($user['account_status'] === 'rejected') {
                            $error = "❌ تم رفض طلب التحقق من هويتك. يرجى التواصل مع الدعم الفني.";
                        } else {
                            // إرسال رمز التحقق لكل عملية دخول - SÉCURITÉ RENFORCÉE
                            $otp_code = generateOTP();
                            if (createVerification($db, $user['id'], 'login_verification', $otp_code)) {
                                $time_now = date('H:i');
                                $date_now = date('Y-m-d');
                                $email_message = "مرحباً {$user['fullname']}،\n\n🔐 رمز التحقق لتسجيل الدخول هو: $otp_code\n\n⏰ هذا الرمز صالح لمدة " . OTP_EXPIRY_MINUTES . " دقائق فقط.\n\n📍 تفاصيل محاولة الدخول:\n• التاريخ: $date_now\n• الوقت: $time_now\n• عنوان IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'غير معروف') . "\n\n🚨 إذا لم تكن أنت من يحاول تسجيل الدخول، يرجى:\n1. تجاهل هذه الرسالة فوراً\n2. تغيير كلمة مرورك\n3. التواصل مع الدعم الفني\n\n✅ إذا كنت أنت من يحاول الدخول، ادخل الرمز أعلاه لإكمال عملية تسجيل الدخول الآمن.\n\nشكراً لك،\nفريق الأمان - منصة موريتانيا\n🇲🇷 أمانكم أولويتنا";
                                
                                if (sendEmail($user['email'], "🔐 رمز التحقق الآمن لتسجيل الدخول - منصة موريتانيا", $email_message)) {
                                    $_SESSION['temp_user_id'] = $user['id'];
                                    $_SESSION['temp_user_email'] = $user['email'];
                                    $_SESSION['temp_user_data'] = $user;
                                    $_SESSION['login_step'] = 'login_verification';
                                    
                                    header("Location: verify_login.php");
                                    exit();
                                } else {
                                    $error = "حدث خطأ أثناء إرسال رمز التحقق. يرجى المحاولة لاحقاً.";
                                }
                            } else {
                                $error = "حدث خطأ أثناء إنشاء رمز التحقق";
                            }
                        }
                    } else {
                        $error = "❌ البيانات غير صحيحة. يرجى التأكد من البريد الإلكتروني/الهاتف وكلمة المرور.";
                        
                        // تسجيل محاولة دخول فاشلة - SÉCURITÉ
                        if ($user) {
                            logActivity($db, $user['id'], 'failed_login', 'محاولة دخول فاشلة - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                        } else {
                            error_log("Failed login attempt for: $login_field - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Database error in login: " . $e->getMessage());
                    $error = "حدث خطأ في النظام. يرجى المحاولة لاحقاً";
                }
            }
        }
    }
}

// تسجيل الخروج - AMÉLIORÉ
if ($action === 'logout') {
    if (isLoggedIn()) {
        logActivity($db, $_SESSION['user_id'], 'logout', 'تسجيل خروج آمن من النظام - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    session_destroy();
    $_SESSION = array(); // Clear all session data
    
    // Redirect with success message
    header("Location: index.php?logged_out=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'login' ? 'تسجيل الدخول الآمن' : 'إنشاء حساب جديد' ?> - منصة موريتانيا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #0c4a6e 0%, #0f766e 50%, #166534 100%);
            min-height: 100vh;
        }
        
        .form-container {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15, 118, 110, 0.2);
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .floating-shapes::before,
        .floating-shapes::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 8s ease-in-out infinite;
        }
        
        .floating-shapes::before {
            width: 200px;
            height: 200px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .floating-shapes::after {
            width: 150px;
            height: 150px;
            bottom: 10%;
            right: 10%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0f766e 0%, #166534 100%);
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.4);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(15, 118, 110, 0.6);
        }
        
        .alert-enter {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
    
    <!-- Floating background shapes -->
    <div class="floating-shapes"></div>
    
    <div class="max-w-md w-full space-y-8 relative z-10">
        <!-- Header amélioré -->
        <div class="text-center animate__animated animate__fadeInDown">
            <div class="mx-auto h-20 w-20 bg-gradient-to-br from-white to-gray-100 rounded-full flex items-center justify-center shadow-2xl mb-6">
                <i class="fas fa-shield-alt text-teal-600 text-3xl"></i>
            </div>
            <h2 class="text-4xl font-bold text-white mb-2">
                <?= $action === 'login' ? '🔐 دخول آمن' : '🚀 انضم إلينا' ?>
            </h2>
            <p class="text-lg text-gray-200 mb-4">
                <?= $action === 'login' ? 'أدخل بياناتك للوصول إلى حسابك الآمن' : 'أنشئ حسابك الجديد في منصة موريتانيا' ?>
            </p>
            <p class="text-sm text-gray-300">
                <?php if ($action === 'login'): ?>
                    ليس لديك حساب؟ 
                    <a href="auth.php?action=register" class="font-semibold text-yellow-300 hover:text-yellow-200 transition duration-200">
                        🎯 أنشئ حساب جديد
                    </a>
                <?php else: ?>
                    لديك حساب بالفعل؟ 
                    <a href="auth.php?action=login" class="font-semibold text-yellow-300 hover:text-yellow-200 transition duration-200">
                        🏠 تسجيل الدخول
                    </a>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Form Container -->
        <div class="form-container rounded-2xl shadow-2xl p-8 animate__animated animate__fadeInUp">
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg alert-enter" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-xl mr-3"></i>
                        <span class="font-medium"><?= $error ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 px-6 py-4 rounded-lg alert-enter" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-xl mr-3"></i>
                        <span class="font-medium"><?= $success ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Main Form -->
            <form method="POST" class="space-y-6" id="authForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <?php if ($action === 'register'): ?>
                    <!-- Registration Fields Enhanced -->
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="fullname" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user text-teal-600 mr-2"></i>
                                الاسم الكامل *
                            </label>
                            <input id="fullname" name="fullname" type="text" required 
                                   class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500" 
                                   placeholder="أدخل اسمك الكامل كما هو في الهوية الوطنية">
                        </div>
                        
                        <div>
                            <label for="national_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-id-card text-blue-600 mr-2"></i>
                                الرقم الوطني *
                            </label>
                            <input id="national_id" name="national_id" type="text" required 
                                   class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="أدخل رقمك الوطني (8 أرقام على الأقل)">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-phone text-green-600 mr-2"></i>
                                رقم الهاتف *
                            </label>
                            <input id="phone" name="phone" type="tel" required 
                                   class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                                   placeholder="+222XXXXXXXX">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-envelope text-purple-600 mr-2"></i>
                                البريد الإلكتروني *
                            </label>
                            <input id="email" name="email" type="email" required 
                                   class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500" 
                                   placeholder="example@gmail.com">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-lock text-red-600 mr-2"></i>
                                كلمة المرور *
                            </label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required 
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 pl-12" 
                                       placeholder="أدخل كلمة مرور قوية">
                                <button type="button" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-toggle"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2 bg-gray-200" id="password-strength"></div>
                            <p class="mt-2 text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                يجب أن تحتوي على 6 أحرف على الأقل، وتشمل أرقام وحروف
                            </p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-check-double text-orange-600 mr-2"></i>
                                تأكيد كلمة المرور *
                            </label>
                            <div class="relative">
                                <input id="confirm_password" name="confirm_password" type="password" required 
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 pl-12" 
                                       placeholder="أعد إدخال كلمة المرور">
                                <button type="button" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password-toggle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Security Notice -->
                    <div class="bg-gradient-to-r from-blue-50 to-teal-50 border border-blue-200 rounded-xl p-6">
                        <div class="flex">
                            <i class="fas fa-shield-alt text-blue-500 text-2xl mr-4 mt-1"></i>
                            <div class="text-sm text-blue-800">
                                <h4 class="font-bold mb-3 text-lg">🔒 أمان وحماية متقدمة</h4>
                                <ul class="space-y-2 text-sm">
                                    <li class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        التسجيل متاح فقط للمواطنين المخولين
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        تشفير متقدم لحماية بياناتك الشخصية
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        تحقق ثنائي العامل عبر البريد الإلكتروني
                                    </li>
                                    <li class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        مراقبة مستمرة لضمان أمان الحساب
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Login Fields Enhanced -->
                    <div class="space-y-6">
                        <div>
                            <label for="login_field" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-user-circle text-teal-600 mr-2"></i>
                                البريد الإلكتروني أو رقم الهاتف *
                            </label>
                            <input id="login_field" name="login_field" type="text" required 
                                   class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500" 
                                   placeholder="أدخل بريدك الإلكتروني أو رقم هاتفك">
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-key text-red-600 mr-2"></i>
                                كلمة المرور *
                            </label>
                            <div class="relative">
                                <input id="password" name="password" type="password" required 
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 pl-12" 
                                       placeholder="أدخل كلمة المرور">
                                <button type="button" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="password-toggle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enhanced Login Info -->
                    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6">
                        <div class="flex">
                            <i class="fas fa-shield-alt text-yellow-500 text-2xl mr-4 mt-1"></i>
                            <div class="text-sm text-yellow-800">
                                <h4 class="font-bold mb-2 text-lg">🛡️ دخول آمن بخطوتين</h4>
                                <p class="text-sm leading-relaxed">
                                    بعد إدخال كلمة المرور الصحيحة، سنرسل لك رمز تحقق آمن إلى بريدك الإلكتروني. 
                                    هذا يضمن أن حسابك محمي حتى لو علم أحد كلمة مرورك.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Submit Button Enhanced -->
                <button type="submit" 
                        class="btn-primary w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition duration-200"
                        id="submitBtn">
                    <i class="fas fa-<?= $action === 'login' ? 'sign-in-alt' : 'user-plus' ?> mr-3"></i>
                    <span id="submitText"><?= $action === 'login' ? '🚀 دخول آمن الآن' : '✨ إنشاء الحساب' ?></span>
                    <i class="fas fa-spinner fa-spin mr-3 hidden" id="submitSpinner"></i>
                </button>
            </form>
            
            <!-- Back to Home Link -->
            <div class="mt-8 text-center">
                <a href="index.php" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800 transition duration-200">
                    <i class="fas fa-home mr-2"></i>
                    العودة إلى الصفحة الرئيسية
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('authForm');
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Enhanced form submission
            form.addEventListener('submit', function(e) {
                submitBtn.disabled = true;
                submitText.textContent = 'جارٍ المعالجة...';
                submitSpinner.classList.remove('hidden');
                
                // Re-enable after 5 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitText.textContent = '<?= $action === 'login' ? '🚀 دخول آمن الآن' : '✨ إنشاء الحساب' ?>';
                    submitSpinner.classList.add('hidden');
                }, 5000);
            });
            
            // Enhanced password confirmation validation
            if (confirmPassword) {
                function validatePasswords() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('كلمات المرور غير متطابقة');
                        confirmPassword.classList.add('border-red-500');
                        confirmPassword.classList.remove('border-green-500');
                    } else {
                        confirmPassword.setCustomValidity('');
                        confirmPassword.classList.remove('border-red-500');
                        confirmPassword.classList.add('border-green-500');
                    }
                }
                
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
            
            // Enhanced phone number formatting
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    let value = this.value.replace(/\D/g, '');
                    if (value.startsWith('222')) {
                        this.value = '+' + value;
                    } else if (!value.startsWith('+222')) {
                        this.value = '+222' + value;
                    }
                });
            }
            
            // National ID validation
            const nationalIdInput = document.getElementById('national_id');
            if (nationalIdInput) {
                nationalIdInput.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                    if (this.value.length >= 8) {
                        this.classList.add('border-green-500');
                        this.classList.remove('border-red-500');
                    } else {
                        this.classList.add('border-red-500');
                        this.classList.remove('border-green-500');
                    }
                });
            }
            
            // Enhanced password strength indicator
            if (password && document.getElementById('password-strength')) {
                password.addEventListener('input', function() {
                    const strength = checkPasswordStrength(this.value);
                    updatePasswordStrength(strength);
                });
            }
            
            // Auto-hide alerts after 10 seconds
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(100%)';
                    setTimeout(() => alert.remove(), 300);
                }, 10000);
            });
        });
        
        // Password visibility toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(inputId + '-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            if (password.length >= 6) score++;
            if (password.match(/[a-zA-Z]/)) score++;
            if (password.match(/[0-9]/)) score++;
            if (password.match(/[^a-zA-Z0-9]/)) score++;
            return score;
        }
        
        // Update password strength indicator
        function updatePasswordStrength(score) {
            const indicator = document.getElementById('password-strength');
            const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'];
            const widths = ['25%', '50%', '75%', '100%'];
            
            indicator.className = `password-strength mt-2 ${colors[score - 1] || 'bg-gray-200'}`;
            indicator.style.width = widths[score - 1] || '0%';
        }
    </script>
</body>
</html>