<?php
/* Page de profil — niveau, titre, statistiques, graphique et choix d'avatar */
session_start();
require_once 'includes/connexion_bdd.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}
$user_id = intval($_SESSION['utilisateur_id']);

/* Récupère toutes les infos de l'utilisateur connecté */
$requete = $pdo->prepare("SELECT * FROM utilisateur WHERE id = :id");
$requete->bindValue(':id', $user_id, PDO::PARAM_INT);
$requete->execute();
$utilisateur = $requete->fetch();

/* Compte supprimé entre temps → déconnexion propre */
if (!$utilisateur) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

$score       = $utilisateur['score'];
$avatar      = $utilisateur['avatar'];
$niveau      = floor($score / 100) + 1;
$progression = $score % 100; // XP dans le niveau actuel

/* Titre débloqué : on garde le dernier palier atteint */
$titres = [
    1 => "Débutant",  5 => "Apprenti",    10 => "Explorateur",
    20 => "Aventurier", 35 => "Guerrier", 50 => "Héros",
    75 => "Légende", 100 => "Maître",   150 => "Dieu du quotidien"
];
$titre_actuel = "Débutant";
foreach ($titres as $n => $t) {
    if ($niveau >= $n) $titre_actuel = $t;
}

/* Traitement du changement d'avatar */
if (isset($_POST['avatar'])) {
    $nouvel_avatar = $_POST['avatar']; // Nom du fichier image (ex: avatar3.png)

    $requete = $pdo->prepare("UPDATE utilisateur SET avatar = :avatar WHERE id = :id");
    $requete->bindValue(':avatar', $nouvel_avatar);
    $requete->bindValue(':id',     $user_id, PDO::PARAM_INT);
    $requete->execute();

    /* Met à jour la variable locale pour afficher le bon avatar sans recharger */
    $avatar = $nouvel_avatar;
    $_SESSION['avatar'] = $avatar;
}

