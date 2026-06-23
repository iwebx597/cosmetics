<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit;
}

require 'cmt_db.php'; // ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ cmt_db

$msg = "";
$msg_type = "";

// 📌 ຕອນກົດປຸ່ມບັນທຶກຂໍ້ມູນ (ເພີ່ມສິນຄ້າໃໝ່)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_save'])) {
    $pro_id = trim($_POST['pro_id']);
    $pro_name = trim($_POST['pro_name']);
    $pro_brand = trim($_POST['pro_brand']);
    $type_id = $_POST['type_id'];
    $loc_id = $_POST['loc_id'];
    $unit_id = $_POST['unit_id'];
    $qty = intval($_POST['qty']);
    $price = floatval($_POST['price']);
    
    // ຈັດການເລື່ອງຮູບພາບ (pro_img)
    $pro_img = "";
    if (isset($_FILES['pro_img']) && $_FILES['pro_img']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp','jfif'];
        $ext = strtolower(pathinfo($_FILES['pro_img']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // ສ້າງໂຟນເດີ uploads ຖ້າຍັງບໍ່ມີ
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            // ຕັ້ງຊື່ຮູບໃໝ່ໂດຍໃຊ້ time() ປ້ອງກັນຊື່ຊ້ຳ
            $pro_img = "prod_" . time() . "_" . uniqid() . "." . $ext;
            move_uploaded_file($_FILES['pro_img']['tmp_name'], "uploads/" . $pro_img);
        } else {
            $msg = "❌ ຟໍແມັດຮູບພາບບໍ່ຖືກຕ້ອງ! ອະນຸຍາດສະເພาະ JPG, JPEG, PNG, GIF, WEBP, JFIF";
            $msg_type = "danger";
        }
    }

    if (empty($msg) && !empty($pro_id) && !empty($pro_name)) {
        try {
            // ກວດສອບລະຫັດສິນຄ້າຊ້ຳ
            $chk = $conn->prepare("SELECT pro_id FROM product WHERE pro_id = :pro_id");
            $chk->execute(['pro_id' => $pro_id]);
            if ($chk->rowCount() > 0) {
                $msg = "❌ ລະຫັດສິນຄ້ານີ້ [ $pro_id ] ມີຢູ່ໃນລະບົບແລ້ວ! ກະລຸນາໃຊ້ລະຫັດອື່ນ.";
                $msg_type = "danger";
            } else {
                // ບັນທຶກລົງຕາຕະລາງ product
                $sql = "INSERT INTO product (pro_id, pro_name, pro_brand, type_id, loc_id, unit_id, qty, price, pro_img) 
                        VALUES (:pro_id, :pro_name, :pro_brand, :type_id, :loc_id, :unit_id, :qty, :price, :pro_img)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'pro_id' => $pro_id,
                    'pro_name' => $pro_name,
                    'pro_brand' => $pro_brand,
                    'type_id' => $type_id,
                    'loc_id' => $loc_id,
                    'unit_id' => $unit_id,
                    'qty' => $qty,
                    'price' => $price,
                    'pro_img' => $pro_img
                ]);

                echo "<script>alert('🎉 ບັນທຶກຂໍ້ມູນສິນຄ້າໃໝ່ຮຽບຮ້ອຍແລ້ວ!'); location='form_product.php';</script>";
                exit;
            }
        } catch (PDOException $e) {
            $msg = "❌ Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// ດຶງຂໍ້ມູນຄວາມສຳພັນ (Dropdowns)
$types = $conn->query("SELECT * FROM product_type ORDER BY type_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$units = $conn->query("SELECT * FROM unit ORDER BY unit_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$locations = $conn->query("SELECT * FROM location ORDER BY loc_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <link href="style/fonts.css" rel="stylesheet">
    <title>ເພີ່ມຂໍ້ມູນສິນຄ້າໃໝ່</title>
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

        /* Override navbar margin so header is flush */
        .custom-navbar {
            margin-bottom: 0 !important;
        }

        /* Page Header Title (Sand/Beige gradient) */
        .pg-header-box {
            background: linear-gradient(90deg, #ffffff 0%, #C9956A 100%) !important;
            height: 60px !important;
            padding: 0 24px !important;
            margin-bottom: 0 !important;
            border-radius: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
        }

        .pg-header-title {
            color: #1a1a1a !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            margin: 0 !important;
        }

        .pg-back-btn {
            background-color: #ffffff !important;
            color: #333333 !important;
            border: 1px solid #cccccc !important;
            border-radius: 0 !important;
            font-weight: 700 !important;
            font-size: 14px !important;
            padding: 7px 18px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            transition: background 0.15s !important;
        }

        .pg-back-btn:hover {
            background-color: #7A1530 !important;
            color: #ffffff !important;
            border-color: #7A1530 !important;
        }

        /* Content wrapper */
        .pg-content-wrapper {
            padding: 24px !important;
        }

        /* Main Form Card */
        .pg-form-card {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
            background-color: #ffffff !important;
            margin-bottom: 40px !important;
        }

        .pg-form-card-header {
            background: #7A1530 !important;
            padding: 14px 24px !important;
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

        /* Image Upload Box Section */
        .img-upload-section {
            background-color: #eeeeee !important;
            padding: 20px !important;
            border-radius: 0 !important;
            margin-bottom: 24px !important;
            display: flex !important;
            align-items: center !important;
            gap: 24px !important;
        }

        .preview-box {
            width: 120px !important;
            height: 120px !important;
            background-color: #ffffff !important;
            border: 1px solid #dddddd !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
        }

        .preview-box img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        .preview-placeholder {
            font-size: 13px !important;
            color: #666666 !important;
            font-weight: bold !important;
            text-align: center !important;
            line-height: 1.4 !important;
        }

        /* Custom form controls - material style */
        .form-label-custom {
            font-weight: 700 !important;
            font-size: 14px !important;
            color: #333333 !important;
            margin-bottom: 8px !important;
            display: block !important;
        }

        .form-control-custom {
            width: 100% !important;
            border-radius: 0 !important;
            border: 1px solid #cccccc !important;
            background-color: #eeeeee !important;
            padding: 10px 14px !important;
            font-size: 15px !important;
            outline: none !important;
            box-shadow: none !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .form-control-custom:focus {
            background-color: #ffffff !important;
            border-color: #C9956A !important;
        }

        /* Thick horizontal divider */
        .form-divider {
            border-bottom: 4px solid #444444 !important;
            margin-top: 16px !important;
            margin-bottom: 24px !important;
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
            font-size: 16px !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .btn-save:hover {
            background-color: #5a0f22 !important;
        }

        .btn-cancel {
            background-color: #c62828 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 10px 32px !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            font-family: 'noto-sans-lao-regular', sans-serif !important;
        }

        .btn-cancel:hover {
            background-color: #b71c1c !important;
        }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">

<?php include 'sidebar.php'; ?>

<div class="col-md-10 p-0">
<?php include 'navbar.php'; ?>
<!-- Override navbar margin so header is flush against it -->
<style>.custom-navbar { margin-bottom: 0 !important; }</style>

<div class="container-fluid p-0">

    <!-- Page Header -->
    <div class="pg-header-box">
        <h4 class="pg-header-title">ເພີ່ມຂໍ້ມູນສິນຄ້າໃໝ່</h4>
        <a href="form_product.php" class="pg-back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            ກັບຄືນ
        </a>
    </div>

    <!-- Content wrapper -->
    <div class="pg-content-wrapper">

        <?php if(!empty($msg)): ?>
            <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm rounded-0 mb-4"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="pg-form-card">
            <div class="pg-form-card-header">
                <h6 class="pg-form-card-title">ກະລຸນາປ້ອນລາຍລະອຽດສິນຄ້າໃຫ້ຄົບຖ້ວນ</h6>
            </div>
            
            <div class="pg-form-body">
                <form method="POST" action="product_add.php" enctype="multipart/form-data">
                    
                    <!-- Image Upload -->
                    <div class="img-upload-section">
                        <div class="preview-box" id="previewContainer">
                            <div class="preview-placeholder" id="previewPlaceholder">Product Preview</div>
                            <img src="" id="imgPreview" style="display:none;" alt="Product Preview">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label-custom">ຮູບພາບສິນຄ້າ</label>
                            <input type="file" name="pro_img" class="form-control-custom bg-white border" accept="image/*" onchange="previewFile(this)">
                            <small class="text-muted mt-2 d-block">(ຮອງຮັບໄຟລ໌ຮູບພາບ JPG, JPEG, PNG, GIF, WEBP, JFIF)</small>
                        </div>
                    </div>

                    <!-- Input Rows -->
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label-custom">ລະຫັດສินຄ້າ (Barcode/ID)</label>
                            <input type="text" name="pro_id" class="form-control-custom" placeholder="ປ້ອນ ຫຼື ຍິງບາໂຄດ" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">ຊື່ສິນຄ້າ</label>
                            <input type="text" name="pro_name" class="form-control-custom" placeholder="ປ້ອນຊື່ສິນຄ້າ" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label-custom">ແບຣນ/ຍີ່ຫໍ້</label>
                            <input type="text" name="pro_brand" class="form-control-custom" placeholder="ປ້ອນແບຣນ/ຍີ່ຫໍ້ສິນຄ້າ">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">ປະເພດສິນຄ້າ</label>
                            <select name="type_id" class="form-control-custom form-select" required>
                                <option value="">--ເລືອກປະເພດ--</option>
                                <?php foreach($types as $t): ?>
                                    <option value="<?= $t['type_id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label-custom">ຫົວໜ່ວຍນັບ</label>
                            <select name="unit_id" class="form-control-custom form-select" required>
                                <option value="">--ເລືອກຫົວໜ່ວຍນັບ--</option>
                                <?php foreach($units as $u): ?>
                                    <option value="<?= $u['unit_id'] ?>"><?= htmlspecialchars($u['unit_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">ບ່ອນຈັດເກັບ (Location)</label>
                            <select name="loc_id" class="form-control-custom form-select" required>
                                <option value="">--ເລືອກບ່ອນຈັດເກັບ (Location)--</option>
                                <?php foreach($locations as $l): ?>
                                    <option value="<?= $l['loc_id'] ?>"><?= htmlspecialchars($l['loc_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label-custom">ຈຳນວນເບື້ອງຕົ້ນ</label>
                            <input type="number" name="qty" class="form-control-custom text-center" value="0" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-custom">ລາຄາ (LAK)</label>
                            <input type="number" name="price" class="form-control-custom text-center" value="0" step="0.01">
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="form-divider"></div>

                    <!-- Action Buttons -->
                    <div class="btn-submit-group">
                        <button type="submit" name="btn_save" class="btn-save">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            ບັນທຶກ
                        </button>
                        <a href="form_product.php" class="btn-cancel">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            ຍົກເລີກ
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </div><!-- end pg-content-wrapper -->
</div><!-- end container-fluid -->

</div>
</div>
</div>

<script>
function previewFile(input) {
    const file = input.files[0];
    const preview = document.getElementById('imgPreview');
    const placeholder = document.getElementById('previewPlaceholder');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
        placeholder.style.display = 'block';
    }
}
</script>
</body>
</html>