# SWAP-Group-5

## Installation Steps

1. Download all the files.
2. Place them inside a folder called **group**.
3. Move the **group** folder to your web root directory.
   - **For XAMPP**: Place it inside `htdocs/group/`.
4. Open a browser and go to [http://localhost/group/login.php](http://localhost/group/login.php) to log in as **Admin, Researcher, or Research Assistant**.

---

## User Credentials

### Admin
- **Email**: `admin@amc.com`
- **Password**: `test`

### Researcher
- **Email**: `researcher@amc.com`
- **Password**: `test`

### Research Assistant
- **Email**: `assistant@amc.com`
- **Password**: `test`

> If you create a new user, they will not have a password initially.  
> Use the **Forgot Password** feature on the login page and enter the registered email to set a new password.  
> If the reset email is not in the inbox, check the junk or spam folder.

---

## Navigating the System

### **Dashboard Overview**
- **Admin**: Manage users, projects, reports, and view inventory.
- **Researcher**: Manage research projects, reports, and assigned equipment.
- **Research Assistant**: Manage equipment and assigned tasks.

### **User Management (Admin & Researcher)**
- **Creating Users**: Click **"Create User"**, fill out the form, and submit.
- **Editing Users**: Click **"Edit"** next to the user's name.
- **Deleting Users**: Click **"Delete"** next to the user's name (*Only Admins can delete users*).

### **Project & Report Management**
#### **Research Projects**
- **Creating Projects**: Navigate to *View Projects* to add a new project.
- **Updating & Deleting Projects**:  
  - Admins and Researchers can update projects.  
  - Only **Admins** can delete projects.  
  - Completed projects cannot be modified.

#### **Reports**
- Researchers can create, update, and delete reports related to their projects.
- Admins have full access to all reports.

### **Equipment Management (Admin & Research Assistant)**
#### **Managing Equipment**
- **Adding Equipment**: Fill out the form on the Equipment Management page.
- **Updating Equipment**: Click **"Edit"** next to the item.
- **Deleting Equipment**: Click **"Delete"** next to the item (*Only Admins can delete equipment*).
- **Viewing Inventory**:
  - Admins can see both **admin** and **researcher** equipment.
  - Researchers can update but **cannot delete** equipment.

---

## Security Features
- **SQL Injection Prevention**: Uses prepared statements.
- **Cross-Site Scripting (XSS) Protection**: Input validation and escaping.
- **CSRF Protection**: CSRF tokens prevent unauthorized form submissions.
- **Session Security**:  
  - Sessions regenerate every **60 seconds** to prevent hijacking.  
  - Users who remain inactive for **30 minutes** will be automatically logged out.  
  - Logging out manually will immediately end the session.  
- **Password Security**: Passwords are stored securely using **bcrypt hashing**.

---

## Logging Out
- Click the **Logout** button in the top-right corner.
- Users who remain inactive for **30 minutes** will be automatically logged out.

---

## Contact Information
If you experience any issues setting up or testing the system, please reach out for support.
