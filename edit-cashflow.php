<?php
// edit-cashflow.php

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

require_once "config.php";

// Ambil ID cashflow dari URL
$cashflow_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($cashflow_id) || !ctype_digit($cashflow_id)) {
    header("location: home.php");
    exit;
}

// Inisialisasi variabel
$project_id = $year = $production = $price_per_barrel = $opex = "";
$error_message = "";
$validation_errors = [];

// --- LOGIKA SAAT FORM DIKIRIM (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $project_id = trim($_POST['project_id']);
    $year = trim($_POST['year']);
    $production = trim($_POST['production']);
    $price_per_barrel = trim($_POST['price_per_barrel']);
    $opex = trim($_POST['opex']);

    // Ambil detail proyek untuk perhitungan
    $sql_project = "SELECT tax, depreciation FROM projects WHERE id = ? AND user_id = ?";
    if ($stmt_project = $mysqli->prepare($sql_project)) {
        $stmt_project->bind_param("ii", $project_id, $user_id);
        $stmt_project->execute();
        $result = $stmt_project->get_result();
        $project_details = $result->fetch_assoc();
        $stmt_project->close();
    }

    if (empty($validation_errors) && $project_details) {
        // --- PERHITUNGAN ULANG ---
        $tax_percentage = $project_details['tax'];
        $depreciation_usd = $project_details['depreciation'];
        $income = $production * $price_per_barrel;
        $taxable_income = $income - $opex - $depreciation_usd;
        $tax_amount = ($taxable_income > 0) ? $taxable_income * ($tax_percentage / 100) : 0;
        $net_cashflow = $income - $opex - $tax_amount;

        // Update data di database
        $sql_update = "UPDATE cashflows SET year=?, production=?, income=?, opex=?, taxable_income=?, net_cashflow=?, updated_at=NOW() WHERE id=?";
        if ($stmt_update = $mysqli->prepare($sql_update)) {
            $stmt_update->bind_param("ididddi", $year, $production, $income, $opex, $taxable_income, $net_cashflow, $cashflow_id);
            if ($stmt_update->execute()) {
                header("location: project-details.php?id=" . $project_id);
                exit();
            } else {
                $error_message = "Gagal memperbarui data.";
            }
            $stmt_update->close();
        }
    }
} else {
    // --- LOGIKA SAAT HALAMAN DIBUKA (GET) ---
    // Pastikan cashflow ini milik user yang login
    $sql_fetch = "SELECT * FROM cashflows WHERE id = ?";
    if ($stmt_fetch = $mysqli->prepare($sql_fetch)) {
        $stmt_fetch->bind_param("i", $cashflow_id);
        if ($stmt_fetch->execute()) {
            $result = $stmt_fetch->get_result();
            if ($result->num_rows == 1) {
                $cashflow = $result->fetch_assoc();
                $project_id = $cashflow['project_id'];
                $year = $cashflow['year'];
                $production = $cashflow['production'];
                $opex = $cashflow['opex'];
                // Hitung mundur harga per barel untuk ditampilkan di form
                $price_per_barrel = ($production > 0) ? ($cashflow['income'] / $production) : 0;
            } else {
                header("location: home.php");
                exit;
            }
        }
        $stmt_fetch->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data Cashflow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="flex h-screen">
        <aside class="w-64 bg-white shadow-md flex flex-col">
            <div class="p-6 text-center border-b">
                <h2 class="text-xl font-bold text-blue-600">Kalkulator Migas</h2>
            </div>
            <nav class="flex-1 p-4 space-y-2"><a href="home.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-home mr-3"></i> Dashboard</a></nav>
            <div class="p-4 border-t"><a href="logout.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg"><i class="fas fa-sign-out-alt mr-3"></i> Keluar</a></div>
        </aside>
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="mb-8">
                <a href="project-details.php?id=<?php echo htmlspecialchars($project_id); ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                <h1 class="text-3xl font-bold text-gray-800">Edit Data Arus Kas Tahun <?php echo htmlspecialchars($year); ?></h1>
            </header>
            <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
                <?php if (!empty($error_message)) {
                    echo '<div class="bg-red-100 border-red-400 text-red-700 p-4 rounded-lg mb-6">' . $error_message . '</div>';
                } ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $cashflow_id; ?>" method="POST" class="space-y-6">
                    <!-- Input tersembunyi untuk membawa project_id -->
                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_id); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Tahun ke-</label>
                            <input type="number" name="year" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($year); ?>">
                        </div>
                        <div>
                            <label for="production" class="block text-sm font-medium text-gray-700">Produksi (Mbbl)</label>
                            <input type="number" name="production" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($production); ?>">
                        </div>
                        <div>
                            <label for="price_per_barrel" class="block text-sm font-medium text-gray-700">Harga per Barel (USD)</label>
                            <input type="number" step="any" name="price_per_barrel" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($price_per_barrel); ?>">
                        </div>
                        <div>
                            <label for="opex" class="block text-sm font-medium text-gray-700">Opex (USD)</label>
                            <input type="number" name="opex" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($opex); ?>">
                        </div>
                    </div>
                    <div class="flex justify-end pt-6">
                        <a href="project-details.php?id=<?php echo htmlspecialchars($project_id); ?>" class="bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg mr-4">Batal</a>
                        <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>