<?php
// Identitas Praktikum Cloud Computing
define('PEMILIK', '24161562016 - Andivo Tegar Latuso');
define('VERSI', '1.0');

$db_url = getenv('DATABASE_URL') ?: 'postgresql://app:latihan@db:5432/app';
$dbopts = parse_url($db_url);
$dsn = "pgsql:host=" . ($dbopts["host"] ?? 'db') . ";port=" . ($dbopts["port"] ?? '5432') . ";dbname=" . ltrim($dbopts["path"], '/');
$user = $dbopts["user"] ?? 'app';
$pass = $dbopts["pass"] ?? 'latihan';

$pdo = null;
$db_status = "gagal";
try {
    $pdo = new PDO($dsn, $user, $pass);
    $db_status = "ok";
    
    // Buat tabel jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS bukutamu (
        id SERIAL PRIMARY KEY,
        nama VARCHAR(100),
        pesan TEXT,
        tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama = $_POST['nama'] ?? '';
        $pesan = $_POST['pesan'] ?? '';
        if (!empty($nama)) {
            $stmt = $pdo->prepare("INSERT INTO bukutamu (nama, pesan) VALUES (?, ?)");
            $stmt->execute([$nama, $pesan]);
            header("Location: /");
            exit;
        }
    }
} catch (Exception $e) {
    $db_status = "gagal: " . $e->getMessage();
}

// Endpoint kesehatan untuk Dockerfile (/healthz dan /health)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri === '/healthz') {
    header('Content-Type: application/json');
    echo json_encode(["status" => "ok"]);
    exit;
}
if ($uri === '/health') {
    header('Content-Type: application/json');
    if ($db_status === "ok") {
        echo json_encode(["status" => "ok", "db" => "ok"]);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "error", "db" => $db_status]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tamu - <?php echo PEMILIK; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f4f4; color: #333; }
        .container { max-width: 600px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, textarea { width: 100%; padding: 8px; margin: 6px 0 12px 0; box-sizing: border-box; }
        button { background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; }
        button:hover { background: #0056b3; }
        .entry { border-bottom: 1px solid #ddd; padding: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Buku Tamu Cloud Computing</h2>
        <p><strong>Pemilik:</strong> <?php echo PEMILIK; ?> (Versi: <?php echo VERSI; ?>)</p>
        
        <form method="POST">
            <label>Nama:</label>
            <input type="text" name="nama" required placeholder="Masukkan nama atau NIM...">
            <label>Pesan:</label>
            <textarea name="pesan" placeholder="Tulis pesan..."></textarea>
            <button type="submit">Kirim</button>
        </form>

        <h3>Daftar Tamu</h3>
        <?php
        if ($pdo) {
            $stmt = $pdo->query("SELECT * FROM bukutamu ORDER BY id DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<div class='entry'>";
                echo "<strong>" . htmlspecialchars($row['nama']) . "</strong> <em>(" . $row['tanggal'] . ")</em><br>";
                echo "<p>" . nl2br(htmlspecialchars($row['pesan'])) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p style='color:red;'>Database belum terhubung (akan aktif di Bagian E).</p>";
        }
        ?>
    </div>
</body>
</html>