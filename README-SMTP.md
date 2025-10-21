# 📧 Configuration SMTP — EasyAppointments / InterHop

Ce guide explique comment configurer l’envoi d’e-mails pour l’application, aussi bien en **développement** qu’en **recette / production**.

---

## 🔧 1. Prérequis

- PHP-FPM 8.3 (recommandé) + Nginx  
- Base de données opérationnelle  
- (Environnement dev) : [Mailpit](https://github.com/axllent/mailpit)

```bash
sudo docker run -d --name mailpit -p 1025:1025 -p 8025:8025 axllent/mailpit
```

Interface : [http://localhost:8025](http://localhost:8025)  
SMTP : `localhost:1025` *(pas d’authentification)*

---

## ⚙️ 2. Comportement général

L’application charge la configuration SMTP via les **variables d’environnement**.

| Variable | Description |
|-----------|--------------|
| `EA_SMTP_DRIVER` | Définit le mode : `mailhog`, `riseup`, `personal` |
| `EA_SMTP_HOST` / `PORT` / `CRYPTO` | Hôte, port et type de chiffrement |
| `EA_SMTP_USER` / `EA_SMTP_PASS` | Identifiants SMTP |
| `EA_EMAIL_FROM` / `EA_EMAIL_FROM_NAME` | Adresse et nom expéditeur |

---

## 🧪 3. Environnement de développement — Mailpit

Configuration dans `/etc/php/8.3/fpm/pool.d/www.conf` :

```ini
env[EA_SMTP_DRIVER] = mailhog
; pas d’authentification pour Mailpit
```

Recharger les services :

```bash
sudo systemctl reload php8.3-fpm
sudo nginx -t && sudo systemctl reload nginx
```

### ✅ Test

1. Lancer Mailpit (voir ci-dessus).  
2. Créer un **rendez-vous test** depuis l’application.  
3. Vérifier le mail dans [http://localhost:8025](http://localhost:8025).  
4. Cliquer le lien → la page de confirmation s’affiche correctement.

---

## 🚀 4. Recette / Production — Riseup

Configuration FPM :

```ini
env[EA_SMTP_DRIVER] = riseup
env[EA_SMTP_HOST]   = mail.riseup.net
env[EA_SMTP_PORT]   = 587
env[EA_SMTP_CRYPTO] = tls
env[EA_SMTP_USER]   = interhop@riseup.net
env[EA_SMTP_PASS]   = (mot_de_passe_riseup)
env[EA_EMAIL_FROM]  = interhop@riseup.net
env[EA_EMAIL_FROM_NAME] = "Prise de RDV InterHop"
```

Rechargement :

```bash
sudo systemctl reload php8.3-fpm
sudo nginx -t && sudo systemctl reload nginx
```

### 🔍 Vérification réseau (optionnelle)

```bash
openssl s_client -starttls smtp -connect mail.riseup.net:587 -crlf -ign_eof </dev/null | head
```

---

## ⚙️ 5. Variables applicatives utiles

```dotenv
EA_REQUIRE_EMAIL_CONFIRMATION=true
EA_CONFIRM_TTL_SECONDS=300
EA_CONFIRM_RESEND_COOLDOWN=120
APP_ENV=development            # ou production
APP_URL=http://localhost:8080  # URL publique
```

---

## 🧰 6. Dépannage rapide

| Problème | Cause probable | Solution |
|-----------|----------------|-----------|
| **111 Connection refused** | Service SMTP non accessible | En dev : Mailpit non lancé ; en prod : vérifier port / pare-feu |
| **535 Authentication failed** | Identifiants ou From invalides | Vérifier `EA_SMTP_USER/PASS` et `EA_EMAIL_FROM` |
| **Lien invalide** | Token inexistant ou expiré | Créer un nouveau rendez-vous test |
| **502 après modif FPM** | Mauvaise syntaxe `env[...]` | Entourer les valeurs contenant des espaces de guillemets |

---

## 🔒 7. Sécurité

- Ne **jamais** committer d’identifiants.
- Ajouter dans `.gitignore` :
  ```
  .env
  */.env
  ```
- Supprimer les utilitaires de test (`DevMail.php`, `tools/env-check.php`).
- Si un mot de passe a été committé, **le régénérer** côté fournisseur.

---

## 🔁 8. Rollback rapide (mode Mailpit)

En cas de panne SMTP en recette / prod :

```ini
env[EA_SMTP_DRIVER] = mailhog
```

Recharger PHP-FPM et Nginx → les mails sont capturés par Mailpit (aucun envoi réel).

---

## ✉️ 9. Fournisseurs alternatifs (optionnel)

**Yahoo (mot de passe d’application)** :

```ini
env[EA_SMTP_DRIVER] = personal
env[EA_SMTP_HOST]   = smtp.mail.yahoo.com
env[EA_SMTP_PORT]   = 465
env[EA_SMTP_CRYPTO] = ssl
env[EA_SMTP_USER]   = votre_adresse@yahoo.fr
env[EA_SMTP_PASS]   = (mdp_application)
env[EA_EMAIL_FROM]  = votre_adresse@yahoo.fr
```

**Outlook/Hotmail** :  
SMTP basic souvent **désactivé** (`5.7.139`). Utiliser Yahoo ou un service transactionnel (SendGrid, Brevo, Postmark).

---

## 📝 10. Commit suggéré

```bash
docs(email): add SMTP setup (Mailpit dev / Riseup prod)
chore(email): load SMTP from environment (mailhog / personal / riseup)
```
