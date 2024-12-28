-- Create database
CREATE DATABASE class_cloud;

-- Use the database
USE class_cloud;

-- Table for institutions
CREATE TABLE institutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_name VARCHAR(255) NOT NULL,
    institution_id VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for instructors
CREATE TABLE instructors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    instructor_id VARCHAR(50) UNIQUE NOT NULL,
    institution_id VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    courses VARCHAR(255) NOT NULL DEFAULT '',
    year INT DEFAULT 1,
     semester INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    first_time_login TINYINT(1) DEFAULT 1,
    FOREIGN KEY (institution_id) REFERENCES institutions(institution_id)
        ON DELETE CASCADE
);

-- Table for students
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    instructor_id VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    courses VARCHAR(255) NOT NULL DEFAULT '',
     year INT DEFAULT 1,
     semester INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    first_time_login TINYINT(1) DEFAULT 1,
    FOREIGN KEY (instructor_id) REFERENCES instructors(instructor_id)
        ON DELETE CASCADE
);



-- Create courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institution_id VARCHAR(50) NOT NULL,  -- Reference to institution
    instructor_id VARCHAR(50) NOT NULL,   -- Reference to instructor
    course_name VARCHAR(255) NOT NULL,     -- Name of the course
    description TEXT,                      -- Description of the course
    semester INT DEFAULT 1,                -- Semester for the course
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Course creation timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last updated timestamp
    FOREIGN KEY (institution_id) REFERENCES institutions(institution_id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(instructor_id) ON DELETE CASCADE
);

-- Create subjects table for each course
CREATE TABLE subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,               -- Reference to course
    subject_name VARCHAR(255) NOT NULL,    -- Name of the subject
    description TEXT,                     -- Description of the subject
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Subject creation timestamp
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create topics table for each subject
CREATE TABLE topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,              -- Reference to subject
    topic_name VARCHAR(255) NOT NULL,      -- Name of the topic
    description TEXT,                     -- Description of the topic
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Topic creation timestamp
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

-- Create notes table for each topic
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,                -- Reference to topic
    note_title VARCHAR(255) NOT NULL,      -- Title of the note
    note_content TEXT,                    -- Content of the note
    file_path VARCHAR(255),               -- Optional: Path to any uploaded file
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Note creation timestamp
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
);


