
# AWS EC2 Server Setup Guide - LAMP Stack with SSL

This guide covers the step-by-step process to set up an AWS EC2 instance with:

- Apache (HTTP Server)
- MariaDB (MySQL)
- PHP
- Certbot (Let's Encrypt SSL)
- Basic firewall and security configuration

---

## ✅ Prerequisites

- AWS EC2 instance running **Amazon Linux 2023** (or compatible AMI).
- SSH key pair (`.pem` file) for secure access.
- Security Group with the following ports open:
  - **22** - SSH
  - **80** - HTTP
  - **443** - HTTPS
- A domain name pointing to the instance's Elastic IP.

---

## 🚀 Step 1: Update the System

```bash
sudo dnf update -y
```

---

## 🌐 Step 2: Install Apache (HTTP Server)

```bash
sudo dnf install -y httpd
sudo systemctl start httpd
sudo systemctl enable httpd
```

Check if Apache is working:  
Visit `http://<YOUR_PUBLIC_IP>` — you should see the Apache test page.


### Configure Apache Virtual Host

Create a virtual host configuration file for your domain:

```bash
sudo nano /etc/httpd/conf.d/petranaki.conf
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

    ErrorLog /var/log/httpd/petranaki-error.log
    CustomLog /var/log/httpd/petranaki-access.log combined
</VirtualHost>
```

### Create the document root directory:

```bash
sudo mkdir -p /var/www/html/petranaki
sudo chown -R ec2-user:ec2-user /var/www/html/petranaki
```

(Optional) Add an index.html file to test:

```bash
echo "Site is working" | sudo tee /var/www/html/petranaki/index.html
```

### Restart Apache to apply the changes:

```bash
sudo systemctl restart httpd
```

Now visit `http://petranaki.net` in your browser to confirm the virtual host is working.


---

## 🐬 Step 3: Install MariaDB (MySQL)

```bash
sudo dnf install -y mariadb105-server
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### Secure the MariaDB installation:

```bash
sudo mysql_secure_installation
```

Recommended steps:
- Set the root password
- Remove anonymous users
- Disallow remote root login
- Remove test databases
- Reload privilege tables

### Create a new user for the application

```bash
CREATE USER 'swuapp'@'localhost' IDENTIFIED BY 'senha_forte';
GRANT ALL PRIVILEGES ON swuonline.* TO 'swuapp'@'localhost';
FLUSH PRIVILEGES;
```

---


## 🐘 Step 4: Install PHP

```bash
sudo dnf install -y php php-mysqlnd php-cli php-gd php-mbstring php-xml php-zip php-soap php-intl
sudo systemctl restart httpd
```

### Test PHP:

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

Check in the browser:  
`http://<YOUR_PUBLIC_IP>/info.php`  
→ You should see the PHP info page.

---

## 🔒 Step 5: Install Certbot (Let's Encrypt SSL)

### Install Certbot and the Apache plugin:

```bash
sudo dnf install -y certbot python3-certbot-apache
```

### Generate SSL certificate:

```bash
sudo certbot --apache
```

Follow the prompts:
- Enter your domain (e.g., `example.com`)
- Agree to the terms
- Choose whether to redirect HTTP to HTTPS (recommended)

### Test auto-renewal:

```bash
sudo certbot renew --dry-run
```

---

## 🧹 Step 6: Clean Up PHP Info File (Security Best Practice)

```bash
sudo rm /var/www/html/info.php
```

---

## 🔄 Step 7: Install Git

```bash
sudo dnf install -y git
```

---


## 🛡️ Best Practices Checklist

- ✅ Keep your system updated (`sudo dnf update -y` regularly).
- ✅ Use an Elastic IP to maintain a static IP.
- ✅ Configure regular MySQL database backups.
- ✅ Use SSH key-based authentication (disable password login for extra security).
- ✅ Secure your instance with Security Groups and optionally tools like Fail2Ban.
- ✅ Monitor certificate expiration (`sudo certbot renew --dry-run`).
