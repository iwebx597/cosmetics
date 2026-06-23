<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}
// Only admin can access settings
if ($_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('❌ ທ່ານບໍ່ມີສິດເຂົ້າເຖິງໜ້ານີ້! ສະເພາະ Admin ເທົ່ານັ້ນ.');
        location='form_dashboard.php';
    </script>";
    exit;
}

$settings_file = __DIR__ . '/settings.json';

// Load current settings
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true) ?? [];
}
$system_name = $settings['system_name'] ?? 'ລະບົບສາງເຄື່ອງສໍາอາງ';
$system_logo = $settings['system_logo'] ?? '';
$login_name  = $settings['login_name'] ?? 'ລະບົບຄຸ້ມຄອງສາງເຄື່ອງສໍາອາງ';
$login_logo  = $settings['login_logo'] ?? '';
$login_bg    = $settings['login_bg'] ?? 'logo/1750677368957.jpg';

$msg = '';
$msg_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_system_name = trim($_POST['system_name'] ?? '');
    $new_login_name  = trim($_POST['login_name'] ?? '');
    
    $new_system_logo = $system_logo;
    $new_login_logo  = $login_logo;
    $new_login_bg    = $login_bg;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];

    if (!file_exists('logo')) {
        mkdir('logo', 0777, true);
    }

    // 1. Upload system_logo
    if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'logo_' . time() . '_' . rand(100,999) . '.' . $ext;
            $upload_path = __DIR__ . '/logo/' . $new_filename;
            if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $upload_path)) {
                $new_system_logo = 'logo/' . $new_filename;
            }
        }
    }

    // 2. Upload login_logo
    if (isset($_FILES['login_logo']) && $_FILES['login_logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['login_logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'login_logo_' . time() . '_' . rand(100,999) . '.' . $ext;
            $upload_path = __DIR__ . '/logo/' . $new_filename;
            if (move_uploaded_file($_FILES['login_logo']['tmp_name'], $upload_path)) {
                $new_login_logo = 'logo/' . $new_filename;
            }
        }
    }

    // 3. Upload login_bg
    if (isset($_FILES['login_bg']) && $_FILES['login_bg']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['login_bg']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_filename = 'login_bg_' . time() . '_' . rand(100,999) . '.' . $ext;
            $upload_path = __DIR__ . '/logo/' . $new_filename;
            if (move_uploaded_file($_FILES['login_bg']['tmp_name'], $upload_path)) {
                $new_login_bg = 'logo/' . $new_filename;
            }
        }
    }

    // Save
    $new_settings = [
        'system_name' => $new_system_name ?: $system_name,
        'system_logo' => $new_system_logo,
        'login_name'  => $new_login_name ?: $login_name,
        'login_logo'  => $new_login_logo,
        'login_bg'    => $new_login_bg
    ];

    file_put_contents($settings_file, json_encode($new_settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    
    $system_name = $new_settings['system_name'];
    $system_logo = $new_settings['system_logo'];
    $login_name  = $new_settings['login_name'];
    $login_logo  = $new_settings['login_logo'];
    $login_bg    = $new_settings['login_bg'];

    $msg = 'ບັນທຶກການຕັ້ງຄ່າສຳເລັດແລ້ວ!';
    $msg_type = 'success';
}
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ຕັ້ງຄ່າລະບົບ</title>
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
        .custom-navbar { margin-bottom: 0 !important; }

        /* Page Header Title (Sand/Beige gradient) */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            margin-bottom: 0 !important;
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

        /* Settings Form Card */
        .pg-form-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
            margin-bottom: 24px !important;
            max-width: 960px;
            margin: 0 auto;
        }

        .pg-form-card-header {
            background: #7A1530 !important;
            padding: 17px 24px !important;
            border: none !important;
            border-radius: 0 !important;
        }

        .pg-form-card-title {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            margin: 0 !important;
        }

        .pg-form-body {
            padding: 24px !important;
        }

        /* Form Labels */
        .form-label-custom {
            font-weight: 700 !important;
            font-size: 15px !important;
            color: #1a1a1a !important;
            margin-bottom: 10px !important;
            display: block !important;
            text-align: left !important;
        }

        /* Input Controls */
        .form-control-custom {
            display: block !important;
            width: 100% !important;
            border-radius: 0 !important;
            border: 1px solid #cccccc !important;
            padding: 10px 14px !important;
            font-size: 15px !important;
            outline: none !important;
            box-shadow: none !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
            font-weight: bold !important;
            color: #1a1a1a !important;
            box-sizing: border-box !important;
        }

        .form-control-custom[type="text"] {
            background-color: #eeeeee !important;
        }

        .form-control-custom[type="text"]:focus {
            background-color: #ffffff !important;
            border-color: #C9956A !important;
        }

        .form-control-custom[type="file"] {
            background-color: #ffffff !important;
        }
        
        .form-control-custom[type="file"]:focus {
            border-color: #C9956A !important;
        }

        /* Thick separator line */
        .thick-divider {
            border: none !important;
            border-top: 5px solid #333333 !important;
            opacity: 1 !important;
            margin: 30px 0 20px 0 !important;
        }

        /* Button group */
        .btn-submit-group {
            display: flex !important;
            justify-content: center !important;
            gap: 16px !important;
        }

        .btn-save {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-weight: 700 !important;
            font-size: 15px !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .btn-save:hover {
            background-color: #5a0f22 !important;
        }

        /* Alert bar */
        .pg-alert {
            border-radius: 0 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 12px 18px !important;
            margin-bottom: 20px !important;
            border: none !important;
            max-width: 960px;
            margin-left: auto;
            margin-right: auto;
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
        <h4 class="pg-header-title">ຕັ້ງຄ່າລະບົບ (System Settings)</h4>
        <a href="form_dashboard.php" class="pg-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            ກັບຄືນໜ້າຫຼັກ
        </a>
    </div>

    <div class="pg-content-wrapper">

        <!-- Alert messages -->
        <?php if (!empty($msg)): ?>
        <div class="pg-alert" style="
            background-color: <?= $msg_type == 'success' ? '#e8f5e9' : '#ffebee' ?> !important;
            color: <?= $msg_type == 'success' ? '#2e7d32' : '#c62828' ?> !important;
            border-left: 4px solid <?= $msg_type == 'success' ? '#2e7d32' : '#c62828' ?> !important;
        ">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="pg-form-card">
            <div class="pg-form-card-header">
                <h6 class="pg-form-card-title">ຕັ້ງຄ່າຂໍ້ມູນ ແລະ ໂລໂກ້ຂອງລະບົບ</h6>
            </div>
            
            <div class="pg-form-body">
                <form method="POST" action="form_settings.php" enctype="multipart/form-data">
                    
                    <!-- 1. System Name -->
                    <div class="mb-4">
                        <label class="form-label-custom">ຊື່ລະບົບ (System Name)</label>
                        <input type="text" name="system_name" class="form-control-custom"
                               value="<?= htmlspecialchars($system_name) ?>"
                               placeholder="ກະລຸນາໃສ່ຊື່ລະບົບ..."
                               maxlength="80" required>
                    </div>

                    <!-- 2. System Logo -->
                    <div class="mb-4">
                        <label class="form-label-custom">ອັບໂຫລດໂລໂກ້ໃໝ່ (Upload New Logo)</label>
                        <input type="file" name="system_logo" class="form-control-custom" accept="image/*">
                        <div style="font-size:13px; color:#666; margin-top:8px; font-weight:600;">(ຮອງຮັບໄຟລ໌ຮູບພາບ JPG, JPEG, PNG, GIF, WEBP, JFIF)</div>
                    </div>

                    <!-- 3. Login Name -->
                    <div class="mb-4">
                        <label class="form-label-custom">ຊື່ລະບົບ (System Name)ໃນໜ້າ Login</label>
                        <input type="text" name="login_name" class="form-control-custom"
                               value="<?= htmlspecialchars($login_name) ?>"
                               placeholder="ກະລຸນາໃສ່ຊື່ລະບົບໃນໜ້າ Login..."
                               maxlength="80" required>
                    </div>

                    <!-- 4. Login Logo -->
                    <div class="mb-4">
                        <label class="form-label-custom">ອັບໂຫລດໂລໂກ້ໃໝ່ (Upload New Logo)ໃນໜ້າ Login</label>
                        <input type="file" name="login_logo" class="form-control-custom" accept="image/*">
                        <div style="font-size:13px; color:#666; margin-top:8px; font-weight:600;">(ຮອງຮັບໄຟລ໌ຮູບພາບ JPG, JPEG, PNG, GIF, WEBP, JFIF)</div>
                    </div>

                    <!-- 5. Login Background -->
                    <div class="mb-4">
                        <label class="form-label-custom">ອັບໂຫລດຮູບພາບພື້ນຫຼັງໃໝ່ (Upload New BG Pic)ໃນໜ້າ Login</label>
                        <input type="file" name="login_bg" class="form-control-custom" accept="image/*">
                        <div style="font-size:13px; color:#666; margin-top:8px; font-weight:600;">(ຮອງຮັບໄຟລ໌ຮູບພາບ JPG, JPEG, PNG, GIF, WEBP, JFIF)</div>
                    </div>

                    <!-- Thick Separator Line -->
                    <hr class="thick-divider">

                    <!-- Buttons Group -->
                    <div class="btn-submit-group">
                        <button type="submit" class="btn-save">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            ບັນທຶກການຕັ້ງຄ່າ
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

</div>
</div>
</div>
</body>
</html>
