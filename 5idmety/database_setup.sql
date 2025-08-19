-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS mauritanie_services CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mauritanie_services;

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
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
);

-- جدول التحقق من الهوية
CREATE TABLE IF NOT EXISTS verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('email_verification', 'login_verification', 'password_reset') NOT NULL,
    code VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول المستخدمين المخولين (للتحقق من الهوية)
CREATE TABLE IF NOT EXISTS authorized_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    national_id VARCHAR(50) UNIQUE NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    authorized_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الخدمات
CREATE TABLE IF NOT EXISTS services (
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
);

-- جدول طلبات الخدمات
CREATE TABLE IF NOT EXISTS service_requests (
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
);

-- جدول المرفقات
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE
);

-- جدول سجل الأنشطة
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول الإشعارات
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- إدراج بيانات تجريبية للخدمات
INSERT INTO services (name_ar, name_fr, description_ar, description_fr, category, icon) VALUES
('خدمات البلدية', 'Services municipaux', 'طلبات البلدية والقرى', 'Demandes municipales et villageoises', 'municipal', 'fas fa-city'),
('خدمات التعليم', 'Services éducatifs', 'خدمات المدارس والمعاهد', 'Services des écoles et instituts', 'education', 'fas fa-graduation-cap'),
('خدمات الصحة', 'Services de santé', 'الخدمات الصحية', 'Services de santé', 'health', 'fas fa-heartbeat'),
('خدمات النقل', 'Services de transport', 'خدمات النقل والمواصلات', 'Services de transport et communication', 'transport', 'fas fa-bus'),
('خدمات اجتماعية', 'Services sociaux', 'الخدمات الاجتماعية', 'Services sociaux', 'social', 'fas fa-users'),
('خدمات قانونية', 'Services juridiques', 'الخدمات القانونية', 'Services juridiques', 'legal', 'fas fa-gavel');

-- إدراج مستخدمين مخولين تجريبيين (في وضع التطوير)
INSERT INTO authorized_users (fullname, national_id, phone, email, authorized_by) VALUES
('أحمد محمد', '12345678', '+22212345678', 'ahmed@example.com', 'system'),
('فاطمة علي', '87654321', '+22287654321', 'fatima@example.com', 'system'),
('محمد عبدالله', '11223344', '+22211223344', 'mohamed@example.com', 'system');

-- إنشاء مستخدم إداري افتراضي
INSERT INTO users (fullname, email, phone, national_id, password, role, account_status, verified_email, verified_identity) VALUES
('مدير النظام', 'admin@mauritania.gov.mr', '+22200000000', '00000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active', 1, 1);
