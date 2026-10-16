-- Creates the two databases and the dedicated MySQL account used by the application.
-- Run once as MySQL root:  mysql -u root -p < database/create-database-user.sql
-- Replace the password below with your own and copy it into .env.local (DATABASE_URL).

CREATE DATABASE IF NOT EXISTS knowledge_learning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS knowledge_learning_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- The account can only connect from this machine and only reaches the project databases.
CREATE USER IF NOT EXISTS 'knowledge_admin'@'localhost' IDENTIFIED BY 'CHANGE_ME';
GRANT ALL PRIVILEGES ON knowledge_learning.* TO 'knowledge_admin'@'localhost';
GRANT ALL PRIVILEGES ON knowledge_learning_test.* TO 'knowledge_admin'@'localhost';
FLUSH PRIVILEGES;
