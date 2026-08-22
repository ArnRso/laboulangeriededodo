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
  aux couleurs d'apps réelles (Uber Eats, Instagram, Tinder, Doctolib), palette
  pastel rose → pêche → lilas, police Plus Jakarta Sans. Voir `maquettes/g5.html`
  et les `maquettes/open-*.html`.
- **Implémenté** dans `templates/feed/` et `assets/styles/feed.css`.
- **À venir** : les apps TikTok, Hinge et BeReal (maquettées dans
  `maquettes/g3-feed.html`, pas encore codées).

## Ajouter une inspiration

Déposer l'image dans `inspirations/` avec un nom qui dit d'où elle vient et
ce qu'on y cherche, par exemple `pinterest-birthday-app-cartes-pastel.png`.
Si l'image est la référence d'un écran précis, le noter dans
`inspirations/README.md`.
