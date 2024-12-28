CREATE TABLE colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE
);
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'instructor', 'learner', 'approval') NOT NULL DEFAULT 'approval',
    college_code VARCHAR(50),
    FOREIGN KEY (college_code) REFERENCES colleges(code) ON DELETE CASCADE
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(255) NOT NULL,
    semester VARCHAR(100) NOT NULL,
    year VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    topic TEXT NOT NULL,
    notes TEXT NOT NULL,
    college_code VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
