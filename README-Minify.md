# Self Regeneration of .min assets

Cette branche ajoute/valide la chaîne de build pour que les fichiers minifiés
(`.min.js`, `.min.css`) soient régénérés et servis automatiquement en production.

## Pourquoi ?
En prod (`debug=false`), Easy!Appointments sert les versions **minifiées** via `asset_url()`.
Sans régénération, les modifications faites dans les sources `.js/.css` ne sont pas visibles.

## Comment ça marche
- En **dev** (`debug=true`) : les sources non minifiées sont servies.
- En **prod** (`debug=false`) : `asset_url()` remplace automatiquement `.js` → `.min.js` et `.css` → `.min.css`.
- Le `gulpfile.js` fournit les tâches de build pour générer les `.min`.

## Commandes utiles

## Passer en mode **dev** (`debug=true`) :
```bash
  sed -i -E "s|(\$config\['debug'\]\s*=\s*)(true|false)\s*;|\1true;|" application/config/config.php
  grep -n "\$config\['debug'\]" application/config/config.php
```
## Passer en mode **prod** (`debug=false`) :
```bash
npx run build ou npm start 
```
(régénère tous les fichiers .min) voir plus bas pour les versions courtes.

puis:

```bash
sed -i -E "s|(\$config\['debug'\]\s*=\s*)(true|false)\s*;|\1false;|" application/config/config.php
grep -n "\$config\['debug'\]" application/config/config.php
```

- Recompiler uniquement les JS (rapide) :
  ```bash
  npx gulp scripts
  ``` 
- Recompiler uniquement les CSS/SCSS (rapide) :
  ```bash
  npx gulp styles
  ``` 
- Recompiler les js/CSS (rapide) :
  ```bash
  npx gulp compile
  ``` 
