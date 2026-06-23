<?php
session_start();
$settings_file = __DIR__ . '/settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true) ?? [];
}
$login_name = $settings['login_name'] ?? 'ລະບົບຄຸ້ມຄອງສາງເຄື່ອງສໍາອາງ';
$login_logo = $settings['login_logo'] ?? '';
$login_bg   = $settings['login_bg'] ?? 'logo/1750677368957.jpg';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <link href="style/fonts.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cosmetis - Login</title>
    <link href="bootstrap-5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="bootstrap-5.3.8/dist/js/bootstrap.min.js"></script>
    <script src="bootstrap-5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: linear-gradient(rgba(239, 239, 239, 0.75), rgba(174, 63, 115, 0.75)), url('<?= htmlspecialchars($login_bg) ?>') no-repeat center center fixed !important;
            background-size: cover !important;
            font-family: 'noto-sans-lao-regular', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            padding: 60px;
            box-sizing: border-box;
        }
        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 900px;
        }
        .login-card {
            display: flex !important;
            width: 100%;
            background: none;
            border: none;
            border-radius: 0px !important;
            overflow: hidden;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
            align-items: stretch;
        }
        .left-box {
            background-color: #474d57;
            color: #ffffff;
            width: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px 30px;
            border-right: 5px solid #ffffff;
            text-align: center;
            min-height: 400px;
        }
        .logo-placeholder {
            width: 120px;
            height: 120px;
            background-color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: Arial, sans-serif;
            font-size: 26px;
            font-weight: bold;
            color: #464646;
            margin-bottom: 10px;
            border-radius: 0px !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .left-title {
            font-family: 'noto-sans-lao-bold', sans-serif;
            font-size: 22px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }
        .powered-by {
            font-family: 'noto-sans-lao-regular', sans-serif;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            margin: 0;
            line-height: 1.6;
        }
        .right-box {
            background-color: #7A1530;
            color: #ffffff;
            width: 50%;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            padding: 45px;
            box-sizing: border-box;
        }
        .right-title {
            font-family: 'noto-sans-lao-bold', sans-serif;
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .form-label {
            font-family: 'noto-sans-lao-bold', sans-serif;
            font-size: 15px;
            color: #ffffff;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .input-group-custom {
            position: relative;
            margin-bottom: 0;
        }
        .form-control-custom {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 0px !important;
            background-color: #ffffff;
            color: #333333;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-control-custom:focus {
            border-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }
        .form-control-custom::placeholder {
            color: #c0c0c0;
        }
        #passwordField {
            padding-right: 46px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #b0b0b0;
            display: flex;
            align-items: center;
            transition: color 0.2s ease;
        }
        .password-toggle:hover {
            color: #777777;
        }
        .btn-login-custom {
            width: 100%;
            background-color: #ffffff;
            color: #464646;
            border: none;
            border-radius: 0px !important;
            padding: 12px;
            font-family: 'noto-sans-lao-bold', sans-serif;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-login-custom:hover {
            background-color: #f8f9fa;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            transform: translateY(-1px);
        }
        .btn-login-custom:active {
            transform: translateY(1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }
        .copyright-text {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 100%;
            color: #ffffff;
            text-align: center;
            font-size: 14px;
            font-family: Arial, sans-serif;
            pointer-events: none;
        }        
        /* Responsive design */
        @media (max-width: 768px) {
            body {
                padding: 20px;
                align-items: flex-start;
            }
            .main-container {
                flex-direction: column;
            }
            .login-card {
                flex-direction: column;
                border-radius: 0px !important;
            }
            .left-box {
                width: 100%;
                border-right: none;
                border-bottom: 2px solid #ffffff;
                min-height: auto;
                padding: 35px 20px;
            }
            .right-box {
                width: 100%;
                min-height: auto;
                padding: 35px 20px;
            }
            .copyright-text {
                position: static;
                margin-top: 20px;
                padding-bottom: 10px;
                pointer-events: auto;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="login-card">

        <div class="left-box">
            <div class="logo-placeholder" style="overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #ffffff; border-radius: 0;">
                <?php if (!empty($login_logo) && file_exists($login_logo)): ?>
                    <img src="<?= htmlspecialchars($login_logo) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                <?php else: ?>
                    Logo
                <?php endif; ?>
            </div>
            <div class="left-title"><?= htmlspecialchars($login_name) ?></div>
            <div class="powered-by">Powered by : ທ້າວ ບຸນມີ ພົມມະຈັນ,ທ້າວ ຄູວິຽງ ຄົວຈັນວົງ,ທ້າວ ຂັນໄລ ມະນີວົງ</div>
        </div>

        <div class="right-box">
            <div style="width: 100%; margin-top: auto; margin-bottom: auto;">
                <h3 class="right-title">ເຂົ້າສູ່ລະບົບ</h3>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger text-center py-2 mb-3" style="border-radius: 0;">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="login_check.php" style="display:flex; flex-direction:column;">

                    <div style="margin-bottom: 8px;">
                        <label class="form-label">ຊື່ຜູ້ໃຊ້</label>
                        <div class="input-group-custom">
                            <input type="text" name="username" class="form-control-custom" placeholder="ກະ​ລຸ​ນາ​ປ້ອນ​ຊື່​ຜູ້​ໃຊ້" required>
                        </div>
                    </div>

                   
                    <div style="margin-bottom: 35px;">
                        <label class="form-label">ລະຫັດຜ່ານ</label>
                        <div class="input-group-custom">
                            <input type="password" name="password" id="passwordField" class="form-control-custom" placeholder="ກະ​ລຸ​ນາ​ປ້ອນ​ລະ​ຫັດ​ຜ່ານ" required>
                            <span class="password-toggle" onclick="togglePasswordVisibility()">
                                <!-- Eye icon -->
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                                    <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                                    <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn-login-custom">
                            <!-- Icon: Sign-in circle -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-right-circle-fill" viewBox="0 0 16 16">
                                <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0M4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5z"/>
                            </svg>
                            ເຂົ້າສູ່ລະບົບ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- COPYRIGHT — flows below card in mobile, absolute on desktop -->
    <div class="copyright-text">
        Copyright © 2026 CSMS All rights reserved.
    </div>

</div>

<script>
function togglePasswordVisibility() {
    var passwordField = document.getElementById("passwordField");
    var eyeIcon = document.getElementById("eyeIcon");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        // Slash eye icon SVG
        eyeIcon.innerHTML = '<path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486z"/>' +
            '<path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>' +
            '<path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/>';
    } else {
        passwordField.type = "password";
        // Normal eye icon SVG
        eyeIcon.innerHTML = '<path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>' +
            '<path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>';
    }
}
</script>

</body>
</html>