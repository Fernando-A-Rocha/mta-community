# How to deploy this platform on a VPS

## Initial Setup

- Clone the repo in a secure folder of your linux user (like home)
- Run the commands from the main README.md (same as a first development setup)
- Make sure to configure .env according to production environment

## Deploy with Nginx (Web server)

Use `nginx.conf` with your domain (generate a certificate for it) and `deploy.sh`.

It requires specific configuration of your VPS, proceed with caution.
