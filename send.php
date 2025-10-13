<?php
// send.php — Envío de formulario por SMTP con PHPMailer
header('Content-Type: text/html; charset=utf-8');

require __DIR__ . '/config.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';
require __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* Utilidad básica */
function s($v){ return trim(filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS)); }

/* Solo POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

/* Honeypot antispam (campo oculto) */
if (!empty($_POST['website'])) {
  http_response_code(200);
  exit('OK'); // silencioso para bots
}

/* Campos del form */
$name    = s($_POST['name']    ?? '');
$email   = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$phone   = s($_POST['phone']   ?? '');
$company = s($_POST['company'] ?? '');
$message = s($_POST['message'] ?? '');

if (!$name || !$email || !$phone) {
  http_response_code(400);
  exit('Faltan campos obligatorios.');
}

/* Construir correo */
$subject = "Nueva solicitud de cotización - $name";
$body  = "<h2>Nueva solicitud de cotización</h2>";
$body .= "<p><strong>Nombre:</strong> {$name}</p>";
$body .= "<p><strong>Correo:</strong> {$email}</p>";
$body .= "<p><strong>Teléfono:</strong> {$phone}</p>";
if ($company !== '') $body .= "<p><strong>Empresa:</strong> {$company}</p>";
if ($message !== '') $body .= "<p><strong>Mensaje:</strong><br>" . nl2br($message) . "</p>";
$body .= "<hr><p style='color:#777'>Enviado desde el formulario del sitio.</p>";

try {
  $mail = new PHPMailer(true);

  // SMTP
  $mail->isSMTP();
  $mail->Host       = SMTP_HOST;      // gcmaquinaria.com
  $mail->SMTPAuth   = true;
  $mail->Username   = SMTP_USERNAME;  // rentas@gcmaquinaria.com
  $mail->Password   = SMTP_PASSWORD;  // tu contraseña
  $mail->SMTPSecure = SMTP_SECURE;    // 'ssl'
  $mail->Port       = SMTP_PORT;      // 465

  // Cabeceras
  $mail->CharSet = 'UTF-8';
  $mail->isHTML(true);

  $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
  $mail->addAddress(MAIL_TO);
  if (defined('MAIL_BCC') && MAIL_BCC !== '') {
    $mail->addBCC(MAIL_BCC); // copia oculta opcional (ej. Gmail)
  }

  // Para poder responderte directo al cliente
  $mail->addReplyTo($email, $name);

  // Contenido
  $mail->Subject = $subject;
  $mail->Body    = $body;
  $mail->AltBody = strip_tags(
    "Nueva solicitud de cotización\n\n".
    "Nombre: $name\nCorreo: $email\nTeléfono: $phone\n".
    ($company ? "Empresa: $company\n" : "").
    ($message ? "Mensaje: $message\n" : "")
  );

  $mail->send();

  // === RESPUESTA ===
  // Opción A: mensaje simple (mantén si envías el form normal)
  echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
  echo '<div class="container py-5"><div class="alert alert-success">';
  echo '¡Gracias! Hemos recibido tu solicitud y te contactaremos pronto.';
  echo '</div><a class="btn btn-primary" href="./">Volver</a></div></html>';

  // O bien Opción B: si prefieres redirigir a una página de gracias:
  // header('Location: /gracias.html'); exit;

} catch (Exception $e) {
  // Si algo falla, muestra error genérico (no exponemos detalles)
  http_response_code(500);
  echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
  echo '<div class="container py-5"><div class="alert alert-danger">';
  echo 'Lo sentimos, no pudimos enviar tu solicitud. Intenta más tarde.';
  echo '</div><a class="btn btn-secondary" href="./">Volver</a></div></html>';

  // Para depurar (temporalmente):
  // echo $mail->ErrorInfo;
}
