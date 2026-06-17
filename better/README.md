# 🌟 BETTER

Une application web de bien-être gamifiée en PHP/SQLite.  
Chaque jour, l'utilisateur accomplit des quêtes dans 4 catégories, gagne de l'XP, monte en niveau et débloque des titres.

---

## Fonctionnalités

- **Quêtes quotidiennes** dans 4 catégories : social, mental, physique, écologie
- **Système RPG** : XP, niveaux, titres débloqués, streaks
- **Suivi d'humeur** journalier
- **Profil** avec avatar personnalisable et graphique de progression
- **Panel admin** : gestion des utilisateurs, réinitialisation, date custom
- Base de données SQLite embarquée — aucun serveur MySQL requis

---

## Prérequis

- PHP 7.4 ou supérieur avec l'extension `pdo_sqlite`
- Un serveur web local (XAMPP, WAMP, ou le serveur intégré PHP)

---

## Installation

**1. Cloner ou dézipper le projet**

```
better/
```

**2. Lancer un serveur PHP local**

```bash
php -S localhost:8000
```

Ou avec XAMPP/WAMP : place le dossier `better/` dans `htdocs/` et démarre Apache.

**3. Initialiser la base de données**

Ouvre dans le navigateur :
```
http://localhost:8000/install.php
```

Puis pour insérer les quêtes :
```
http://localhost:8000/base_donnees/init.sql
```
*(à exécuter via un client SQLite ou en adaptant install.php selon le besoin)*

**4. C'est prêt**

```
http://localhost:8000/
```

> Sous Windows, tu peux aussi double-cliquer sur `lancer.bat` si tu as PHP dans ton PATH.

---

## Utilisation

| Page | Rôle |
|---|---|
| `index.php` | Redirige vers la connexion |
| `inscription.php` | Créer un compte |
| `connexion.php` | Se connecter |
| `accueil.php` | Tableau de bord — quêtes du jour, XP, streaks |
| `profil.php` | Niveau, titres, graphique, choix d'avatar |
| `deconnexion.php` | Déconnexion |
| `connexion_admin.php` | Accès au panel admin |
| `admin.php` | Gestion des utilisateurs |

---

## Structure du projet

```
better/
├── index.php
├── inscription.php
├── connexion.php
├── connexion_admin.php
├── accueil.php
├── profil.php
├── admin.php
├── deconnexion.php
├── install.php
├── lancer.bat
├── includes/
│   └── connexion_bdd.php   # connexion PDO + création automatique des tables
├── base_donnees/
│   ├── better.sqlite        # base de données
│   └── init.sql             # insertion des quêtes
├── css/
│   └── style.css
├── js/
│   └── script.js
└── ressources/
    └── images/
```

---

## Architecture

Le projet suit le pattern **MVC simplifié** :
- `includes/connexion_bdd.php` gère la connexion PDO et crée les tables au premier lancement
- Chaque page PHP joue le rôle de contrôleur et de vue
- La base SQLite est entièrement embarquée dans `base_donnees/`

---

## Licence

Projet académique — Licence Informatique L2, Université Jean Monnet
