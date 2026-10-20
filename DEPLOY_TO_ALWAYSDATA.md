# 🚀 Deploying PharmaCare POS to Alwaysdata (100% Free - NO Credit Card Required)

[Alwaysdata](https://www.alwaysdata.com) offers a **100MB free cloud plan forever** that requires **NO credit card or payment info**—just an email address. It includes PHP 8.1/8.2, MySQL, phpMyAdmin, and free SSL.

---

## 📦 What We Have Prepared For You:
1. Ready-to-import database: [`database_dump.sql`](./database_dump.sql) (Contains all tables, products, categories, users & seeders).
2. Ready-to-use configuration: [`.env.alwaysdata.example`](./.env.alwaysdata.example).
3. Root [`.htaccess`](./.htaccess) to route traffic safely to `/public`.

---

## Step 1: Create Your Free Account (1 Minute)
1. Go to **[https://www.alwaysdata.com](https://www.alwaysdata.com)**.
2. Click **"Sign Up"** and select the **Free Plan (100MB)**.
3. Choose an account name (e.g., `mypharmapos`).
   - Your free website URL will be: `https://mypharmapos.alwaysdata.net`.
4. Enter your email and password. **No credit card is asked.**
5. Confirm your email and log in to the **Alwaysdata Administration Panel**.

---

## Step 2: Create the MySQL Database & Import Data
1. In the left menu of the Alwaysdata dashboard, go to **Databases** > **MySQL**.
2. Click **"Add a database"**:
   - **Name**: `pos` (Alwaysdata prefixes it to `mypharmapos_pos`).
   - Click **Submit**.
3. Under **Users**, create a MySQL user or note your default user name and set a password.
4. Under **Permissions**, ensure your user has full read/write rights to the database.
5. In the database list, click the **phpMyAdmin** link:
   - Click on your database `mypharmapos_pos` on the left.
   - Click the **"Import"** tab at the top.
   - Click **"Choose File"** and upload [`database_dump.sql`](./database_dump.sql) from your local folder.
   - Click **"Go"** at the bottom.
   - *All tables, initial medications, seeders, and roles are now live!*

---

## Step 3: Upload the Application Code
You can upload either using **Git (Fastest)** or **ZIP Upload**:

### Option A: Via SSH / Git (Recommended)
1. In the Alwaysdata dashboard, go to **Remote access** > **SSH**. Ensure SSH is **Enabled**.
2. Go to **Web** > **Sites** > click the **Terminal (Web SSH)** icon, or connect with PuTTY / Terminal:
   ```bash
   ssh mypharmapos@ssh-mypharmapos.alwaysdata.net
   ```
3. Inside your home folder, clone your GitHub repository:
   ```bash
   git clone https://github.com/<your-username>/pharmacare-pos.git phamacy_pos
   cd phamacy_pos
   cp .env.alwaysdata.example .env
   ```

### Option B: Via Web File Manager (ZIP)
1. On your Windows machine, zip the `phamacy_pos` folder (exclude `vendor` to keep it tiny).
2. In Alwaysdata dashboard, click **Files** > navigate to your home directory.
3. Click **Upload** and upload your zip file, then click **Extract**.

---

## Step 4: Configure Your Site on Alwaysdata
1. Go to **Web** > **Sites** in the Alwaysdata dashboard.
2. Click the **Edit** (pencil icon) next to your default site (`mypharmapos.alwaysdata.net`):
   - **Type**: Select **PHP**.
   - **PHP version**: Select **8.1** or **8.2**.
   - **Application directory**: `/home/mypharmapos/phamacy_pos`
   - **Working directory (Root)**: `/home/mypharmapos/phamacy_pos/public`
   - **SSL**: Check **Force HTTPS** (Free Let's Encrypt SSL is included).
3. Click **Submit**.

---

## Step 5: Update the `.env` File
In Alwaysdata **Files** (or via SSH), edit `/home/mypharmapos/phamacy_pos/.env`:

```env
APP_NAME="PharmaCare POS"
APP_ENV=production
APP_KEY=base64:aHQcHjOQ0tYd1nb6M6stuCY7p0ylfpc2e3F3T3hN6hc=
APP_DEBUG=false
APP_URL=https://mypharmapos.alwaysdata.net

DB_CONNECTION=mysql
DB_HOST=mysql-mypharmapos.alwaysdata.net
DB_PORT=3306
DB_DATABASE=mypharmapos_pos
DB_USERNAME=mypharmapos
DB_PASSWORD=your_mysql_password
```

---

## Step 6: Test Your Live POS!
Open your browser and visit:
👉 **`https://<your-account>.alwaysdata.net`**

Log in using the seeded default credentials:
- **Admin**: `admin@pharmacy.com` | Password: `password`
- **Cashier**: `cashier@pharmacy.com` | Password: `password`
