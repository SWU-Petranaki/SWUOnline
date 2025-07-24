# Ubuntu Server Setup Guide

This guide covers the step-by-step process for setting up an AWS EC2 instance with:

* Apache (HTTP Server)
* MariaDB (MySQL)
* PHP
* Certbot (Let's Encrypt SSL)
* Basic firewall and security configuration

---

## ✅ Prerequisites

* VPS instance running **Ubuntu** (recommended 20.04 or higher).
* SSH key pair (`.pem` file) for secure access.
* Firewall with the following open ports:

  * **22** - SSH (Inbound - TCP)
  * **80** - HTTP (Inbound - TCP, Outbound - TCP)
  * **443** - HTTPS (Inbound - TCP, Outbound - TCP)
  * **53** - DNS (Outbound - TCP & UDP)
  * **123** - NTP (Outbound - UDP)

---

## 🚀 Step 1: Update the System

```bash
sudo apt update -y && sudo apt upgrade -y
```

---

## 🌐 Step 2: Install Apache (HTTP Server)

```bash
sudo apt install -y apache2
sudo systemctl start apache2
sudo systemctl enable apache2
```

Check if Apache is working:
Visit `http://<YOUR_PUBLIC_IP>` — you should see the Apache test page.

### Configure Apache Virtual Host

Create a configuration file for your domain:

```bash
sudo nano /etc/apache2/sites-available/petranaki.conf
```

Add the following content to the file:

```apache
<VirtualHost *:80>
    ServerName petranaki.net
    ServerAlias www.petranaki.net

    DocumentRoot /var/www/html/petranaki

    <Directory /var/www/html/petranaki>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Create the website root directory:

```bash
sudo mkdir -p /var/www/html/petranaki
sudo chown -R www-data:www-data /var/www/html/petranaki
```

(Optional) Add an `index.html` file to test:

```bash
echo "Site is working" | sudo tee /var/www/html/petranaki/index.html
```

### Enable the site and restart Apache:

```bash
sudo a2ensite petranaki.conf
sudo systemctl reload apache2
```

Now visit `http://petranaki.net` in your browser to confirm if the Virtual Host is working.

---

## 🐘 Step 3: Install PHP

```bash
sudo apt install -y php php-mysql libapache2-mod-php php-gd php-mbstring php-xml php-zip php-soap php-intl php-curl
sudo systemctl restart apache2
```

### Test PHP:

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

Visit in the browser:
`http://<YOUR_PUBLIC_IP>/info.php`
→ You should see the PHP info page.

---



## 🔒 Step 4: Install Certbot (Let's Encrypt SSL)

### Install Certbot and the Apache plugin:

```bash
sudo apt install -y certbot python3-certbot-apache
```

### Generate the SSL certificate:

```bash
sudo certbot --apache
```

Follow the prompts:

* Enter your domain (e.g., `example.com`)
* Agree to the terms
* Choose to redirect HTTP to HTTPS (recommended)

### Test auto-renewal:

```bash
sudo certbot renew --dry-run
```

---

## 🐬 Step 5: Install MariaDB (MySQL)

```bash
sudo apt install -y mariadb-server
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### Secure the MariaDB installation:

```bash
sudo mysql_secure_installation
```

Recommended steps:

* Set the root password
* Remove anonymous users
* Disable remote root login
* Remove test databases
* Reload privilege tables

### Create a new user for the application

```bash
sudo mysql -u root -p
CREATE USER 'swuonline'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON swuonline.* TO 'swuonline'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🧹 Step 6: Clean up the PHP info file (Security Best Practice)

```bash
sudo rm /var/www/html/info.php
```

---

## 🔄 Step 7: Install Git

```bash
sudo apt install -y git
```

---

## 🛡️ Best Practices Checklist

* ✅ Keep your system updated (`sudo apt update && sudo apt upgrade -y` regularly).
* ✅ Use an Elastic IP to maintain a static IP.
* ✅ Set up regular MySQL database backups.
* ✅ Use SSH key-based authentication (disable password login for better security).
* ✅ Secure your instance with Security Groups and optionally tools like Fail2Ban.
* ✅ Monitor SSL certificate expiration (`sudo certbot renew --dry-run`).

---

Now your server is set up, with Apache, MariaDB, PHP, and SSL working correctly!
