# Design

Tout ce qui a servi — et servira — à dessiner l'espace de Dorian.

```
docs/design/
├── README.md            ← ce fichier
├── univers-textuel.md   ← le vocabulaire, les mécaniques, le ton
├── inspirations/        ← captures Pinterest, screenshots d'apps, références
└── maquettes/           ← maquettes HTML autonomes, avec l'historique des directions
```

## Où en est-on

- **Direction validée** : écran verrouillé iPhone, fausses notifications habillées
  aux couleurs d'apps réelles, palette pastel rose → pêche → lilas, police
  Plus Jakarta Sans. Voir `maquettes/g5.html` et les `maquettes/open-*.html`.
- **Implémenté** dans `templates/feed/`, `assets/styles/feed.css` (socle et
  quatre premières apps) et `assets/styles/apps/` (une feuille par app).
  La liste des 27 apps imitées est dans `inspirations/README.md`.

## Ajouter une inspiration

Déposer l'image dans `inspirations/` avec un nom qui dit d'où elle vient et
ce qu'on y cherche, par exemple `pinterest-birthday-app-cartes-pastel.png`.
Si l'image est la référence d'un écran précis, le noter dans
`inspirations/README.md`.
