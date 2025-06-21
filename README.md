# SWUOnline
Star Wars Unlimited Sim

## Contact info
If you need to contact us or would like to get involved in the dev work, please reach out in our [Discord server](https://discord.gg/AN5GEXSu)!

## Dev Quickstart

We have two options for setting up a development environment: a docker-based method that is fully automatic and supports live debug, or a walkthrough for how to set up the environment manually on your local machine.

### Dev Environment Option 1: Docker environment with live debugging
We provide a docker environment for local run with built-in xdebug support for live debugging, which can be installed and started in two commands if you already have docker set up. A preconfigured setup is provided for Visual Studio Code (it would be very simple to set up other tools as well).

#### Step 0. Install docker
If you are on Windows, please follow the instructions for installing Docker Desktop on Windows (either WSL or Hyper-V backend should work but we have only tested with WSL): https://docs.docker.com/desktop/install/windows-install/

#### Step 1. Starting / stopping the service

Run the following commands to start / stop the service
```bash
bash petranaki.sh start
bash petranaki.sh stop
bash petranaki.sh restart
```

#### Step 2. Accessing the application

Open this address in your browser: http://localhost:8080/Arena/MainMenu.php

If you want to play a game against yourself, open multiple windows / tabs and connect.

#### Step 3. Run debugger in VSCode (optional)
Xdebug is already running in the service, you can use these steps to do live debugging with breakpoints in Visual Studio Code:

1. Install an extension that supports PHP debugging, such as https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode
2. We have a preconfigured [launch.json](.vscode/launch.json) to enable the debug action. In the vscode debug window (Ctrl + Shift + D), select the configuration `SWUOnline: Listen for Xdebug` and hit the Run button.
3. You are now connected for debugging, add a breakpoint and try it out :)

Additionally, any tool that can connect to xdebug remotely should work as well.

### Dev Environment Option 2: Manual Environment Setup

We have a Google Doc with instructions for setting up the environment. Some steps may be missing or require extra detail, if you find any issues please contact us via the Discord so we can improve the document.

https://docs.google.com/document/d/10u3qGpxr1ddvwobq8__lVZfYCgqtanZShHEaYljiA1M/edit?usp=sharing

---

### CI/CD Configuration Guide

This guide explains how to set up CI/CD, including deploying with GitHub, securing webhooks, and configuring environment variables.


#### 1. Configure a Webhook
- Generate a secret key to secure the webhook:
  ```bash
  openssl rand -hex 32
  ```
  Save this secret for later use.

- In your GitHub repository, go to **Settings > Webhooks** and create a new webhook with the following settings:
  - **Payload URL**: `https://petranaki.net/Arena/Webhook.php`
  - **Content Type**: `application/json`
  - **Secret**: `<webhook-secret>` (use the secret generated earlier)
  - **SSL Verification**: Enabled
  - **Events**: Select "Just the push event" to trigger the webhook on push events.

#### 2. Configure `.htaccess`
To secure your project and set environment variables:
- Navigate to your project directory: `/var/www/html/petranaki`
- Create or edit an `.htaccess` file with the following content:
  ```apache
  RedirectMatch 404 /\.git
  SetEnv MYSQL_SERVER_NAME localhost
  SetEnv MYSQL_SERVER_USER_NAME root
  SetEnv MYSQL_ROOT_PASSWORD <mysql-password> (password for the custom user)
  SetEnv WEBHOOK_SECRET <webhook-secret>
  SetEnv PATREON_CLIENT_ID <patreon-client-id>
  SetEnv PATREON_CLIENT_SECRET <patreon-client-secret>

  RewriteEngine On

  # Redirect to MainMenu.php if the request is for the root directory
  RewriteCond %{REQUEST_URI} ^/$
  RewriteRule ^$ /Arena/MainMenu.php [R=302,L]

  # Redirect to MainMenu.php if the request is for the Arena directory
  RewriteCond %{REQUEST_URI} ^/Arena/?$
  RewriteRule ^Arena/?$ /Arena/MainMenu.php [R=302,L]
  ```

This configuration ensures that the `.git` folder is inaccessible and adds environment variables for your project.

#### 3. Sync the Project to GitHub
If your project is not yet synced to GitHub, follow these steps:

- Initialize the project as a Git repository:
  ```bash
  git init
  ```

- Fetch the project from GitHub:
  ```bash
  git fetch https://github.com/SWU-Petranaki/SWUOnline.git main
  ```

- Reset the project to the latest version:
  ```bash
  git reset --hard FETCH_HEAD
  ```
  **Note**: Files listed in `.gitignore` will remain unaffected. Back up important files before running this command to avoid accidental data loss.

- Pull the latest changes from GitHub:
  ```bash
  git pull https://github.com/SWU-Petranaki/SWUOnline.git main
  ```
  

#### 5. Grant Permissions to the `apache` User
The `Webhook.php` script will execute using the `apache` user, so it must have permissions to run `git pull`.

- Edit the sudoers file:
  ```bash
  sudo visudo
  ```

- Add the following line to grant limited permissions:
  ```text
  apache ALL=(ALL) NOPASSWD: /usr/bin/git
  ```

This ensures that the apache user can authenticate with GitHub when the webhook triggers a git pull operation.

#### Final Steps
Your project is now configured for CI/CD. Any commit pushed to the `main` branch will trigger the webhook, which executes a `git pull` on the server to update the project files automatically.
