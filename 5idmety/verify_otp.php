<?php
include 'config.php';
session_start();

if (!isset($_SESSION['temp_user_id'])) {
    header("Location: auth.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Token de sécurité invalide";
    } else {
        $otp_code = sanitize($_POST['otp_code']);
        $user_id = $_SESSION['temp_user_id'];
        
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND otp_code = ? AND otp_expires_at > NOW()");
        $stmt->execute([$user_id, $otp_code]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Vérifier le compte
            $stmt = $db->prepare("UPDATE users SET verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
            
            // Connecter l'utilisateur
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_name'] = $user['fullname'];
            unset($_SESSION['temp_user_id']);
            
            // Log de vérification
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user['id'], 'account_verified', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            
            $_SESSION['message'] = "تم تأكيد حسابك بنجاح!";
            redirectByRole();
        } else {
            $error = "رمز التحقق غير صحيح أو منتهي الصلاحية";
        }
    }
}

// Resend OTP
if (isset($_GET['resend'])) {
    $user_id = $_SESSION['temp_user_id'];
    $stmt = $db->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $otp_code = generateOTP();
        $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp_code, $otp_expires, $user_id]);
        
        sendSMS($user['phone'], "رمز التحقق الجديد: $otp_code");
        $success = "تم إرسال رمز تحقق جديد";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد رقم الهاتف - منصة موريتانيا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-16 w-16 bg-white rounded-full flex items-center justify-center">
                <i class="fas fa-mobile-alt text-green-600 text-2xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                تأكيد رقم الهاتف
            </h2>
            <p class="mt-2 text-center text-sm text-gray-200">
                تم إرسال رمز التحقق إلى هاتفك. يرجى إدخاله أدناه
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= $error ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= $success ?></span>
                </div>
            <?php endif; ?>
            
            <div>
                <label for="otp_code" class="sr-only">رمز التحقق</label>
                <input id="otp_code" name="otp_code" type="text" required maxlength="6" 
                       class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 text-center text-2xl tracking-widest focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10" 
                       placeholder="000000">
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-check text-green-500 group-hover:text-green-400"></i>
                    </span>
                    تأكيد الرمز
                </button>
            </div>
            
            <div class="text-center space-y-2">
                <a href="verify_otp.php?resend=1" class="text-yellow-300 hover:text-yellow-200 text-sm">
                    <i class="fas fa-redo mr-1"></i>
                    إعادة إرسال الرمز
                </a>
                <br>
                <a href="auth.php" class="text-yellow-300 hover:text-yellow-200 text-sm">
                    <i class="fas fa-arrow-right mr-1"></i>
                    العودة إلى تسجيل الدخول
                </a>
            </div>
        </form>
    </div>

    <script>
        // Auto-focus and format OTP input
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp_code');
            otpInput.focus();
            
            otpInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Auto-submit when 6 digits entered
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>
