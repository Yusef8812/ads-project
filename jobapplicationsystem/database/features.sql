-- Advanced SQL Features
USE JobSystemDB;

-- 1. TRIGGER: Prevent Application if Job is Closed ("Application Guard")
DELIMITER //
CREATE TRIGGER trg_PreventClosedJobApplication
BEFORE INSERT ON Applications
FOR EACH ROW
BEGIN
    DECLARE jobStatus VARCHAR(10);
    
    -- Check the status of the job being applied for
    SELECT Status INTO jobStatus
    FROM Jobs
    WHERE JobID = NEW.JobID;
    
    -- If job is closed, signal an error to abort the insert
    IF jobStatus = 'Closed' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Cannot apply to a closed job.';
    END IF;
END //
DELIMITER ;

-- 2. STORED PROCEDURE: Toggle Job Status
DELIMITER //
CREATE PROCEDURE sp_ToggleJobStatus(IN p_JobID INT)
BEGIN
    UPDATE Jobs
    SET Status = CASE 
        WHEN Status = 'Open' THEN 'Closed' 
        ELSE 'Open' 
    END
    WHERE JobID = p_JobID;
END //
DELIMITER ;

-- 3. STORED PROCEDURE: Hire Applicant (Updates status)
DELIMITER //
CREATE PROCEDURE sp_HireApplicant(IN p_AppID INT)
BEGIN
    UPDATE Applications
    SET Status = 'Accepted'
    WHERE ApplicationID = p_AppID;
END //
DELIMITER ;

-- 4. STORED FUNCTION: Get Total Applications for a Company
DELIMITER //
CREATE FUNCTION fn_GetCompanyApplicationCount(p_CompanyID INT) 
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE appCount INT;
    
    SELECT COUNT(a.ApplicationID) INTO appCount
    FROM Applications a
    JOIN Jobs j ON a.JobID = j.JobID
    WHERE j.CompanyID = p_CompanyID;
    
    RETURN IFNULL(appCount, 0);
END //
DELIMITER ;

-- 5. VIEW: Public Job Board View
CREATE OR REPLACE VIEW vw_OpenJobs AS
SELECT 
    j.JobID,
    j.Title,
    j.Salary,
    j.JobType,
    c.CompanyName,
    c.Logo,
    j.CategoryID, -- Added for filtering
    cat.CategoryName,
    j.PostedDate
FROM Jobs j
JOIN Companies c ON j.CompanyID = c.CompanyID
JOIN Categories cat ON j.CategoryID = cat.CategoryID
WHERE j.Status = 'Open';

-- 6. INDEX: Optimize Job Title Search
CREATE INDEX idx_job_title ON Jobs(Title);
