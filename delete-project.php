<?php
// delete-project.php

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
$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) {
  header("location: home.php");
  exit;
}

// 4. SIAPKAN PERINTAH DELETE
// Kita bisa langsung mencoba menghapus, dengan menambahkan user_id di klausa WHERE
// Ini adalah cara yang lebih efisien karena tidak perlu SELECT terlebih dahulu
$sql_delete = "DELETE FROM projects WHERE id = ? AND user_id = ?";

if ($stmt = $mysqli->prepare($sql_delete)) {
  $stmt->bind_param("ii", $project_id, $user_id);

  // Eksekusi penghapusan
  if ($stmt->execute()) {
    // Periksa apakah ada baris yang terpengaruh (dihapus)
    if ($stmt->affected_rows > 0) {
      // Berhasil, kembali ke halaman utama
      header("location: home.php");
      exit();
    } else {
      // Tidak ada baris yang terhapus, kemungkinan karena ID proyek tidak cocok dengan user_id
      // Redirect untuk keamanan
      header("location: home.php");
      exit();
    }
  } else {
    echo "Gagal menghapus proyek. Silakan coba lagi.";
  }
  $stmt->close();
}

// Tutup koneksi
$mysqli->close();
