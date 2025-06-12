<?php
// delete-cashflow.php

// 1. MULAI SESSION & CEK AUTENTIKASI
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. KONEKSI DATABASE
require_once "config.php";

// 3. AMBIL ID DARI URL DAN VALIDASI
$cashflow_id = isset($_GET['id']) ? trim($_GET['id']) : null;
$project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : null;

// Jika salah satu ID tidak ada atau tidak valid, kembali ke home
if (empty($cashflow_id) || !ctype_digit($cashflow_id) || empty($project_id) || !ctype_digit($project_id)) {
    header("location: home.php");
    exit;
}

// 4. VERIFIKASI KEPEMILIKAN SEBELUM MENGHAPUS
// Pastikan cashflow yang akan dihapus adalah milik proyek dari user yang sedang login
$sql_verify = "SELECT cf.id FROM cashflows cf JOIN projects p ON cf.project_id = p.id WHERE cf.id = ? AND p.user_id = ?";

if ($stmt_verify = $mysqli->prepare($sql_verify)) {
    $stmt_verify->bind_param("ii", $cashflow_id, $user_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();

    // Jika data ditemukan (jumlah baris = 1), maka pengguna berhak menghapus
    if ($stmt_verify->num_rows == 1) {
        
        // 5. SIAPKAN PERINTAH DELETE
        $sql_delete = "DELETE FROM cashflows WHERE id = ?";
        if ($stmt_delete = $mysqli->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $cashflow_id);
            
            // Eksekusi penghapusan
            if ($stmt_delete->execute()) {
                // Berhasil, kembali ke halaman detail proyek
                header("location: project-details.php?id=" . $project_id);
                exit();
            } else {
                echo "Gagal menghapus data. Silakan coba lagi.";
            }
            $stmt_delete->close();
        }
    } else {
        // Jika data tidak ditemukan atau pengguna tidak berhak, kembali ke halaman utama
        header("location: home.php");
        exit;
    }
    $stmt_verify->close();
}

// Tutup koneksi
$mysqli->close();
?>
