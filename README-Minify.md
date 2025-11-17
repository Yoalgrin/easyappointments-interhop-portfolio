# Self Regeneration of .min assets

Cette branche valide la chaîne de build permettant de régénérer automatiquement les fichiers minifiés (`.min.js`, `.min.css`) utilisés en production.

## 💡 Pourquoi ?

En production (`debug = false`), Easy!Appointments sert automatiquement les versions minifiées via `asset_url()`.

Sans régénération :
- les modifications faites dans les sources (`.js` / `.css`) ne seraient pas visibles en prod ;
- Git pourrait contenir des `.min` obsolètes.

Cette branche garantit que les `.min` sont toujours à jour.

---

## ⚙️ Comment ça marche ?

### 🔧 En développement (`debug = true`)

- `asset_url()` sert les fichiers non minifiés : `*.js`, `*.css`.
- Aucun besoin des `.min` en local.
- Pratique pour le debug, car plus lisible.

### 🚀 En production (`debug = false`)

- Pour les fichiers marqués minifiables, `asset_url()` remplace automatiquement l’extension :
    - `*.js` → `*.min.js`
    - `*.css` → `*.min.css`
- Les vues ne changent pas : elles appellent toujours par exemple :

  asset_url('assets/js/pages/account.js')

- C’est la configuration (`debug`) et la logique d’`asset_url()` qui décident si on sert la version minifiée ou non.

Pour un asset qui ne doit pas être minifié ou qui n’a pas de `.min`, on peut appeler :

    asset_url('assets/js/vendor/mon-script-sans-min.js', false);

### 🧩 Gulp

Le `gulpfile.js` fournit les tâches permettant de régénérer les fichiers `.min` à partir des sources :

- `clean`
- `vendor`
- `scripts` → JS
- `styles` → CSS
- `compile` → clean + vendor + scripts + styles
- `build` → compile + archive (zip complet du projet, lourd, plutôt pour une release)

---

## 🧪 Commandes utiles

### 🔄 Passer en mode développement (`debug = true`)

    sed -i -E "s|(\$config\['debug'\]\s*=\s*)(true|false)\s*;|\1true;|" application/config/config.php
    grep -n "\$config\['debug'\]" application/config/config.php

Dans ce mode :
- tu modifies les `.js` / `.css`,
- tu testes directement les sources non minifiées.

---

### 🚀 Passer en mode production (`debug = false`)

1) Régénérer les `.min` (pipeline complet des assets, sans archive) :

   npx gulp compile

Cela exécute :
- `clean`
- `vendor`
- `scripts` (JS + minification)
- `styles` (CSS + minification)

2) Activer le mode production :

   sed -i -E "s|(\$config\['debug'\]\s*=\s*)(true|false)\s*;|\1false;|" application/config/config.php
   grep -n "\$config\['debug'\]" application/config/config.php

Dans ce mode :
- `asset_url()` sert les `.min.js` / `.min.css` pour les assets minifiables,
- tu confirmes que les pages chargent bien les fichiers minifiés.

---

## ⚡ Recompiler rapidement uniquement certains assets

Recompiler uniquement les JS :

    npx gulp scripts

Recompiler uniquement les CSS/SCSS :

    npx gulp styles

Recompiler JS + CSS (sans archive) :

    npx gulp compile

Idéal quand tu modifies un asset et que tu veux régénérer uniquement les `.min` sans faire d’archive complète.

---

## ✔️ Résumé

| Situation               | Ce qui est servi                | Commande de build recommandée |
|------------------------|---------------------------------|-------------------------------|
| Dev (`debug = true`)   | fichiers non minifiés           | (aucune, direct)              |
| Prod (`debug = false`) | fichiers `.min.js` / `.min.css` | `npx gulp compile`            |
| Rebuild JS seul        | régénère les `*.min.js`         | `npx gulp scripts`            |
| Rebuild CSS seul       | régénère les `*.min.css`        | `npx gulp styles`             |

---

## 🗣️ Version expliquée simplement

En développement, tu touches directement aux fichiers `.js` / `.css`, et le site les charge tels quels (`debug = true`).

Quand tu passes en production (`debug = false`) :

1. Tu lances Gulp (`npx gulp compile` ou au minimum `npx gulp scripts` / `styles`) pour régénérer les `.min`.
2. Tu passes `debug` à `false`.
3. `asset_url()` renvoie automatiquement la version minifiée (`.min.js` / `.min.css`).

Tu n’as jamais besoin de changer les chemins dans les vues : elles restent pointées sur les fichiers sources, et c’est la combinaison `debug + asset_url + Gulp` qui fait le reste.
