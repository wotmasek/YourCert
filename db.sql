-- ==============================================
-- 1) SCHEMA
-- ==============================================
SET time_zone = '+00:00';

DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS account_delete_requests;
DROP TABLE IF EXISTS email_change_requests;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS action_logs;
DROP TABLE IF EXISTS user_certificates;
DROP TABLE IF EXISTS certificates;      
DROP TABLE IF EXISTS courses;           
DROP TABLE IF EXISTS institutions_seo;  
DROP TABLE IF EXISTS institutions;
DROP TABLE IF EXISTS cms_pages;
DROP TABLE IF EXISTS cms_post_images;
DROP TABLE IF EXISTS cms_posts;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS permission_settings;

-- Permissions settings
CREATE TABLE IF NOT EXISTS permission_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin                          TINYINT(1) DEFAULT 0,
    otherUsersDataManagement       TINYINT(1) DEFAULT 0,
    otherUsersInformations         TINYINT(1) DEFAULT 0,
    getUsers                       TINYINT(1) DEFAULT 0,
    requestEmailChange             TINYINT(1) DEFAULT 0,
    confirmEmailChange             TINYINT(1) DEFAULT 0,
    requestAccountDelete           TINYINT(1) DEFAULT 0,
    confirmAccountDelete           TINYINT(1) DEFAULT 0,
    createCourse                   TINYINT(1) DEFAULT 0,
    updateCourse                   TINYINT(1) DEFAULT 0,
    updateCourseField              TINYINT(1) DEFAULT 0,
    deleteCourse                   TINYINT(1) DEFAULT 0,
    createCertificate              TINYINT(1) DEFAULT 0,
    updateCertificate              TINYINT(1) DEFAULT 0,
    updateCertificateField         TINYINT(1) DEFAULT 0,
    deleteCertificate              TINYINT(1) DEFAULT 0,
    assignCertificate              TINYINT(1) DEFAULT 0,
    getUserCertificates            TINYINT(1) DEFAULT 0,
    getUser                        TINYINT(1) DEFAULT 0,
    updateUser                     TINYINT(1) DEFAULT 0,
    updateUserField                TINYINT(1) DEFAULT 0,
    getUserPermissions             TINYINT(1) DEFAULT 0,
    updateInstitution              TINYINT(1) DEFAULT 0,
    updateInstitutionField         TINYINT(1) DEFAULT 0,
    updateInstitutionSEO           TINYINT(1) DEFAULT 0,
    updateInstitutionSEOField      TINYINT(1) DEFAULT 0,
    createPage                     TINYINT(1) DEFAULT 0,
    updatePage                     TINYINT(1) DEFAULT 0,
    deletePage                     TINYINT(1) DEFAULT 0,
    createPost                     TINYINT(1) DEFAULT 0,
    updatePost                     TINYINT(1) DEFAULT 0,
    deletePost                     TINYINT(1) DEFAULT 0,
    getAssignedCertificates        TINYINT(1) DEFAULT 0,
    getAssignedCertificate         TINYINT(1) DEFAULT 0,
    deleteUserCertificate          TINYINT(1) DEFAULT 0,
    updateAssignedCertificateField TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Roles
