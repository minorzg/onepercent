<?php
require_once '../config.php';

if (empty($_SESSION['uid'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Session expirée']); exit;
}

header('Content-Type: application/json');

$uid  = (int)$_SESSION['uid'];
$in   = json_decode(file_get_contents('php://input'), true);
$plan = isset($in['plan']) ? $in['plan'] : '';

if (!in_array($plan, ['pro', 'vip'])) {
    echo json_encode(['error' => 'Plan invalide']); exit;
}

$amount  = ($plan === 'pro') ? PRO_PRICE : VIP_PRICE;
$orderId = '1PCT-' . $uid . '-' . $plan . '-' . time();

$payload = json_encode([
    'amount'             => $amount,
    'currency'           => 'USD',
    'lifetime'           => 60,
    'fee_paid_by_payer'  => 1,
    'mixed_payment'      => true,
    'callback_url'       => SITE_URL . '/payment/webhook.php',
    'return_url'         => SITE_URL . '/payment/success.php?plan=' . $plan,
    'order_id'           => $orderId,
    'description'        => '1% ' . ucfirst($plan) . ' — 30 jours',
    'sandbox'            => false,
]);

$ch = curl_init('https://api.oxapay.com/v1/payment/invoice');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'merchant_api_key: ' . OXAPAY_MERCHANT_KEY,
    ],
]);

$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    error_log('OxaPay cURL error: ' . $err);
    echo json_encode(['error' => 'Erreur réseau.']); exit;
}

$result = json_decode($res, true);

if (isset($result['status']) && (int)$result['status'] === 200 && isset($result['data']['payment_url'])) {
    echo json_encode(['payment_url' => $result['data']['payment_url']]);
} else {
    $msg = isset($result['message']) ? $result['message'] : 'Erreur OxaPay.';
    error_log('OxaPay error: ' . $res);
    echo json_encode(['error' => $msg]);
}
