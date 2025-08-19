<?php 
include 'config.php';
session_start();

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
            // معالجة التسجيل
            $fullname = sanitize($_POST['fullname']);
            $email = sanitize($_POST['email']);
            $phone = sanitize($_POST['phone']);
            $national_id = sanitize($_POST['national_id']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // التحقق من صحة البيانات
            if (empty($fullname) || empty($email) || empty($phone) || empty($national_id) || empty($password)) {
                $error = "جميع الحقول مطلوبة";
            } elseif (!isValidEmail($email)) {
                $error = "البريد الإلكتروني غير صحيح";
            } elseif (!isValidMauritanianPhone($phone)) {
                $error = "رقم الهاتف غير صحيح (يجب أن يبدأ بـ +222 أو 222)";
            } elseif (strlen($password) < 6) {
                $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
            } elseif ($password !== $confirm_password) {
                $error = "كلمات المرور غير متطابقة";
            } elseif (strlen($national_id) < 8) {
                $error = "الرقم الوطني غير صحيح";
            } else {
                try {
                    // التحقق من أن المستخدم مخول للتسجيل
                    if (!isAuthorizedUser($db, $fullname, $national_id)) {
                        $error = "عذراً، لا يمكنك التسجيل. اسمك أو رقمك الوطني غير مدرج في قائمة المستخدمين المخولين";
                    } else {
                        // التحقق من وجود البريد الإلكتروني أو الرقم الوطني
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR national_id = ?");
                        $stmt->execute([$email, $national_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            $error = "البريد الإلكتروني أو الرقم الوطني مستخدم بالفعل";
                        } else {
                            // إنشاء الحساب
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            $stmt = $db->prepare("
                                INSERT INTO users (fullname, email, phone, national_id, password, account_status, verified_identity) 
                                VALUES (?, ?, ?, ?, ?, 'active', 1)
                            ");
                            
                            if ($stmt->execute([$fullname, $email, $phone, $national_id, $hashed_password])) {
                                $user_id = $db->lastInsertId();
                                
                                // إرسال رمز التحقق للبريد الإلكتروني
                                $otp_code = generateOTP();
                                if (createVerification($db, $user_id, 'email_verification', $otp_code)) {
                                    $email_message = "مرحباً $fullname،\n\nمرحباً بك في منصة موريتانيا للخدمات!\n\nرمز التحقق الخاص بك هو: $otp_code\n\nهذا الرمز صالح لمدة " . OTP_EXPIRY_MINUTES . " دقائق فقط.\n\nبعد التحقق من بريدك الإلكتروني، ستحتاج إلى رمز تحقق في كل مرة تسجل دخولك للحفاظ على أمان حسابك.\n\nشكراً لك،\nفريق منصة موريتانيا";
                                    
                                    if (sendEmail($email, "مرحباً بك - رمز التحقق", $email_message)) {
                                        // تسجيل النشاط
                                        logActivity($db, $user_id, 'account_created', "تم إنشاء حساب جديد للمستخدم المخول: $fullname");
                                        
                                        $_SESSION['temp_user_id'] = $user_id;
                                        $_SESSION['temp_user_email'] = $email;
                                        $_SESSION['registration_step'] = 'email_verification';
                                        
                                        header("Location: verify_email.php");
                                        exit();
                                    } else {
                                        $error = "حدث خطأ أثناء إرسال رمز التحقق";
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
            // معالجة تسجيل الدخول
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
                            $error = "حسابك معلق. يرجى التواصل مع الإدارة";
                        } elseif ($user['account_status'] === 'rejected') {
                            $error = "تم رفض طلب التحقق من هويتك";
                        } else {
                            // إرسال رمز التحقق لكل عملية دخول
                            $otp_code = generateOTP();
                            if (createVerification($db, $user['id'], 'login_verification', $otp_code)) {
                                $email_message = "مرحباً {$user['fullname']}،\n\nرمز التحقق لتسجيل الدخول هو: $otp_code\n\nهذا الرمز صالح لمدة " . OTP_EXPIRY_MINUTES . " دقائق فقط.\n\nإذا لم تكن أنت من يحاول تسجيل الدخول، يرجى تجاهل هذه الرسالة.\n\nشكراً لك،\nفريق منصة موريتانيا";
                                
                                if (sendEmail($user['email'], "رمز التحقق لتسجيل الدخول", $email_message)) {
                                    $_SESSION['temp_user_id'] = $user['id'];
                                    $_SESSION['temp_user_email'] = $user['email'];
                                    $_SESSION['temp_user_data'] = $user;
                                    $_SESSION['login_step'] = 'login_verification';
                                    
                                    header("Location: verify_login.php");
                                    exit();
                                } else {
                                    $error = "حدث خطأ أثناء إرسال رمز التحقق";
                                }
                            } else {
                                $error = "حدث خطأ أثناء إنشاء رمز التحقق";
                            }
                        }
                    } else {
                        $error = "البيانات غير صحيحة";
                        
                        // تسجيل محاولة دخول فاشلة
                        if ($user) {
                            logActivity($db, $user['id'], 'failed_login', 'محاولة دخول فاشلة');
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

// تسجيل الخروج
if ($action === 'logout') {
    if (isLoggedIn()) {
        logActivity($db, $_SESSION['user_id'], 'logout', 'تسجيل خروج من النظام');
    }
    
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'login' ? 'تسجيل الدخول' : 'إنشاء حساب جديد' ?> - منصة موريتانيا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-white rounded-full flex items-center justify-center shadow-lg">
                <i class="fas fa-flag text-green-600 text-2xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-white">
                <?= $action === 'login' ? 'تسجيل الدخول' : 'إنشاء حساب جديد' ?>
            </h2>
            <p class="mt-2 text-sm text-gray-200">
                <?php if ($action === 'login'): ?>
                    ليس لديك حساب؟ 
                    <a href="auth.php?action=register" class="font-medium text-yellow-300 hover:text-yellow-200 transition">
                        أنشئ حساب جديد
                    </a>
                <?php else: ?>
                    لديك حساب بالفعل؟ 
                    <a href="auth.php?action=login" class="font-medium text-yellow-300 hover:text-yellow-200 transition">
                        تسجيل الدخول
                    </a>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Form -->
        <div class="bg-white rounded-lg shadow-xl p-8">
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle ml-2"></i>
                        <span><?= $error ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle ml-2"></i>
                        <span><?= $success ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <?php if ($action === 'register'): ?>
                    <!-- Registration Fields -->
                    <div>
                        <label for="fullname" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user ml-1"></i>
                            الاسم الكامل
                        </label>
                        <input id="fullname" name="fullname" type="text" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="أدخل اسمك الكامل كما هو في الهوية">
                    </div>
                    
                    <div>
                        <label for="national_id" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-id-card ml-1"></i>
                            الرقم الوطني
                        </label>
                        <input id="national_id" name="national_id" type="text" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="أدخل رقمك الوطني">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone ml-1"></i>
                            رقم الهاتف
                        </label>
                        <input id="phone" name="phone" type="tel" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="+222XXXXXXXX">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope ml-1"></i>
                            البريد الإلكتروني
                        </label>
                        <input id="email" name="email" type="email" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="example@email.com">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock ml-1"></i>
                            كلمة المرور
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="أدخل كلمة مرور قوية">
                        <p class="mt-1 text-xs text-gray-500">يجب أن تكون 6 أحرف على الأقل</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock ml-1"></i>
                            تأكيد كلمة المرور
                        </label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="أعد إدخال كلمة المرور">
                    </div>
                    
                    <!-- Security Notice -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-info-circle text-blue-400 ml-2 mt-0.5"></i>
                            <div class="text-sm text-blue-700">
                                <p class="font-medium mb-1">ملاحظة مهمة:</p>
                                <ul class="text-xs space-y-1">
                                    <li>• التسجيل متاح فقط للمستخدمين المخولين</li>
                                    <li>• سيتم التحقق من بريدك الإلكتروني</li>
                                    <li>• ستحتاج رمز تحقق في كل تسجيل دخول</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Login Fields -->
                    <div>
                        <label for="login_field" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user ml-1"></i>
                            البريد الإلكتروني أو رقم الهاتف
                        </label>
                        <input id="login_field" name="login_field" type="text" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="أدخل بريدك الإلكتروني أو رقم هاتفك">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock ml-1"></i>
                            كلمة المرور
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                               placeholder="أدخل كلمة المرور">
                    </div>
                    
                    <!-- Login Info -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex">
                            <i class="fas fa-shield-alt text-yellow-400 ml-2 mt-0.5"></i>
                            <div class="text-sm text-yellow-700">
                                <p class="font-medium mb-1">أمان إضافي:</p>
                                <p class="text-xs">سيتم إرسال رمز تحقق إلى بريدك الإلكتروني بعد إدخال كلمة المرور الصحيحة</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                    <i class="fas fa-<?= $action === 'login' ? 'sign-in-alt' : 'user-plus' ?> ml-2"></i>
                    <?= $action === 'login' ? 'تسجيل الدخول' : 'إنشاء الحساب' ?>
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="index.php" class="text-sm text-gray-600 hover:text-gray-500 transition">
                    <i class="fas fa-arrow-right ml-1"></i>
                    العودة إلى الصفحة الرئيسية
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Password confirmation validation
            if (confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('كلمات المرور غير متطابقة');
                        confirmPassword.classList.add('border-red-500');
                    } else {
                        confirmPassword.setCustomValidity('');
                        confirmPassword.classList.remove('border-red-500');
                    }
                });
            }
            
            // Phone number formatting
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
                });
            }
        });
    </script>
</body>
</html>
