# 🚀 Deploying PharmaCare POS to Render.com (100% Free)

This guide walks you through deploying the **PharmaCare Pharmacy POS & Ledger System** to [Render.com](https://render.com) for free using Docker and automated Git deployments.

---

## 📋 Prerequisites
1. A free account on [GitHub](https://github.com)
2. A free account on [Render.com](https://render.com)

---

## Step 1: Push Code to Your GitHub Repository

If you haven't already initialized git for this project, run these commands in your PowerShell / Terminal inside `phamacy_pos`:

```bash
git init
git add .
git commit -m "Deploy PharmaCare POS to Render"
git branch -M main
```

Next, create a new **Public** or **Private** repository on GitHub (e.g. `pharmacare-pos`), then connect and push your code:

```bash
git remote add origin https://github.com/<your-username>/pharmacare-pos.git
git push -u origin main
```

---

## Step 2: Deploy on Render

You can deploy using either **Option A (Automated Blueprint - Recommended)** or **Option B (Manual Web Service)**.

---

### 🌟 Option A: Automated Blueprint (Recommended - 1 Click)

1. Log into your [Render Dashboard](https://dashboard.render.com).
2. Click **"Blueprints"** in the top navigation bar.
3. Click **"New Blueprint Instance"**.
4. Connect your GitHub account and select your `pharmacare-pos` repository.
5. Render will automatically detect [`render.yaml`](./render.yaml).
6. Give your Blueprint a group name (e.g., `pharmacare-production`) and select your preferred region (e.g. `Oregon` or `Frankfurt`).
7. Click **"Apply"**.

Render will automatically:
- Provision a **Free Managed PostgreSQL Database** (`pharmacare-db`)
- Build the **Docker Web Service** (`pharmacare-pos`)
- Link the database connection string automatically
- Run initial database migrations & seeders (`admin@pharmacy.com` / `cashier@pharmacy.com`)
- Generate an `APP_KEY` automatically
- Give you a live public URL (e.g., `https://pharmacare-pos.onrender.com`)

---

### 🛠️ Option B: Manual Web Service Setup

If you prefer to configure the Web Service manually:

#### 1. Create a Free Database
- In Render Dashboard, click **New +** > **PostgreSQL**.
- Name: `pharmacare-db`
- Plan: **Free**
- Click **Create Database**.
- Once created, copy the **Internal Database URL** (e.g., `postgres://user:pass@dpg-xxx:5432/db`).

*(Note: If you prefer **MySQL**, you can create a free MySQL database on [TiDB Cloud](https://tidbcloud.com) or [Aiven](https://aiven.io) and copy its connection URL).*

#### 2. Create the Web Service
- Click **New +** > **Web Service**.
- Select **"Build and deploy from a Git repository"** and select your repository.
- **Name**: `pharmacare-pos`
- **Region**: Same as your database (e.g., `Oregon`)
- **Runtime**: **Docker** (Render will automatically detect our `Dockerfile`)
- **Instance Type**: **Free**

#### 3. Configure Environment Variables
Scroll to **"Environment Variables"** and add:

| Key | Value | Notes |
| :--- | :--- | :--- |
| `APP_NAME` | `PharmaCare POS` | Application Name |
| `APP_ENV` | `production` | Production mode |
| `APP_DEBUG` | `false` | Disable debug bar in production |
| `APP_KEY` | *(Generate via `php artisan key:generate --show`)* | Encryption key |
| `APP_URL` | `https://your-service-name.onrender.com` | Your Render public URL |
| `LOG_CHANNEL` | `stderr` | Sends logs to Render dashboard |
| `SESSION_DRIVER` | `cookie` | Zero-disk session storage |
| `CACHE_DRIVER` | `array` | Ephemeral caching |
| `RUN_MIGRATIONS` | `true` | Runs `php artisan migrate --force` at startup |
| `SEED_DATABASE` | `true` | Seeds initial roles, users, and catalog data |
| `DB_CONNECTION` | `pgsql` *(or `mysql`)* | Database driver |
| `DATABASE_URL` | *(Paste Internal DB URL from Step 1)* | Full connection string |

- Click **Create Web Service**.

---

## Step 3: Accessing Your Live Application

Once the deployment finishes building (approx. 2–3 minutes):
1. Click the URL at the top of your Render Web Service page (e.g. `https://pharmacare-pos.onrender.com`).
2. Log in using the seeded credentials:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin** | `admin@pharmacy.com` | `password` |
| **Cashier** | `cashier@pharmacy.com` | `password` |

---

## 💡 Important Tips for Render Free Tier

1. **Spin Down after Inactivity**: Render's free tier spins down web services after 15 minutes of inactivity. When a new request arrives, it takes ~30–50 seconds to spin back up.
2. **Persistence**: The Docker container uses `cookie` sessions and connects to a persistent database, so all sales, drug catalog updates, and customer balances are safely stored in your database.
3. **Running Console Commands**: If you ever need to run artisan commands in production, click the **"Shell"** tab in your Render Web Service dashboard and run:
   ```bash
   php artisan master:index
   php artisan db:seed --force
   ```
