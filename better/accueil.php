<?php
/* Tableau de bord principal — protégé par session */
session_start();
require_once 'includes/connexion_bdd.php';

/* Redirige vers la connexion si l'utilisateur n'est pas connecté */
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}
$identifiant = intval($_SESSION['utilisateur_id']);

/* Récupère le score, l'avatar et la date custom de l'utilisateur */
$requete = $pdo->prepare("SELECT score, avatar, date_custom FROM utilisateur WHERE id = :id");
$requete->bindValue(':id', $identifiant, PDO::PARAM_INT);
$requete->execute();
$utilisateur = $requete->fetch();

/* fetch() retourne false si le compte n'existe plus → déconnexion propre */
if (!$utilisateur) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

$score  = $utilisateur['score'];
$avatar = $utilisateur['avatar'];

/* Date de référence : date custom si définie par l'admin, sinon aujourd'hui */
$date_brute = $utilisateur['date_custom'];
if (empty($date_brute)) {
    $date_reference = date('Y-m-d');
    $date_affichee  = date('d/m/Y');
} else {
    $date_reference = $date_brute;
    $date_affichee  = date('d/m/Y', strtotime($date_brute));
}

/* Traitement du coche / décoche d'une quête (formulaire POST) */
if (isset($_POST['quete_id']) && isset($_POST['action'])) {
    $quete_id = intval($_POST['quete_id']);
    $action   = $_POST['action'];

    if ($action === 'cocher') {
        /* Vérifie qu'elle n'est pas déjà cochée ce jour */
        $requete = $pdo->prepare("SELECT id FROM quete_accomplie WHERE quete_id = :qid AND utilisateur_id = :uid AND date_accomplie = :date");
        $requete->bindValue(':qid',  $quete_id,    PDO::PARAM_INT);
        $requete->bindValue(':uid',  $identifiant, PDO::PARAM_INT);
        $requete->bindValue(':date', $date_reference);
        $requete->execute();

        if (!$requete->fetch()) {
            /* Enregistre la quête accomplie */
            $requete = $pdo->prepare("INSERT INTO quete_accomplie (quete_id, utilisateur_id, date_accomplie) VALUES (:qid, :uid, :date)");
            $requete->bindValue(':qid',  $quete_id,    PDO::PARAM_INT);
            $requete->bindValue(':uid',  $identifiant, PDO::PARAM_INT);
            $requete->bindValue(':date', $date_reference);
            $requete->execute();

            /* Ajoute 10 XP */
            $requete = $pdo->prepare("UPDATE utilisateur SET score = score + 10 WHERE id = :id");
            $requete->bindValue(':id', $identifiant, PDO::PARAM_INT);
            $requete->execute();

            /* Mise à jour du streak */
            $requete = $pdo->prepare("SELECT * FROM streak WHERE utilisateur_id = :id");
            $requete->bindValue(':id', $identifiant, PDO::PARAM_INT);
            $requete->execute();
            $streak_data = $requete->fetch();
            $hier = date('Y-m-d', strtotime($date_reference . ' -1 day'));

            if (!$streak_data) {
                /* Premier coche : crée le streak à 1 */
                $requete = $pdo->prepare("INSERT INTO streak (utilisateur_id, streak_actuel, derniere_date) VALUES (:id, 1, :date)");
                $requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
                $requete->bindValue(':date', $date_reference);
                $requete->execute();
            } elseif ($streak_data['derniere_date'] == $date_reference) {
                /* Streak déjà mis à jour aujourd'hui : rien à faire */
            } elseif ($streak_data['derniere_date'] == $hier) {
                /* Jour consécutif : on incrémente */
                $requete = $pdo->prepare("UPDATE streak SET streak_actuel = streak_actuel + 1, derniere_date = :date WHERE utilisateur_id = :id");
                $requete->bindValue(':date', $date_reference);
                $requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
                $requete->execute();
            } else {
                /* Rupture de série : on repart à 1 */
                $requete = $pdo->prepare("UPDATE streak SET streak_actuel = 1, derniere_date = :date WHERE utilisateur_id = :id");
                $requete->bindValue(':date', $date_reference);
                $requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
                $requete->execute();
            }
        }

    } elseif ($action === 'decocher') {
        /* Supprime la quête accomplie */
        $requete = $pdo->prepare("DELETE FROM quete_accomplie WHERE quete_id = :qid AND utilisateur_id = :uid AND date_accomplie = :date");
        $requete->bindValue(':qid',  $quete_id,    PDO::PARAM_INT);
        $requete->bindValue(':uid',  $identifiant, PDO::PARAM_INT);
        $requete->bindValue(':date', $date_reference);
        $requete->execute();

        /* Retire 10 XP sans descendre sous 0 */
        $requete = $pdo->prepare("UPDATE utilisateur SET score = MAX(0, score - 10) WHERE id = :id");
        $requete->bindValue(':id', $identifiant, PDO::PARAM_INT);
        $requete->execute();

        /* Si plus aucune quête ce jour, annule la contribution au streak */
        $requete = $pdo->prepare("SELECT COUNT(*) FROM quete_accomplie WHERE utilisateur_id = :uid AND date_accomplie = :date");
        $requete->bindValue(':uid',  $identifiant, PDO::PARAM_INT);
        $requete->bindValue(':date', $date_reference);
        $requete->execute();
        if ($requete->fetchColumn() == 0) {
            $hier = date('Y-m-d', strtotime($date_reference . ' -1 day'));
            $requete = $pdo->prepare("UPDATE streak SET streak_actuel = CASE WHEN streak_actuel > 1 THEN streak_actuel - 1 ELSE 0 END, derniere_date = :hier WHERE utilisateur_id = :id AND derniere_date = :date");
            $requete->bindValue(':hier', $hier);
            $requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
            $requete->bindValue(':date', $date_reference);
            $requete->execute();
        }
    }

    /* Redirection POST/Redirect/GET pour éviter la re-soumission au rafraîchissement */
    header('Location: accueil.php');
    exit;
}

