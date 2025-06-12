<?php
// create-project.php (Versi Depresiasi dari Tahun Investasi)

session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

require_once "config.php";

$name = $site_manager = $invest_capital = $invest_noncapital = $tax = $investment_years_input = "";
$error_message = "";
$validation_errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form
    $name = trim($_POST['name']);
    $site_manager = trim($_POST['site_manager']);
    $invest_capital = trim($_POST['invest_capital']);
    $invest_noncapital = trim($_POST['invest_noncapital']);
    $tax = trim($_POST['tax']);
    $investment_years_input = trim($_POST['investment_years']); // Input baru: Jumlah Tahun Investasi

    // Validasi per field
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


    // Lanjutkan jika tidak ada error validasi
    if (empty($validation_errors)) {
        // --- PERHITUNGAN DEPRESIASI BARU SEBELUM INSERT ---
        // Nilai depresiasi tahunan (dalam USD) dihitung di sini
        $calculated_depreciation_usd = ($investment_years_input > 0) ? round($invest_capital / $investment_years_input) : 0;

        $mysqli->begin_transaction();
        try {
            // Simpan nilai depresiasi yang sudah dihitung ke kolom 'depreciation' di database
            $sql_project = "INSERT INTO projects (name, site_manager, invest_capital, invest_noncapital, tax, depreciation, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            if ($stmt_project = $mysqli->prepare($sql_project)) {
                // Tipe data disesuaikan: 7 variabel (ssiiddi) dengan 7 placeholder (?)
                $stmt_project->bind_param("ssiiddi", $name, $site_manager, $invest_capital, $invest_noncapital, $tax, $calculated_depreciation_usd, $user_id);
                $stmt_project->execute();

                $new_project_id = $mysqli->insert_id;

                // Buat entri Tahun 0
                $year_0 = 0;
                $initial_investment = - ($invest_capital + $invest_noncapital);

                // --- PERBAIKAN ---
                // Query diubah untuk menyertakan semua kolom yang NOT NULL
                $sql_cashflow_0 = "INSERT INTO cashflows (project_id, year, production, income, opex, taxable_income, net_cashflow, created_at, updated_at) VALUES (?, ?, 0, 0, 0, 0, ?, NOW(), NOW())";
                $stmt_cashflow_0 = $mysqli->prepare($sql_cashflow_0);
                // Bind parameter sesuai dengan placeholder di query
                $stmt_cashflow_0->bind_param("iii", $new_project_id, $year_0, $initial_investment);
                $stmt_cashflow_0->execute();

                $mysqli->commit();
                header("Location: home.php");
                exit();
            } else {
                throw new Exception("Gagal menyiapkan statement: " . $mysqli->error);
            }
        } catch (Exception $exception) {
            $mysqli->rollback();
            $error_message = "Gagal membuat proyek: " . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Proyek Baru</title>
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
                <a href="home.php" class="text-blue-600 hover:text-blue-800 mb-4 inline-block"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
                <h1 class="text-3xl font-bold text-gray-800">Buat Proyek Investasi Baru</h1>
                <p class="text-gray-500">Depresiasi tahunan (USD) akan dihitung otomatis dari (Investasi Modal / Jumlah Tahun Investasi).</p>
            </header>

            <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-lg">
                <?php if (!empty($error_message)) {
                    echo '<div class="bg-red-100 border-red-400 text-red-700 p-3 mb-6 rounded-lg">' . $error_message . '</div>';
                } ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nama Proyek</label>
                            <input type="text" name="name" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg <?php echo !empty($validation_errors['name']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($name); ?>">
                            <span class="text-red-500 text-sm"><?php echo $validation_errors['name'] ?? ''; ?></span>
                        </div>
                        <div>
                            <label for="site_manager" class="block text-sm font-medium text-gray-700">Site Manager</label>
                            <input type="text" name="site_manager" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg <?php echo !empty($validation_errors['site_manager']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($site_manager); ?>">
                            <span class="text-red-500 text-sm"><?php echo $validation_errors['site_manager'] ?? ''; ?></span>
                        </div>
                    </div>
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800">Parameter Keuangan Proyek</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                            <div>
                                <label for="invest_capital" class="block text-sm font-medium text-gray-700">Investasi Modal (USD)</label>
                                <input type="number" name="invest_capital" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg <?php echo !empty($validation_errors['invest_capital']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($invest_capital); ?>">
                                <span class="text-red-500 text-sm"><?php echo $validation_errors['invest_capital'] ?? ''; ?></span>
                            </div>
                            <div>
                                <label for="invest_noncapital" class="block text-sm font-medium text-gray-700">Investasi Non-Modal (USD)</label>
                                <input type="number" name="invest_noncapital" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg <?php echo !empty($validation_errors['invest_noncapital']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($invest_noncapital); ?>">
                                <span class="text-red-500 text-sm"><?php echo $validation_errors['invest_noncapital'] ?? ''; ?></span>
                            </div>
                            <div>
                                <label for="tax" class="block text-sm font-medium text-gray-700">Pajak (%)</label>
                                <input type="number" step="any" name="tax" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg <?php echo !empty($validation_errors['tax']) ? 'border-red-500' : 'border-gray-300'; ?>" value="<?php echo htmlspecialchars($tax); ?>">
                                <span class="text-red-500 text-sm"><?php echo $validation_errors['tax'] ?? ''; ?></span>
                            </div>
                            <div>
                                <label for="investment_years" class="block text-sm font-medium text-gray-700">Jumlah Tahun Investasi</label>
                                <input type="number" name="investment_years" class="mt-1 w-full p-3 bg-gray-50 border rounded-lg <?php echo !empty($validation_errors['investment_years']) ? 'border-red-500' : 'border-gray-300'; ?>" placeholder="Contoh: 10" value="<?php echo htmlspecialchars($investment_years_input); ?>">
                                <span class="text-red-500 text-sm"><?php echo $validation_errors['investment_years'] ?? ''; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end pt-6">
                        <a href="home.php" class="bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg mr-4">Batal</a>
                        <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg">Simpan Proyek</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>