<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}
if ($_SESSION['role'] !== 'admin') {
    if (!isset($_GET['id']) || intval($_GET['id']) !== intval($_SESSION['userid'])) {
        echo "<script>
            alert('❌ ທ່ານບໍ່ມີສິດເຂົ້າເຖິງຂໍ້ມູນຂອງພະນັກງານທ່ານອື່ນ!');
            location='form_dashboard.php';
        </script>";
        exit;
    }
}
require 'cmt_db.php';

$msg = "";
$msg_type = "";

if (isset($_GET['id'])) {
    $emp_id = $_GET['id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM employee WHERE emp_id = :emp_id LIMIT 1");
        $stmt->execute(['emp_id' => $emp_id]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$emp) { die("❌ ບໍ່ພົບຂໍ້ມູນພະນັກງານຄົນນີ້ໃນລະບົບ"); }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: form_employee.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emp_fname = trim($_POST['emp_fname']);
    $emp_lname = trim($_POST['emp_lname']);
    $gender    = $_POST['gender'] ?? null;
    $dob       = $_POST['dob'];
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);
    $position  = $_POST['position'] ?? null;
    $username  = trim($_POST['username']);
    $password  = trim($_POST['password']);

    if (!empty($emp_fname) && !empty($emp_lname) && !empty($username)) {
        try {
            $check_stmt = $conn->prepare("SELECT emp_id FROM employee WHERE username = :username AND emp_id != :emp_id LIMIT 1");
            $check_stmt->execute(['username' => $username, 'emp_id' => $emp_id]);

            if ($check_stmt->rowCount() > 0) {
                $msg = "❌ ຊື່ຜູ້ໃຊ້ (Username) ນີ້ມີຄົນອື່ນໃຊ້ແລ້ວ!";
                $msg_type = "danger";
            } else {
                $filename = $emp['image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
                    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        if (!file_exists('employees')) { mkdir('employees', 0777, true); }
                        $filename = "emp_" . uniqid() . "." . $ext;
                        $target = "employees/" . $filename;
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                            if (!empty($emp['image']) && file_exists("employees/" . $emp['image'])) {
                                unlink("employees/" . $emp['image']);
                            }
                        } else {
                            throw new Exception("ບໍ່ສາມາດຍ້າຍໄຟລ໌ຮູບພາບໄດ້");
                        }
                    } else {
                        throw new Exception("ຟໍແມັດໄຟລ໌ບໍ່ຖືກຕ້ອງ! ອະนຸຍາດ JPG, JPEG, PNG, GIF, WEBP, JFIF");
                    }
                }

                if (!empty($password)) {
                    $secure_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE employee SET emp_fname=:emp_fname, emp_lname=:emp_lname, gender=:gender,
                            dob=:dob, phone=:phone, address=:address, position=:position,
                            username=:username, password=:password, image=:image WHERE emp_id=:emp_id";
                    $params = ['emp_fname'=>$emp_fname,'emp_lname'=>$emp_lname,'gender'=>$gender,
                               'dob'=>$dob,'phone'=>$phone,'address'=>$address,'position'=>$position,
                               'username'=>$username,'password'=>$secure_password,'image'=>$filename,'emp_id'=>$emp_id];
                } else {
                    $sql = "UPDATE employee SET emp_fname=:emp_fname, emp_lname=:emp_lname, gender=:gender,
                            dob=:dob, phone=:phone, address=:address, position=:position,
                            username=:username, image=:image WHERE emp_id=:emp_id";
                    $params = ['emp_fname'=>$emp_fname,'emp_lname'=>$emp_lname,'gender'=>$gender,
                               'dob'=>$dob,'phone'=>$phone,'address'=>$address,'position'=>$position,
                               'username'=>$username,'image'=>$filename,'emp_id'=>$emp_id];
                }

                $conn->prepare($sql)->execute($params);

                $stmt_reload = $conn->prepare("SELECT * FROM employee WHERE emp_id = :emp_id LIMIT 1");
                $stmt_reload->execute(['emp_id' => $emp_id]);
                $emp = $stmt_reload->fetch(PDO::FETCH_ASSOC);

                $msg = "🎉 ແກ້ໄຂຂໍ້ມູນພະນັກງານສຳເລັດແລ້ວ!";
                $msg_type = "success";
            }
        } catch (Exception $e) {
            $msg = "❌ ເກີດຂໍ້ຜິດພາດ: " . $e->getMessage();
            $msg_type = "danger";
        }
    } else {
        $msg = "⚠️ ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບຖ້ວນ!";
        $msg_type = "warning";
    }
}

