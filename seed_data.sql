-- Start transaction to ensure data integrity
START TRANSACTION;

-- Insert Admin Users
INSERT INTO `user` (`id`, `name`, `contact_information`, `area_of_expertise`, `age`, `email`, `password`, `role`) VALUES
(1, 'Admin User', '6534 5312', 'System Administration', 35, 'admin@amc.com', '$2y$10$q/JqVDIghlE7WDFWZSZAKenGv0QZ/UMV7kDjf.fCUADnO6Dtk3Wde', 'Admin');

-- Insert Researchers
INSERT INTO `user` (`id`, `name`, `contact_information`, `area_of_expertise`, `age`, `email`, `password`, `role`) VALUES
(2, 'John Doe', '6555 5321', 'Cybersecurity Research', 29, 'researcher@amc.com', '$2y$10$0524k.mq2uKm9JVOkvbvMOBnipUgSdk8J43Wgw.68FLcmEwYZ5nLa', 'Researcher'),
(3, 'Jane Smith', '1234 5678', 'Quantum Computing', 31, 'jane@amc.com', '$2y$10$someHashedPasswordHere', 'Researcher');

-- Insert Research Assistants
INSERT INTO `user` (`id`, `name`, `contact_information`, `area_of_expertise`, `age`, `email`, `password`, `role`) VALUES
(4, 'Michael Scott', '2221 1342', 'Data Analysis', 27, 'assistant@amc.com', '$2y$10$EC84tq.s6OkovPVJKDxHteAg5X5iG7FNW2zhGQJOL/kWfcMhM6dUy', 'Research Assistant');

-- Insert Research Projects
INSERT INTO `research_projects` (`id`, `title`, `description`, `assigned_to`, `funding`, `status`, `created_at`, `updated_at`) VALUES
(1, 'AI in Cybersecurity', 'Using AI for threat detection.', 2, 20000.00, 'In Progress', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, 'Quantum Algorithms', 'Developing quantum-based encryption.', 3, 30000.00, 'In Progress', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Insert Equipment
INSERT INTO `equipment` (`id`, `name`, `usage_status`, `availability`, `assigned_to`, `created_at`, `updated_at`) VALUES
(1, 'High-Performance Server', 'Available', 5, 1, NOW(), NOW()),
(2, 'Quantum Processor', 'Under Maintenance', 0, 2, NOW(), NOW());

-- Insert Equipment Requests
INSERT INTO `equipment_requests` (`id`, `researcher_id`, `equipment_id`, `request_date`, `status`, `updated_at`) VALUES
(1, 2, 1, NOW(), 'Pending', NOW()),
(2, 3, 2, NOW(), 'Pending', NOW());

-- Insert Reports
INSERT INTO `reports` (`id`, `project_id`, `created_by`, `assigned_to`, `description`, `equipment_percentage_used`, `funding`, `progress`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 3, 'AI-based security test completed.', 50, 10000.00, '40% complete', NOW(), NOW()),
(2, 2, 3, 4, 'Quantum encryption algorithm prototype.', 70, 20000.00, '30% complete', NOW(), NOW());

-- Insert Project Team Assignments
INSERT INTO `project_team` (`id`, `project_id`, `user_id`) VALUES
(1, 1, 2),
(2, 2, 3),
(3, 1, 4);

-- Commit transaction
COMMIT;