/* Traitement du formulaire humeur */
if (isset($_POST['humeur'])) {
    $humeur_value = $_POST['humeur'];

    $requete = $pdo->prepare("SELECT id FROM humeur WHERE utilisateur_id = :id AND date_humeur = :date");
    $requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
    $requete->bindValue(':date', $date_reference);
    $requete->execute();

    if ($requete->fetch()) {
        /* Humeur déjà enregistrée aujourd'hui : on la met à jour */
        $requete = $pdo->prepare("UPDATE humeur SET humeur = :humeur WHERE utilisateur_id = :id AND date_humeur = :date");
        $requete->bindValue(':humeur', $humeur_value);
        $requete->bindValue(':id',     $identifiant, PDO::PARAM_INT);
        $requete->bindValue(':date',   $date_reference);
        $requete->execute();
    } else {
        /* Première humeur du jour : on insère */
        $requete = $pdo->prepare("INSERT INTO humeur (utilisateur_id, humeur, date_humeur) VALUES (:id, :humeur, :date)");
        $requete->bindValue(':id',     $identifiant, PDO::PARAM_INT);
        $requete->bindValue(':humeur', $humeur_value);
        $requete->bindValue(':date',   $date_reference);
        $requete->execute();
    }
    header('Location: accueil.php');
    exit;
}

/* Relit le score après d'éventuelles modifications */
$requete = $pdo->prepare("SELECT score, avatar FROM utilisateur WHERE id = :id");
$requete->bindValue(':id', $identifiant, PDO::PARAM_INT);
$requete->execute();
$utilisateur_maj = $requete->fetch();
$score  = $utilisateur_maj['score'];
$avatar = $utilisateur_maj['avatar'];

