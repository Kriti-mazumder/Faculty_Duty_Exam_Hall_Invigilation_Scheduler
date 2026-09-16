ALTER TABLE `department` 
ADD COLUMN `status` ENUM('active','inactive') DEFAULT 'active' AFTER `department_name`;
