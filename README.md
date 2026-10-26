# Invitations QR

Génération de QR codes signés, envoi par email et contrôle à l'entrée (PHP 8.1+, MySQL 5.7+/MariaDB).

## Installation

```bash
mysql -u root -p -e "CREATE DATABASE invitations CHARACTER SET utf8mb4;"
composer install
cp .env.example .env      # renseigner APP_URL, DB_*, QR_SECRET, ADMIN_KEY, SMTP_*, MAIL_FROM
php bin/migrate.php       # crée la table
php -S localhost:8000 -t public
```

## Utilisation

- **Admin** : `/` (mot de passe HTTP Basic = `ADMIN_KEY`, identifiant libre) : ajouter un invité, l'inviter, renvoyer, suivre les entrées.
- **Import en masse** : `php bin/import.php invites.csv` (colonnes `nom,email`). Relançable sans doublon.
- **Contrôle à l'entrée (sans scanner intégré)** : le QR contient un lien `APP_URL/i.php?t=...`. Le contrôleur le scanne avec l'appareil photo de son téléphone (ou n'importe quelle appli de scan) et arrive sur la page de l'invité :
  - tout le monde voit le nom et le statut (valide / déjà utilisée) ;
  - le contrôleur saisit une fois sa clé (`ADMIN_KEY`, section « Espace contrôleur ») pour voir l'email et appuyer sur **Valider l'entrée**.

## Fonctionnement

- Le QR contient `APP_URL/i.php?t=<id>.<signature HMAC>` : impossible à falsifier sans `QR_SECRET`.
- Ouvrir le lien ne consomme **jamais** l'invitation (aperçus de liens et curieux sans effet) : seul le bouton « Valider l'entrée » le fait.
- La validation fait un `UPDATE ... WHERE used_at IS NULL` atomique : un QR ne passe qu'une fois, même avec plusieurs contrôleurs.
- Dates stockées en UTC, affichées à l'heure de Douala.

## Production

- Servir **uniquement `public/`** (racine web), en **HTTPS** (le lien du QR et la clé contrôleur doivent être protégés).
- Ne jamais commiter `.env`.
- Utiliser un utilisateur MySQL dédié (droits limités à la base `invitations`) et faire un `mysqldump` avant l'événement.