// Build current image src
$current_img_src = "";
if (!empty($emp['image']) && file_exists("employees/" . $emp['image'])) {
    $current_img_src = "employees/" . $emp['image'];
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ແກ້ໄຂຂໍ້ມູນພະນັກງານ</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #FAF4EB !important;
        }
        .col-md-10 {
            background-color: #FAF4EB !important;
        }
        .custom-navbar {
            margin-bottom: 0 !important;
        }

        /* Page Header */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center;
            justify-content: space-between;
        }
        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }
        
        /* Back Button styled as flat Crimson rect */
        .pg-back-btn {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            border-radius: 0 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 7px 18px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            transition: background 0.15s, color 0.15s !important;
        }

        .pg-back-btn:hover {
            background-color: #5a0f22 !important;
            color: #ffffff !important;
        }

        /* Content Wrapper */
        .pg-content-wrapper {
            padding: 24px !important;
        }

        /* Card */
        .pg-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
            margin-bottom: 24px !important;
        }
        
        .pg-card-header {
            background-color: #7A1530 !important;
            padding: 17px 24px !important;
            border: none !important;
            border-radius: 0 !important;
        }
        
        .pg-card-title {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            margin: 0 !important;
        }

        /* Form Body */
        .pg-card-body {
            padding: 24px !important;
        }

        /* Image Preview section */
        .img-preview-section {
            background-color: #f7f7f7 !important;
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            padding: 20px !important;
            display: flex !important;
            align-items: flex-start !important;
            gap: 24px !important;
            margin-bottom: 20px !important;
        }
        .img-preview-box {
            width: 120px !important;
            height: 120px !important;
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
            background-color: #eeeeee !important;
            color: #666 !important;
            font-size: 12px !important;
            text-align: center !important;
            font-weight: bold !important;
        }
        .img-preview-box img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 0 !important;
        }

        /* Form Labels */
        .pg-label {
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #1a1a1a !important;
            margin-bottom: 6px !important;
            display: block !important;
        }

        /* Inputs */
        .pg-input {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            font-size: 15px !important;
            padding: 10px 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            width: 100% !important;
            box-sizing: border-box !important;
            font-weight: bold !important;
        }
        
        .pg-input:focus {
            outline: none !important;
            border-color: #C9956A !important;
            box-shadow: 0 0 0 2px rgba(201,149,106,0.15) !important;
        }
        
        .pg-input[readonly] {
            background-color: #eeeeee !important;
            color: #777777 !important;
            cursor: not-allowed !important;
            border-color: #dddddd !important;
        }
        
        .pg-select {
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            font-size: 15px !important;
            padding: 10px 14px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            background-color: #ffffff !important;
            color: #1a1a1a !important;
            width: 100% !important;
            box-sizing: border-box !important;
            appearance: auto !important;
            font-weight: bold !important;
        }
        
        .pg-select:focus {
            outline: none !important;
            border-color: #C9956A !important;
            box-shadow: 0 0 0 2px rgba(201,149,106,0.15) !important;
        }
        
        textarea.pg-input {
            resize: vertical !important;
            min-height: 80px !important;
        }

        /* Radio Gender */
        .gender-radio-group {
            display: flex !important;
            gap: 28px !important;
            align-items: center !important;
            padding: 10px 0 !important;
        }
        .gender-radio-label {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-size: 15px !important;
            font-weight: bold !important;
            color: #1a1a1a !important;
            cursor: pointer !important;
        }
        .gender-radio-label input[type="radio"] {
            width: 18px !important;
            height: 18px !important;
            accent-color: #7A1530 !important;
            cursor: pointer !important;
        }

        /* Divider + section label */
        .account-section-divider {
            border-top: 2px solid #7A1530 !important;
            margin: 24px 0 16px !important;
        }
        
        .account-section-title {
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #7A1530 !important;
            text-align: center !important;
            margin-bottom: 6px !important;
        }
        .account-section-hint {
            font-size: 13px !important;
            color: #888 !important;
            text-align: center !important;
            margin-bottom: 20px !important;
            font-weight: bold !important;
        }

        /* Alert bar */
        .pg-alert {
            border-radius: 0 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 12px 18px !important;
            margin-bottom: 16px !important;
            border: none !important;
        }

        /* Bottom divider before action buttons */
        .bottom-divider {
            border-top: 2px solid #7A1530 !important;
            margin: 24px 0 20px !important;
        }

        /* Action Buttons */
        .btn-save {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
        .btn-save:hover { background-color: #5a0f22 !important; }

        .btn-reset {
            background-color: #c62828 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
        .btn-reset:hover { background-color: #b71c1c !important; }

        .form-row {
            display: flex !important;
            gap: 20px !important;
            margin-bottom: 16px !important;
            flex-wrap: wrap !important;
        }
        .form-col-half {
            flex: 1 1 calc(50% - 10px) !important;
            min-width: 200px !important;
        }
        .form-col-full {
            flex: 1 1 100% !important;
        }
        .form-col-third {
            flex: 1 1 calc(33.33% - 14px) !important;
            min-width: 150px !important;
        }
        
        /* Native calendar picker color */
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(19%) sepia(91%) saturate(5464%) hue-rotate(352deg) brightness(85%) contrast(100%);
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 p-0">
<?php include 'navbar.php'; ?>
<style>.custom-navbar { margin-bottom: 0 !important; }</style>

<div class="container-fluid p-0">

    <!-- Page Header -->
    <div class="pg-header-box">
        <h4 class="pg-header-title">ແກ້ໄຂຂໍ້ມູນພະນັກງານ (ລະຫັດ: <?= htmlspecialchars($emp['emp_id']) ?>)</h4>
        <a href="form_employee.php" class="pg-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            ກັບຄືນ
        </a>
    </div>

    <!-- Content -->
    <div class="pg-content-wrapper">

        <!-- Alert message -->
        <?php if(!empty($msg)): ?>
        <div class="pg-alert alert-<?= $msg_type ?>" style="
            background-color: <?= $msg_type == 'success' ? '#e8f5e9' : ($msg_type == 'danger' ? '#ffebee' : '#fff8e1') ?> !important;
            color: <?= $msg_type == 'success' ? '#2e7d32' : ($msg_type == 'danger' ? '#c62828' : '#f57f17') ?> !important;
            border-left: 4px solid <?= $msg_type == 'success' ? '#2e7d32' : ($msg_type == 'danger' ? '#c62828' : '#f57f17') ?> !important;
        ">
            <?= $msg ?>
        </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="pg-card">
            <div class="pg-card-header">
                <h6 class="pg-card-title">ກະລຸນາປ້ອນຂໍ້ມູນພະນັກງານໃຫ້ຄົບຖ້ວນ</h6>
            </div>
            <div class="pg-card-body">
                <form method="POST" action="employee_edit.php?id=<?= htmlspecialchars($emp['emp_id']) ?>" enctype="multipart/form-data">

                    <!-- Image Upload Section -->
                    <div class="img-preview-section">
                        <div class="img-preview-box" id="previewContainer">
                            <?php if(!empty($current_img_src)): ?>
                                <img id="imgPreview" src="<?= htmlspecialchars($current_img_src) ?>" alt="Employee Photo" style="display:block;">
                                <span id="previewText" style="display:none;">ຮູບຕົວຢ່າງ (Preview)</span>
                            <?php else: ?>
                                <img id="imgPreview" src="" alt="" style="display:none;">
                                <span id="previewText">ຮູບຕົວຢ່າງ (Preview)</span>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1;">
                            <label class="pg-label">ຮູບພາບພະນັກງານ (ອັບເດດໃໝ່)</label>
                            <input type="file" name="image" class="pg-input" style="padding:6px 10px !important; background:#fff !important;" accept="image/*" onchange="previewFile(this)">
                            <div style="font-size:13px; color:#666; margin-top:8px; font-weight:600;">(ຮອງຮັບໄຟລ໌ຮູບພາບ JPG, JPEG, PNG, GIF, WEBP, JFIF)</div>
                        </div>
                    </div>

                    <!-- Row: ລະຫັດພະນັກງານ (readonly) + ຊື່ພະນັກງານ -->
                    <div class="form-row">
                        <div class="form-col-half">
                            <label class="pg-label">ລະຫັດພະນັກງານ</label>
                            <input type="text" class="pg-input" value="<?= htmlspecialchars($emp['emp_id']) ?>" readonly>
                        </div>
                        <div class="form-col-half">
                            <label class="pg-label">ຊື່ພະນັກງານ <span style="color:#c62828;">*</span></label>
                            <input type="text" name="emp_fname" class="pg-input" value="<?= htmlspecialchars($emp['emp_fname']) ?>" placeholder="ປ້ອນຊື່ພະນັກງານ" required>
                        </div>
                    </div>

                    <!-- Row: ນາມສະກຸນ + ເພດ -->
                    <div class="form-row">
                        <div class="form-col-half">
                            <label class="pg-label">ນາມສະກຸນ</label>
                            <input type="text" name="emp_lname" class="pg-input" value="<?= htmlspecialchars($emp['emp_lname']) ?>" placeholder="ປ້ອນນາມສະກຸນ">
                        </div>
                        <div class="form-col-half">
                            <label class="pg-label">ເພດຜູ້ຕິດຕໍ່</label>
                            <div class="gender-radio-group">
                                <label class="gender-radio-label">
                                    <input type="radio" name="gender" value="M" <?= $emp['gender'] == 'M' ? 'checked' : '' ?>>
                                    ຊາຍ (M)
                                </label>
                                <label class="gender-radio-label">
                                    <input type="radio" name="gender" value="F" <?= $emp['gender'] == 'F' ? 'checked' : '' ?>>
                                    ຍິງ (F)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Row: ວັນເດືອນປີເກີດ + ເບີໂທລະສັບ -->
                    <div class="form-row">
                        <div class="form-col-half">
                            <label class="pg-label">ວັນເດືອນປີເກີດ</label>
                            <input type="date" name="dob" class="pg-input" value="<?= htmlspecialchars($emp['dob'] ?? '') ?>">
                        </div>
                        <div class="form-col-half">
                            <label class="pg-label">ເບີໂທລະສັບ</label>
                            <input type="text" name="phone" class="pg-input" value="<?= htmlspecialchars($emp['phone'] ?? '') ?>" placeholder="020XXXXXXXXX">
                        </div>
                    </div>

                    <!-- Row: ທີ່ຢູ່ (full width) -->
                    <div class="form-row">
                        <div class="form-col-full">
                            <label class="pg-label">ທີ່ຢູ່ປະຈຸບັນ</label>
                            <textarea name="address" class="pg-input" placeholder="ບ້ານ, ເມືອງ, ແຂວງ"><?= htmlspecialchars($emp['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Divider + Account section -->
                    <div class="account-section-divider"></div>
                    <div class="account-section-title">ข้อมูลการเข้าใช้ระบบ (Account)</div>
                    <div class="account-section-hint">* ຖ້າບໍ່ຕ້ອງການປ່ຽນລະຫັດຜ່ານ ໃຫ້ປະໄວ້ຫວ່າງ</div>

                    <!-- Row: Username + Password + Position -->
                    <div class="form-row">
                        <div class="form-col-third">
                            <label class="pg-label">ຊື່ຜູ້ໃຊ້ (Username) <span style="color:#c62828;">*</span></label>
                            <input type="text" name="username" class="pg-input" value="<?= htmlspecialchars($emp['username']) ?>" placeholder="ປ້ອນຊື່ຜູ້ໃຊ້" required>
                        </div>
                        <div class="form-col-third">
                            <label class="pg-label">ລະຫັດຜ່ານໃໝ່ (Password)</label>
                            <input type="password" name="password" class="pg-input" placeholder="ປ້ອນລະຫັດໃໝ່ຫາກຕ້ອງການປ່ຽน">
                        </div>
                        <div class="form-col-third">
                            <label class="pg-label">ຕຳແໜ່ງ / ສິດທິການໃຊ້ <span style="color:#c62828;">*</span></label>
                            <select name="position" class="pg-select" required>
                                <option value="sales"     <?= $emp['position']=='sales'     ? 'selected':'' ?>>Sales (ຝ່າຍຂາຍ)</option>
                                <option value="warehouse" <?= $emp['position']=='warehouse' ? 'selected':'' ?>>Warehouse (ຝ່າຍສາງ)</option>
                                <option value="admin"     <?= $emp['position']=='admin'     ? 'selected':'' ?>>Admin (ຜູ້ດູແລລະບົບ)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bottom divider -->
                    <div class="bottom-divider"></div>

                    <!-- Action Buttons -->
                    <div style="display:flex; justify-content:center; gap:20px;">
                        <button type="submit" class="btn-save">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            ບັນທຶກການແກ້ໄຂ
                        </button>
                        <a href="form_employee.php" class="btn-reset" style="text-decoration:none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
                            ຍົກເລີກ
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div><!-- end pg-content-wrapper -->
</div>

</div>
</div>
</div>

<script>
function previewFile(input) {
    const file = input.files[0];
    const imgPreview = document.getElementById('imgPreview');
    const previewText = document.getElementById('previewText');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imgPreview.setAttribute('src', e.target.result);
            imgPreview.style.display = 'block';
            if(previewText) previewText.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>