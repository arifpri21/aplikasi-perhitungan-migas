<?php
// project-details.php

// 1. MULAI SESSION & CEK AUTENTIKASI
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. KONEKSI DATABASE
require_once "config.php";

// 3. AMBIL ID PROYEK DARI URL
$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;

// Jika tidak ada ID, redirect ke home
if (empty($project_id) || !ctype_digit($project_id)) {
    header("location: home.php");
    exit;
}

// Inisialisasi variabel
$project = null;
$cashflows = [];

// 4. AMBIL DETAIL PROYEK
// Pastikan proyek ini milik pengguna yang sedang login
$sql_project = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);

    if ($stmt_project->execute()) {
        $result_project = $stmt_project->get_result();
        if ($result_project->num_rows == 1) {
            $project = $result_project->fetch_assoc();
        } else {
            // Proyek tidak ditemukan atau bukan milik pengguna, redirect
            header("location: home.php");
            exit;
        }
    } else {
        echo "Error mengambil data proyek.";
    }
    $stmt_project->close();
}

// 5. AMBIL DATA CASHFLOW UNTUK PROYEK INI
if ($project) {
    $sql_cashflows = "SELECT * FROM cashflows WHERE project_id = ? ORDER BY year ASC";
    if ($stmt_cashflows = $mysqli->prepare($sql_cashflows)) {
        $stmt_cashflows->bind_param("i", $project_id);

        if ($stmt_cashflows->execute()) {
            $result_cashflows = $stmt_cashflows->get_result();
            $cashflows = $result_cashflows->fetch_all(MYSQLI_ASSOC);
        } else {
            echo "Error mengambil data cashflow.";
        }
        $stmt_cashflows->close();
    }
}

// Tutup koneksi database
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Proyek - <?php echo htmlspecialchars($project['name']); ?></title>
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
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($project['name']); ?></h1>
                        <p class="text-gray-500">Site Manager: <?php echo htmlspecialchars($project['site_manager']); ?></p>
                    </div>
                    <div>
                        <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-yellow-600 transition flex items-center">
                            <i class="fas fa-pencil-alt mr-2"></i> Edit Proyek
                        </a>
                    </div>
                </div>
            </header>

            <!-- Project Details Card -->
            <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Parameter Proyek</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Investasi Modal</p>
                        <p class="text-lg font-semibold text-gray-800">$<?php echo number_format($project['invest_capital']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Investasi Non-Modal</p>
                        <p class="text-lg font-semibold text-gray-800">$<?php echo number_format($project['invest_noncapital']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Pajak</p>
                        <p class="text-lg font-semibold text-gray-800"><?php echo $project['tax']; ?>%</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Depresiasi</p>
                        <p class="text-lg font-semibold text-gray-800"><?php echo $project['depreciation']; ?>%</p>
                    </div>
                </div>
            </div>

            <!-- Cashflow Table -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Tabel Arus Kas (Cashflow)</h2>
                    <a href="add-cashflow.php?project_id=<?php echo $project['id']; ?>" class="bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 transition flex items-center">
                        <i class="fas fa-plus mr-2"></i> Tambah Data
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Tahun</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Produksi</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Pemasukan</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Opex</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Net Cashflow</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cashflows)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-table text-3xl mb-2"></i><br>
                                        Data cashflow belum tersedia.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cashflows as $flow): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($flow['year']); ?></td>
                                        <td class="py-3 px-4"><?php echo number_format($flow['production']); ?></td>
                                        <td class="py-3 px-4">$<?php echo number_format($flow['income']); ?></td>
                                        <td class="py-3 px-4">$<?php echo number_format($flow['opex']); ?></td>
                                        <td class="py-3 px-4 font-semibold text-green-600">$<?php echo number_format($flow['net_cashflow']); ?></td>
                                        <td class="py-3 px-4">
                                            <a href="edit-cashflow.php?id=<?php echo $flow['id']; ?>" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete-cashflow.php?id=<?php echo $flow['id']; ?>&project_id=<?php echo $project['id']; ?>" onclick="return confirm('Anda yakin ingin menghapus data ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>

</html>