<?php
// register.php

// 1. MULAI SESSION
session_start();

// 2. KONEKSI DATABASE
// Baris ini sangat penting untuk disertakan
require_once "config.php";

// Definisikan variabel dan inisialisasi dengan string kosong
$name = $email = $password = "";
$name_err = $email_err = $password_err = "";
$error_message = "";

// 3. LOGIKA REGISTRASI SAAT FORM DIKIRIM
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi nama
    if(empty(trim($_POST["name"]))){
        $name_err = "Silakan masukkan nama Anda.";
    } else{
        $name = trim($_POST["name"]);
    }

    // Validasi email
    if(empty(trim($_POST["email"]))){
        $email_err = "Silakan masukkan email Anda.";
    } else {
        // Periksa apakah email sudah ada
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if($stmt_check = $mysqli->prepare($sql_check)){
            $stmt_check->bind_param("s", $param_email_check);
            $param_email_check = trim($_POST["email"]);
            
            if($stmt_check->execute()){
                $stmt_check->store_result();
                if($stmt_check->num_rows == 1){
                    $email_err = "Email ini sudah terdaftar.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                $error_message = "Oops! Terjadi kesalahan. Silakan coba lagi.";
            }
            $stmt_check->close();
        }
    }

    // Validasi password
    if(empty(trim($_POST["password"]))){
        $password_err = "Silakan masukkan password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password minimal harus 6 karakter.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Periksa error input sebelum memasukkan ke database
    if(empty($name_err) && empty($email_err) && empty($password_err) && empty($error_message)){
        
        // 4. QUERY UNTUK MENYIMPAN PENGGUNA BARU
        $sql = "INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
         
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("sss", $param_name, $param_email, $param_password);
            
            // Set parameter
            $param_name = $name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Enkripsi password
            
            if($stmt->execute()){
                // Redirect ke halaman login setelah berhasil
                header("location: login.php");
                exit();
            } else{
                $error_message = "Registrasi gagal, silakan coba lagi.";
            }
            $stmt->close();
        }
    }
    
    // Tutup koneksi
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Kalkulator Investasi Migas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 m-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Buat Akun Baru</h1>
            <p class="text-gray-500 mt-2">Mulai analisis investasi Anda hari ini.</p>
        </div>

        <?php
        if (!empty($error_message)) {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">';
            echo '<span class="block sm:inline">' . $error_message . '</span>';
            echo '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" class="w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($name_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="text-red-500 text-sm mt-1"><?php echo $name_err; ?></span>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($email_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="text-red-500 text-sm mt-1"><?php echo $email_err; ?></span>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password" class="w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($password_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <span class="text-red-500 text-sm mt-1"><?php echo $password_err; ?></span>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                    Daftar
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Sudah punya akun?
                <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Masuk di sini</a>
            </p>
        </div>
    </div>

</body>
</html>
