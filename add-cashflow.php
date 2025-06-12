<?php
// add-cashflow.php

// 1. MULAI SESSION & CEK AUTENTIKASI
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. KONEKSI DATABASE
require_once "config.php";

// 3. AMBIL ID PROYEK DARI URL DAN VALIDASI
$project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) {
    header("location: home.php");
    exit;
}

// Verifikasi kepemilikan proyek
$sql_verify = "SELECT id FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_verify = $mysqli->prepare($sql_verify)) {
    $stmt_verify->bind_param("ii", $project_id, $user_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();
    if ($stmt_verify->num_rows == 0) {
        // Jika proyek tidak ditemukan atau bukan milik user, redirect
        header("location: home.php");
        exit;
    }
    $stmt_verify->close();
}


// Inisialisasi variabel
$year = $production = $income = $opex = $taxable_income = $net_cashflow = "";
$error_message = "";
$validation_errors = [];

// 4. LOGIKA TAMBAH CASHFLOW SAAT FORM DIKIRIM
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validasi input
    $year = trim($_POST['year']);
    $production = trim($_POST['production']);
    $income = trim($_POST['income']);
    $opex = trim($_POST['opex']);
    $taxable_income = trim($_POST['taxable_income']);
    $net_cashflow = trim($_POST['net_cashflow']);

    if (empty($year) || !ctype_digit($year)) $validation_errors['year'] = "Tahun harus berupa angka yang valid.";
    if (empty($production) || !ctype_digit($production)) $validation_errors['production'] = "Produksi harus berupa angka yang valid.";
    if (empty($income) || !ctype_digit($income)) $validation_errors['income'] = "Pemasukan harus berupa angka yang valid.";
    if (empty($opex) || !ctype_digit($opex)) $validation_errors['opex'] = "Opex harus berupa angka yang valid.";
    if (empty($taxable_income) || !ctype_digit($taxable_income)) $validation_errors['taxable_income'] = "Taxable Income harus berupa angka yang valid.";
    if (empty($net_cashflow) || !ctype_digit($net_cashflow)) $validation_errors['net_cashflow'] = "Net Cashflow harus berupa angka yang valid.";

    // Jika tidak ada error validasi
    if (empty($validation_errors)) {
        $sql = "INSERT INTO cashflows (project_id, year, production, income, opex, taxable_income, net_cashflow, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iiiiiii", $project_id, $year, $production, $income, $opex, $taxable_income, $net_cashflow);
            
            if ($stmt->execute()) {
                header("Location: project-details.php?id=" . $project_id);
                exit();
            } else {
                $error_message = "Gagal menambah data cashflow. Silakan coba lagi.";
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data Cashflow - Kalkulator Investasi Migas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style> body { font-family: 'Inter', sans-serif; } </style>
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
             <a href="project-details.php?id=<?php echo htmlspecialchars($project_id); ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Kembali ke Detail Proyek</a>
            <h1 class="text-3xl font-bold text-gray-800">Tambah Data Arus Kas (Cashflow)</h1>
            <p class="text-gray-500">Isi data untuk satu periode (tahun).</p>
        </header>

        <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
            <?php
            if (!empty($error_message)) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6" role="alert">';
                echo '<span class="block sm:inline">' . $error_message . '</span>';
                echo '</div>';
            }
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?project_id=' . htmlspecialchars($project_id); ?>" method="POST" class="space-y-6">
                <p class="text-sm text-gray-600 bg-blue-50 p-4 rounded-lg">Catatan: `Taxable Income` dan `Net Cashflow` idealnya dihitung otomatis oleh sistem. Untuk saat ini, silakan isi manual.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="year" class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" placeholder="Contoh: 2025" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (isset($validation_errors['year'])) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg">
                        <span class="text-red-500 text-sm mt-1"><?php echo $validation_errors['year'] ?? ''; ?></span>
                    </div>
                    <div>
                        <label for="production" class="block text-sm font-medium text-gray-700">Produksi (unit)</label>
                        <input type="number" id="production" name="production" value="<?php echo htmlspecialchars($production); ?>" placeholder="Contoh: 1000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (isset($validation_errors['production'])) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg">
                         <span class="text-red-500 text-sm mt-1"><?php echo $validation_errors['production'] ?? ''; ?></span>
                    </div>
                     <div>
                        <label for="income" class="block text-sm font-medium text-gray-700">Pemasukan (USD)</label>
                        <input type="number" id="income" name="income" value="<?php echo htmlspecialchars($income); ?>" placeholder="Contoh: 500000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (isset($validation_errors['income'])) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg">
                         <span class="text-red-500 text-sm mt-1"><?php echo $validation_errors['income'] ?? ''; ?></span>
                    </div>
                    <div>
                        <label for="opex" class="block text-sm font-medium text-gray-700">Opex (USD)</label>
                        <input type="number" id="opex" name="opex" value="<?php echo htmlspecialchars($opex); ?>" placeholder="Contoh: 100000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (isset($validation_errors['opex'])) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg">
                         <span class="text-red-500 text-sm mt-1"><?php echo $validation_errors['opex'] ?? ''; ?></span>
                    </div>
                    <div>
                        <label for="taxable_income" class="block text-sm font-medium text-gray-700">Taxable Income (USD)</label>
                        <input type="number" id="taxable_income" name="taxable_income" value="<?php echo htmlspecialchars($taxable_income); ?>" placeholder="Contoh: 400000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (isset($validation_errors['taxable_income'])) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg">
                         <span class="text-red-500 text-sm mt-1"><?php echo $validation_errors['taxable_income'] ?? ''; ?></span>
                    </div>
                     <div>
                        <label for="net_cashflow" class="block text-sm font-medium text-gray-700">Net Cashflow (USD)</label>
                        <input type="number" id="net_cashflow" name="net_cashflow" value="<?php echo htmlspecialchars($net_cashflow); ?>" placeholder="Contoh: 320000" class="mt-1 w-full px-4 py-3 bg-gray-50 border <?php echo (isset($validation_errors['net_cashflow'])) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg">
                         <span class="text-red-500 text-sm mt-1"><?php echo $validation_errors['net_cashflow'] ?? ''; ?></span>
                    </div>
                </div>
                
                <div class="flex justify-end pt-6">
                    <a href="project-details.php?id=<?php echo htmlspecialchars($project_id); ?>" class="bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg hover:bg-gray-300 mr-4 transition">Batal</a>
                    <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">Simpan Data</button>
                </div>
            </form>
        </div>
    </main>
</div>

</body>
</html>
