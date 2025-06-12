<?php
// Mulai session
session_start();

// Jika pengguna sudah login, arahkan ke halaman home
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: home.php");
    exit;
}

// Sisipkan file config.php
require_once "config.php";

// Definisikan variabel dan inisialisasi dengan string kosong
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Memproses data form ketika form disubmit
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Periksa apakah email kosong
    if(empty(trim($_POST["email"]))){
        $email_err = "Silakan masukkan email Anda.";
    } else{
        $email = trim($_POST["email"]);
    }

    // Periksa apakah password kosong
    if(empty(trim($_POST["password"]))){
        $password_err = "Silakan masukkan password Anda.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validasi kredensial
    if(empty($email_err) && empty($password_err)){
        // Siapkan statement select
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";

        if($stmt = $mysqli->prepare($sql)){
            // Bind variabel ke statement sebagai parameter
            $stmt->bind_param("s", $param_email);

            // Set parameter
            $param_email = $email;

            // Eksekusi statement
            if($stmt->execute()){
                // Simpan hasil
                $stmt->store_result();

                // Periksa apakah email ada, jika ya, verifikasi password
                if($stmt->num_rows == 1){
                    // Bind hasil ke variabel
                    $stmt->bind_result($id, $name, $email, $hashed_password);
                    if($stmt->fetch()){
                        if(password_verify($password, $hashed_password)){
                            // Password benar, mulai session baru
                            session_start();

                            // Simpan data di variabel session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["user_name"] = $name;

                            // Arahkan ke halaman home
                            header("location: home.php");
                        } else{
                            // Password tidak valid
                            $login_err = "Email atau password yang Anda masukkan salah.";
                        }
                    }
                } else{
                    // Email tidak ditemukan
                    $login_err = "Email atau password yang Anda masukkan salah.";
                }
            } else{
                echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
            }

            // Tutup statement
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
    <title>Login - Kalkulator Investasi Migas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 m-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Selamat Datang Kembali</h1>
            <p class="text-gray-500 mt-2">Masuk untuk melanjutkan ke proyek Anda.</p>
        </div>
        
        <?php 
        if(!empty($login_err)){
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">';
            echo '<span class="block sm:inline">' . $login_err . '</span>';
            echo '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>"
                       class="w-full px-4 py-3 bg-gray-50 border rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition <?php echo (!empty($email_err)) ? 'border-red-500' : 'border-gray-300'; ?>">
                <span class="text-red-500 text-sm mt-1"><?php echo $email_err; ?></span>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" id="password" name="password"
                       class="w-full px-4 py-3 bg-gray-50 border rounded-lg text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 transition <?php echo (!empty($password_err)) ? 'border-red-500' : 'border-gray-300'; ?>">
                <span class="text-red-500 text-sm mt-1"><?php echo $password_err; ?></span>
            </div>

            <div>
                <button type="submit"
                        class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105">
                    Masuk
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Belum punya akun?
                <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">Daftar di sini</a>
            </p>
        </div>
    </div>

</body>
</html>
