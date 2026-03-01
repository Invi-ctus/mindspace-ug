USE if0_41273948_mindspace;

-- Demo users
INSERT INTO users (username, email, password) VALUES
('demo_user',  'demo@mindspaceug.com',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('test_youth',  'youth@mindspaceug.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample mood entries
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

-- Sample community posts
INSERT INTO community_posts (user_id, message, created_at) VALUES
(1, 'Remember: it is okay not to be okay. You are not alone in this journey.',       DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'I have been struggling with anxiety lately. Any tips from the community?',       DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Breathing exercises have really helped me calm down during stressful moments!', NOW()),
(2, 'Sending love and positive energy to everyone here today. You are stronger than you think!', NOW());
