<?php
declare(strict_types=1);
require is_file(__DIR__ . '/../src/bootstrap.php') ? __DIR__ . '/../src/bootstrap.php' : __DIR__ . '/../invitations-qr/src/bootstrap.php';

if (!adminKeyOk($_SERVER['PHP_AUTH_PW'] ?? null)) {
    header('WWW-Authenticate: Basic realm="Admin"');
    http_response_code(401);
    exit('Accès refusé');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            QrMailer::send(Invitations::create(trim($_POST['nom'] ?? ''), trim($_POST['email'] ?? '')));
            $msg = 'Invitation envoyée.';
        } elseif ($action === 'resend') {
            QrMailer::send(Invitations::find($_POST['id'] ?? '') ?? throw new RuntimeException('Invitation inconnue'));
            $msg = 'Invitation renvoyée.';
        }
    } catch (Throwable $e) {
        $msg = 'Erreur : ' . $e->getMessage();
    }
}

$rows = Invitations::all();
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
$sent = count(array_filter($rows, fn($r) => $r['sent_at']));
$used = count(array_filter($rows, fn($r) => $r['used_at']));
?>
<!doctype html>
<html lang="fr">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invitations – <?= $h(env('EVENT_NAME')) ?></title>
<link rel="stylesheet" href="style.css">
<header class="top"><small>RP237 · Invitations</small><h1><?= $h(env('EVENT_NAME')) ?></h1></header>
<main>
<div class="stats">
  <div class="stat"><b><?= count($rows) ?></b><span>Invités</span></div>
  <div class="stat"><b><?= $sent ?></b><span>Envoyées</span></div>
  <div class="stat"><b><?= $used ?></b><span>Entrées</span></div>
</div>
<?php if ($msg): ?><p class="msg"><?= $h($msg) ?></p><?php endif ?>

<form class="add" method="post">
  <input type="hidden" name="action" value="add">
  <input name="nom" placeholder="Nom" required>
  <input name="email" type="email" placeholder="Email" required>
  <button>Inviter et envoyer</button>
</form>

<div class="table-wrap"><table>
  <tr><th>Nom</th><th>Email</th><th>Envoyée</th><th>Entrée</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= $h($r['nom']) ?></td>
    <td><?= $h($r['email']) ?></td>
    <td class="<?= $r['sent_at'] ? 'ok' : 'no' ?>"><?= $r['sent_at'] ? '✔' : '—' ?></td>
    <td class="<?= $r['used_at'] ? 'ok' : 'no' ?>"><?= $r['used_at'] ? $h($r['used_at']) . ' UTC' : '—' ?></td>
    <td>
      <form method="post">
        <input type="hidden" name="action" value="resend">
        <input type="hidden" name="id" value="<?= $h($r['id']) ?>">
        <button>Renvoyer</button>
      </form>
    </td>
  </tr>
  <?php endforeach ?>
</table></div>
</main>
