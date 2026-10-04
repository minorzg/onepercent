<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../config.php';

$body = file_get_contents('php://input');
$d    = json_decode($body, true);

if (!$d || !isset($d['status'])) { http_response_code(400); die('Invalid'); }

$status = strtolower($d['status'] ?? '');
if ($status !== 'paid') { echo 'OK'; exit; }

$orderId = isset($d['order_id']) ? $d['order_id'] : (isset($d['orderId']) ? $d['orderId'] : '');
preg_match('/^1PCT-(\d+)-(pro|vip)-/', $orderId, $m);
$uid  = isset($m[1]) ? (int)$m[1] : 0;
$plan = isset($m[2]) ? $m[2] : '';

if ($uid <= 0) { http_response_code(400); die('UID not found'); }
if (empty($plan)) {
    $amount = floatval($d['amount'] ?? 0);
    $plan   = ($amount >= VIP_PRICE) ? 'vip' : 'pro';
}

$isVip   = ($plan === 'vip') ? 1 : 0;
$expires = date('Y-m-d', strtotime('+30 days'));

try {
    getDB()->prepare('UPDATE users SET is_premium=1, is_vip=:v, plan_expires=:e WHERE id=:u')
           ->execute([':v'=>$isVip, ':e'=>$expires, ':u'=>$uid]);
    echo 'OK';
} catch (Exception $e) {
    error_log('Webhook DB error: ' . $e->getMessage());
    http_response_code(500); die('Server error');
}
