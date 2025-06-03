<?php
header('Content-Type: application/json');
include 'config.php';

$data = json_decode(file_get_contents("php://input"), true);

// Basic validation
if (
    !isset($data['action'], $data['time_spent'], $data['browser'], $data['device'], $data['name']) ||
    !is_numeric($data['time_spent'])
) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
    exit();
}

$name = trim($data['name']) ?: "Anonymous Visitor";
$action = $data['action'];
$timeSpent = (int)$data['time_spent'];
$browser = $data['browser'];
$device = $data['device'];
$eventType = $data['event_type'] ?? 'auto';

// Optional IP
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO stemulator_activity (name, action, time_spent, browser, device, event_type, ip_address)
        VALUES (:name, :action, :time_spent, :browser, :device, :event_type, :ip)
    ");
    $stmt->execute([
        'name' => $name,
        'action' => $action,
        'time_spent' => $timeSpent,
        'browser' => $browser,
        'device' => $device,
        'event_type' => $eventType,
        'ip' => $ip
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Activity logged successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
}
