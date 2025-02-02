# SWAP-Group-5

  1. Download all the files
  2. Place them inside a folder called group
  3. Move the group folder to their web root directory For XAMPP: htdocs/group/
  4. go to http://localhost/group/login.php to login as Admin, Researcher Or Research Assistant
     
---

User Credentials:

Admin
Email: admin@amc.com
Password: test

Researcher
Email: researcher@amc.com
Password: test

Research Assistant
Email: assistant@amc.com
Password: test

If you create a new user, they will not have a password initially. Use the "Forgot Password" feature on the login page and enter the registered email to set a new password. If the reset email is not in the inbox, check the junk or spam folder.

Navigating the System:

Dashboard Overview:


User Management for Admins and Researchers:

Researcher Profiles:
To create a new user, click "Create User" and fill in the form.
To edit a user, click "Edit" next to their name.
To delete a user, click "Delete" next to their name (only Admins can delete users).
Equipment Management for Research Assistants:

Research Projects:
To create a new research project and also assign team members, navigate to the view projects for either admin or researcher. You can delete and update projects provided they have not been
completed. (Admin)
researchers can do the same thing except for delete.

Equipment:
To add equipment, fill out the form on the Equipment Management page.
To update equipment, click "Edit" next to the item.
To delete equipment, click "Delete" next to the item.
Researchers cant delete equipment and admin can see both admin and researcher equipment in the inventory

Report:
Admins can create, update, and delete reports related to projects.
Admins can create, update, and delete reports related to projects.

Security Features:

SQL injection, XSS, CSRF Token implemented have all been implemented to secure the system

Logging Out:

Click the "Logout" button in the top-right corner of the system.
Users who remain inactive for 30 minutes will be automatically logged out due to session timeout and will need to log in again.

Contact Information:
If you experience any issues setting up or testing the system, please reach out for support.
