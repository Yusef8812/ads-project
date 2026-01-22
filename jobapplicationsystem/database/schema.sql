-- Database Schema for Job Application System
-- Database: JobSystemDB

CREATE DATABASE IF NOT EXISTS JobSystemDB;
USE JobSystemDB;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Recruiter', 'Candidate') NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX ix_UserEmail (Email)
);

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS Categories (
    CategoryID INT AUTO_INCREMENT PRIMARY KEY,
    CategoryName VARCHAR(100) NOT NULL UNIQUE
);

-- 3. Companies Table
CREATE TABLE IF NOT EXISTS Companies (
    CompanyID INT AUTO_INCREMENT PRIMARY KEY,
    RecruiterID INT NOT NULL,
    CompanyName VARCHAR(100) NOT NULL,
    Description TEXT,
    Logo VARCHAR(255),
    FOREIGN KEY (RecruiterID) REFERENCES Users(UserID) ON DELETE CASCADE
);

-- 4. Jobs Table
CREATE TABLE IF NOT EXISTS Jobs (
    JobID INT AUTO_INCREMENT PRIMARY KEY,
    CompanyID INT NOT NULL,
    CategoryID INT NOT NULL,
    Title VARCHAR(150) NOT NULL,
    Description TEXT NOT NULL,
    Salary VARCHAR(50),
    JobType ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL,
    Status ENUM('Open', 'Closed') DEFAULT 'Open',
    PostedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CompanyID) REFERENCES Companies(CompanyID) ON DELETE CASCADE,
    FOREIGN KEY (CategoryID) REFERENCES Categories(CategoryID) ON DELETE CASCADE,
    INDEX ix_JobTitle_Category (Title, CategoryID)
);

-- 5. Applications Table
CREATE TABLE IF NOT EXISTS Applications (
    ApplicationID INT AUTO_INCREMENT PRIMARY KEY,
    JobID INT NOT NULL,
    CandidateID INT NOT NULL,
    ResumePath VARCHAR(255) NOT NULL,
    Status ENUM('Pending', 'Accepted', 'Rejected', 'Interview') DEFAULT 'Pending',
    AppliedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (JobID) REFERENCES Jobs(JobID) ON DELETE CASCADE,
    FOREIGN KEY (CandidateID) REFERENCES Users(UserID) ON DELETE CASCADE,
    UNIQUE KEY uk_Job_Candidate (JobID, CandidateID) -- Prevent double application
);
