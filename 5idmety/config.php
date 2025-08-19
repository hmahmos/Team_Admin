<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$dbname = 'mauritanie_services';
$username = 'root';
$password = '';

// Configuration de sécurité
define('OTP_EXPIRY_MINUTES', 10);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME_MINUTES', 30);

// Configuration email Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'hmahmeoumar@gmail.com');
define('SMTP_PASSWORD', ''); // IMPORTANT: Vous devez ajouter votre mot de passe d'application Gmail ici
define('FROM_EMAIL', 'hmahmeoumar@gmail.com');
define('FROM_NAME', 'منصة موريتانيا للخدمات');

// Development mode - set to false in production
define('DEV_MODE', true);

// Connexion à la base de données avec gestion d'erreur améliorée
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Language functions
function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'ar';
}

function t($key, $ar, $fr) {
    $lang = getCurrentLanguage();
    return $lang === 'fr' ? $fr : $ar;
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: admin_login.php');
        exit();
    }
}

// Utility functions
function generateTrackingNumber() {
    return 'MR' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function generateOTP() {
    return str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

// Create tables if they don't exist
try {
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        role ENUM('citizen', 'admin') DEFAULT 'citizen',
        is_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Services table
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name_ar VARCHAR(255) NOT NULL,
        name_fr VARCHAR(255) NOT NULL,
        description_ar TEXT,
        description_fr TEXT,
        category VARCHAR(100) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Requests table
    $pdo->exec("CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        tracking_number VARCHAR(50) UNIQUE NOT NULL,
        details TEXT,
        attachment_path VARCHAR(500),
        status ENUM('pending', 'in_progress', 'approved', 'rejected') DEFAULT 'pending',
        admin_comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (service_id) REFERENCES services(id)
    )");

    // OTP table
    $pdo->exec("CREATE TABLE IF NOT EXISTS otp_codes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        code VARCHAR(10) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert default services if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $services = [
            ['استخراج شهادة ميلاد', 'Extrait d\'acte de naissance', 'شهادة ميلاد رسمية', 'Certificat de naissance officiel', 'civil'],
            ['استخراج بطاقة هوية', 'Carte d\'identité nationale', 'بطاقة هوية وطنية', 'Carte d\'identité nationale', 'civil'],
            ['رخصة قيادة', 'Permis de conduire', 'رخصة قيادة المركبات', 'Permis de conduire des véhicules', 'transport'],
            ['شهادة إقامة', 'Certificat de résidence', 'شهادة إقامة رسمية', 'Certificat de résidence officiel', 'civil'],
            ['رخصة تجارية', 'Licence commerciale', 'رخصة لممارسة التجارة', 'Licence pour exercer le commerce', 'business']
        ];

        $stmt = $pdo->prepare("INSERT INTO services (name_ar, name_fr, description_ar, description_fr, category) VALUES (?, ?, ?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute($service);
        }
    }

} catch(PDOException $e) {
    // Tables might already exist, continue
}

// Fonction de sécurité
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier le rôle
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Fonction pour rediriger selon le rôle
function redirectByRole() {
    if (!isLoggedIn()) {
        header("Location: auth.php");
        exit();
    }
    
    switch($_SESSION['role']) {
        case 'citizen':
            header("Location: citizen_dashboard.php");
            break;
        case 'admin_service':
            header("Location: admin_dashboard.php");
            break;
        case 'super_admin':
            header("Location: super_admin.php");
            break;
        default:
            header("Location: auth.php");
    }
    exit();
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Fonction pour envoyer un email avec PHPMailer
function sendEmail($to, $subject, $message) {
    // En mode développement, afficher le code
    if (DEV_MODE) {
        preg_match('/\d{6}/', $message, $matches);
        if (!empty($matches)) {
            $_SESSION['last_otp_code'] = $matches[0];
            $_SESSION['last_otp_time'] = time();
            error_log("CODE OTP POUR $to: " . $matches[0]);
        }
        return true;
    }
    
    // Si PHPMailer n'est pas installé, utiliser la méthode simple
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return sendEmailSimple($to, $subject, $message);
    }
    
    require_once 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Destinataires
        $mail->setFrom(FROM_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        
        // Contenu
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        
        // Log de succès
        error_log("Email envoyé avec succès à: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email: " . $mail->ErrorInfo);
        
        // En cas d'échec, essayer la méthode simple
        return sendEmailSimple($to, $subject, $message);
    }
}

// Fonction d'envoi d'email simple (sans PHPMailer)
function sendEmailSimple($to, $subject, $message) {
    // Headers pour l'email
    $headers = array(
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . phpversion()
    );
    
    // Essayer d'envoyer avec la fonction mail() de PHP
    $success = mail($to, $subject, $message, implode("\r\n", $headers));
    
    if ($success) {
        error_log("Email envoyé avec succès (méthode simple) à: $to");
        return true;
    } else {
        error_log("Échec d'envoi d'email à: $to");
        
        // En mode développement, stocker le code pour l'affichage
        if (DEV_MODE) {
            preg_match('/\d{6}/', $message, $matches);
            if (!empty($matches)) {
                $_SESSION['last_otp_code'] = $matches[0];
                $_SESSION['last_otp_time'] = time();
            }
        }
        
        return false;
    }
}

// Fonction pour envoyer un SMS (simulation)
function sendSMS($phone, $message) {
    error_log("SMS ENVOYÉ À: $phone");
    error_log("MESSAGE: $message");
    return true;
}

// Fonction pour vérifier si un utilisateur est autorisé
function isAuthorizedUser($db, $fullname, $national_id) {
    // En mode développement, autoriser tous les utilisateurs
    if (DEV_MODE) {
        return true;
    }
    
    $stmt = $db->prepare("SELECT id FROM authorized_users WHERE LOWER(TRIM(fullname)) = LOWER(TRIM(?)) AND national_id = ?");
    $stmt->execute([$fullname, $national_id]);
    return $stmt->rowCount() > 0;
}

// Fonction pour créer une vérification OTP
function createVerification($db, $user_id, $type, $code) {
    try {
        $expires_at = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));
        $hashed_code = password_hash($code, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO verifications (user_id, type, code, expires_at, ip_address) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type, $hashed_code, $expires_at, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (PDOException $e) {
        error_log("Error creating verification: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier un code OTP
function verifyOTP($db, $user_id, $type, $code) {
    try {
        $stmt = $db->prepare("
            SELECT * FROM verifications 
            WHERE user_id = ? AND type = ? AND used_at IS NULL AND expires_at > NOW() 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$user_id, $type]);
        $verification = $stmt->fetch();
        
        if ($verification && password_verify($code, $verification['code'])) {
            // Marquer comme utilisé
            $stmt = $db->prepare("UPDATE verifications SET used_at = NOW() WHERE id = ?");
            $stmt->execute([$verification['id']]);
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error verifying OTP: " . $e->getMessage());
        return false;
    }
}

// Fonction pour logger les activités
function logActivity($db, $user_id, $action, $description = null) {
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $user_id, 
            $action, 
            $description, 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier la force du mot de passe
function isStrongPassword($password) {
    return strlen($password) >= 6;
}

// Fonction pour valider le format du téléphone mauritanien
function isValidMauritanianPhone($phone) {
    return preg_match('/^(\+222|222)[0-9]{8}$/', $phone);
}

// Fonction pour valider l'email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>
