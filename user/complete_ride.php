<?php
declare(strict_types=1);

/**
 * user/complete_ride.php
 * POST endpoint — lets the user mark a ride as 'completed'.
 *
 * Purpose: temporary stand-in for the driver module during development.
 * A ride must be in 'started' status before it can be completed.
 *
 * Required POST field: ride_id
 * Requires active session with user_id set.
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Ride.php';

// ── Method check ──────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// ── Auth ──────────────────────────────────────────────────────────────────────

if (empty($_SESSION['user_id']) || !is_int($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$sessionUserId = $_SESSION['user_id'];

// ── Input ─────────────────────────────────────────────────────────────────────

$rawRideId = $_POST['ride_id'] ?? '';
$rideId    = filter_var($rawRideId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($rideId === false || $rideId === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ride_id must be a positive integer.']);
    exit;
}

// ── Fetch ride ────────────────────────────────────────────────────────────────

try {
    $pdo  = Database::getInstance()->getConnection();
    $ride = new Ride($pdo);
    $row  = $ride->getRideById($rideId);
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not fetch ride.']);
    exit;
}

if ($row === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ride not found.']);
    exit;
}

// ── Ownership ─────────────────────────────────────────────────────────────────

if ((int) $row['user_id'] !== $sessionUserId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

// ── Status guard ──────────────────────────────────────────────────────────────
// Only a ride in 'started' state can be moved to 'completed'.

if ($row['ride_status'] !== 'started') {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => "Cannot complete a ride with status '{$row['ride_status']}'. Ride must be 'started'.",
    ]);
    exit;
}

// ── Update status ─────────────────────────────────────────────────────────────

try {
    $ride->updateStatus($rideId, 'completed');
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update ride status.']);
    exit;
}

// ── Response ──────────────────────────────────────────────────────────────────

echo json_encode([
    'success' => true,
    'message' => 'Ride marked complete.',
]);
