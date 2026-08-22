# Maquettes de l'espace destinataire

Fichiers HTML autonomes (polices Google, aucun build) : il suffit de les ouvrir
dans un navigateur, de préférence en largeur mobile (375 px). Ils retracent
l'itération qui a mené au design implémenté dans `templates/feed/` et
`assets/styles/feed.css`.

## Retenu

| Fichier | Écran | Statut |
|---|---|---|
| `g5.html` | Accueil : écran verrouillé iPhone, horloge, aura, Nouveau / En route / Déjà consultées | **Validé** — c'est `templates/feed/index.html.twig` |
| `open-ubereats.html` | Ouverture d'une notification Uber Eats | **Validé** — `feed/open/uber_eats.html.twig` |
| `open-instagram.html` | Ouverture d'une notification Instagram | **Validé** — `feed/open/instagram.html.twig` |
| `open-tinder.html` | Ouverture d'une notification Tinder | **Validé** — `feed/open/tinder.html.twig` |
| `open-doctolib.html` | Ouverture d'une notification Doctolib | **Validé** — `feed/open/doctolib.html.twig` |

Différences entre G5 et l'implémentation : pas de barre de navigation en bas
(supprimée à la demande), une roue ⚙︎ en haut à gauche pour les options.

## Chemin parcouru

Dans l'ordre chronologique. Chaque étape a été écartée ou fusionnée dans la suivante.

| Fichier | Direction | Pourquoi écartée |
|---|---|---|
| `a-patisserie.html` | Crème / beurre / fraise, vocabulaire boulangerie | Trop sage, pas de mécanique de jeu |
| `b-fete.html` | Dégradé corail, tuiles bento, confettis | Idem |
| `c-carnet.html` | Kraft, polaroids, écriture manuscrite | Idem |
| `d-game.html` | HUD de jeu mobile, XP, niveaux, parcours Candy Crush | Palette jugée trop « geek » ; XP abandonné au profit de l'aura |
| `e-garage.html` | Pop / queer / auto, contours BD, route arc-en-ciel | Bonne idée de vocabulaire, mais le concept a basculé vers les notifications |
| `f-dashboard.html` | Tableau de bord Y2K, delulu meter en compteur | Rejetée |
| `h-tabloid.html` | Une de magazine à scandale | Rejetée |
| `g-notifs.html` | Premier fil de fausses notifications iPhone | Retenue comme concept, trop chargée |
| `g2-home.html`, `g2-choose.html` | Malentendu : accueil Instagram / Uber Eats, choix Tinder | Écartées, mauvaise lecture du brief |
| `g3-feed.html` | Fausses notifications habillées par app réelle (Uber Eats, Doctolib, TikTok, Instagram, Tinder, Hinge, BeReal) | Concept validé |
| `g3a-lock-light.html`, `g3b-lock-dark.html`, `g3c-center.html` | Trois cadres pour G3 : verrouillé clair, verrouillé sombre, centre de notifications | Mélangées dans G4 |
| `g4-mix.html` | En-tête « Notifications » de G3C + palette G3A + consultées grisées | Gris jugé trop présent |

Les apps maquettées mais pas encore implémentées : TikTok, Hinge, BeReal
(voir `g3-feed.html` pour leur habillage).
