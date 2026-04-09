<?php
session_start();

// Konfigurasi API Key (sudah terbukti valid karena online berfungsi)
define('API_KEY', '776b5c977a35c2324696c338d93cc19c');
define('API_URL', 'https://api.openweathermap.org/data/2.5/weather');

// Inisialisasi session history
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

function saveToHistory($city) {
    $history = $_SESSION['history'];
    if (($key = array_search($city, $history)) !== false) {
        unset($history[$key]);
    }
    array_unshift($history, $city);
    $history = array_slice($history, 0, 10);
    $_SESSION['history'] = $history;
}

function getWeatherFromAPI($city) {
    $url = API_URL . "?q=" . urlencode($city) . "&units=metric&appid=" . API_KEY;
    
    // Gunakan file_get_contents (tanpa cURL)
    $response = @file_get_contents($url);
    
    if ($response === false) {
        return ['success' => false, 'message' => 'Gagal mengambil data. Pastikan server lokal bisa mengakses internet.'];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['main'])) {
        $weatherData = [
            'city' => $data['name'],
            'country' => $data['sys']['country'],
            'temp' => round($data['main']['temp'], 1),
            'description' => ucfirst($data['weather'][0]['description']),
            'humidity' => $data['main']['humidity'],
            'wind' => $data['wind']['speed'],
            'icon' => $data['weather'][0]['icon']
        ];
        return ['success' => true, 'data' => $weatherData];
    } elseif (isset($data['cod']) && $data['cod'] == 404) {
        return ['success' => false, 'message' => 'Kota tidak ditemukan.'];
    } else {
        return ['success' => false, 'message' => 'Gagal memproses data.'];
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
header('Content-Type: application/json');

if ($action == 'getWeather') {
    $city = isset($_GET['city']) ? trim($_GET['city']) : '';
    if (empty($city)) {
        echo json_encode(['success' => false, 'message' => 'Nama kota tidak boleh kosong.']);
        exit;
    }
    $result = getWeatherFromAPI($city);
    if ($result['success']) {
        saveToHistory($city);
    }
    echo json_encode($result);
    exit;
} elseif ($action == 'getHistory') {
    echo json_encode(['success' => true, 'history' => $_SESSION['history']]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
    exit;
}
?>