<?php
session_start(); // ເປີດການໃຊ້ງານ session ເພື່ອເຂົ້າເຖິງຂໍ້ມູນທີ່ເກັບໄວ້

// 📌 1. ລ້າງຄ່າຕົວແປ Session ທັງໝົດ
$_SESSION = array();

// 📌 2. ທຳລາຍ Session Cookie ໃນ Browser ຂອງຜູ້ໃຊ້ (ຖ້າມີ) ເພື່ອຄວາມປອດໄພທີ່ສຸດ
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 📌 3. ສັ່ງທຳລາຍ Session ຢູ່ໃນ Server
session_destroy();

// 📌 4. ສົ່ງຜູ້ໃຊ້ກັບໄປໜ້າ Login (index.php) ທັນທີ
header("Location: index.php");
exit;
?>