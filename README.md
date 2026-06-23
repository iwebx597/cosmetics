# 🧴 ລະບົບຄຸ້ມຄອງສາງເຄື່ອງສໍາອາງ
### Cosmetics Inventory Management System

ລະບົບຈັດການສາງເຄື່ອງສໍາອາງ ພັດທະນາດ້ວຍ PHP + MySQL ຮອງຮັບການຕິດຕາມສິນຄ້າ, ການນຳເຂົ້າ-ຈ່າຍອອກ, ການແຈ້ງເຕືອນ ແລະ ລາຍງານສະຖິຕິ

---

## 📋 ສ່ວນປະກອບຂອງລະບົບ

| ໂມດູນ | ລາຍລະອຽດ |
|---|---|
| 🏠 ພາບລວມ (Dashboard) | ສະຖິຕິສິນຄ້າ, ກາຟ, ຂໍ້ມູນສຳຄັນ |
| 📦 ຈັດການສິນຄ້າ | ເພີ່ມ / ແກ້ໄຂ / ລຶບຂໍ້ມູນສິນຄ້າ |
| 🗂️ ປະເພດສິນຄ້າ | ຈັດຫຼວດໝູ່ສິນຄ້າ |
| 📏 ໜ່ວຍນັບ | ຈັດການໜ່ວຍນັບ (ກ່ອງ, ອັນ, ແຜ່ນ...) |
| 📍 ບ່ອນຈັດເກັບ | ຈັດການ Location ພາຍໃນສາງ |
| 🚚 ຜູ້ສະໜອງ | ຂໍ້ມູນ Supplier / ຜູ້ສະໜອງ |
| 🛒 ລາຍການສັ່ງຊື້ (PO) | ສ້າງ ແລະ ຕິດຕາມ Purchase Order |
| 📥 ນຳເຂົ້າສິນຄ້າ | ບັນທຶກການຮັບສິນຄ້າເຂົ້າສາງ |
| 📤 ຈ່າຍອອກສິນຄ້າ | ບັນທຶກການຈ່າຍສິນຄ້າອອກ |
| 🔔 ແຈ້ງເຕືອນ | ສິນຄ້າໃກ້ໝົດ, ໝົດອາຍຸ, PO ຄ້າງ |
| 📊 ລາຍງານ / ສະຖິຕິ | ສ້າງລາຍງານ ແລະ ກາຟ |
| 👤 ຈັດການພະນັກງານ | ເພີ່ມ / ຈັດການ User |
| ⚙️ ຕັ້ງຄ່າ | ຕັ້ງຊື່ລະບົບ, ໂລໂກ້, ຮູບ Background |

---

## 🛠️ ຄວາມຕ້ອງການຂອງລະບົບ (Requirements)

| ໂປຣແກຣມ | ເວີຊັ່ນຂັ້ນຕ່ຳ | ດາວໂຫຼດ |
|---|---|---|
| **Docker Desktop** | 4.x ຂຶ້ນໄປ | https://www.docker.com/products/docker-desktop |
| **PHP** | 8.0 ຂຶ້ນໄປ | https://www.php.net/downloads |
| **Git** | ທຸກເວີຊັ່ນ | https://git-scm.com/downloads |

> ⚠️ **ໝາຍເຫດ:** MySQL ແລະ phpMyAdmin ຈະຕິດຕັ້ງຜ່ານ Docker ອັດຕະໂນມັດ ບໍ່ຕ້ອງຕິດຕັ້ງເອງ

---

## 🚀 ຂັ້ນຕອນການຕິດຕັ້ງ (Installation)

### ຂັ້ນຕອນທີ 1 — Clone ໂປຣເຈັກ

```bash
git clone https://github.com/iwebx597/cosmetics.git
cd cosmetics
```

---

### ຂັ້ນຕອນທີ 2 — ສ້າງໄຟລ໌ການເຊື່ອມຕໍ່ Database

ໄຟລ໌ `cmt_db.php` ບໍ່ໄດ້ຢູ່ໃນ GitHub (ເນື່ອງຈາກຄວາມປອດໄພ) ຕ້ອງສ້າງໃໝ່ເອງ:

ສ້າງໄຟລ໌ `cmt_db.php` ໃນ root ຂອງໂປຣເຈັກ:

```php
<?php
$host = "127.0.0.1";
$dbname = "iwebxstu_cosmetics";
$username = "iwebxstu_cosmetics";
$password = "#n21tTxbuNfZj8?q";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ການເຊື່ອມຕໍ່ຜິດພາດ: " . $e->getMessage());
}
```

> ⚠️ **ສຳຄັນ:** ໃຊ້ `127.0.0.1` ແທນ `localhost` ເພື່ອໃຫ້ PHP ເຊື່ອມຕໍ່ຜ່ານ TCP/IP ໄດ້ຖືກຕ້ອງ

---

### ຂັ້ນຕອນທີ 3 — ເລີ່ມ MySQL ດ້ວຍ Docker

```bash
docker compose up -d
```

Docker ຈະ:
- 🐬 ດາວໂຫຼດ MySQL ແລະ phpMyAdmin image
- 🗄️ ສ້າງ database `iwebxstu_cosmetics` ອັດຕະໂນມັດ
- 📥 ນຳເຂົ້າຂໍ້ມູນເລີ່ມຕົ້ນຈາກ `cmt_db.sql`

