<?php
// 1. MULAI SESSION & CEK AUTENTIKASI
session_start();

// Periksa apakah pengguna sudah login, jika tidak, arahkan ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: login.php");
  exit;
}

// Ambil data pengguna dari session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 2. KONEKSI DATABASE
require_once "config.php"; // Gunakan require_once untuk keamanan

// 3. AMBIL DATA PROYEK DARI DATABASE MENGGUNAKAN MYSQLI
$projects = []; // Inisialisasi array untuk menampung proyek
$sql = "SELECT id, name, site_manager, invest_capital, created_at FROM projects WHERE user_id = ? ORDER BY created_at DESC";

if ($stmt = $mysqli->prepare($sql)) {
  // Bind variabel ke statement yang sudah disiapkan sebagai parameter
  $stmt->bind_param("i", $param_user_id);

  // Set parameter
  $param_user_id = $user_id;

  // Coba eksekusi statement
  if ($stmt->execute()) {
    // Dapatkan hasil
    $result = $stmt->get_result();

    // Ambil semua baris hasil sebagai array asosiatif
    $projects = $result->fetch_all(MYSQLI_ASSOC);
  } else {
    echo "Oops! Terjadi kesalahan. Silakan coba lagi nanti.";
  }

  // Tutup statement
  $stmt->close();
}

// Tutup koneksi
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Kalkulator Investasi Migas</title>
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
        <a href="home.php" class="flex items-center px-4 py-2 text-white bg-blue-600 rounded-lg">
          <i class="fas fa-home mr-3"></i> Dashboard
        </a>
        <!-- Tambahkan link lain jika perlu -->
      </nav>
      <div class="p-4 border-t">
        <a href="logout.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
          <i class="fas fa-sign-out-alt mr-3"></i> Keluar
        </a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 overflow-y-auto">
      <header class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">Dashboard Proyek</h1>
          <p class="text-gray-500">Selamat datang kembali, <?php echo htmlspecialchars($user_name); ?>!</p>
        </div>
        <a href="create-project.php" class="bg-blue-600 text-white font-bold py-2 px-5 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform transform hover:scale-105 flex items-center">
          <i class="fas fa-plus mr-2"></i> Buat Proyek Baru
        </a>
      </header>

      <!-- Project Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($projects)): ?>
          <div class="col-span-full text-center py-12 bg-white rounded-lg shadow">
            <i class="fas fa-folder-open text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700">Belum Ada Proyek</h3>
            <p class="text-gray-500 mt-2">Mulai dengan membuat proyek investasi pertama Anda.</p>
          </div>
        <?php else: ?>
          <?php foreach ($projects as $project): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:-translate-y-1 transition-all duration-300">
              <div class="p-6">
                <h3 class="text-xl font-bold text-gray-800 truncate"><?php echo htmlspecialchars($project['name']); ?></h3>
                <p class="text-gray-500 text-sm mt-1">Site Manager: <?php echo htmlspecialchars($project['site_manager']); ?></p>

                <div class="mt-4 border-t pt-4">
                  <p class="text-sm text-gray-600">Investasi Modal</p>
                  <p class="text-lg font-semibold text-blue-600">$<?php echo number_format($project['invest_capital']); ?></p>
                </div>

                <div class="mt-4 text-xs text-gray-400">
                  Dibuat pada: <?php echo date('d M Y', strtotime($project['created_at'])); ?>
                </div>
              </div>
              <a href="project-details.php?id=<?php echo $project['id']; ?>" class="block bg-gray-50 hover:bg-gray-100 text-center text-blue-600 font-semibold py-3 transition">
                Lihat Detail
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>

</body>

</html>