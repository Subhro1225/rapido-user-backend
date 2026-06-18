<?php
declare(strict_types=1);

/**
 * user/pay_ride.php
 * POST — record payment for a completed ride.
 *
 * Required POST fields: ride_id, payment_method (cash|upi|card)
 * Requires active session with user_id set.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Payment.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (empty($_SESSION['user_id']) || !is_int($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$userId = $_SESSION['user_id'];
$rideId = (int)($_POST['ride_id'] ?? 0);
$method = trim((string)($_POST['payment_method'] ?? ''));

if ($rideId <= 0 || $method === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ride_id and payment_method are required.']);
    exit;
}

try {
    $pdo  = Database::getInstance()->getConnection();

    // Fetch fare from the ride to avoid client-supplied amount
    $stmt = $pdo->prepare(
        'SELECT fare FROM rides WHERE id = :rid AND user_id = :uid LIMIT 1'
    );
    $stmt->execute([':rid' => $rideId, ':uid' => $userId]);
    $ride = $stmt->fetch();

    if (!$ride) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ride not found.']);
        exit;
    }

    $payment   = new Payment($pdo);
    $paymentId = $payment->createPayment($rideId, $userId, (float) $ride['fare'], $method);

    echo json_encode(['success' => true, 'payment_id' => $paymentId]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Payment failed.']);
}
