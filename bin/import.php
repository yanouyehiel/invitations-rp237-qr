<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

// Usage : php bin/import.php invites.csv   (colonnes : nom,email)
$file = $argv[1] ?? exit("Usage : php bin/import.php invites.csv\n");
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: exit("Fichier illisible\n");

foreach ($lines as $line) {
    [$nom, $email] = array_map('trim', str_getcsv($line) + [1 => '']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        continue; // en-tête ou ligne invalide
    }
    try {
        $inv = Invitations::findByEmail($email) ?? Invitations::create($nom, $email);
        if ($inv['sent_at']) {
            echo "= $email (déjà envoyé)\n";
            continue;
        }
        QrMailer::send($inv);
        echo "✔ $email\n";
    } catch (Throwable $e) {
        echo "✘ $email : {$e->getMessage()}\n";
    }
}
