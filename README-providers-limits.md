# 🧩 Feature 22 — Limitation du nombre de patient(e)s par soignant(e)
### InterHop — EasyAppointments modifié (branche actuelle : `feature/22-customer-limit-by-provider`)

> **Statut** : ✅ fonctionnalité livrée et testée sur la branche **`feature/22-customer-limit-by-provider`**.  
> **Modularisation** (hooks-only packagée) : ⏳ à faire (voir plan en fin de document).

---

## 📚 Sommaire
1. [🎯 Objectif](#-objectif)
2. [🧱 Ce qui est en place aujourd’hui](#-ce-qui-est-en-place-aujourdhui)
3. [🧮 Comportement fonctionnel](#-comportement-fonctionnel)
4. [🧪 Tests réalisés](#-tests-réalisés)
5. [⚠️ Limites connues](#️-limites-connues)
6. [🚀 Prochaines étapes : modularisation (plan court)](#-prochaines-étapes--modularisation-plan-court)
7. [✍️ Auteur et encadrement](#️-auteur-et-encadrement)

---

## 🎯 Objectif
Limiter le nombre de **patient(e)s distinct(e)s** pouvant réserver avec un(e) soignant(e), sans bloquer les patient(e)s **déjà suivi(e)s**.

---

## 🧱 Ce qui est en place aujourd’hui

### 🗄️ Base de données
- Table : `ea_interhop_providers_limits`
    - `provider_id` (INT)
    - `max_patients` (INT/NULL → **NULL = illimité**)
- Persistance réalisée à l’enregistrement des écrans concernés (admin / compte).

### 🔤 Traductions
- Hook **`InterhopTranslationHook`**  
  Ajoute les clés (`max_patients`, `max_patients_placeholder`, etc.) et alias legacy.

### 💾 Sauvegarde de la limite
- Hook **`InterhopAccountHook`** (version actuelle)  
  Logique de sauvegarde depuis les écrans Account/Providers (selon rôle).  
  *(NB : édition soignant pouvant être bloquée côté contrôles/flags si nécessaire.)*

### 🧮 Enforcement (contrôle de la limite)
- Règle métier : **autoriser** un patient déjà connu du soignant ; **bloquer** un nouveau patient si la limite est atteinte.
- La vérification se base sur les **patients distincts** non annulés.

> ⚙️ **Note** : l’interception via hook de la route “création de RDV” et l’injection d’un message d’erreur front (HTTP 409) sont faciles à ajouter (voir plan “Modularisation”).

---

## 🧮 Comportement fonctionnel

| Cas | Résultat |
|------|----------|
| `max_patients = NULL` | Illimité |
| Nouveau patient au-delà de la limite | ❌ Refus |
| Patient déjà connu | ✅ Autorisé |
| RDV annulé | 🚫 Non compté |

---

## 🧪 Tests réalisés
- Création de `N` patient(e)s distinct(e)s → OK jusqu’à la limite.
- `N+1` nouveau/elle patient(e) → refus (côté back).
- Patient déjà connu → autorisé.
- Valeur `NULL` (= illimité) → aucune restriction.

*(Les messages d’erreur front peuvent être finalisés via un petit `booking-override.js` — cf. plan ci-dessous.)*

---

## ⚠️ Limites connues
- Pas encore de **packaging “hooks-only” complet** (branche `-modulable` non implémentée).
- Message d’erreur côté **front** perfectible (idéalement : intercept **HTTP 409** et affichage clair).
- Pas encore de **flag** centralisé “on/off” dans `config/interhop.php` (facile à ajouter).

---

## 🚀 Prochaines étapes : modularisation (plan court)

> **But** : isoler la feature pour qu’elle soit **activable/désactivable**, sans dépendre d’une modification des écrans, et avec un **message front** propre.

### 1️⃣ Créer la branche dédiée
~~~bash
git checkout develop
git pull --ff-only
git checkout -b feature/22-customer-limit-by-provider-modulable
~~~

### 2️⃣ Ajouter un flag de configuration
Créer (ou compléter) le fichier `application/config/interhop.php` :

~~~php
<?php defined('BASEPATH') or exit('No direct script access allowed');

$config['interhop_providers_limit_enabled'] = '1';             // 0 pour désactiver entièrement la feature
$config['interhop_providers_limit_allow_provider_edit'] = '0'; // 1 pour autoriser l’édition côté soignant (Account)
~~~

> 💡 Avec ces flags, tu peux activer/désactiver la feature sans toucher au code.

### 3️⃣ Créer un hook d’enforcement (post_controller)
- Intercepter la route de **création de rendez-vous** (`ajax/appointments/save` ou équivalent).
- Vérifier :
    - Si la **limite** du soignant est atteinte ;
    - Si le **patient** est **nouveau** (non déjà suivi).
- Si le quota est dépassé :
    - **HTTP 409**
    - JSON :
      ~~~json
      { "success": false, "message": "Ce soignant a atteint la limite de patient·e·s autorisés." }
      ~~~

### 4️⃣ Injecter un JS côté front
Créer `assets/js/backend/booking-override.js` et l’injecter via le hook (post_controller_constructor) :
- Intercepter `.fetch()` et/ou `$.ajax` liés à la création de RDV ;
- Si **409**, afficher le message retourné ;
- Ne rien modifier au core EA.

### 5️⃣ Injecter des scripts UI pour Admin / Compte soignant
- `assets/js/interhop/providers-limit-admin.js`  
  ➜ Champ *“Limite de patient·e·s (max)”* côté **Admin** (lecture/écriture via mini-API ou form).
- `assets/js/interhop/providers-limit-account-ro.js`  
  ➜ **Lecture seule** côté **soignant** (affichage de la valeur courante).

### 6️⃣ Mettre à jour le README
- Cocher la section : “✅ Modularisation effectuée (branche `feature/22-customer-limit-by-provider-modulable`)”.
- Lister :
    - Le hook **enforcement** + l’**injection** de scripts ;
    - Les **flags** de configuration ;
    - Les scripts **Admin / Account / Booking**.

---

## ✍️ Auteur et encadrement

| Rôle | Nom                                  |
|------|--------------------------------------|
| 👨‍💻 Développement & intégration | **Gabriel [Nom]**                    |
| 🧑‍🏫 Encadrement | *Adrien PARROT*                      |
| 🏢 Structure | InterHop / EasyAppointments InterHop |
| 📅 Période | Stage du 22/10/2025 au 14/11/2025    |

---

✅ **Cette version du README correspond à l’état actuel du projet (`feature/22-customer-limit-by-provider`)**,  
tout en intégrant un plan clair et structuré pour la future **modularisation** (`feature/22-customer-limit-by-provider-modulable`).
