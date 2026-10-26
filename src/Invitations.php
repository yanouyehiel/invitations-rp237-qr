<?php
declare(strict_types=1);

final class Invitations
{
    private static function sign(string $id): string
    {
        $secret = env('QR_SECRET') ?? throw new RuntimeException('QR_SECRET manquant dans .env');
        return substr(hash_hmac('sha256', $id, $secret), 0, 16);
    }

    /** Contenu du QR code : "<id>.<signature>" */
    public static function token(string $id): string
    {
        return $id . '.' . self::sign($id);
    }

    /** Lien encodé dans le QR code : ouvert par l'appareil photo de n'importe quel téléphone */
    public static function url(string $id): string
    {
        return rtrim(env('APP_URL', ''), '/') . '/i.php?t=' . self::token($id);
    }

    public static function idFromToken(string $token): ?string
    {
        [$id, $sig] = array_pad(explode('.', trim($token), 2), 2, '');
        return $id !== '' && hash_equals(self::sign($id), $sig) ? $id : null;
    }

    public static function create(string $nom, string $email): array
    {
        if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Nom ou email invalide');
        }
        $email = strtolower($email);
        $dejaInvite = 'Cet email est déjà enregistré : utilisez « Renvoyer » dans la liste pour renvoyer le mail.';
        if (self::findByEmail($email)) {
            throw new RuntimeException($dejaInvite);
        }
        $id = bin2hex(random_bytes(8));
        try {
            db()->prepare('INSERT INTO invitations (id, nom, email, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP())')->execute([$id, $nom, $email]);
        } catch (PDOException $e) {
            throw $e->getCode() === '23000' ? new RuntimeException($dejaInvite) : $e; // doublon simultané
        }
        return self::find($id);
    }

    public static function find(string $id): ?array
    {
        $st = db()->prepare('SELECT * FROM invitations WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $st = db()->prepare('SELECT * FROM invitations WHERE email = ?');
        $st->execute([strtolower(trim($email))]);
        return $st->fetch() ?: null;
    }

    public static function all(): array
    {
        return db()->query('SELECT * FROM invitations ORDER BY created_at DESC, nom')->fetchAll();
    }

    /** Valide l'entrée. Le UPDATE conditionnel est atomique : un QR ne passe qu'une fois. */
    public static function checkin(string $token): array
    {
        $id = self::idFromToken($token);
        if (!$id) {
            return ['ok' => false, 'error' => 'QR invalide'];
        }
        $st = db()->prepare('UPDATE invitations SET used_at = UTC_TIMESTAMP() WHERE id = ? AND used_at IS NULL');
        $st->execute([$id]);

        $inv = self::find($id);
        if (!$inv) {
            return ['ok' => false, 'error' => 'Invitation inconnue'];
        }
        return $st->rowCount()
            ? ['ok' => true, 'nom' => $inv['nom']]
            : ['ok' => false, 'error' => 'Déjà utilisé', 'nom' => $inv['nom'], 'used_at' => $inv['used_at']];
    }
}
