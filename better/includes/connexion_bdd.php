<?php
/* Connexion à la base SQLite, inclus dans toutes les pages */

$dossier_bdd = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'base_donnees';
$chemin_bdd  = $dossier_bdd . DIRECTORY_SEPARATOR . 'better.sqlite';

/* Crée le dossier base_donnees/ s'il n'existe pas */
if (!is_dir($dossier_bdd)) {
    mkdir($dossier_bdd, 0777, true);
}

try {
    $pdo = new PDO('sqlite:' . $chemin_bdd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       // les erreurs SQL lèvent une exception
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // résultats en tableaux associatifs
    $pdo->exec('PRAGMA foreign_keys = ON;');                              // active les clés étrangères

    /* Crée les tables au premier lancement si elles n'existent pas encore */
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateur (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        nom          TEXT NOT NULL,
        prenom       TEXT NOT NULL,
        email        TEXT NOT NULL UNIQUE,
        mot_de_passe TEXT NOT NULL,
        score        INTEGER NOT NULL DEFAULT 0,
        avatar       TEXT NOT NULL DEFAULT 'avatar1.png',
        role         TEXT NOT NULL DEFAULT 'user',
        date_custom  TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quete (
        id                 INTEGER PRIMARY KEY AUTOINCREMENT,
        titre              TEXT NOT NULL,
        description_longue TEXT NOT NULL,
        categorie          TEXT NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS quete_accomplie (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        quete_id       INTEGER NOT NULL,
        utilisateur_id INTEGER NOT NULL,
        date_accomplie DATE NOT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS streak (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        utilisateur_id INTEGER NOT NULL UNIQUE,
        streak_actuel  INTEGER NOT NULL DEFAULT 0,
        derniere_date  DATE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS humeur (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        utilisateur_id INTEGER NOT NULL,
        humeur         TEXT NOT NULL,
        date_humeur    DATE NOT NULL
    )");

    /* Insère les quêtes uniquement si la table est vide */
    $nb = $pdo->query("SELECT COUNT(*) FROM quete")->fetchColumn();
    if ($nb == 0) {
        $pdo->exec("INSERT INTO quete (titre, description_longue, categorie) VALUES
('Aider quelqu''un aujourd''hui','Tends la main à quelqu''un qui en a besoin.','social'),
('Faire un compliment sincère','Dis quelque chose de gentil et vrai à quelqu''un.','social'),
('Prendre des nouvelles d''un proche','Envoie un message ou appelle quelqu''un que tu aimes.','social'),
('Envoyer un message gentil','Écris quelques mots chaleureux à quelqu''un aujourd''hui.','social'),
('Dire merci à quelqu''un','Exprime ta gratitude à une personne qui le mérite.','social'),
('Sourire à une personne inconnue','Un sourire peut changer la journée de quelqu''un.','social'),
('Tenir la porte à quelqu''un','Un petit geste qui fait toute la différence.','social'),
('Proposer ton aide à quelqu''un','Offre ton temps ou tes compétences sans qu''on te le demande.','social'),
('Écouter sans interrompre','Donne toute ton attention à quelqu''un qui te parle.','social'),
('Passer du temps avec un proche','Profite d''un moment de qualité avec un ami ou ta famille.','social'),
('Encourager quelqu''un','Motive quelqu''un à continuer ses efforts.','social'),
('Partager un moment positif','Crée un souvenir agréable avec quelqu''un aujourd''hui.','social'),
('Inviter quelqu''un à discuter','Engage une vraie conversation avec quelqu''un.','social'),
('Complimenter un collègue','Reconnais le travail ou les qualités d''un camarade.','social'),
('Aider à résoudre un problème','Mets-toi en mode solution pour quelqu''un.','social'),
('Remercier quelqu''un pour son aide','Montre ta reconnaissance envers quelqu''un de généreux.','social'),
('Faire une bonne action inattendue','Fais quelque chose de bien sans attendre en retour.','social'),
('Participer de manière bienveillante','Contribue positivement à une discussion ou réunion.','social'),
('Respecter un avis différent du tien','Écoute et accepte une perspective qui diffère de la tienne.','social'),
('Soutenir une personne en difficulté','Sois présent pour quelqu''un qui traverse une période dure.','social'),
('Prendre 10 minutes pour soi','Accorde-toi un moment rien que pour toi.','mental'),
('Respirer profondément','Fais 5 minutes de respiration consciente et lente.','mental'),
('Écrire une pensée positive','Note une idée ou intention positive pour la journée.','mental'),
('Noter 3 choses positives','Identifie trois bonnes choses qui se sont passées aujourd''hui.','mental'),
('Pause sans écran','Déconnecte-toi de tous les écrans pendant 15 minutes.','mental'),
('Lire quelques pages','Plonge dans un livre et laisse ton esprit voyager.','mental'),
('Écouter de la musique relaxante','Mets une playlist douce et laisse-toi porter.','mental'),
('Se concentrer sur le présent','Recentre ton attention sur l''instant présent pendant 5 min.','mental'),
('Éviter les pensées négatives','Remplace une pensée négative par une pensée constructive.','mental'),
('Prendre un moment de silence','Offre-toi quelques minutes de calme absolu.','mental'),
('Exercice de gratitude','Pense à ce pour quoi tu es reconnaissant aujourd''hui.','mental'),
('Se fixer un objectif simple','Choisis une chose réalisable à accomplir aujourd''hui.','mental'),
('Se féliciter pour une réussite','Reconnais et célèbre quelque chose que tu as bien fait.','mental'),
('Se déconnecter des réseaux sociaux','Mets de côté Instagram, TikTok etc. pendant 30 minutes.','mental'),
('Visualiser une réussite','Imagine-toi en train de réussir quelque chose qui te tient à cœur.','mental'),
('Prendre du recul','Face au stress, respire et regarde la situation autrement.','mental'),
('Se reposer sans culpabiliser','Autorise-toi une vraie pause, tu le mérites.','mental'),
('Exercice de relaxation','Essaie un exercice de détente : yoga, sophrologie, etc.','mental'),
('Penser à quelque chose qui te rend heureux','Laisse une pensée joyeuse envahir ton esprit.','mental'),
('Écrire dans un carnet','Exprime tes pensées et émotions par écrit.','mental'),
('Recycler un objet','Trie correctement un déchet ou donne une seconde vie à un objet.','écologie'),
('Éteindre les lumières inutiles','Passe dans chaque pièce et éteins ce qui ne sert pas.','écologie'),
('Réduire son temps de douche','Essaie de te doucher en moins de 5 minutes aujourd''hui.','écologie'),
('Utiliser un sac réutilisable','Laisse les sacs plastique au magasin, prends le tien.','écologie'),
('Ramasser un déchet','Ramasse un déchet que tu croises dans la rue ou un parc.','écologie'),
('Éviter la veille des appareils','Éteins complètement TV, box et chargeurs inutilisés.','écologie'),
('Utiliser une gourde','Remplace une bouteille jetable par ta gourde réutilisable.','écologie'),
('Ne pas gaspiller de nourriture','Finis ton assiette et utilise les restes intelligemment.','écologie'),
('Préférer la marche ou les transports','Laisse la voiture et opte pour un moyen plus vert.','écologie'),
('Éviter les produits jetables','Remplace un produit jetable par une alternative durable.','écologie'),
('Réutiliser au lieu de jeter','Donne une seconde vie à un objet que tu aurais jeté.','écologie'),
('Trier ses déchets','Assure-toi de bien séparer tes déchets dans les bonnes poubelles.','écologie'),
('Réduire l''utilisation du papier','Utilise le recto-verso ou passe au numérique quand c''est possible.','écologie'),
('Éteindre l''eau en se brossant les dents','Coupe le robinet pendant que tu te brosses les dents.','écologie'),
('Repas sans viande','Opte pour un repas végétarien aujourd''hui.','écologie'),
('Fermer les fenêtres avec le chauffage','Évite de chauffer l''extérieur en gardant les fenêtres fermées.','écologie'),
('Utiliser moins de plastique','Identifie et évite un usage de plastique dans ta journée.','écologie'),
('Donner un objet inutilisé','Offre ou donne un objet que tu n''utilises plus.','écologie'),
('Réparer plutôt que remplacer','Essaie de réparer quelque chose avant d''en acheter un neuf.','écologie'),
('Prendre conscience de son impact','Réfléchis à tes habitudes et comment les améliorer.','écologie'),
('Faire 10 minutes de sport','Bouge ton corps, même un peu, ça compte !','physique'),
('Marcher 15 minutes','Sors prendre l''air et marche à ton rythme.','physique'),
('S''étirer pendant 5 minutes','Détends tes muscles avec quelques étirements simples.','physique'),
('Boire 1,5 litre d''eau','Hydrate-toi correctement tout au long de la journée.','physique'),
('Renforcement musculaire','Fais quelques exercices de gainage ou de musculation légère.','physique'),
('Prendre les escaliers','Évite l''ascenseur et monte les marches à pied.','physique'),
('Séance de cardio','Fais monter ton rythme cardiaque pendant quelques minutes.','physique'),
('Promenade à l''extérieur','Profite du grand air en te baladant dans ton quartier.','physique'),
('Bouger toutes les heures','Lève-toi et marche quelques minutes entre chaque heure assise.','physique'),
('Faire 10 squats','Simple, rapide et efficace pour tes jambes et fessiers.','physique'),
('Faire 10 pompes','Un classique pour renforcer ton haut du corps.','physique'),
('S''étirer après une activité','Prends le temps de récupérer après l''effort.','physique'),
('Activité physique que tu aimes','Danse, vélo, natation… fais ce qui te fait plaisir !','physique'),
('Améliorer ta posture','Veille à te tenir droit(e) tout au long de la journée.','physique'),
('Pause active','Remplace une pause assise par une pause en mouvement.','physique'),
('Quelques minutes de yoga','Enchaîne quelques postures pour te recentrer et t''étirer.','physique'),
('Dormir au moins 7 heures','Le sommeil est la base de ta récupération physique.','physique'),
('Limiter le temps assis','Alterne entre position assise et debout dans ta journée.','physique'),
('Sport en extérieur','Cours, fais du vélo ou du sport au grand air aujourd''hui.','physique'),
('Prendre soin de ton corps','Accorde de l''attention à ton bien-être physique ce soir.','physique')
        ");
    }

} catch (PDOException $e) {
    die('Erreur base de données : ' . $e->getMessage() . '<br>Chemin : ' . $chemin_bdd);
}
?>
