<?php
// --- KONFIGURASI ---
$host = '8.215.1.198';
$user = 'admin';
$password = 'Mars123//';
$dbname = 'porto_db';
$webhook_url = 'https://discord.com/api/webhooks/1396436570608898068/24HxXVISytveEbG8PWG4KqV03zyau6UeQncI3mAM_aTe9D5mqGFK8Z4gmBK4iblrXWTZ';

// --- KONEKSI DATABASE ---
date_default_timezone_set('Asia/Jakarta');
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit();
}

// --- PENCATATAN PENGUNJUNG ---
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';

$conn->query("UPDATE views SET count = count + 1 WHERE id = 1");

$log_stmt = $conn->prepare("INSERT INTO visit_logs (ip_address, visit_time) VALUES (?, NOW())");
$log_stmt->bind_param("s", $ip_address);
$log_stmt->execute();
$log_stmt->close();

$count_stmt = $conn->prepare("SELECT COUNT(*) as today_count FROM visit_logs WHERE ip_address = ? AND DATE(visit_time) = CURDATE()");
$count_stmt->bind_param("s", $ip_address);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$visits_today = $count_row['today_count'];
$count_stmt->close();

$result = $conn->query("SELECT count FROM views WHERE id = 1");
$row = $result->fetch_assoc();
$total_views = $row['count'];

$conn->close();

// --- FUNGSI BANTUAN ---
function getOS($userAgent) {
    $os_platform = "Unknown OS";
    $os_array = [
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    ];
    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $os_platform = $value;
        }
    }
    return $os_platform;
}

function getBrowser($userAgent) {
    $browser = "Unknown Browser";
    $browser_array = [
        '/msie/i'       =>  'Internet Explorer',
        '/firefox/i'    =>  'Firefox',
        '/safari/i'     =>  'Safari',
        '/chrome/i'     =>  'Chrome',
        '/edge/i'       =>  'Edge',
        '/opera/i'      =>  'Opera',
        '/netscape/i'   =>  'Netscape',
        '/maxthon/i'    =>  'Maxthon',
        '/konqueror/i'  =>  'Konqueror',
        '/mobile/i'     =>  'Handheld Browser'
    ];
    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            $browser = $value;
        }
    }
    return $browser;
}

function getDeviceType($userAgent) {
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $userAgent)) return 'Tablet';
    if (preg_match('/(mobi|ipod|phone|blackberry|opera mini|fennec|minimo|symbian|psp|nintendo ds)/i', $userAgent)) return 'Mobile';
    return 'PC/Desktop';
}

// --- NOTIFIKASI WEBHOOK ---
$device_type = getDeviceType($user_agent);
$os = getOS($user_agent);
$browser = getBrowser($user_agent);

// Terjemahkan hari dan bulan ke Bahasa Indonesia
$day_en = date('l');
$month_en = date('F');

$days_id = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];

$months_id = [
    'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 'April' => 'April',
    'May' => 'Mei', 'June' => 'Juni', 'July' => 'Juli', 'August' => 'Agustus',
    'September' => 'September', 'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
];

$day_id = $days_id[$day_en];
$month_id = $months_id[$month_en];

$access_time = $day_id . date(', d ') . $month_id . date(' Y H:i:s'); // Format: Hari, Tanggal Bulan Tahun Jam:Menit:Detik

$country = 'Unknown';
$region = 'Unknown';
$city = 'Unknown';
$isp = 'Unknown';

try {
    $geoip_url = "http://ip-api.com/json/{$ip_address}";
    $geoip_json = @file_get_contents($geoip_url);
    if ($geoip_json) {
        $geoip_data = json_decode($geoip_json, true);
        if ($geoip_data && $geoip_data['status'] == 'success') {
            $country = $geoip_data['country'] ?: 'Unknown';
            $region = $geoip_data['regionName'] ?: 'Unknown';
            $city = $geoip_data['city'] ?: 'Unknown';
            $isp = $geoip_data['isp'] ?: 'Unknown';
        }
    }
} catch (Exception $e) {
    // Biarkan nilai default jika terjadi error
}

$timestamp = date("c");
$embed = [
    "title" => "🚀 Pengunjung Baru di Portofolio!",
    "description" => "Seseorang baru saja mengunjungi halaman portofolio Anda.",
    "color" => hexdec("00FF00"),
    "fields" => [
        ["name" => "🗓️ Waktu Akses", "value" => $access_time, "inline" => false],
        ["name" => "📍 Lokasi", "value" => "**Kota:** {$city}
**Wilayah:** {$region}
**Negara:** {$country}", "inline" => true],
        ["name" => "🌐 Jaringan", "value" => "**Alamat IP:** {$ip_address}
**ISP:** {$isp}", "inline" => true],
        ["name" => "💻 Info Perangkat", "value" => "**Tipe:** {$device_type}
**OS:** {$os}
**Browser:** {$browser}", "inline" => true],
        ["name" => "📈 Statistik", "value" => "**Kunjungan IP Hari Ini:** {$visits_today}", "inline" => true],
        ["name" => "🕵️ User Agent", "value" => "```{$user_agent}```", "inline" => false]
    ],
    "footer" => ["text" => "Total Pengunjung: {$total_views}"],
    "timestamp" => $timestamp
];

$payload = json_encode(["embeds" => [$embed]]);

if (strpos($webhook_url, 'ganti_dengan') === false && filter_var($webhook_url, FILTER_VALIDATE_URL)) {
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_exec($ch);
    curl_close($ch);
}

// --- RESPONSE ---
header('Content-Type: application/json');
echo json_encode(['count' => $total_views]);
?>