CREATE TABLE IF NOT EXISTS roles (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(50) NOT NULL UNIQUE,
    description             VARCHAR(255),
    permission_settings_id  INT NOT NULL,
    CONSTRAINT fk_roles_permissions
      FOREIGN KEY (permission_settings_id)
      REFERENCES permission_settings(id)
      ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    password               VARCHAR(255) NOT NULL,
    email                  VARCHAR(255) NOT NULL UNIQUE,
    first_name             VARCHAR(50)  NOT NULL,
    last_name              VARCHAR(50)  NOT NULL,
    birth_date             DATE         NOT NULL,
    permission_id          INT          DEFAULT 1,
    created_at             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    is_confirmed           BOOLEAN      DEFAULT FALSE,
    is_active              BOOLEAN      DEFAULT TRUE,
    last_login_attempt     DATETIME     DEFAULT NULL,
    failed_login_attempts  INT          DEFAULT 0,
    confirmation_code      VARCHAR(255) NOT NULL,
    CONSTRAINT fk_users_roles
      FOREIGN KEY (permission_id)
      REFERENCES roles(id)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Institutions
CREATE TABLE IF NOT EXISTS institutions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    description       TEXT,
    contact_email     VARCHAR(255),
    phone_number      VARCHAR(50),
    website_url       VARCHAR(255),
    logo_url          VARCHAR(255),
    profile_image_url VARCHAR(255),
    banner_url        VARCHAR(255),
    favicon_url       VARCHAR(255),
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Institutions SEO
CREATE TABLE IF NOT EXISTS institutions_seo (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    institution_id    INT NOT NULL,
    meta_title        VARCHAR(255),
    meta_description  VARCHAR(160),
    meta_keywords     VARCHAR(255),
    og_title          VARCHAR(255),
    og_description    VARCHAR(160),
    og_image_url      VARCHAR(255),
    og_type           VARCHAR(50),
    canonical_url     VARCHAR(255),
    robots_index      TINYINT(1) NOT NULL DEFAULT 1,
    robots_follow     TINYINT(1) NOT NULL DEFAULT 1,
    json_ld           TEXT,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_seo_institution
      FOREIGN KEY (institution_id)
      REFERENCES institutions(id)
      ON DELETE CASCADE,
    UNIQUE KEY ux_institution_seo (institution_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CMS: Static pages
CREATE TABLE IF NOT EXISTS cms_pages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL UNIQUE,   
    title      VARCHAR(200) NOT NULL,          
    content    TEXT        NOT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    slug       VARCHAR(200) NOT NULL UNIQUE,
    content    TEXT        NOT NULL,
    created_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_post_images (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    post_id   INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    caption   VARCHAR(255),
    position  INT DEFAULT 0,
    CONSTRAINT fk_post_images_post
      FOREIGN KEY (post_id)
      REFERENCES cms_posts(id)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses
CREATE TABLE IF NOT EXISTS courses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(100) NOT NULL,
    description   TEXT,
    course_author TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Certificates
CREATE TABLE IF NOT EXISTS certificates (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    course_id              INT,
    title                  VARCHAR(100) NOT NULL,
    description            TEXT,
    certificate_image_path VARCHAR(255),
    valid_until            TIMESTAMP NULL,
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_certificates_course
      FOREIGN KEY (course_id)
      REFERENCES courses(id)
      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- User certificates
CREATE TABLE IF NOT EXISTS user_certificates (
    id                            INT AUTO_INCREMENT PRIMARY KEY,
    user_id                       INT NOT NULL,
    certificate_id                INT NOT NULL,
    valid_until                   TIMESTAMP NULL,
    awarded_at                    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    personalized_certificate_image_path VARCHAR(255) UNIQUE,
    token                         VARCHAR(32) NOT NULL UNIQUE,
    CONSTRAINT fk_user_certificates_user
      FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_certificates_certificate
      FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Action logs
CREATE TABLE IF NOT EXISTS action_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    performed_by INT NOT NULL,
    target_user  INT,
    action       VARCHAR(50) NOT NULL,
    entity       VARCHAR(50) NOT NULL,
    entity_id    INT,
    details      TEXT,
    timestamp    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Password resets
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Email change requests
CREATE TABLE IF NOT EXISTS email_change_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    new_email   VARCHAR(255) NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    CONSTRAINT fk_email_change_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Account deletion requests
CREATE TABLE IF NOT EXISTS account_delete_requests (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token_hash  VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    CONSTRAINT fk_account_delete_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Rate limits
CREATE TABLE IF NOT EXISTS rate_limits (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    action     VARCHAR(50) NOT NULL,
    count      INT DEFAULT 0,
    reset_time TIMESTAMP NOT NULL,
    UNIQUE KEY (user_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ==============================================
-- 2) REQUIRED INSERTS (Application Requires)
-- ==============================================

-- Default permission settings (user)
INSERT INTO permission_settings 
    (requestEmailChange, confirmEmailChange, requestAccountDelete, confirmAccountDelete,
     getUserCertificates, getUser, updateUser, updateUserField)
VALUES
    (1, 1, 1, 1, 1, 1, 1, 1);
SET @userPermissionId = LAST_INSERT_ID();

-- Default permission settings (admin)
INSERT INTO permission_settings (
    admin, otherUsersDataManagement, otherUsersInformations, getUsers,
    requestEmailChange, confirmEmailChange, requestAccountDelete, confirmAccountDelete,
    createCourse, updateCourse, updateCourseField, deleteCourse,
    createCertificate, updateCertificate, updateCertificateField, deleteCertificate,
    assignCertificate, getUserCertificates, getUser, updateUser,
    updateUserField, getUserPermissions, updateInstitution, updateInstitutionField,
    updateInstitutionSEO, updateInstitutionSEOField, createPage, updatePage, deletePage,
    createPost, updatePost, deletePost, getAssignedCertificates, getAssignedCertificate,
    deleteUserCertificate, updateAssignedCertificateField 
)
VALUES
    (
     1, 1, 1, 1,
     1, 1, 1, 1,
     1, 1, 1, 1,
     1, 1, 1, 1,
     1, 1, 1, 1,
     1, 1, 1, 1,
     1, 1, 1, 1, 
     1, 1, 1, 1, 
     1, 1, 1, 1
    );
SET @adminPermissionId = LAST_INSERT_ID();


-- Roles
INSERT INTO roles (name, description, permission_settings_id)
VALUES
    ('User', 'Default user role with limited permissions', @userPermissionId),
    ('Administrator', 'Administrator role with full access', @adminPermissionId);

-- Default institution
INSERT INTO institutions
    (name, description, contact_email, phone_number, website_url, profile_image_url, banner_url, logo_url)
VALUES
    ('YourCert Academy',
     'YourCert Academy is a modern digital education provider focused on practical online learning, innovation, and accessibility for students and professionals worldwide.',
     'contact@yourcert.academy',
     '+1 234 567 890',
     'https://yourcert.academy',
     'default_avatar.jpg',
     'default_banner.jpg', 
     'default_logo.jpg');
SET @defaultInstitutionId = LAST_INSERT_ID();

-- Default SEO for institution
INSERT INTO institutions_seo
    (institution_id, meta_title, meta_description, meta_keywords,
     og_title, og_description, og_image_url, og_type, canonical_url)
VALUES
    (@defaultInstitutionId,
     'YourCert Academy – Digital Learning',
     'Empowering learners through flexible, certificate-backed online education.',
     'online learning, certificates, education, YourCert',
     'Join YourCert Academy',
     'Advance your career with certified online courses from YourCert.',
     'https://cdn.yourcert.academy/img/og-image.png',
     'website',
     'https://yourcert.academy');

-- Default Terms and Conditions page
INSERT INTO cms_pages (name, title, content)
VALUES
    ('terms', 'Terms and Conditions',
     'These terms and conditions govern the use of the YourCert platform. By using our services, you agree to these terms. Please read them carefully.');

