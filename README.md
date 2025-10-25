# KidsSmart Docker Development Environment Setup

## Prerequisites

- Linux Mint / Ubuntu 24.04 or later, Windows 10/11, or macOS (Big Sur or later)
- Docker Engine installed
- Docker Compose V2 installed (`docker compose` command)
- Git installed

---

## Installation Instructions

### Linux (Mint/Ubuntu)

Update package list and install prerequisites

`sudo apt update` <br />
`sudo apt install -y apt-transport-https ca-certificates curl software-properties-common lsb-release`

Add Docker’s GPG key and repository

`sudo mkdir -p /etc/apt/keyrings` <br />
`curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg` <br />
`echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu noble stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null`

Update and install Docker Engine and Compose plugin

`sudo apt update` <br />
`sudo apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin`

Start Docker and enable on boot

`sudo systemctl start docker` <br />
`sudo systemctl enable docker`

Add current user to docker group (replace $USER if needed)

`sudo groupadd docker` <br />
`sudo usermod -aG docker $USER`

Activate group change (logout/login recommended)

`newgrp docker`

Verify Docker and Compose installation

`docker --version` <br />
`docker compose version`

---

### Windows

Download and install Docker Desktop from:
https://www.docker.com/products/docker-desktop/

Launch Docker Desktop app
Verify installation

`docker --version` <br />
`docker compose version`

---

### macOS

Download Docker Desktop from:
https://www.docker.com/products/docker-desktop/

Mount the DMG, drag Docker app to Applications folder
Launch Docker Desktop app
Verify installation

`docker --version` <br />
`docker compose version`

---

## Stop Conflicting Services (Linux)

Make sure to stop XAMPP and system MySQL to free ports 3306 and 80:

`sudo /opt/lampp/lampp stop` <br />
`sudo systemctl stop mysql` <br />
`sudo systemctl disable mysql`

---

## Set Up KidsSmart Docker Environment

`git clone https://github.com/ariqfitri/NITCapstone.git` <br />
`cd NITCapstone`

`docker compose build` <br />
`docker compose up -d`

---

## Verify Running Containers

`docker ps`

Expected containers:

- `kidssmart_db` (MySQL)
- `kidssmart_web` (Flask app)
- `kidssmart_phpmyadmin` (phpMyAdmin)
- `kidssmart_scraper` (Scraper)

---

## Access Services

- Flask web app: [http://localhost:5000](http://localhost:5000)
- phpMyAdmin: [http://localhost:8080](http://localhost:8080)  
  Username: `kidssmart_user`  
  Password: `SecurePass123!`

---

## Important Notes

- Use `docker compose` (space) commands as Docker Compose V2 replaces `docker-compose`.
- Remove the deprecated `version: '3.8'` line from `docker-compose.yml`.
- Ensure XAMPP and system MySQL are stopped when using Docker to avoid port conflicts.
- Logout and log back in after adding your user to the `docker` group.

---

## Useful Commands

Stop containers

`docker compose down`

Restart web container after code changes

`docker compose build web` <br />
`docker compose up -d web`

Stream Flask web logs

`docker compose logs -f web`

Run scraper spiders

`docker compose run --rm scraper scrapy crawl activities` <br />
`docker compose run --rm scraper scrapy crawl kidsbook`

---

## Add current user to docker group (Linux)

`sudo groupadd docker` # If docker group doesn’t exist
`sudo usermod -aG docker $USER`
`newgrp docker` # Refresh group membership
`groups` # Verify group membership
`docker run hello-world` # Test Docker without sudo

---

## Troubleshooting

### Flask Database Connection

Ensure `main.py` uses Docker service name:

app.config['SQLALCHEMY_DATABASE_URI'] = 'mysql+pymysql://kidssmart_user:SecurePass123!@database/kidssmart'

---

This README provides clear instructions to set up and run the KidsSmart application in Docker across popular OS platforms, ensuring a consistent development environment.

