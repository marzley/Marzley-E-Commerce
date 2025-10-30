<?php
// STK initiation endpoint
// Note: keep your consumerKey/consumerSecret and passkey secret in env or config, not in source for production.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

// Simple helper to output a styled message
function output_card($html, $bg = '#eef9f0', $color = '#133f1e') {
    echo '<div style="font-family:system-ui;padding:18px;border-radius:10px;background:' . $bg . ';color:' . $color . ';">';
    echo $html;
    echo '</div>';
}

// validate required inputs
$rawPhone = $_POST['phone'] ?? '';
$rawAmount = $_POST['amount'] ?? '';

// normalize and validate phone number to format 2547XXXXXXXX
function normalize_phone($p) {
    $p = trim($p);
    if ($p === '') return false;
    // remove spaces, dashes, parentheses
    $p = preg_replace('/[^\d+]/', '', $p);
    if (strpos($p, '+') === 0) $p = substr($p, 1);
    // 07XXXXXXXX
    if (preg_match('/^0(7\d{8})$/', $p, $m)) return '254' . $m[1];
    // 7XXXXXXXX
    if (preg_match('/^(7\d{8})$/', $p, $m)) return '254' . $m[1];
    // 2547XXXXXXXX
    if (preg_match('/^2547\d{8}$/', $p)) return $p;
    return false;
}

$phone = normalize_phone($rawPhone);
if (!$phone) {
    http_response_code(400);
    output_card('<strong>Error:</strong> Invalid phone number. Use 2547XXXXXXXX or 07XXXXXXXX format.', '#ffecec', '#9b1b1b');
    exit;
}

// sanitize amount - allow numbers and dot, then convert to integer KES (STK usually expects integer)
$cleanAmount = preg_replace('/[^0-9.]/', '', (string)$rawAmount);
$amountFloat = (float)$cleanAmount;
$amount = (int) round($amountFloat); // STK push expects integer amount (KES)
if ($amount <= 0) {
    http_response_code(400);
    output_card('<strong>Error:</strong> Invalid amount. Please return to cart and try again.', '#ffecec', '#9b1b1b');
    exit;
}

// set timezone
date_default_timezone_set('Africa/Nairobi');

// NOTE: move these credentials to environment/config for production
$consumerKey = '6UUyNe7APGWZJKjQUUEr6fAZ5nwS4aCygL0IdNACJydITK1Y';
$consumerSecret = 'AWpAR7AC8e1fWCk8WUL4xI3TVWvc0XYub7vIHGUMB3SzBNvjqJJoHbXZrZXQzb6k';

$BusinessShortCode = '174379';
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

$AccountReference = 'Marzley Tech Solutions';
$TransactionDesc = 'Thank you for shopping with us';

// timestamp & password
$Timestamp = date('YmdHis');
$Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

// endpoints
$access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

// callback url - set to your reachable HTTPS endpoint
$CallBackURL = 'https://your-callback-url.example.com/911/MPESA/callback_url.php';

// request access token
$headers = ['Content-Type:application/json; charset=utf8'];
$ch = curl_init($access_token_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
$result = curl_exec($ch);
if ($result === false) {
    $err = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    output_card('<strong>Error:</strong> Failed to request access token. ' . htmlspecialchars($err), '#ffecec', '#9b1b1b');
    exit;
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tokenData = json_decode($result, true);
if (!isset($tokenData['access_token'])) {
    http_response_code(500);
    output_card('<strong>Error:</strong> Could not obtain access token. Response: <pre style="white-space:pre-wrap;">' . htmlspecialchars($result) . '</pre>', '#fff8e6', '#7a4b00');
    exit;
}
$access_token = $tokenData['access_token'];

// prepare stk push
$stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];

$curl_post_data = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $Password,
    'Timestamp' => $Timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $CallBackURL,
    'AccountReference' => $AccountReference,
    'TransactionDesc' => $TransactionDesc
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $initiate_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $stkheader);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$curl_response = curl_exec($ch);
if ($curl_response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    output_card('<strong>Error:</strong> Failed to submit STK request. ' . htmlspecialchars($err), '#ffecec', '#9b1b1b');
    exit;
}
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// decode response
$resp = json_decode($curl_response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($resp)) {
    $code = $resp['ResponseCode'] ?? null;
    $checkout = $resp['CheckoutRequestID'] ?? null;
    $desc = $resp['ResponseDescription'] ?? ($resp['errorMessage'] ?? ($resp['message'] ?? 'Response received'));

    if ($code === '0' || $code === 0) {
        // success - centered box with icons
        $html = '
        <div style="max-width:720px;margin:18px auto;font-family:system-ui;text-align:center;">
          <div style="display:inline-block;background:#fff;border-radius:14px;padding:20px 24px;box-shadow:0 12px 30px rgba(20,40,60,0.08);min-width:320px;">
            <div style="width:64px;height:64px;border-radius:12px;margin:0 auto 12px;background:linear-gradient(135deg,#34a853,#1e7b34);display:flex;align-items:center;justify-content:center;color:#fff;">
              <svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>

            <h3 style="margin:0 0 8px;font-size:20px;color:#133f1e;">Request submitted</h3>
            <p style="margin:0 0 16px;color:#3b4b5a;line-height:1.35;">
              Your payment prompt was sent successfully. Check your phone to complete the payment.
            </p>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
              <div style="min-width:220px;background:#ffffff;border-radius:10px;padding:10px 14px;box-shadow:0 6px 18px rgba(0,0,0,0.04);display:flex;align-items:center;gap:12px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.07 4.18 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.72c.12.9.38 1.76.78 2.56a2 2 0 0 1-.45 2.11L9.91 9.91a15.05 15.05 0 0 0 6 6l1.52-1.52a2 2 0 0 1 2.11-.45c.8.4 1.66.66 2.56.78A2 2 0 0 1 22 16.92z" stroke="#1f7a3a" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div style="text-align:left;min-width:0;">
                  <div style="font-size:13px;color:#1f7a3a;font-weight:700;">Reference</div>
                  <div title="' . htmlspecialchars($checkout, ENT_QUOTES, 'UTF-8') . '" style="font-size:13px;color:#0b3b24;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:260px;">' . htmlspecialchars($checkout, ENT_QUOTES, 'UTF-8') . '</div>
                </div>
              </div>

              <div style="min-width:160px;background:#fff7ef;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:12px;box-shadow:0 6px 12px rgba(0,0,0,0.03);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 1v22" stroke="#e67e22" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M17 5H9.5a3 3 0 0 0 0 6H15a3 3 0 0 1 0 6H6" stroke="#e67e22" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div style="text-align:left;">
                  <div style="font-size:13px;color:#b85b12;font-weight:700;">Amount</div>
                  <div style="font-size:13px;color:#8a4a0f;">KES ' . htmlspecialchars((string)$amount, ENT_QUOTES, 'UTF-8') . '</div>
                </div>
              </div>
            </div>
          </div>
        </div>';
        output_card($html, '#ffffff', '#133f1e');
    } else {
        // API returned an error
        output_card('<strong>Notice:</strong> ' . htmlspecialchars($desc) . '<br><small>HTTP code: ' . intval($http_code) . '</small>', '#fff8e6', '#7a4b00');
    }
} else {
    // non-JSON response - show raw for debugging
    output_card('<strong>Raw response:</strong><pre style="white-space:pre-wrap;">' . htmlspecialchars($curl_response) . '</pre>', '#f6f8fa', '#222');
}

exit;
?>
