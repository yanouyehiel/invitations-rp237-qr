<?php
declare(strict_types=1);

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use PHPMailer\PHPMailer\PHPMailer;

final class QrMailer
{
    public static function png(string $data): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'scale' => 10,
            'quietzoneSize' => 2,
        ])))->render($data);
    }

    public static function send(array $inv): void
    {
        $event = env('EVENT_NAME', 'Événement');

        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host = env('SMTP_HOST', '');
        $m->Port = (int) env('SMTP_PORT', '587');
        $m->SMTPSecure = env('SMTP_SECURE', 'tls');
        $m->SMTPAuth = true;
        $m->Username = env('SMTP_USER', '');
        $m->Password = env('SMTP_PASS', '');
        $m->CharSet = 'UTF-8';

        $m->setFrom(env('MAIL_FROM', ''), $event);
        $m->addAddress($inv['email'], $inv['nom']);
        $m->Subject = "Votre invitation – $event";
        $png = self::png(Invitations::url($inv['id']));
        $m->addStringEmbeddedImage($png, 'qr', 'qr.png', 'base64', 'image/png');
        $m->isHTML(true);
        $nom = htmlspecialchars($inv['nom']);
        $ev = htmlspecialchars($event);
        $m->Body = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;border:1px solid #dbe3de;border-radius:10px;overflow:hidden">'
            . '<div style="background:#0b6b45;color:#fff;padding:18px 22px;border-bottom:5px solid #f7c515">'
            . "<div style=\"color:#f7c515;font-size:12px;font-weight:bold;letter-spacing:1px\">RP237 · INVITATION</div>"
            . "<div style=\"font-size:20px;font-weight:bold\">$ev</div></div>"
            . '<div style="padding:22px;color:#16241d">'
            . "<p>Bonjour <b>$nom</b>,</p>"
            . "<p>Vous êtes invité(e) à <b>$ev</b>. Présentez ce QR code à l'entrée :</p>"
            . '<p style="text-align:center"><img src="cid:qr" width="260" height="260" alt="QR code" style="border:4px solid #f7c515;border-radius:8px"></p>'
            . '<p style="color:#6b7a72;font-size:13px">Ce code est personnel et ne peut être utilisé qu\'une seule fois.</p>'
            . '</div><div style="background:#d8232a;height:5px"></div></div>';
        $m->AltBody = "Bonjour {$inv['nom']}, votre invitation à $event est en pièce jointe (QR code).";
        $m->addStringAttachment($png, 'invitation-qr.png', 'base64', 'image/png');
        $m->send();

        db()->prepare('UPDATE invitations SET sent_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$inv['id']]);
    }
}
