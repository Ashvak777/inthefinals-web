<?php
/**
 * InTheFinals signup form → email info@inthefinals.ai
 * Hostinger: upload this with the site. Requires PHP mail enabled.
 */
declare(strict_types=1);

header('X-Content-Type-Options: nosniff');

function respond(bool $ok, string $message, int $code = 200): void
{
    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch');

    if ($wantsJson) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_SLASHES);
        exit;
    }

    $target = $ok ? './?sent=1' : './?error=1';
    header('Location: ' . $target, true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}

// Honeypot — bots fill this; humans leave it empty
if (!empty($_POST['company_website'] ?? '')) {
    respond(true, 'Thanks — we received your request.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));

if ($name === '' || mb_strlen($name) > 120) {
    respond(false, 'Please enter your name.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
    respond(false, 'Please enter a valid email.', 422);
}
if ($phone === '' || mb_strlen($phone) > 40) {
    respond(false, 'Please enter your phone number.', 422);
}

$to = 'info@inthefinals.ai';
$from = 'info@inthefinals.ai';
$safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safePhone = htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$when = (new DateTimeImmutable('now', new DateTimeZone('America/Toronto')))->format('M j, Y g:i A T');

$subject = 'New InTheFinals signup — ' . $name;

$textBody = "New signup request from inthefinals.ai\n\n"
    . "Name:  {$name}\n"
    . "Email: {$email}\n"
    . "Phone: {$phone}\n"
    . "When:  {$when}\n"
    . "Page:  https://inthefinals.ai/signup/\n";

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>New signup</title></head>
<body style="margin:0;padding:0;background:#0a1220;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#f1f5f9;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0a1220;padding:32px 16px;">
    <tr><td align="center">
      <table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;width:100%;background:#111b2e;border:1px solid rgba(255,255,255,0.1);border-radius:16px;overflow:hidden;">
        <tr>
          <td style="padding:24px 28px;background:#0d1628;border-bottom:1px solid rgba(255,255,255,0.1);">
            <div style="font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#7dd3fc;font-weight:700;">InTheFinals</div>
            <div style="margin-top:8px;font-size:22px;font-weight:700;color:#ffffff;">New signup request</div>
            <div style="margin-top:6px;font-size:14px;color:#94a3b8;">Submitted from inthefinals.ai</div>
          </td>
        </tr>
        <tr>
          <td style="padding:28px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
              <tr>
                <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:13px;color:#64748b;width:110px;">Name</td>
                <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:16px;color:#f1f5f9;font-weight:600;">{$safeName}</td>
              </tr>
              <tr>
                <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:13px;color:#64748b;">Email</td>
                <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:16px;">
                  <a href="mailto:{$safeEmail}" style="color:#7dd3fc;text-decoration:none;font-weight:600;">{$safeEmail}</a>
                </td>
              </tr>
              <tr>
                <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:13px;color:#64748b;">Phone</td>
                <td style="padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.08);font-size:16px;">
                  <a href="tel:{$safePhone}" style="color:#f1f5f9;text-decoration:none;font-weight:600;">{$safePhone}</a>
                </td>
              </tr>
              <tr>
                <td style="padding:12px 0;font-size:13px;color:#64748b;">Submitted</td>
                <td style="padding:12px 0;font-size:15px;color:#94a3b8;">{$when}</td>
              </tr>
            </table>
            <div style="margin-top:24px;">
              <a href="mailto:{$safeEmail}?subject=Re%3A%20InTheFinals%20access" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 18px;border-radius:999px;">Reply to applicant</a>
            </div>
          </td>
        </tr>
        <tr>
          <td style="padding:16px 28px;background:#0d1628;border-top:1px solid rgba(255,255,255,0.1);font-size:12px;color:#64748b;">
            Sent automatically by the InTheFinals signup form · ASH IT Consulting Inc.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

$boundary = 'itf_' . bin2hex(random_bytes(8));
$headers = [
    'From: InTheFinals Signup <' . $from . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
    'X-Mailer: InTheFinals-Hostinger',
];

$body = "--{$boundary}\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $textBody . "\r\n"
    . "--{$boundary}\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $htmlBody . "\r\n"
    . "--{$boundary}--\r\n";

$sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

if (!$sent) {
    respond(false, 'Could not send right now. Email info@inthefinals.ai directly.', 500);
}

respond(true, 'Thanks — we received your request and will follow up soon.');
