# SWAP-Group-5

## Installation Steps

1. Download all the files.
2. Ensure to download vendor as well as it is needed for the password reset
3. Place them inside a folder called **group**.
4. Move the **group** folder to your web root directory.
   - **For XAMPP**: Place it inside `htdocs/group/`.
5. Open a browser and go to [http://localhost/group/login.php](http://localhost/group/login.php) to log in as **Admin, Researcher, or Research Assistant**.

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
- ## Security Features

- **Authentication**: Secure login system using PHP sessions.  
  - Passwords are **hashed** and stored securely in the database.  
  - Sessions regenerate every **60 seconds** to prevent session hijacking.  

- **Authorization**:  
  - Users can access only data and functions relevant to their assigned roles.  
  - Admins, Researchers, and Research Assistants have restricted access to prevent unauthorized actions.  

- **Input Validation**:  
  - Server-side validation is implemented to prevent **SQL Injection, Cross-Site Scripting (XSS), and other injection attacks**.  
  - Prepared statements and input sanitization ensure secure data handling.  

- **CSRF Protection**:  
  - All forms are secured with **CSRF tokens** to prevent cross-site request forgery attacks.  
  - Token validation is enforced on all POST requests.  

- **Encryption**:  
  - Sensitive data, such as researcher personal details and project information, is **encrypted**.  
  - Passwords are stored using **bcrypt hashing** for strong security.  

- **Error Handling**:  
  - Secure error handling is implemented to prevent system exposure through error messages.  
  - Errors are logged securely without exposing sensitive details to users.  

- **Session Security**:  
  - Users who remain inactive for **30 minutes** are automatically **logged out** due to session timeout.  
  - Sessions are **bound to the user’s IP and browser** to prevent hijacking.  
  - **Strict session cookie policies** (`SameSite=Strict`, `HttpOnly`, `Secure`) are enforced.  

---

## Logging Out
- Click the **Logout** button in the top-right corner.
- Users who remain inactive for **30 minutes** will be automatically logged out.

---

## Contact Information
If you experience any issues setting up or testing the system, please reach out for support.
