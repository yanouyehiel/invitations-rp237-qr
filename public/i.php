<?php
declare(strict_types=1);
require is_file(__DIR__ . '/../src/bootstrap.php') ? __DIR__ . '/../src/bootstrap.php' : __DIR__ . '/../invitations-qr/src/bootstrap.php';

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'cookie_secure' => !empty($_SERVER['HTTPS'])]);
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
$local = fn(?string $utc) => $utc
    ? (new DateTime($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Africa/Douala'))->format('d/m/Y à H:i')
    : '';

$token = (string) ($_GET['t'] ?? '');
$id = Invitations::idFromToken($token);
$error = $just = null;

// Le simple affichage (GET) ne modifie jamais l'invitation : seule l'action "valider" d'un contrôleur le fait.
if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        if (adminKeyOk($_POST['key'] ?? null)) {
            $_SESSION['staff'] = true;
        } else {
            $error = 'Clé incorrecte';
        }
    } elseif ($action === 'validate' && !empty($_SESSION['staff'])) {
        $just = Invitations::checkin($token);
    }
}

$inv = $id ? Invitations::find($id) : null;
$staff = !empty($_SESSION['staff']);
?>
<!doctype html>
<html lang="fr">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>Invitation – <?= $h(env('EVENT_NAME')) ?></title>
<link rel="stylesheet" href="style.css">
<header class="top"><small>RP237 · Invitation</small><h1><?= $h(env('EVENT_NAME')) ?></h1></header>
<main class="narrow">
<?php if (!$inv): ?>
  <div class="status ko">✘ QR code invalide</div>
  <p class="muted">Ce lien ne correspond à aucune invitation.</p>
<?php else: ?>
  <?php if ($just && $just['ok']): ?>
    <div class="status ok">✔ Entrée validée</div>
  <?php elseif ($just): ?>
    <div class="status ko">✘ <?= $h($just['error']) ?></div>
  <?php elseif ($inv['used_at']): ?>
    <div class="status ko">✘ Déjà utilisée le <?= $h($local($inv['used_at'])) ?></div>
  <?php else: ?>
    <div class="status ok">✔ Invitation valide</div>
  <?php endif ?>

  <div class="card">
    <small>Invité(e)</small>
    <h2><?= $h($inv['nom']) ?></h2>
    <?php if ($staff): ?>
      <p><?= $h($inv['email']) ?></p>
      <p class="muted">Invitation envoyée le <?= $h($local($inv['sent_at']) ?: '—') ?></p>
    <?php endif ?>
  </div>

  <?php if ($staff && !$inv['used_at']): ?>
    <form method="post">
      <input type="hidden" name="action" value="validate">
      <button class="big">Valider l'entrée</button>
    </form>
  <?php elseif (!$staff): ?>
    <details class="staff">
      <summary>Espace contrôleur</summary>
      <?php if ($error): ?><p class="msg"><?= $h($error) ?></p><?php endif ?>
      <form method="post" class="add">
        <input type="hidden" name="action" value="login">
        <input type="password" name="key" placeholder="Clé contrôleur" required>
        <button>Accéder</button>
      </form>
    </details>
  <?php endif ?>
<?php endif ?>
</main>
