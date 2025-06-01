# minegame
petit jeu de minage web
prévu en php, a installer sur un serveur php ou via xampp ou équivalent

vous pouvez le tester sur 

🚀 Présentation
MaduMiner est un prototype de jeu web de gestion minière en 2D, réalisé en PHP pur avec Bootstrap 5, accessible à tous, directement dans le navigateur.
Le joueur gère une petite ville robotique façon steampunk dans un univers Far West : il doit extraire les ressources de la mine, placer astucieusement ses bâtiments, optimiser l’espace, et battre son propre record de points en franchissant le plus de niveaux possible !

🔥 Caractéristiques clés
Aucune dépendance JS externe (AJAX natif, Bootstrap CDN)

Génération aléatoire de la mine à chaque partie pour une vraie rejouabilité

Surface (ville) et mine (souterrain) liées : chaque ressource minée donne accès à la pose d’un bâtiment

Bâtiments de tailles et formes variées (L, barre, carré… façon Tetris)

Rotation des bâtiments à la pose

Prévisualisation intuitive : vert = pose possible / rouge = bloqué

Roches (malus) qui apparaissent en surface à chaque extraction de roche

Système de points et de niveaux infinis

Responsive (PC & mobile/tablette)

Interface simple, sans surcharge visuelle

Règles du jeu affichées dès l’accueil, UX pédagogique

🎮 Règles du jeu (résumé)
Extraire des ressources (miner) dans la grille souterraine

Placer sur la grille de surface un bâtiment, à chaque ressource découverte

Chaque bâtiment possède plusieurs formes (L, barre, carré…)

Le bâtiment doit toujours toucher un autre bâtiment déjà posé (pas la roche)

Impossible de placer sur une case roche ou déjà occupée

Utilisez la rotation (“Tourner”) pour optimiser l’espace !

Roches-malus : chaque fois qu’on découvre de la roche, il y a une chance que la roche bloque la surface au-dessus

Fin du niveau : si toute la mine est vidée

Défaite : si un bâtiment ne peut plus être placé alors qu’il reste des cases à miner

Chaque action rapporte des points : tentez d’enchaîner les niveaux pour battre votre score !

📦 Installation rapide
bash
Copier
Modifier
# Clonez le dépôt
git clone https://github.com/votre-utilisateur/maduminer.git
cd maduminer

# Placez le fichier sur votre serveur PHP (XAMPP/WAMP ou autre hébergement)
# Lancez http://localhost/maduminer/mine.php ou l’équivalent dans votre navigateur
🎯 Aucune base de données requise
Juste du PHP 8+, sessions activées.

🛠️ Structure du code
mine.php : Un seul fichier ! Tout le jeu (HTML, PHP, JS, Bootstrap) est contenu dans ce fichier.

Sessions PHP : gestion de la grille, du score, de l’inventaire.

AJAX natif : pour la réactivité sans rechargement.

Bootstrap : design responsive, moderne, léger.

Pas de dépendances obscures : copie, lance, joue !

📸 Captures d’écran
(Ajoutez ici quelques screenshots pour Github, exemple ci-dessous)

<p align="center"> <img src="https://user-images.githubusercontent.com/123456789/maduminer_demo1.png" width="600"><br> <em>Vue générale du jeu (surface, mine, encart bâtiment)</em> </p> <p align="center"> <img src="https://user-images.githubusercontent.com/123456789/maduminer_demo2.png" width="600"><br> <em>Prévisualisation de pose de bâtiment (vert/rouge), rotation active</em> </p>
💡 Fonctionnalités détaillées
Surface (ville)
Grille de 12 colonnes × 8 lignes.

Les bâtiments s’imbriquent comme des pièces de Tetris.

Chaque pose rapporte des points (selon la rareté).

Mine (souterrain)
Grille de 12 colonnes × 6 lignes, remplie aléatoirement à chaque niveau.

Extraction case par case, ressource visible après minage.

Bâtiments
Atelier (🛠️) : petite taille, 2 cases (différentes formes)

Usine (🏭) : 3 cases, formes variées

Banque (🏦), Musée (🏺) : rare, prend plus de place

Entrepôt (📦) et Générateur (🔋) toujours présents au départ

Roches malus
Quand on mine de la roche, il y a une chance croissante d’ajouter un malus “roche” en surface, qui bloque à vie la case (plus de pose possible à cet endroit)

Scoring & niveaux
Score visible en haut de page, points pour chaque bâtiment ou ressource

Chaque niveau propose une nouvelle mine (et donc de nouveaux défis d’optimisation)

Niveaux infinis, difficulté croissante (plus de roche, moins de ressources rares)

📱 Contrôles
Miner une case : cliquer sur “Miner” dans la grille souterraine

Placer un bâtiment : survoler la grille de surface pour voir la zone de pose, cliquer pour poser

Tourner la forme : bouton “↔️ Tourner”, ou touche “R” du clavier

Nouvelle partie : bouton jaune “Nouvelle partie”

Passer au niveau suivant : bouton bleu (s’affiche si la mine est totalement vidée)

📝 Développement / Personnalisation
Tout est documenté dans le code, commentaires et structures claires

Pour modifier les formes de bâtiments ou leur score, éditez simplement les tableaux en début de fichier

Pour augmenter la difficulté, jouez sur la quantité de roche, de ressources, ou sur la génération aléatoire

🖌️ Idées d’amélioration
Classement highscore (sessions, localStorage, DB…)

Skins / sprites personnalisés

Plus de types de bâtiments ou effets spéciaux (réseaux électriques, automatisation, etc.)

Effets sonores ou musiques

Support mobile total (drag & drop tactile)

⚖️ Licence
Projet open-source sous licence MIT.

🤝 Crédits
Idée, code & prototypage : Madum

MegaGPT, prompts, polish UX : OpenAI & l’équipe Madum.top

Icônes Emoji : Unicode Standard

Framework CSS : Bootstrap 5

🗨️ Contact & feedback
Problème ? Suggestion ?
Créez une issue sur Github
ou contactez Madum sur Discord / Twitch.

Bon jeu, et amusez-vous à optimiser vos villes steampunk !
Made with ❤️ by Madum


https://www.madum.top/jeux/mine.php
![image](https://github.com/user-attachments/assets/2ba7989f-4a91-455a-ba46-d7f1f3ebc7a2)
