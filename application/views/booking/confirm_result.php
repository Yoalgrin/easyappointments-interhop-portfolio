<?php
$messages = [
  'confirmed' => "Merci, votre rendez-vous est confirmé.",
  'expired'   => "Lien expiré. Veuillez reprendre un rendez-vous.",
  'invalid'   => "Lien invalide.",
  'cancelled' => "Cette demande a été annulée.",
  'pending'   => "Un e-mail de confirmation vient de vous être envoyé."
];
?>
<div style="margin:2rem 0;font-family:sans-serif">
  <p><?= $messages[$state] ?? "Statut: " . htmlspecialchars($state) ?></p>
</div>
