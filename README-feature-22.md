# Feature 22 — Limite de patients par soignant (InterHop)

## 🎯 Objectif
Ajouter une fonctionnalité permettant de **limiter le nombre de patients** qu’un·e soignant·e peut suivre, entièrement modulable via le système d’overrides InterHop, **sans modifier le core EasyAppointments**.

Cette feature comprend :
- Une **table dédiée** : `ea_interhop_providers_limits`
- Un **endpoint API InterHop** pour GET / SET
- Un **champ d’édition** dans l’admin (édition d’un soignant)
- Un **champ en lecture seule / édition** dans le compte soignant (`/account`)
- Une logique stable et compatible avec les hooks + overrides JS/CSS

---

## 🧱 Architecture générale
```
JS (override) → Routes CI → InterhopProvidersLimit controller → Model → DB
                                     ↓
                                 JSON → DOM (champ)
```

### 📂 Fichiers principaux
- `application/controllers/InterhopProvidersLimit.php`
- `application/models/Interhop_providers_limit_model.php`
- `application/hooks/InterhopAccountHook.php` (upsert depuis /account)
- `application/hooks/InterhopProvidersCompatHook.php` (compatibilité POST providers)
- `assets/js/pages/interhop-account-override.js`
- `assets/js/pages/interhop-providers-override.js`
- `assets/js/pages/interhop-providers-http-override.js`
- `application/config/routes.php` (routes InterHop)

---

## 🗃️ Base de données
### Table : `ea_interhop_providers_limits`
```
provider_id (INT, PK, FK)
max_patients (INT, nullable)
updated_by (INT)
updated_at (DATETIME)
```

- `NULL` = aucune limite
- valeur ≥ 1 = limite active

---

## 🌐 Routage CodeIgniter
```
$route['interhop/providerslimit/get_self']    = 'InterhopProvidersLimit/get_self';
$route['interhop/providerslimit/get']         = 'InterhopProvidersLimit/get_self';
$route['interhop/providerslimit/get/(:num)']  = 'InterhopProvidersLimit/get/$1';
$route['interhop/providerslimit/set']         = 'InterhopProvidersLimit/set';
$route['interhop/providerslimit/upsert']      = 'InterhopProvidersLimit/upsert';
```

Ces routes sont indispensables pour :
- l’hydratation du champ sur `/account`
- l’hydratation et l’enregistrement côté admin

---

## 🔧 Fonctionnement contrôleur
### `get_self()`
Renvoie la limite **du soignant connecté**.

### `get($provider_id)`
Pour l’admin (lecture de n’importe quel soignant) ou pour un provider lisant sa propre limite.

### `set()`
Écriture directe côté admin.

### `upsert()`
Alias de set() pour plus de flexibilité.

---

## 🖥️ Fonctionnement côté admin (/providers)
- Un champ “Limite de patients” est injecté dynamiquement dans le formulaire
- Après clic sur un soignant → `find(pid)` → contrôle du `max_patients`
- Le bouton **Sauvegarder** enregistre la valeur via :
```
POST /interhop/providerslimit/set
```

---

## 🧑‍⚕️ Fonctionnement côté soignant (/account)
- Le champ est injecté via `interhop-account-override.js`
- Au chargement :
```
GET /interhop/providerslimit/get_self
```
- Le champ s’hydrate correctement après refresh / hard refresh
- Le hook `InterhopAccountHook` assure l’upsert en base après `/account/save`

---

## 🐞 Principaux bugs corrigés
- Champ non prérempli sur `/account` → **route manquante** corrigée
- Champ non prérempli côté admin → problème d’hydratation corrigé
- Prévention des resets JSON côté providers grâce à **InterhopProvidersCompatHook**
- Double chargement JS résolu par mécanisme `__IH_*_ONCE__`
- Cohérence des valeurs visible/hidden par `setBothValues()`

---

## ✔️ Tests fonctionnels
### Admin
- [x] Liste soignants OK
- [x] Ouverture fiche soignant OK (valeurs hydratées)
- [x] Modification + sauvegarde OK
- [x] Valeur NULL OK

### Soignant
- [x] Champ visible et verrouillé
- [x] Hydratation depuis DB OK
- [x] Hard refresh OK
- [x] Sauvegarde /account OK

### API
- [x] GET /get_self → 200 / JSON correct
- [x] GET /get/{id} → 200
- [x] POST /set → mise à jour DB
- [x] Table correctement remplie

---

## 💡 Améliorations futures possibles
- Validation côté JS (limites min/max)
- Ajout d’une contrainte métier : refuser une prise de RDV si limite atteinte
- Tableau récapitulatif par soignant dans l’admin

---

## 🧾 Auteur
Développement réalisé dans le cadre du stage InterHop (EasyAppointments InterHop Fork).

Feature conçue pour être **entièrement modulaire**, sans aucune modification du core.

---

## 📌 Notes finales
Cette feature respecte :
- le système d’overrides InterHop
- l’organisation MVC CodeIgniter
- la compatibilité multisession (admin / soignant)
- le non-dépassement du core E!A

Prête pour intégration et soutenance.
