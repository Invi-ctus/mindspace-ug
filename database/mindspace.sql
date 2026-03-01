
USE if0_41273948_mindspace;

CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,           -- bcrypt hash via password_hash()
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_email    (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moods (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    mood        ENUM('happy','neutral','sad','frustrated','anxious') NOT NULL,
    note        TEXT         NULL,               -- optional journal entry
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS community_posts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,           -- stored but never shown publicly
    message     TEXT         NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO users (username, email, password) VALUES
('demo_user',  'demo@mindspaceug.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('test_youth',  'youth@mindspaceug.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO moods (user_id, mood, note, created_at) VALUES
(1, 'happy',      'Had a great day today!',             DATE_SUB(NOW(), INTERVAL 6 DAY)),
(1, 'neutral',    'Just an ordinary day.',               DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 'anxious',    'Exam stress is getting to me.',       DATE_SUB(NOW(), INTERVAL 4 DAY)),
(1, 'sad',        'Feeling lonely today.',               DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'neutral',    'Better than yesterday.',              DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 'happy',      'Talked to a friend, feels better!',  DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'happy',      'Good morning vibes.',                 NOW()),
(2, 'frustrated', 'Work was really tough today.',        DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'neutral',    '',                                    DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'happy',      'Feeling grateful.',                   DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO community_posts (user_id, message, created_at) VALUES
(1, 'Remember: it is okay not to be okay. You are not alone in this journey.',       DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'I have been struggling with anxiety lately. Any tips from the community?',       DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Breathing exercises have really helped me calm down during stressful moments!', NOW()),
(2, 'Sending love and positive energy to everyone here today. You are stronger than you think!', NOW());
