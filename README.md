# 🏆 MaduMiner – Prototype jeu de gestion minière 2D Far West Steampunk

![image](https://github.com/user-attachments/assets/49409a25-c3f9-4654-b48d-d3cd1da2d604)

<sup>*(Ajoutez ici une capture d’écran du jeu)*</sup>

---

## 🚀 Présentation

**MaduMiner** est un prototype de jeu web de gestion minière **100 % PHP/Bootstrap** inspiré des jeux de gestion/tetris. Explore la mine, place tes bâtiments en surface, optimise, et grimpe les niveaux !

---

## 🔥 Fonctionnalités

- **Aucune base de données ni dépendance externe**
- Génération aléatoire de la mine et surface à chaque partie
- Bâtiments de formes variées (barre, L, carré, etc.), rotation possible
- Pose intuitive : prévisualisation verte (OK), rouge (impossible)
- Système de points et de niveaux infinis
- Roches “malus” qui bloquent la surface selon l’extraction
- 100% responsive (mobile/tablette/desktop)
- Règles du jeu et UX intégrées

---

## 🎮 Règles du jeu

- **Miner** : clique sur la grille de la mine pour découvrir des ressources ou de la roche
- **Placer des bâtiments** : chaque ressource donne un bâtiment à poser en surface, à côté d’un bâtiment existant
- **Rotation** : tourne les bâtiments (“Tourner” ou touche `R`) pour optimiser l’espace
- **Contraintes** : impossible de poser sur une case roche ou déjà occupée, ni sans toucher un autre bâtiment
- **Score** : chaque ressource ou bâtiment rapporte des points
- **Défaite** : si tu ne peux plus rien poser alors qu’il reste à miner
- **Niveau suivant** : si tu as vidé toute la mine

---

## 📦 Installation

```bash
git clone https://github.com/votre-utilisateur/maduminer.git
cd maduminer
# Place le fichier mine.php sur ton serveur PHP (XAMPP/WAMP)
# Lance http://localhost/maduminer/mine.php dans le navigateur