/* Niveau et XP courant */
$niveau_precedent = intval($_SESSION['niveau_precedent'] ?? (floor($score / 100) + 1));
$niveau_actuel    = floor($score / 100) + 1;
$xp_actuel        = $score % 100;
$progression_pct  = $xp_actuel;
$nb_quetes        = min($niveau_actuel, 3); // max 3 quêtes par catégorie

/* Stocke le niveau en session pour détecter un passage de niveau côté JS */
$_SESSION['niveau_precedent'] = $niveau_actuel;

/* Titres débloqués selon le niveau */
$titres = [
    1 => "Débutant",  5 => "Apprenti",    10 => "Explorateur",
    20 => "Aventurier", 35 => "Guerrier", 50 => "Héros",
    75 => "Légende", 100 => "Maître",   150 => "Dieu du quotidien"
];
$titre_actuel = "Débutant";
foreach ($titres as $niveau => $titre) {
    if ($niveau_actuel >= $niveau) $titre_actuel = $titre;
}

/* Quêtes déjà cochées aujourd'hui */
$requete = $pdo->prepare("SELECT quete_id FROM quete_accomplie WHERE utilisateur_id = :id AND date_accomplie = :date");
$requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
$requete->bindValue(':date', $date_reference);
$requete->execute();
$deja_cochees = $requete->fetchAll(PDO::FETCH_COLUMN);
$nb_cochees   = count($deja_cochees);
$nb_total     = $nb_quetes * 4; // 4 catégories

/* Streak actuel */
$requete = $pdo->prepare("SELECT streak_actuel FROM streak WHERE utilisateur_id = :id");
$requete->bindValue(':id', $identifiant, PDO::PARAM_INT);
$requete->execute();
$donnees_streak = $requete->fetch();
$streak = $donnees_streak ? $donnees_streak['streak_actuel'] : 0;

/* Calcul de la semaine pour l'affichage du streak */
$timestamp_reference = strtotime($date_reference);
$numero_jour_semaine = intval(date('N', $timestamp_reference)); // 1=lundi, 7=dimanche
$jour_actuel         = $numero_jour_semaine;
$lundi_semaine       = date('Y-m-d', $timestamp_reference - ($numero_jour_semaine - 1) * 86400);
$dimanche_semaine    = date('Y-m-d', $timestamp_reference + (7 - $numero_jour_semaine) * 86400);
$jours               = ['L','M','M','J','V','S','D'];

