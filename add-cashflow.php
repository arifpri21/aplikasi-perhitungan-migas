<?php
// add-cashflow.php (Versi Depresiasi Otomatis dari Proyek)

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

require_once "config.php";

$project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) {
    header("location: home.php");
    exit;
}

// Ambil detail proyek (pajak dan nilai depresiasi tahunan)
$project_details = null;
$sql_project = "SELECT tax, depreciation FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) {
        $result = $stmt_project->get_result();
        if ($result->num_rows == 1) {
            $project_details = $result->fetch_assoc();
        } else {
            header("location: home.php");
            exit;
        }
    }
    $stmt_project->close();
}

// Inisialisasi variabel input
$year = $production = $price_per_barrel = $opex = "";
$error_message = "";
$validation_errors = [];

// Logika saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $year = trim($_POST['year']);
    $production = trim($_POST['production']);
    $price_per_barrel = trim($_POST['price_per_barrel']);
    $opex = trim($_POST['opex']);

    // Validasi input
    if (empty($year) || !ctype_digit($year) || $year == 0) $validation_errors['year'] = "Tahun harus angka positif.";
    if (empty($production) || !is_numeric($production)) $validation_errors['production'] = "Produksi harus angka.";
    if (empty($price_per_barrel) || !is_numeric($price_per_barrel)) $validation_errors['price_per_barrel'] = "Harga per barel harus angka.";
    if (empty($opex) || !is_numeric($opex)) $validation_errors['opex'] = "Opex harus angka.";


    if (empty($validation_errors)) {
        // --- PERHITUNGAN OTOMATIS ---
        $tax_percentage = $project_details['tax'];
        // Ambil nilai depresiasi tahunan dari data proyek
        $depreciation_usd = $project_details['depreciation'];

        // 1. Hitung Income 
        $income = $production * $price_per_barrel;

        // 2. Hitung Taxable Income
        $taxable_income = $income - $opex - $depreciation_usd;

        // 3. Hitung Tax
        $tax_amount = ($taxable_income > 0) ? $taxable_income * ($tax_percentage / 100) : 0;

        // 4. Hitung NCF
        $net_cashflow = $income - $opex - $tax_amount;

        // Simpan ke database
        $sql = "INSERT INTO cashflows (project_id, year, production, income, opex, taxable_income, net_cashflow, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        if ($stmt = $mysqli->prepare($sql)) {
            // bind_param tipe data: i=integer, d=double/float
            $stmt->bind_param("iididdd", $project_id, $year, $production, $income, $opex, $taxable_income, $net_cashflow);

            if ($stmt->execute()) {
                header("Location: project-details.php?id=" . $project_id);
                exit();
            } else {
                $error_message = "Gagal menambah data.";
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
    <title>Tambah Data Cashflow Tahunan</title>
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
                <a href="project-details.php?id=<?php echo htmlspecialchars($project_id); ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Kembali ke Detail Proyek</a>
                <h1 class="text-3xl font-bold text-gray-800">Tambah Data Arus Kas Tahunan</h1>
                <p class="text-gray-500">Depresiasi akan digunakan secara otomatis dari parameter proyek.</p>
            </header>
            <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
                <?php if (!empty($error_message)) {
                    echo '<div class="bg-red-100 border-red-400 text-red-700 p-4 rounded-lg mb-6">' . $error_message . '</div>';
                } ?>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?project_id=' . htmlspecialchars($project_id); ?>" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Tahun ke-</label>
                            <input type="number" id="year" name="year" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($year); ?>">
                            <span class="text-red-500 text-sm"><?php echo $validation_errors['year'] ?? ''; ?></span>
                        </div>
                        <div>
                            <label for="production" class="block text-sm font-medium text-gray-700">Produksi (Mbbl)</label>
                            <input type="number" id="production" name="production" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($production); ?>">
                            <span class="text-red-500 text-sm"><?php echo $validation_errors['production'] ?? ''; ?></span>
                        </div>
                        <div>
                            <label for="price_per_barrel" class="block text-sm font-medium text-gray-700">Harga per Barel (USD)</label>
                            <input type="number" step="any" id="price_per_barrel" name="price_per_barrel" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($price_per_barrel); ?>">
                            <span class="text-red-500 text-sm"><?php echo $validation_errors['price_per_barrel'] ?? ''; ?></span>
                        </div>
                        <div>
                            <label for="opex" class="block text-sm font-medium text-gray-700">Opex (USD)</label>
                            <input type="number" id="opex" name="opex" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($opex); ?>">
                            <span class="text-red-500 text-sm"><?php echo $validation_errors['opex'] ?? ''; ?></span>
                        </div>
                    </div>
                    <div class="flex justify-end pt-6">
                        <a href="project-details.php?id=<?php echo htmlspecialchars($project_id); ?>" class="bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg mr-4">Batal</a>
                        <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg">Simpan & Hitung</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>