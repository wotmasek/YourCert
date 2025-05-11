# YourCert

YourCert is an open-source web application designed for educational institutions and organizations that issue certificates. The platform facilitates efficient management of courses, certificates, and user accounts while providing a user-friendly interface for both administrators and end-users.

## Features

* **Institution Profile & Homepage:**
  Showcase the institution's profile with comprehensive details about available courses and issued certificates.

* **User Login & Registration:**
  Secure user authentication system with email and password.

* **Course, Certificate, and News Management:**
  Administrators can:

  * Add, edit, and remove courses.
  * Manage certificates, assign them individually or in bulk, and attach visual versions.
  * Publish news posts to keep users updated with the latest information.

* **User Dashboard:**
  Users can view, download and share their assigned certificates from a personal dashboard.

## Requirements

* **PHP** (version 8.2 or above recommended)
* **MySQL**
* Web Server (e.g., Apache or Nginx)

## Installation

1. **Clone the repository:**

   ```bash
   git clone https://github.com/wotmasek/YourCert.git
   ```

2. **Configure the application:**
   Navigate to the `YourCert` directory and follow the instructions to set up the database and configure your environment variables (e.g., database connection details).

3. **Set up folder permissions (Linux only):**

   Ensure that the web server (e.g., Apache or LAMPP) has the necessary permissions to read and write to specific directories. These include:

   * `/app`: Contains configuration and application logic.
   * `/public/uploads`: Stores user-uploaded files.

   Use the following commands to set the appropriate permissions:

   ```bash
   sudo chown -R www-data:www-data /path/to/YourCert/app
   sudo chown -R www-data:www-data /path/to/YourCert/public/uploads
   sudo chmod -R u+rwX /path/to/YourCert/app
   sudo chmod -R u+rwX /path/to/YourCert/public/uploads
   ```

   ⚠️ **Important:** Without correct permissions, the application may fail to save uploaded files or write necessary configurations.

4. **Run the application:**
   Deploy the application to your local or production server. Ensure your web server's root points to the `/public` directory.

5. **Access the application:**
   Open your browser and navigate to the application’s URL (e.g., `http://localhost/`).

 