ລໍຖ້າ ~30 ວິນາທີ ຈົນ container ພ້ອມ ກວດສອບດ້ວຍ:

```bash
docker ps
```

ຕ້ອງເຫັນ `mysql_container` ແລະ `phpmyadmin_container` ມີ status **Up**

---

### ຂັ້ນຕອນທີ 4 — ເລີ່ມ PHP Web Server

```bash
php -S localhost:8000
```

---

### ຂັ້ນຕອນທີ 5 — ເຂົ້າໃຊ້ງານລະບົບ

| ບໍລິການ | URL |
|---|---|
| 🌐 **ລະບົບຫຼັກ** | http://localhost:8000 |
| 🗄️ **phpMyAdmin** | http://localhost:8082 |

---

## 🔐 ຂໍ້ມູນເຂົ້າສູ່ລະບົບ (Login)

### ລະບົບຫຼັກ
| ຊ່ອງ | ຄ່າ |
|---|---|
| Username | `admin` (ຕາມທີ່ຕັ້ງໃນ database) |
| Password | ຕາມທີ່ກຳນົດໃນ `cmt_db.sql` |

### phpMyAdmin
| ຊ່ອງ | ຄ່າ |
|---|---|
| Username | `root` |
| Password | `#n21tTxbuNfZj8?q` |

---

## 🏗️ ໂຄງສ້າງໂປຣເຈັກ (Project Structure)

```
cosmetics/
├── 📄 index.php                  # ໜ້າ Login
├── 📄 cmt_db.php                 # ⚠️ ກຳນົດ DB (ສ້າງເອງ, ບໍ່ຢູ່ໃນ repo)
├── 📄 auth.php                   # ກວດສອບ Session
├── 📄 sidebar.php                # Navigation sidebar
├── 📄 navbar.php                 # Top navbar
├── 📄 docker-compose.yaml        # Docker config (MySQL + phpMyAdmin)
├── 📄 cmt_db.sql                 # ⚠️ ບໍ່ຢູ່ໃນ repo (ຕ້ອງ export ເອງ)
├── 📄 settings.json              # ການຕັ້ງຄ່າລະບົບ (ຊື່, ໂລໂກ້)
│
├── 📂 form_*.php                 # ໜ້າຫຼັກແຕ່ລະໂມດູນ
├── 📂 bootstrap-5.3.8/           # Bootstrap CSS/JS framework
├── 📂 style/                     # Font Noto Sans Lao
├── 📂 logo/                      # ຮູບໂລໂກ້ ແລະ Background
├── 📂 employees/                 # ຮູບພະນັກງານ
└── 📂 uploads/                   # ໄຟລ໌ Upload ຈາກລະບົບ
```

---

## ⚙️ ຂໍ້ມູນ Docker Services

```yaml
# MySQL
Host:     127.0.0.1
Port:     3306
Database: iwebxstu_cosmetics
User:     iwebxstu_cosmetics
Password: #n21tTxbuNfZj8?q

# phpMyAdmin
URL:  http://localhost:8082
User: root
Pass: #n21tTxbuNfZj8?q
```

---

## 🔧 ແກ້ໄຂບັນຫາທົ່ວໄປ (Troubleshooting)

### ❌ `MySQL server has gone away`
- **ສາເຫດ:** `cmt_db.php` ໃຊ້ `localhost` ແທນ `127.0.0.1`
- **ແກ້ໄຂ:** ປ່ຽນ `$host = "localhost"` ເປັນ `$host = "127.0.0.1"`

### ❌ Docker container ບໍ່ start
```bash
# ກວດສອບ logs
docker logs mysql_container

# ລຶບ volume ເກົ່າ ແລ້ວ start ໃໝ່
docker compose down -v
docker compose up -d
```

### ❌ ໜ້າເວັບໂຫຼດບໍ່ຂຶ້ນ
- ກວດສອບ PHP ວ່າ start ຢູ່: `php -S localhost:8000`
- ກວດສອບ port 8000 ບໍ່ถືກໃຊ້ໂດຍໂປຣແກຣມອື່ນ

### ❌ Database ຫວ່າງ (ບໍ່ມີຂໍ້ມູນ)
- ໄຟລ໌ `cmt_db.sql` ບໍ່ຢູ່ໃນ repo ເນື່ອງຈາກ `.gitignore`
- ຕ້ອງ import ຂໍ້ມູນ `.sql` ດ້ວຍ phpMyAdmin ດ້ວຍຕົນເອງ

---

## 📸 ໜ້າຈໍຂອງລະບົບ

| ໜ້ານ | ລາຍລະອຽດ |
|---|---|
| `index.php` | ໜ້າ Login ພ້ອມ background ແລະ ໂລໂກ້ |
| `form_dashboard.php` | Dashboard ສະຖິຕິ ແລະ ກາຟ |
| `form_product.php` | ລາຍການສິນຄ້າທັງໝົດ |
| `form_notification.php` | ແຈ້ງເຕືອນ (ໃກ້ໝົດ, ໝົດອາຍຸ, PO) |
| `form_statistics_report.php` | ລາຍງານ ແລະ ສະຖິຕິ |

---

## 👨‍💻 ຜູ້ພັດທະນາ

พัฒนาโดย **iwebx597**  
Repository: https://github.com/iwebx597/cosmetics
