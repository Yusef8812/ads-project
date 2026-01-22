-- Seed Data
USE JobSystemDB;

-- Users (Password is 'password123' hashed with DEFAULT_PASSWORD_HASH for demo purposes needed by PHP's password_verify)
-- Note: In a real scenario, valid bcrypt hashes should be generated. 
-- Here we'll use a placeholder or plain text for now if the app hashes on insert, OR we assume these are pre-hashed.
-- Let's assume the app uses password_hash('password123', PASSWORD_BCRYPT).
-- For this script to work immediately with a PHP login that expects hashes, we need real hashes.
-- Hash for 'password123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO Users (Username, Email, PasswordHash, Role) VALUES
('admin', 'admin@jobsystem.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('recruiter_tech', 'tech@recruiter.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Recruiter'),
('jane_doe', 'jane@candidate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Candidate'),
('john_smith', 'john@candidate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Candidate');

-- Categories (Admin Managed)
INSERT INTO Categories (CategoryName) VALUES ('IT / Software'), ('Finance'), ('Marketing'), ('Human Resources');

-- Companies (Linked to recruiter_tech which is UserID 2)
INSERT INTO Companies (RecruiterID, CompanyName, Description, Logo) VALUES
(2, 'TechCorp Solutions', 'Leading provider of AI solutions.', 'logo_techcorp.png');

-- Jobs
INSERT INTO Jobs (CompanyID, CategoryID, Title, Description, Salary, JobType, Status) VALUES
(1, 1, 'Senior Backend Developer', 'We need a PHP expert.', '$80,000 - $100,000', 'Full-time', 'Open'),
(1, 1, 'Frontend Engineer', 'React and CSS wizardry needed.', '$70,000 - $90,000', 'Full-time', 'Open'),
(1, 4, 'HR Manager', 'Manage our growing team.', '$60,000', 'Full-time', 'Closed');

-- Applications
-- Jane (User 3) applies to Backend Dev (Job 1)
INSERT INTO Applications (JobID, CandidateID, ResumePath, Status) VALUES
(1, 3, 'uploads/resumes/jane_backend.pdf', 'Pending');
