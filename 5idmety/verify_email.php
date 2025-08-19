<?php
include 'config.php';
session_start();

// التحقق من وجود جلسة مؤقتة
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['registration_step']) || $_SESSION['registration_step'] !== 'email_verification') {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['temp_user_id'];
$user_email = $_SESSION['temp_user_email'];
$error = '';
$success = '';

// عرض الكود في وضع التطوير
$dev_code = '';
if (DEV_MODE && isset($_SESSION['last_otp_code'])) {
    $dev_code = $_SESSION['last_otp_code'];
}

// معالجة التحقق من الرمز
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "رمز الأمان غير صحيح";
    } else {
        $otp_code = sanitize($_POST['otp_code']);
        
        if (empty($otp_code)) {
            $error = "يرجى إدخال رمز التحقق";
        } elseif (strlen($otp_code) !== 6) {
            $error = "رمز التحقق يجب أن يكون 6 أرقام";
        } else {
            // التحقق من الرمز
            if (verifyOTP($db, $user_id, 'email_verification', $otp_code)) {
                // تفعيل البريد الإلكتروني
                $stmt = $db->prepare("UPDATE users SET verified_email = 1 WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // تسجيل النشاط
                logActivity($db, $user_id, 'email_verified', 'تم التحقق من البريد الإلكتروني');
                
                // الحصول على بيانات المستخدم
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                // تسجيل الدخول
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'] ?? 'citizen';
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_verified'] = true;
                
                // إزالة الجلسة المؤقتة
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_user_email']);
                unset($_SESSION['registration_step']);
                unset($_SESSION['last_otp_code']);
                
                // رسالة ترحيب
                $_SESSION['welcome_message'] = "مرحباً بك! تم تأكيد بريدك الإلكتروني بنجاح. حسابك جاهز للاستخدام.";
                
                header("Location: citizen_dashboard.php");
                exit();
            } else {
                $error = "رمز التحقق غير صحيح أو منتهي الصلاحية";
            }
        }
    }
}

// إعادة إرسال الرمز
if (isset($_GET['resend'])) {
    try {
        $otp_code = generateOTP();
        createVerification($db, $user_id, 'email_verification', $otp_code);
        
        $stmt = $db->prepare("SELECT fullname FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $email_message = "مرحباً {$user['fullname']}،\n\nرمز التحقق الجديد هو: $otp_code\n\nهذا الرمز صالح لمدة " . OTP_EXPIRY_MINUTES . " دقائق فقط.\n\nشكراً لك،\nفريق منصة موريتانيا";
        sendEmail($user_email, "رمز ��لتحقق الجديد - منصة موريتانيا", $email_message);
        
        logActivity($db, $user_id, 'otp_resent', 'تم إعادة إرسال رمز التحقق');
        $success = "تم إرسال رمز تحقق جديد إلى بريدك الإلكتروني";
        
        // تحديث الكود في وضع التطوير
        if (DEV_MODE) {
            $dev_code = $otp_code;
        }
    } catch (Exception $e) {
        $error = "حدث خطأ أثناء إعادة الإرسال";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد البريد الإلكتروني - منصة موريتانيا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .otp-input {
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            text-align: center;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-16 w-16 bg-white rounded-full flex items-center justify-center shadow-lg">
                <i class="fas fa-envelope text-green-600 text-2xl"></i>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-white">
                تأكيد البريد الإلكتروني
            </h2>
            <p class="mt-2 text-sm text-gray-200">
                تم إرسال رمز التحقق إلى
            </p>
            <p class="text-yellow-300 font-medium">
                <?= htmlspecialchars($user_email) ?>
            </p>
        </div>
        
        <!-- Development Mode Code Display -->
        <?php if (DEV_MODE && $dev_code): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-code text-yellow-400 ml-2 mt-0.5"></i>
                    <div class="text-sm text-yellow-700">
                        <p class="font-medium mb-1">وضع التطوير - رمز التحقق:</p>
                        <p class="text-2xl font-bold text-center bg-yellow-100 p-2 rounded"><?= $dev_code ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
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
                
                <div>
                    <label for="otp_code" class="block text-sm font-medium text-gray-700 mb-2 text-center">
                        <i class="fas fa-key ml-1"></i>
                        أدخل رمز التحقق المكون من 6 أرقام
                    </label>
                    <input id="otp_code" name="otp_code" type="text" required maxlength="6" 
                           class="otp-input w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500" 
                           placeholder="000000"
                           autocomplete="one-time-code">
                </div>
                
                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200">
                    <i class="fas fa-check ml-2"></i>
                    تأكيد الرمز
                </button>
            </form>
            
            <!-- Resend and Back Links -->
            <div class="mt-6 space-y-3 text-center">
                <div>
                    <span class="text-sm text-gray-600">لم تستلم الرمز؟</span>
                    <a href="verify_email.php?resend=1" class="text-sm font-medium text-green-600 hover:text-green-500 transition">
                        إعادة الإرسال
                    </a>
                </div>
                
                <div>
                    <a href="auth.php" class="text-sm text-gray-600 hover:text-gray-500 transition">
                        <i class="fas fa-arrow-right ml-1"></i>
                        العودة إلى تسجيل الدخول
                    </a>
                </div>
            </div>
            
            <!-- Info Box -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-info-circle text-blue-400 ml-2 mt-0.5"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-medium mb-1">معلومات مهمة:</p>
                        <ul class="text-xs space-y-1">
                            <li>• الرمز صالح لمدة <?= OTP_EXPIRY_MINUTES ?> دقائق فقط</li>
                            <li>• تحقق من مجلد الرسائل غير المرغوب فيها</li>
                            <li>• بعد التحقق ستتمكن من الوصول إلى جميع الخدمات</li>
                            <?php if (DEV_MODE): ?>
                                <li>• في وضع التطوير: الرمز معروض أعلاه</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp_code');
            const form = document.querySelector('form');
            
            // Auto-focus on OTP input
            otpInput.focus();
            
            // Only allow numbers
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Auto-submit when 6 digits entered
                if (this.value.length === 6) {
                    form.submit();
                }
            });
            
            // Prevent paste of non-numeric content
            otpInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const numericPaste = paste.replace(/[^0-9]/g, '').substring(0, 6);
                this.value = numericPaste;
                
                if (numericPaste.length === 6) {
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
