# How to deploy this platform on a VPS

## Initial Setup

- Clone the repo in a secure folder of your linux user (like home)
- Run the commands from the main README.md (same as a first development setup)
- Make sure to configure .env according to production environment

## Deploying

### Preparing Web Server

Ensure you have PHP 8.4 installed with GD extension. Install PHP 8.4 FPM.

Install Nginx and PHP-FPM for the correct PHP version.

Configure the site with nginx.conf and the domain (generate a certificate for it).

Configure PHP-FPM memory and upload limits (see [UPLOAD_LIMITS.md](./UPLOAD_LIMITS.md)).

Restart Nginx and PHP-FPM.

### Pushing an Update

This also requires specific configuration of your VPS, proceed with caution.

Run `deploy.sh` every time you want to pull changes and deploy the update to the live site.
