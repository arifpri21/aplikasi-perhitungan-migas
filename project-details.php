<?php
// project-details.php (Versi dengan Info Proyek dan Grafik)

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

// Ambil semua detail proyek
$project = null;
$sql_project = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) {
        $result_project = $stmt_project->get_result();
        if ($result_project->num_rows == 1) {
            $project = $result_project->fetch_assoc();
        } else {
            header("location: home.php");
            exit;
        }
    }
    $stmt_project->close();
}

// Ambil data cashflow untuk proyek ini
$cashflows = [];
$sql_cashflows = "SELECT * FROM cashflows WHERE project_id = ? ORDER BY year ASC";
if ($stmt_cashflows = $mysqli->prepare($sql_cashflows)) {
    $stmt_cashflows->bind_param("i", $project_id);
    if ($stmt_cashflows->execute()) {
        $result_cashflows = $stmt_cashflows->get_result();
        $cashflows = $result_cashflows->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_cashflows->close();
}

$mysqli->close();

// Hitung mundur "Jumlah Tahun" untuk ditampilkan, berdasarkan data yang tersimpan
$investment_years_display = 0;
if (!empty($project['depreciation']) && $project['depreciation'] > 0 && !empty($project['invest_capital'])) {
    $investment_years_display = round($project['invest_capital'] / $project['depreciation']);
}


// Siapkan data untuk grafik
$chart_labels = [];
$chart_data = [];
$total_ncf = 0;

foreach ($cashflows as $flow) {
    // Logika perhitungan yang sama dengan di tabel untuk konsistensi data
    if ($flow['year'] > 0) {
        $depreciation_usd = $project['depreciation'];
        $taxable_income = $flow['income'] - $flow['opex'] - $depreciation_usd;
        $tax_amount = ($taxable_income > 0) ? $taxable_income * ($project['tax'] / 100) : 0;
        $ncf = $flow['income'] - $flow['opex'] - $tax_amount;
    } else {
        $ncf = $flow['net_cashflow'];
    }
    $chart_labels[] = 'Tahun ' . $flow['year'];
    $chart_data[] = $ncf;
    $total_ncf += $ncf;
}

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
    <!-- Masukkan Pustaka Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
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
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($project['name']); ?></h1>
                        <p class="text-gray-500">Site Manager: <?php echo htmlspecialchars($project['site_manager']); ?></p>
                    </div>
                    <div>
                        <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-yellow-600 flex items-center"><i class="fas fa-pencil-alt mr-2"></i> Edit Proyek</a>
                    </div>
                </div>
            </header>

            <!-- Keterangan Project -->
            <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Parameter Proyek</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 text-sm">
                    <div>
                        <p class="text-gray-500">Investasi Modal</p>
                        <p class="font-semibold text-gray-800">$<?php echo number_format($project['invest_capital']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Investasi Non-Modal</p>
                        <p class="font-semibold text-gray-800">$<?php echo number_format($project['invest_noncapital']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500">Pajak</p>
                        <p class="font-semibold text-gray-800"><?php echo $project['tax']; ?>%</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Jumlah Tahun Investasi</p>
                        <p class="font-semibold text-gray-800"><?php echo $investment_years_display; ?> Tahun</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Depresiasi Tahunan</p>
                        <p class="font-semibold text-gray-800">$<?php echo number_format($project['depreciation']); ?></p>
                    </div>
                </div>
            </div>


            <!-- Tabel Arus Kas -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Tabel Arus Kas (Cashflow)</h2>
                    <a href="add-cashflow.php?project_id=<?php echo $project['id']; ?>" class="bg-green-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-600 flex items-center"><i class="fas fa-plus mr-2"></i> Tambah Data</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Tahun</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Produksi</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Harga/Barel</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Income</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Opex</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Depresiasi</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Taxable Income</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Tax</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">NCF</th>
                                <th class="py-2 px-3 text-left font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $temp_chart_data = $chart_data; // Salin data chart agar tidak rusak saat di-shift
                            foreach ($cashflows as $flow):
                            ?>
                                <?php
                                // Ambil nilai yang sudah dihitung untuk baris ini
                                $ncf = array_shift($temp_chart_data);

                                // Inisialisasi tampilan
                                $price_per_barrel_display = '-';
                                $tax_display = '-';

                                if ($flow['year'] > 0) {
                                    $price_per_barrel_display = ($flow['production'] > 0) ? ($flow['income'] / $flow['production']) : 0;
                                    $tax_display = ($flow['taxable_income'] > 0) ? $flow['taxable_income'] * ($project['tax'] / 100) : 0;
                                }
                                ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-2 px-3 font-medium"><?php echo $flow['year']; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? number_format($flow['production']) : '-'; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? '$' . number_format($price_per_barrel_display, 2) : '-'; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? '$' . number_format($flow['income']) : '-'; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? '$' . number_format($flow['opex']) : '-'; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? '$' . number_format($project['depreciation']) : '-'; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? '$' . number_format($flow['taxable_income']) : '-'; ?></td>
                                    <td class="py-2 px-3"><?php echo ($flow['year'] > 0) ? '$' . number_format($tax_display) : '-'; ?></td>
                                    <td class="py-2 px-3 font-semibold <?php echo ($ncf < 0) ? 'text-red-600' : 'text-green-600'; ?>">
                                        $<?php echo number_format($ncf); ?>
                                    </td>
                                    <td class="py-2 px-3">
                                        <?php if ($flow['year'] > 0): ?>
                                            <!-- TOMBOL EDIT DITAMBAHKAN DI SINI -->
                                            <a href="edit-cashflow.php?id=<?php echo $flow['id']; ?>" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="delete-cashflow.php?id=<?php echo $flow['id']; ?>&project_id=<?php echo $project['id']; ?>" onclick="return confirm('Yakin hapus data tahun ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="font-bold bg-gray-200">
                            <tr>
                                <td colspan="8" class="py-2 px-3 text-right">Total NCF Undiscounted</td>
                                <td class="py-2 px-3 <?php echo ($total_ncf < 0) ? 'text-red-600' : 'text-green-600'; ?>">
                                    $<?php echo number_format($total_ncf); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Grafik NCF -->
            <div class="bg-white p-6 mt-8 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Grafik Net Cash Flow (NCF) per Tahun</h2>
                <div class="h-96">
                    <canvas id="ncfChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('ncfChart').getContext('2d');

            const chartLabels = <?php echo json_encode($chart_labels); ?>;
            const chartData = <?php echo json_encode($chart_data); ?>;

            const ncfChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Net Cash Flow (NCF) in $',
                        data: chartData,
                        fill: true,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(59, 130, 246, 1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value, index, values) {
                                    return '$' + new Intl.NumberFormat().format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', {
                                            style: 'currency',
                                            currency: 'USD'
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>