/* Nombre de quêtes accomplies par catégorie (toutes dates confondues) */
$requete = $pdo->prepare("
    SELECT q.categorie, COUNT(*) AS total
    FROM quete_accomplie qa
    JOIN quete q ON q.id = qa.quete_id
    WHERE qa.utilisateur_id = :id
    GROUP BY q.categorie
");
$requete->bindValue(':id', $user_id, PDO::PARAM_INT);
$requete->execute();
$stats_cats = $requete->fetchAll();

/* Total toutes catégories pour calculer les pourcentages du graphique */
$total_quetes = 0;
foreach ($stats_cats as $s) {
    $total_quetes += $s['total'];
}

/* Streak actuel (peut être absent si aucune quête n'a jamais été cochée) */
$requete = $pdo->prepare("SELECT streak_actuel FROM streak WHERE utilisateur_id = :id");
$requete->bindValue(':id', $user_id, PDO::PARAM_INT);
$requete->execute();
$streak_data = $requete->fetch();
$streak = $streak_data ? $streak_data['streak_actuel'] : 0;

/* Humeur la plus souvent renseignée par cet utilisateur */
$requete = $pdo->prepare("SELECT humeur, COUNT(*) AS nb FROM humeur WHERE utilisateur_id = :id GROUP BY humeur ORDER BY nb DESC LIMIT 1");
$requete->bindValue(':id', $user_id, PDO::PARAM_INT);
$requete->execute();
$humeur_fav = $requete->fetch();

/* Couleurs associées à chaque catégorie (utilisées dans le CSS inline et le camembert) */
$palette = [
    'social'   => '#6c5ce7',
    'mental'   => '#4a90e2',
    'écologie' => '#27ae60',
    'physique' => '#e67e22'
];

/* Construction des tableaux JSON passés au script de dessin du camembert */
$categories_json = [];
$totaux_json     = [];
$couleurs_json   = [];
foreach ($stats_cats as $ligne) {
    $categories_json[] = ucfirst($ligne['categorie']);
    $totaux_json[]     = intval($ligne['total']);
    $couleurs_json[]   = $palette[$ligne['categorie']] ?? '#999';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - Better</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="profil_padding">
    <a href="accueil.php" class="retour_btn">← Retour à l'accueil</a>
</div>

<div class="profil_layout">

    <aside class="profil_sidebar">
        <div class="profil_card">
            <div class="profil_avatar_wrap">
                <img src="ressources/images/<?php echo $avatar; ?>" alt="avatar" class="profil_avatar_img">
            </div>
            <p class="profil_nom"><?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></p>
            <p class="profil_email"><?php echo htmlspecialchars($utilisateur['email']); ?></p>
            <span class="profil_badge">⭐ <?php echo $titre_actuel; ?></span>

            <div class="profil_xp_bloc">
                <div class="profil_xp_label">
                    <span>Niveau <?php echo $niveau; ?></span>
                    <span><?php echo $progression; ?>/100 XP</span>
                </div>
                <div class="profil_barre">
                    <div class="profil_prog" style="width:<?php echo $progression; ?>%"></div>
                </div>
            </div>

            <div class="profil_stats_grid">
                <div class="profil_stat_item">
                    <span class="profil_stat_emoji">⭐</span>
                    <div class="profil_stat_val"><?php echo $niveau; ?></div>
                    <div class="profil_stat_nom">Niveau</div>
                </div>
                <div class="profil_stat_item">
                    <span class="profil_stat_emoji">🔥</span>
                    <div class="profil_stat_val"><?php echo $streak; ?></div>
                    <div class="profil_stat_nom">Série</div>
                </div>
                <div class="profil_stat_item">
                    <span class="profil_stat_emoji">⚔️</span>
                    <div class="profil_stat_val"><?php echo $total_quetes; ?></div>
                    <div class="profil_stat_nom">Quêtes</div>
                </div>
                <div class="profil_stat_item">
                    <span class="profil_stat_emoji"><?php echo $humeur_fav ? $humeur_fav['humeur'] : '😊'; ?></span>
                    <div class="profil_stat_val profil_stat_humeur">Humeur</div>
                    <div class="profil_stat_nom">fréquente</div>
                </div>
            </div>
        </div>
    </aside>

    <main class="profil_main">

        <div class="profil_section">
            <h2>⚔️ Quêtes par catégorie</h2>
            <?php if (empty($stats_cats)): ?>
                <p class="texte_vide_profil">Tu n'as pas encore accompli de quêtes !</p>
            <?php else: ?>
                <div class="cats_grid">
                    <?php
                    $icones = [
                        'social'   => 'ressources/images/social.jpg',
                        'mental'   => 'ressources/images/mental.jpg',
                        'écologie' => 'ressources/images/ecologie.jpg',
                        'physique' => 'ressources/images/physique.jpg'
                    ];
                    /* Fonds semi-transparents pour les cartes catégories */
                    $bg_palette = [
                        'social'   => 'rgba(108,92,231,0.08)',
                        'mental'   => 'rgba(74,144,226,0.08)',
                        'écologie' => 'rgba(39,174,96,0.08)',
                        'physique' => 'rgba(230,126,34,0.08)'
                    ];
                    foreach ($stats_cats as $s):
                    ?>
                    <div class="cat_card" style="background:<?php echo $bg_palette[$s['categorie']] ?? ''; ?>">
                        <img src="<?php echo $icones[$s['categorie']] ?? ''; ?>" alt="<?php echo $s['categorie']; ?>">
                        <div class="cat_card_info">
                            <div class="cat_card_label" style="color:<?php echo $palette[$s['categorie']] ?? '#999'; ?>"><?php echo strtoupper($s['categorie']); ?></div>
                            <div class="cat_card_nb"><?php echo $s['total']; ?></div>
                            <!-- Accord au pluriel selon le nombre -->
                            <div class="cat_card_texte">quête<?php echo $s['total'] > 1 ? 's' : ''; ?> accomplie<?php echo $s['total'] > 1 ? 's' : ''; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($stats_cats)): ?>
        <div class="profil_section">
            <h2>📊 Répartition</h2>
            <div class="graphique_wrap">
                <!-- Camembert dessiné en JS avec l'API Canvas -->
                <canvas id="camembert" width="180" height="180"></canvas>
                <div class="legende_graph">
                    <?php
                    $total_g = array_sum($totaux_json); // Total pour calculer les % de chaque part
                    foreach ($stats_cats as $i => $s):
                        $pct = $total_g > 0 ? round($s['total'] / $total_g * 100) : 0;
                    ?>
                    <div class="legende_item">
                        <div class="legende_couleur" style="background:<?php echo $palette[$s['categorie']] ?? '#999'; ?>"></div>
                        <span><?php echo ucfirst($s['categorie']); ?></span>
                        <span class="legende_pct"><?php echo $pct; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="profil_section">
            <h2>🎭 Mon avatar</h2>
            <form method="POST" action="profil.php" id="form_avatar">
                <div class="avatars_grid">
                    <?php for ($i = 1; $i <= 6; $i++):
                        $nom_avatar  = 'avatar' . $i . '.png';
                        $selectionne = ($avatar === $nom_avatar) ? 'selectionne' : '';
                    ?>
                    <label class="avatar_choix <?php echo $selectionne; ?>">
                        <!-- Input radio caché : la sélection soumet le formulaire via JS -->
                        <input type="radio" name="avatar" value="<?php echo $nom_avatar; ?>"
                               <?php echo $selectionne ? 'checked' : ''; ?>
                               class="avatar_input_cache">
                        <img src="ressources/images/<?php echo $nom_avatar; ?>" alt="avatar <?php echo $i; ?>">
                    </label>
                    <?php endfor; ?>
                </div>
            </form>
        </div>

    </main>

</div>

<footer class="footer_profil">© 2026 Better</footer>

<script>
/* Données PHP converties en JSON pour le camembert */
var categories = <?php echo json_encode($categories_json); ?>;
var totaux     = <?php echo json_encode($totaux_json); ?>;
var couleurs   = <?php echo json_encode($couleurs_json); ?>;

/* Dessin du camembert avec l'API Canvas 2D */
var canvas = document.getElementById('camembert');
if (canvas && totaux.length > 0) {
    var ctx   = canvas.getContext('2d');
    var total = 0;
    for (var i = 0; i < totaux.length; i++) total += totaux[i];

    var debut = -Math.PI / 2; // On commence en haut (midi)
    for (var i = 0; i < totaux.length; i++) {
        var angle = (totaux[i] / total) * 2 * Math.PI; // Angle proportionnel à la part
        ctx.beginPath();
        ctx.moveTo(90, 90);
        ctx.arc(90, 90, 80, debut, debut + angle);
        ctx.closePath();
        ctx.fillStyle   = couleurs[i];
        ctx.fill();
        ctx.strokeStyle = 'white'; // Séparation blanche entre les parts
        ctx.lineWidth   = 3;
        ctx.stroke();
        debut += angle;
    }
}

/* Soumet le formulaire avatar dès qu'un radio change (pas besoin de bouton valider) */
document.querySelectorAll('.avatar_input_cache').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('form_avatar').submit();
    });
});
</script>
</body>
</html>