/* Jours de la semaine où au moins une quête a été cochée */
$requete = $pdo->prepare("
    SELECT strftime('%w', date_accomplie) AS jour_semaine
    FROM quete_accomplie
    WHERE utilisateur_id = :id
    AND date_accomplie >= :lundi AND date_accomplie <= :dimanche
    GROUP BY date_accomplie
");
$requete->bindValue(':id',       $identifiant, PDO::PARAM_INT);
$requete->bindValue(':lundi',    $lundi_semaine);
$requete->bindValue(':dimanche', $dimanche_semaine);
$requete->execute();

/* SQLite numérote 0=dimanche, on convertit en ISO 1=lundi 7=dimanche */
$jours_actifs = [];
foreach ($requete->fetchAll() as $jour) {
    $n = intval($jour['jour_semaine']);
    $jours_actifs[] = ($n == 0) ? 7 : $n;
}

/* Humeur du jour */
$requete = $pdo->prepare("SELECT humeur FROM humeur WHERE utilisateur_id = :id AND date_humeur = :date");
$requete->bindValue(':id',   $identifiant, PDO::PARAM_INT);
$requete->bindValue(':date', $date_reference);
$requete->execute();
$humeur_du_jour       = $requete->fetchColumn();
$afficher_popup_humeur = !$humeur_du_jour;

/* Message du jour stable grâce à une graine basée sur la date */
$messages = [
    "Chaque petite action te rapproche d'une meilleure version de toi-même.",
    "Une quête à la fois, tu deviens meilleur(e).",
    "Aujourd'hui est une nouvelle opportunité.",
    "Continue comme ça, tu es sur la bonne voie !",
    "Les grands changements commencent par de petits gestes."
];
srand(intval(str_replace('-', '', $date_reference)));
$message_du_jour = $messages[rand(0, count($messages) - 1)];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Better</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Date de référence affichée en haut à droite -->
<div class="barre_date">
    <?php echo $date_affichee; ?>
</div>

<!-- Popup humeur : s'affiche si l'utilisateur n'a pas encore renseigné son humeur -->
<?php if ($afficher_popup_humeur): ?>
<div id="popup_humeur" class="popup_overlay">
    <div class="popup_humeur_contenu">
        <span class="popup_emoji">🌤</span>
        <h2>Comment tu te sens aujourd'hui ?</h2>
        <p class="popup_sous_titre">Choisis ton humeur pour commencer la journée</p>
        <form method="POST" action="accueil.php" class="form_humeur_popup">
            <button type="submit" name="humeur" value="😴" class="bouton_humeur_popup">
                <span class="humeur_emoji">😴</span>
                <span class="humeur_label">Fatigué(e)</span>
            </button>
            <button type="submit" name="humeur" value="😊" class="bouton_humeur_popup">
                <span class="humeur_emoji">😊</span>
                <span class="humeur_label">Bien</span>
            </button>
            <button type="submit" name="humeur" value="💪" class="bouton_humeur_popup">
                <span class="humeur_emoji">💪</span>
                <span class="humeur_label">Motivé(e)</span>
            </button>
            <button type="submit" name="humeur" value="😤" class="bouton_humeur_popup">
                <span class="humeur_emoji">😤</span>
                <span class="humeur_label">Stressé(e)</span>
            </button>
            <button type="submit" name="humeur" value="😢" class="bouton_humeur_popup">
                <span class="humeur_emoji">😢</span>
                <span class="humeur_label">Pas top</span>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="app_layout">

    <!-- SIDEBAR GAUCHE : profil et humeur -->
    <aside class="sidebar">
        <div class="logo"><span>✦BETTER</span></div>

        <div class="card_profil">
            <div class="avatar_wrapper">
                <img src="ressources/images/<?php echo $avatar; ?>" alt="avatar" class="avatar_img">
                <a href="profil.php" class="avatar_edit">✏️</a>
            </div>
            <p class="card_nom"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            <p class="card_niveau">Niveau <?php echo $niveau_actuel; ?></p>
            <div class="card_barre">
                <div class="card_progression" style="width:<?php echo $progression_pct; ?>%"></div>
            </div>
            <p class="card_xp"><?php echo $xp_actuel; ?> / 100 XP</p>
            <a href="profil.php" class="bouton_mon_profil">👤 Mon profil</a>
            <a href="deconnexion.php" class="lien_deco">Déconnexion</a>
        </div>

        <div class="card_section">
            <p class="section_titre">Comment tu te sens ?</p>
            <p class="section_sous_titre">Clique sur ton humeur du jour</p>
            <form method="POST" action="accueil.php" class="form_humeur_sidebar">
                <?php
                $humeurs = ['😴' => 'Fatigué(e)', '😊' => 'Bien', '💪' => 'Motivé(e)', '😤' => 'Stressé(e)', '😢' => 'Pas top'];
                foreach ($humeurs as $emoji => $label) {
                    $actif = ($humeur_du_jour === $emoji) ? 'humeur_active' : '';
                    echo '<button type="submit" name="humeur" value="' . $emoji . '" class="bouton_humeur_sidebar ' . $actif . '">';
                    echo '<span>' . $emoji . '</span> ' . $label;
                    echo '</button>';
                }
                ?>
            </form>
            <?php if ($humeur_du_jour): ?>
                <p class="humeur_choisie">Aujourd'hui : <?php echo $humeur_du_jour; ?></p>
            <?php endif; ?>
        </div>
    </aside>

    <!-- CONTENU PRINCIPAL : quêtes du jour -->
    <main class="main_content">

        <div class="hero_image">
            <img src="ressources/images/hero.png" alt="Better" class="img_hero">
            <div class="hero_message"><p><?php echo $message_du_jour; ?></p></div>
        </div>

        <div class="quetes_header">
            <h2>LES QUÊTES DU JOUR</h2>
        </div>

        <?php
        $categories = ['social', 'mental', 'écologie', 'physique'];
        foreach ($categories as $categorie) {

            $requete = $pdo->prepare("SELECT * FROM quete WHERE categorie = :categorie");
            $requete->bindValue(':categorie', $categorie);
            $requete->execute();
            $quetes = $requete->fetchAll();

            /* Sélection aléatoire stable sur la journée (graine = date + catégorie) */
            srand(intval(str_replace('-', '', $date_reference)) + crc32($categorie));
            $indices = array_rand($quetes, min($nb_quetes, count($quetes)));
            if (!is_array($indices)) $indices = [$indices];

            if ($categorie === 'social') {
                $couleur = '#6c5ce7'; $label = 'SOCIAL';
                $icone   = 'ressources/images/social.jpg';
                $etoile  = 'ressources/images/etoile_violette.jpg';
            } elseif ($categorie === 'mental') {
                $couleur = '#4a90e2'; $label = 'MENTAL';
                $icone   = 'ressources/images/mental.jpg';
                $etoile  = 'ressources/images/etoile_bleue.jpg';
            } elseif ($categorie === 'écologie') {
                $couleur = '#27ae60'; $label = 'ÉCOLOGIE';
                $icone   = 'ressources/images/ecologie.jpg';
                $etoile  = 'ressources/images/etoile_verte.jpg';
            } else {
                $couleur = '#e67e22'; $label = 'PHYSIQUE';
                $icone   = 'ressources/images/physique.jpg';
                $etoile  = 'ressources/images/etoile_orange.jpg';
            }

            echo '<h3 class="separateur_categorie" style="color:' . $couleur . '">' . $label . '</h3>';
            echo '<div class="groupe_quetes">';

            foreach ($indices as $index) {
                $quete  = $quetes[$index];
                $cochee = in_array($quete['id'], $deja_cochees);

                /* Chaque quête est un formulaire POST : pas besoin de JS pour cocher */
                echo '<form method="POST" action="accueil.php" class="form_quete">';
                echo '<input type="hidden" name="quete_id" value="' . $quete['id'] . '">';
                /* L'action bascule entre cocher et décocher selon l'état actuel */
                echo '<input type="hidden" name="action" value="' . ($cochee ? 'decocher' : 'cocher') . '">';
                echo '<article class="carte_quete ' . ($cochee ? 'cochee' : '') . '" style="border-left:4px solid ' . $couleur . '">';
                echo '<button type="submit" class="checkbox_quete_btn" aria-label="' . ($cochee ? 'Décocher' : 'Cocher') . '">';
                echo '<span class="checkbox_visuel ' . ($cochee ? 'checked' : '') . '">' . ($cochee ? '✓' : '') . '</span>';
                echo '</button>';
                echo '<img src="' . $icone . '" alt="" class="icone_quete">';
                echo '<div class="texte_quete">';
                echo '<span class="label_categorie" style="color:' . $couleur . '">' . $label . '</span>';
                echo '<p class="titre_quete">' . htmlspecialchars($quete['titre']) . '</p>';
                echo '<p class="desc_quete">' . htmlspecialchars($quete['description_longue']) . '</p>';
                echo '</div>';
                echo '<div class="points_quete" style="color:' . $couleur . '">';
                echo '<img src="' . $etoile . '" alt="" class="etoile_quete">';
                echo '<strong>+10</strong><br><span>XP</span>';
                echo '</div>';
                echo '</article>';
                echo '</form>';
            }
            echo '</div>';
        }
        ?>

    </main>

    <!-- SIDEBAR DROITE : progression, streak, énergie -->
    <aside class="sidebar_droite">

        <div class="card_section">
            <p class="section_titre">Ta progression</p>
            <div class="progression_cercle_wrapper">
                <!-- Cercle SVG : stroke-dasharray = (xp / 100) × 264 -->
                <svg viewBox="0 0 100 100" class="progression_cercle">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#ede9f8" stroke-width="10"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#6c5ce7" stroke-width="10"
                        stroke-dasharray="<?php echo round($progression_pct * 2.638); ?> 264"
                        stroke-linecap="round"
                        transform="rotate(-90 50 50)"/>
                </svg>
                <div class="progression_cercle_texte">
                    <span class="prog_niveau">Niveau</span>
                    <span class="prog_num"><?php echo $niveau_actuel; ?></span>
                </div>
            </div>
            <p class="prog_xp_texte"><?php echo $xp_actuel; ?> / 100 XP</p>
            <p class="prog_next">Prochain niveau : <?php echo 100 - $xp_actuel; ?> XP 🎁</p>

            <div class="prog_quetes_jour">
                <p class="prog_quetes_label">Quêtes aujourd'hui</p>
                <div class="prog_quetes_barre_wrap">
                    <div class="prog_quetes_barre">
                        <div class="prog_quetes_fill" style="width:<?php echo $nb_total > 0 ? round($nb_cochees / $nb_total * 100) : 0; ?>%"></div>
                    </div>
                    <span class="prog_quetes_count"><?php echo $nb_cochees; ?>/<?php echo $nb_total; ?></span>
                </div>
            </div>
        </div>

        <div class="card_section">
            <p class="section_titre">Série actuelle</p>
            <p class="streak_nombre"><?php echo $streak; ?> <span>jours</span></p>
            <p class="streak_message">Continue comme ça ! 🔥</p>
            <div class="streak_semaine">
                <?php for ($i = 1; $i <= 7; $i++):
                    $coche = in_array($i, $jours_actifs);
                    if ($coche)              $classe = 'streak_fait';
                    elseif ($i == $jour_actuel) $classe = 'streak_aujourdhui';
                    elseif ($i < $jour_actuel)  $classe = 'streak_passe';
                    else                        $classe = 'streak_futur';
                ?>
                    <div class="streak_jour">
                        <div class="streak_cercle <?php echo $classe; ?>">
                            <?php echo $coche ? '✓' : ''; ?>
                        </div>
                        <span><?php echo $jours[$i - 1]; ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="card_section">
            <p class="section_titre">Énergie du jour</p>
            <?php $energie = min(100, round($nb_cochees / max($nb_total, 1) * 100)); ?>
            <div class="energie_barre_wrap">
                <div class="energie_barre">
                    <div class="energie_fill" style="width:<?php echo $energie; ?>%"></div>
                </div>
                <span class="energie_val"><?php echo $energie; ?>/100</span>
            </div>
            <p class="energie_message">
                <?php
                if ($energie == 100)      echo "Journée parfaite ! 🌟";
                elseif ($energie >= 75)   echo "Tu gères ! Continue 💪";
                elseif ($energie >= 50)   echo "Bien parti(e) ! 😊";
                elseif ($energie >= 25)   echo "C'est un début ! 🔥";
                else                      echo "Reviens demain pour des bonus ✨";
                ?>
            </p>
        </div>

    </aside>

</div>

<footer><p>© 2026 Better</p></footer>

<!-- Popup passage de niveau (géré par le JS) -->
<div id="popup_niveau" class="popup_overlay" style="display:none;">
    <div class="popup_contenu">
        <span class="popup_emoji">🎉</span>
        <h2>Niveau supérieur !</h2>
        <p id="popup_message"></p>
        <button onclick="document.getElementById('popup_niveau').style.display='none'">Super !</button>
    </div>
</div>

<!-- Niveau passé et actuel injectés pour que le JS puisse détecter un passage de niveau -->
<script>
    var niveauPrecedent = <?php echo $niveau_precedent; ?>;
    var niveauActuel    = <?php echo $niveau_actuel; ?>;
</script>
<script src="js/script.js"></script>
</body>
</html>
