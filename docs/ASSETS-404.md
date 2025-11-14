# Gestion des assets & correction des 404 (FullCalendar v6 / Moment / jEditable)

## 1. Contexte

Après passage à FullCalendar v6, des 404 peuvent apparaître :

```
/assets/vendor/fullcalendar/index.global.css
/assets/vendor/fullcalendar-daygrid/index.global.css
/assets/vendor/fullcalendar-timegrid/index.global.css
/assets/vendor/fullcalendar-list/index.global.css
/assets/vendor/fullcalendar-moment/index.global.js
/assets/vendor/jquery-jeditable/jquery.jeditable.js
```

Causes fréquentes :
- Les CSS FullCalendar v6 ne sont plus à inclure via `index.global.css` (changement de packaging).
- Les plugins (moment, jeditable) n’ont pas été copiés dans `assets/vendor/`.
- Ordre de chargement incorrect (moment doit précéder le plugin FullCalendar Moment ; jQuery doit précéder jEditable).

---

## 2. Règles projet (DEV vs PROD)

- **DEV** : copier les JS depuis `node_modules` vers `assets/vendor/*` avec des noms stables (sans `.min` si possible).
- **PROD** : la minification est gérée par le pipeline InterHop (ne pas cibler de `.min` en dur dans le code).

---

## 3. Dépendances à installer

```bash
npm i @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/list @fullcalendar/interaction
npm i @fullcalendar/moment moment moment-timezone
npm i jquery jquery-jeditable
```

---

## 4. Synchronisation des vendors (à intégrer dans votre script ou exécuter une fois)

**Important :** FullCalendar v6 côté assets : JS uniquement.

```bash
# FullCalendar (JS uniquement)
mkdir -p assets/vendor/fullcalendar \
         assets/vendor/fullcalendar-daygrid \
         assets/vendor/fullcalendar-timegrid \
         assets/vendor/fullcalendar-list \
         assets/vendor/fullcalendar-interaction

cp node_modules/@fullcalendar/core/index.global.js        assets/vendor/fullcalendar/index.global.js
cp node_modules/@fullcalendar/daygrid/index.global.js     assets/vendor/fullcalendar-daygrid/index.global.js
cp node_modules/@fullcalendar/timegrid/index.global.js    assets/vendor/fullcalendar-timegrid/index.global.js
cp node_modules/@fullcalendar/list/index.global.js        assets/vendor/fullcalendar-list/index.global.js
cp node_modules/@fullcalendar/interaction/index.global.js assets/vendor/fullcalendar-interaction/index.global.js

# FullCalendar + Moment
mkdir -p assets/vendor/fullcalendar-moment assets/vendor/moment assets/vendor/moment-timezone
cp node_modules/@fullcalendar/moment/index.global.js assets/vendor/fullcalendar-moment/index.global.js

# moment (renommer en .js pour un nom stable)
if [ -f node_modules/moment/min/moment.min.js ]; then
  cp node_modules/moment/min/moment.min.js assets/vendor/moment/moment.js
else
  cp node_modules/moment/moment.js assets/vendor/moment/moment.js
fi

# moment-timezone (optionnel)
if [ -f node_modules/moment-timezone/builds/moment-timezone-with-data.min.js ]; then
  cp node_modules/moment-timezone/builds/moment-timezone-with-data.min.js assets/vendor/moment-timezone/moment-timezone-with-data.js
fi

# jQuery + jEditable
mkdir -p assets/vendor/jquery assets/vendor/jquery-jeditable
cp node_modules/jquery/dist/jquery.min.js assets/vendor/jquery/jquery.js

if [ -f node_modules/jquery-jeditable/dist/jquery.jeditable.min.js ]; then
  cp node_modules/jquery-jeditable/dist/jquery.jeditable.min.js assets/vendor/jquery-jeditable/jquery.jeditable.min.js
elif [ -f node_modules/jquery-jeditable/dist/jquery.jeditable.js ]; then
  cp node_modules/jquery-jeditable/dist/jquery.jeditable.js assets/vendor/jquery-jeditable/jquery.jeditable.min.js
elif [ -f node_modules/jquery-jeditable/jquery.jeditable.js ]; then
  cp node_modules/jquery-jeditable/jquery.jeditable.js assets/vendor/jquery-jeditable/jquery.jeditable.min.js
elif [ -f node_modules/jeditable/jquery.jeditable.js ]; then
  cp node_modules/jeditable/jquery.jeditable.js assets/vendor/jquery-jeditable/jquery.jeditable.min.js
fi
```

---

## 5. Modifications dans les vues (ex. `application/views/pages/calendar.php`)

### À supprimer

```php
<link rel="stylesheet" href="<?= asset_url('assets/vendor/fullcalendar*/index.global.css') ?>">
```

### À conserver / corriger

```php
<!-- Moment avant FullCalendar Moment -->
<script src="<?= asset_url('assets/vendor/moment/moment.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/moment-timezone/moment-timezone-with-data.js') ?>"></script> <!-- si utilisé -->

<!-- FullCalendar v6 -->
<script src="<?= asset_url('assets/vendor/fullcalendar/index.global.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-interaction/index.global.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-daygrid/index.global.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-timegrid/index.global.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-list/index.global.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/fullcalendar-moment/index.global.js') ?>"></script>

<!-- jQuery puis jEditable -->
<script src="<?= asset_url('assets/vendor/jquery/jquery.js') ?>"></script>
<script src="<?= asset_url('assets/vendor/jquery-jeditable/jquery.jeditable.min.js') ?>"></script>
```

---

## 6. Vérifications rapides

```bash
curl -I http://localhost/assets/vendor/fullcalendar-moment/index.global.js
curl -I http://localhost/assets/vendor/jquery-jeditable/jquery.jeditable.min.js
```

Attendu : `HTTP/1.1 200 OK`.

Ensuite : Ctrl+F5 dans le navigateur et vérifier dans l’onglet Réseau qu’il n’y a plus de 404.

---

## 7. Nginx (si 404 persiste)

Vérifier que le `root` pointe bien sur la racine du projet (celle qui contient `index.php` et `assets/`).

```nginx
root /mnt/g/dev/easyappointments-interhop;
```

Puis :

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 8. Check-list rapide

- Rechercher `fullcalendar.*\.css` et supprimer les `<link>` obsolètes.
- Vérifier la présence des `index.global.js` dans `assets/vendor/fullcalendar*`.
- Vérifier que `assets/vendor/jquery-jeditable/jquery.jeditable.min.js` existe.

---

## 9. Exemple de message de commit

```
fix(assets): corrige 404 FullCalendar v6 et jEditable, ajoute moment & vendor sync

- supprime les <link> fullcalendar*.css obsolètes
- ajoute moment/moment-timezone + plugin fullcalendar-moment
- ajoute jquery-jeditable + jquery
- documente la procédure (docs/ASSETS-404.md)
```

---

## 10. Explication pédagogique

Les 404 viennent d’anciennes feuilles CSS FullCalendar appelées alors qu’elles n’existent plus en v6, et de plugins non copiés vers `assets/vendor`.  
Pour corriger :
1. Supprimer les `<link>` FullCalendar CSS obsolètes.
2. Copier les bons fichiers JS depuis `node_modules` (FullCalendar, Moment, jEditable).
3. Respecter l’ordre de chargement : `Moment → FullCalendar → Plugins → jQuery → jEditable`.
4. Laisser le pipeline InterHop gérer les `.min` en production.

Résultat : plus de 404 et un environnement DEV propre et stable.
