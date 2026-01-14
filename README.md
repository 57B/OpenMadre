# OpenMadre
The source code for MadreTube

<h1>To get this to work, type this in your PHPMyAdmin SQL:</h1>


-- MadreTube Database Setup 2026

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    pfp VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    thumbnail_name VARCHAR(255) NOT NULL,
    views INT DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    video_id INT,
    user_id INT,
    comment_text VARCHAR(200) NOT NULL,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    target_id INT,
    target_type ENUM('video', 'comment'),
    stars INT CHECK (stars BETWEEN 1 AND 5),
    UNIQUE(user_id, target_id, target_type)
);

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscriber_id INT,
    channel_id INT,
    FOREIGN KEY (subscriber_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (channel_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE playlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(100) NOT NULL,
    video_ids TEXT, -- JSON or comma-separated IDs
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    video_id INT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);





<h1>Than after that, go into "config.php" and change the DB info to your credentials.</h1>
