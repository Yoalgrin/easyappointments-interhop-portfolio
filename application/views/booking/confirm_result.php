<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Confirmation de rendez-vous</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root { --fg:#222; --ok:#0a7f2e; --warn:#b45309; --err:#b91c1c; --muted:#555; --bg:#fff; }
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; color:var(--fg); background:var(--bg); margin:0; }
        .wrap { max-width:720px; margin:6vh auto; padding:24px; }
        .card { border:1px solid #e5e7eb; border-radius:12px; padding:24px; }
        h1 { margin:0 0 8px; font-size:1.4rem; }
        p { margin:0 0 12px; line-height:1.5; color:var(--muted); }
        .ok { color:var(--ok); }
        .warn { color:var(--warn); }
        .err { color:var(--err); }
        a.btn { display:inline-block; padding:10px 14px; border:1px solid #e5e7eb; border-radius:10px; text-decoration:none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <?php if ($state === 'confirmed'): ?>
            <h1 class="ok">Merci, votre rendez-vous est confirmé.</h1>
            <p>Un e-mail de confirmation va vous être envoyé. Vous pouvez fermer cette page.</p>

        <?php elseif ($state === 'expired'): ?>
            <h1 class="warn">Lien expiré.</h1>
            <p>Le lien de confirmation n’est plus valide. Reprenez la prise de rendez-vous pour recevoir un nouveau lien.</p>

        <?php elseif ($state === 'cancelled'): ?>
            <h1 class="warn">Ce lien est désactivé.</h1>
            <p>La confirmation a été annulée. Reprenez la prise de rendez-vous si nécessaire.</p>

        <?php else: /* invalid / fallback */ ?>
            <h1 class="err">Lien invalide.</h1>
            <p>Le jeton fourni est inconnu ou a déjà été utilisé. Vérifiez l’e-mail reçu et réessayez.</p>
        <?php endif; ?>

        <p><a class="btn" href="<?= base_url('booking'); ?>">Retour à la prise de rendez-vous</a></p>
    </div>
</div>
</body>
</html>

