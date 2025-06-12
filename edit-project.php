<?php
// edit-project.php (Versi Depresiasi dari Jumlah Tahun)

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

require_once "config.php";

$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) {
    header("location: home.php");
    exit;
}

// Inisialisasi variabel
$name = $site_manager = $invest_capital = $invest_noncapital = $tax = "";
$investment_years_display = ""; // Untuk menampilkan jumlah tahun di form
$error_message = "";
$validation_errors = [];

// --- LOGIKA SAAT FORM DIKIRIM (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $name = trim($_POST['name']);
    $site_manager = trim($_POST['site_manager']);
    $invest_capital = trim($_POST['invest_capital']);
    $invest_noncapital = trim($_POST['invest_noncapital']);
    $tax = trim($_POST['tax']);
    $investment_years_input = trim($_POST['investment_years']); // Input baru: Jumlah Tahun Investasi

    // Validasi sederhana
    if (empty($name)) {
        $validation_errors['name'] = "Nama proyek wajib diisi.";
    }
    if (empty($site_manager)) {
        $validation_errors['site_manager'] = "Nama manajer wajib diisi.";
    }
    if (empty($invest_capital) || !is_numeric($invest_capital)) {
        $validation_errors['invest_capital'] = "Investasi modal harus angka.";
    }
    if (empty($invest_noncapital) || !is_numeric($invest_noncapital)) {
        $validation_errors['invest_noncapital'] = "Investasi non-modal harus angka.";
    }
    if (empty($tax) || !is_numeric($tax)) {
        $validation_errors['tax'] = "Pajak harus angka.";
    }
    if (empty($investment_years_input) || !ctype_digit($investment_years_input)) {
        $validation_errors['investment_years'] = "Jumlah tahun harus angka bulat.";
    }

    if (empty($validation_errors)) {
        // --- PERHITUNGAN DEPRESIASI BARU ---
        $calculated_depreciation_usd = ($investment_years_input > 0) ? round($invest_capital / $investment_years_input) : 0;

        // Update database dengan nilai depresiasi yang sudah dihitung
        $sql_update = "UPDATE projects SET name=?, site_manager=?, invest_capital=?, invest_noncapital=?, tax=?, depreciation=?, updated_at=NOW() WHERE id=? AND user_id=?";

        if ($stmt = $mysqli->prepare($sql_update)) {
            // Simpan $calculated_depreciation_usd ke kolom 'depreciation'
            $stmt->bind_param("ssiiddii", $name, $site_manager, $invest_capital, $invest_noncapital, $tax, $calculated_depreciation_usd, $project_id, $user_id);

            if ($stmt->execute()) {
                header("location: project-details.php?id=" . $project_id);
                exit();
            } else {
                $error_message = "Gagal memperbarui proyek.";
            }
            $stmt->close();
        }
    }
} else {
    // --- LOGIKA SAAT HALAMAN DIBUKA (GET) ---
    $sql_fetch = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
    if ($stmt = $mysqli->prepare($sql_fetch)) {
        $stmt->bind_param("ii", $project_id, $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $project = $result->fetch_assoc();
                $name = $project['name'];
                $site_manager = $project['site_manager'];
                $invest_capital = $project['invest_capital'];
                $invest_noncapital = $project['invest_noncapital'];
                $tax = $project['tax'];

                // Hitung mundur "Jumlah Tahun" untuk ditampilkan di form, berdasarkan data yang tersimpan
                if (!empty($project['depreciation']) && $project['depreciation'] > 0 && !empty($project['invest_capital'])) {
                    $investment_years_display = round($project['invest_capital'] / $project['depreciation']);
                }
            } else {
                header("location: home.php");
                exit;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Proyek</title>
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
                <a href="project-details.php?id=<?php echo $project_id; ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                <h1 class="text-3xl font-bold text-gray-800">Edit Proyek: <?php echo htmlspecialchars($name); ?></h1>
            </header>
            <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $project_id; ?>" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nama Proyek</label>
                            <input type="text" name="name" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($name); ?>">
                        </div>
                        <div>
                            <label for="site_manager" class="block text-sm font-medium text-gray-700">Site Manager</label>
                            <input type="text" name="site_manager" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($site_manager); ?>">
                        </div>
                    </div>
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800">Parameter Keuangan Proyek</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                            <div>
                                <label for="invest_capital" class="block text-sm font-medium text-gray-700">Investasi Modal (USD)</label>
                                <input type="number" name="invest_capital" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($invest_capital); ?>">
                            </div>
                            <div>
                                <label for="invest_noncapital" class="block text-sm font-medium text-gray-700">Investasi Non-Modal (USD)</label>
                                <input type="number" name="invest_noncapital" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($invest_noncapital); ?>">
                            </div>
                            <div>
                                <label for="tax" class="block text-sm font-medium text-gray-700">Pajak (%)</label>
                                <input type="number" step="any" name="tax" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($tax); ?>">
                            </div>
                            <div>
                                <label for="investment_years" class="block text-sm font-medium text-gray-700">Jumlah Tahun Investasi</label>
                                <input type="number" name="investment_years" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg" value="<?php echo htmlspecialchars($investment_years_display); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-6">
                        <div>
                            <a href="delete-project.php?id=<?php echo $project_id; ?>" onclick="return confirm('Yakin hapus proyek ini?')" class="text-red-600 hover:text-red-800 font-medium"><i class="fas fa-trash-alt mr-2"></i>Hapus Proyek</a>
                        </div>
                        <div class="flex">
                            <a href="project-details.php?id=<?php echo $project_id; ?>" class="bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg mr-4">Batal</a>
                            <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg">Simpan Perubahan</button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>