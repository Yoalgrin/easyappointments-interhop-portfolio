# easyappointments-interhop-portfolio
Portfolio technique – Travail réalisé sur le fork InterHop d’EasyAppointments (migrations, hooks, overrides, features 5, 8 et 22).
➡️ Le code principal (features 5, 8, 22, pipeline d’assets) se trouve sur la branche [`develop`](https://github.com/Yoalgrin/easyappointments-interhop-portfolio/tree/develop).

# EasyAppointments – InterHop – Portfolio Gabriel Vigou-Guitart

## 🎯 Objectif du dépôt
Ce dépôt sert de **portfolio technique**.  
Il présente **les parties de code que j’ai personnellement développées** dans le cadre du fork EasyAppointments utilisé par InterHop.

➡️ Le projet complet EasyAppointments appartient à ses auteurs d’origine.  
➡️ Ce dépôt ne contient **que** mes contributions : migrations, hooks, overrides, et extraits de fonctionnalités.

---

## 🏥 Contexte
- Stage de 2 mois + mission freelance chez **InterHop**  
- Travail sur un fork d’**EasyAppointments** (application web de prise de rendez-vous)  
- Technologies : **PHP (CodeIgniter 3)**, **JavaScript**, **HTML/CSS**, **MariaDB**, **Nginx**, **Gulp**, **Hooks CI3**, **Overrides**  
- Travail réel sur un projet en production (pas un projet scolaire)

---

## 🧩 Mes contributions principales

### 🔵 Feature 5 – Confirmation d’email anti-spam
- Migration créant la table `ea_appointment_email_confirmations`
- Génération et validation de tokens
- Expiration du token (TTL 300 secondes)
- Envoi d’email de confirmation
- Intégration dans le workflow de réservation

### 🔵 Feature 8 – Harmonisation des vocabulaires
- Remplacement des termes métier dans l’interface :
  - “client” → “patient”
  - “exécutant” → “soignant”
  - “prestation” → “consultation”
- Overrides JS/CSS
- Modifications de vues dans les dossiers InterHop

### 🔵 Feature 22 – Limitation du nombre de patients par soignant
- Création de la migration `062_interhop_providers_limits`
- Table de configuration par soignant
- Hooks pour intégrer la valeur dans la page profil
- Contrôle côté serveur dans Booking
- Message d’erreur propre côté UI
- Version **modulaire**, sans modifier le core, via hooks et overrides

---

## 💡 Architecture des contributions

Ce dépôt contient uniquement :
- Mes **migrations**
- Mes **hooks**
- Mes **overrides JS/CSS**
- Mes **extraits de code commentés** (controllers/models)
- Schémas / workflow utiles à la compréhension

Le dépôt n’inclut pas :
- Le code complet du projet EasyAppointments
- Les parties du fork InterHop qui ne m’appartiennent pas

Pour consulter le projet d’origine :
➡️ https://github.com/alextselegidis/easyappointments

---

## 🧠 Objectif professionnel

Ce dépôt existe pour :
- Montrer mon **niveau réel** en PHP / CodeIgniter / JS
- Démontrer ma capacité à intervenir dans un projet complexe existant
- Servir de support à ma **soutenance ENI**
- Présenter mes compétences aux **recruteurs et entreprises** (Mâcon / Lyon)

---

## 👤 Auteur
**Gabriel Vigou-Guitart**  
Micro-entrepreneur – Studio web & code GVG  
Développeur Web (PHP, WordPress, JS, Linux)  
