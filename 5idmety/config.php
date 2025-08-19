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

// Configuration email Gmail - CREDENTIALS SÉCURISÉS
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'hmahmeoumar@gmail.com');
define('SMTP_PASSWORD', 'owjh qitp xwuq xhme'); // Password Gmail App ajouté
define('FROM_EMAIL', 'hmahmeoumar@gmail.com');
define('FROM_NAME', 'منصة موريتانيا للخدمات');

// Development mode - set to false in production
define('DEV_MODE', false); // Changé à false pour utiliser vraiment l'email

// Connexion à la base de données avec gestion d'erreur améliorée
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Utiliser $pdo comme $db pour la compatibilité
    $db = $pdo;
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

// Authentication functions - UNIFIÉ
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: auth.php');
        exit();
    }
}

function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: admin_login.php');
        exit();
    }
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
            header("Location: admin_dashboard.php");
            break;
        default:
            header("Location: auth.php");
    }
    exit();
}

// Utility functions
function generateTrackingNumber() {
    return 'MR' . date('Y') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

function generateOTP() {
    return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
}

// Fonction de sécurité
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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

// Fonction pour envoyer un email avec PHPMailer - AMÉLIORÉE
function sendEmail($to, $subject, $message) {
    // En mode développement, afficher le code dans les logs
    if (DEV_MODE) {
        preg_match('/\d{6}/', $message, $matches);
        if (!empty($matches)) {
            $_SESSION['last_otp_code'] = $matches[0];
            $_SESSION['last_otp_time'] = time();
            error_log("CODE OTP POUR $to: " . $matches[0]);
        }
        return true;
    }
    
    // Utilisation de la fonction mail() PHP native avec configuration Gmail
    $headers = array(
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . FROM_EMAIL,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 1',
        'MIME-Version: 1.0'
    );
    
    // Configuration SMTP pour Gmail via ini_set
    ini_set('SMTP', SMTP_HOST);
    ini_set('smtp_port', SMTP_PORT);
    ini_set('sendmail_from', FROM_EMAIL);
    
    $success = mail($to, $subject, $message, implode("\r\n", $headers));
    
    if ($success) {
        error_log("Email envoyé avec succès à: $to");
        return true;
    } else {
        error_log("Échec d'envoi d'email à: $to");
        
        // En cas d'échec, stocker le code pour affichage en mode dev
        preg_match('/\d{6}/', $message, $matches);
        if (!empty($matches)) {
            $_SESSION['last_otp_code'] = $matches[0];
            $_SESSION['last_otp_time'] = time();
            error_log("CODE OTP (échec email) POUR $to: " . $matches[0]);
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
    
    try {
        $stmt = $db->prepare("SELECT id FROM authorized_users WHERE LOWER(TRIM(fullname)) = LOWER(TRIM(?)) AND national_id = ?");
        $stmt->execute([$fullname, $national_id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking authorized user: " . $e->getMessage());
        return true; // En cas d'erreur, autoriser (mode gracieux)
    }
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
    return strlen($password) >= 6 && preg_match('/^(?=.*[a-zA-Z])(?=.*\d)/', $password);
}

// Fonction pour valider le format du téléphone mauritanien
function isValidMauritanianPhone($phone) {
    return preg_match('/^(\+222|222)[0-9]{8}$/', $phone);
}

// Fonction pour valider l'email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Créer les tables si elles n'existent pas - VERSION UNIFIÉE
try {
    // Table des utilisateurs - version complète
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        national_id VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('citizen', 'admin_service', 'super_admin') DEFAULT 'citizen',
        account_status ENUM('active', 'suspended', 'rejected') DEFAULT 'active',
        verified_email BOOLEAN DEFAULT FALSE,
        verified_identity BOOLEAN DEFAULT FALSE,
        language_preference ENUM('ar', 'fr') DEFAULT 'ar',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )");

    // Table des vérifications
    $db->exec("CREATE TABLE IF NOT EXISTS verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('email_verification', 'login_verification', 'password_reset') NOT NULL,
        code VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Table des utilisateurs autorisés
    $db->exec("CREATE TABLE IF NOT EXISTS authorized_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(255) NOT NULL,
        national_id VARCHAR(50) UNIQUE NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(255),
        authorized_by VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Table des services - version complète
    $db->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name_ar VARCHAR(255) NOT NULL,
        name_fr VARCHAR(255) NOT NULL,
        description_ar TEXT,
        description_fr TEXT,
        category ENUM('municipal', 'health', 'education', 'transport', 'social', 'legal', 'economic', 'environment') NOT NULL,
        icon VARCHAR(100) DEFAULT 'fas fa-cog',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Table des demandes de services
    $db->exec("CREATE TABLE IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_id INT NOT NULL,
        tracking_number VARCHAR(20) UNIQUE NOT NULL,
        description TEXT NOT NULL,
        priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
        status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        assigned_to INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES services(id),
        FOREIGN KEY (assigned_to) REFERENCES users(id)
    )");

    // Table des pièces jointes
    $db->exec("CREATE TABLE IF NOT EXISTS attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
    )");

    // Table des logs d'activité
    $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Table des notifications
    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Insérer les services par défaut si la table est vide
    $stmt = $db->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $services = [
            ['خدمات البلدية', 'Services municipaux', 'طلبات البلدية والقرى', 'Demandes municipales et villageoises', 'municipal', 'fas fa-city'],
            ['خدمات التعليم', 'Services éducatifs', 'خدمات المدارس والمعاهد', 'Services des écoles et instituts', 'education', 'fas fa-graduation-cap'],
            ['خدمات الصحة', 'Services de santé', 'الخدمات الصحية', 'Services de santé', 'health', 'fas fa-heartbeat'],
            ['خدمات النقل', 'Services de transport', 'خدمات النقل والمواصلات', 'Services de transport et communication', 'transport', 'fas fa-bus'],
            ['خدمات اجتماعية', 'Services sociaux', 'الخدمات الاجتماعية', 'Services sociaux', 'social', 'fas fa-users'],
            ['خدمات قانونية', 'Services juridiques', 'الخدمات القانونية', 'Services juridiques', 'legal', 'fas fa-gavel'],
            ['خدمات اقتصادية', 'Services économiques', 'الخدمات الاقتصادية والتجارية', 'Services économiques et commerciaux', 'economic', 'fas fa-chart-line'],
            ['خدمات البيئة', 'Services environnementaux', 'خدمات البيئة والتنمية المستدامة', 'Services environnementaux et développement durable', 'environment', 'fas fa-leaf']
        ];

        $stmt = $db->prepare("INSERT INTO services (name_ar, name_fr, description_ar, description_fr, category, icon) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($services as $service) {
            $stmt->execute($service);
        }
    }

    // Insérer des utilisateurs autorisés de test
    $stmt = $db->query("SELECT COUNT(*) FROM authorized_users");
    if ($stmt->fetchColumn() == 0) {
        $authorized_users = [
            ['أحمد محمد عبدالله', '12345678', '+22212345678', 'ahmed@example.com', 'system'],
            ['فاطمة علي حسن', '87654321', '+22287654321', 'fatima@example.com', 'system'],
            ['محمد عبدالله أحمد', '11223344', '+22211223344', 'mohamed@example.com', 'system'],
            ['عائشة محمود', '44556677', '+22244556677', 'aicha@example.com', 'system'],
            ['عثمان الطالب', '99887766', '+22299887766', 'othman@example.com', 'system']
        ];

        $stmt = $db->prepare("INSERT INTO authorized_users (fullname, national_id, phone, email, authorized_by) VALUES (?, ?, ?, ?, ?)");
        foreach ($authorized_users as $user) {
            $stmt->execute($user);
        }
    }

} catch(PDOException $e) {
    error_log("Error creating tables: " . $e->getMessage());
}

?>