<?php
// create-project.php

// 1. MULAI SESSION & CEK AUTENTIKASI
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. KONEKSI DATABASE
require_once "config.php";

// Definisikan variabel dan inisialisasi dengan string kosong
$name = $site_manager = $invest_capital = $invest_noncapital = $tax = $depreciation = "";
$name_err = $site_manager_err = $invest_capital_err = $invest_noncapital_err = $tax_err = $depreciation_err = "";
$error_message = "";

// 3. LOGIKA TAMBAH PROYEK
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi Nama Proyek
    if (empty(trim($_POST['name']))) {
        $name_err = "Nama proyek tidak boleh kosong.";
    } else {
        $name = trim($_POST['name']);
    }

    // Validasi Site Manager
    if (empty(trim($_POST['site_manager']))) {
        $site_manager_err = "Nama site manager tidak boleh kosong.";
    } else {
        $site_manager = trim($_POST['site_manager']);
    }

    // Validasi Investasi Modal
    if (empty(trim($_POST['invest_capital']))) {
        $invest_capital_err = "Investasi modal tidak boleh kosong.";
    } elseif (!ctype_digit(trim($_POST['invest_capital']))) {
        $invest_capital_err = "Mohon masukkan angka yang valid.";
    } else {
        $invest_capital = trim($_POST['invest_capital']);
    }

    // Validasi Investasi Non-Modal
    if (empty(trim($_POST['invest_noncapital']))) {
        $invest_noncapital_err = "Investasi non-modal tidak boleh kosong.";
    } elseif (!ctype_digit(trim($_POST['invest_noncapital']))) {
        $invest_noncapital_err = "Mohon masukkan angka yang valid.";
    } else {
        $invest_noncapital = trim($_POST['invest_noncapital']);
    }

    // Validasi Pajak
    if (empty(trim($_POST['tax']))) {
        $tax_err = "Pajak tidak boleh kosong.";
    } elseif (!is_numeric(trim($_POST['tax']))) {
        $tax_err = "Mohon masukkan angka yang valid.";
    } else {
        $tax = trim($_POST['tax']);
    }

    // Validasi Depresiasi
    if (empty(trim($_POST['depreciation']))) {
        $depreciation_err = "Depresiasi tidak boleh kosong.";
    } elseif (!is_numeric(trim($_POST['depreciation']))) {
        $depreciation_err = "Mohon masukkan angka yang valid.";
    } else {
        $depreciation = trim($_POST['depreciation']);
    }


    // Periksa jika tidak ada error validasi
    if (empty($name_err) && empty($site_manager_err) && empty($invest_capital_err) && empty($invest_noncapital_err) && empty($tax_err) && empty($depreciation_err)) {

        // 4. QUERY UNTUK MENYIMPAN PROYEK BARU
        $sql = "INSERT INTO projects (name, site_manager, invest_capital, invest_noncapital, tax, depreciation, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        if ($stmt = $mysqli->prepare($sql)) {
            // Bind parameter
            $stmt->bind_param("ssiiiii", $param_name, $param_sm, $param_ic, $param_inc, $param_tax, $param_dep, $param_uid);

            // Set parameter
            $param_name = $name;
            $param_sm = $site_manager;
            $param_ic = $invest_capital;
            $param_inc = $invest_noncapital;
            $param_tax = $tax;
            $param_dep = $depreciation;
            $param_uid = $user_id;

            // Eksekusi statement
            if ($stmt->execute()) {
                // Jika berhasil, redirect ke halaman home
                header("Location: home.php");
                exit();
            } else {
                $error_message = "Gagal membuat proyek. Silakan coba lagi.";
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
    <title>Buat Proyek Baru - Kalkulator Investasi Migas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b">
                <h2 class="text-xl font-bold text-blue-600">Kalkulator Migas</h2>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="home.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-home mr-3"></i> Dashboard
                </a>
            </nav>
            <div class="p-4 border-t">
                <a href="logout.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-3"></i> Keluar
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="mb-8">
                <a href="home.php" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard</a>
                <h1 class="text-3xl font-bold text-gray-800">Buat Proyek Investasi Baru</h1>
                <p class="text-gray-500">Isi detail di bawah ini untuk memulai analisis proyek baru.</p>
            </header>

            <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
                <?php
                if (!empty($error_message)) {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">';
                    echo '<span class="block sm:inline">' . $error_message . '</span>';
                    echo '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nama Proyek</label>
                            <input type="text" id="name" name="name" value="<?php echo $name; ?>" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($name_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-red-500 text-sm mt-1"><?php echo $name_err; ?></span>
                        </div>
                        <div>
                            <label for="site_manager" class="block text-sm font-medium text-gray-700">Site Manager</label>
                            <input type="text" id="site_manager" name="site_manager" value="<?php echo $site_manager; ?>" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($site_manager_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-red-500 text-sm mt-1"><?php echo $site_manager_err; ?></span>
                        </div>
                    </div>

                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800">Parameter Keuangan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                            <div>
                                <label for="invest_capital" class="block text-sm font-medium text-gray-700">Investasi Modal (USD)</label>
                                <input type="number" id="invest_capital" name="invest_capital" value="<?php echo $invest_capital; ?>" placeholder="Contoh: 5000000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($invest_capital_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="text-red-500 text-sm mt-1"><?php echo $invest_capital_err; ?></span>
                            </div>
                            <div>
                                <label for="invest_noncapital" class="block text-sm font-medium text-gray-700">Investasi Non-Modal (USD)</label>
                                <input type="number" id="invest_noncapital" name="invest_noncapital" value="<?php echo $invest_noncapital; ?>" placeholder="Contoh: 1500000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($invest_noncapital_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="text-red-500 text-sm mt-1"><?php echo $invest_noncapital_err; ?></span>
                            </div>
                            <div>
                                <label for="tax" class="block text-sm font-medium text-gray-700">Pajak (%)</label>
                                <input type="number" id="tax" name="tax" step="any" value="<?php echo $tax; ?>" placeholder="Contoh: 20" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($tax_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="text-red-500 text-sm mt-1"><?php echo $tax_err; ?></span>
                            </div>
                            <div>
                                <label for="depreciation" class="block text-sm font-medium text-gray-700">Depresiasi (%)</label>
                                <input type="number" id="depreciation" name="depreciation" step="any" value="<?php echo $depreciation; ?>" placeholder="Contoh: 10" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (!empty($depreciation_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <span class="text-red-500 text-sm mt-1"><?php echo $depreciation_err; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6">
                        <a href="home.php" class="bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg hover:bg-gray-300 mr-4 transition">Batal</a>
                        <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">Simpan Proyek</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>

</html>