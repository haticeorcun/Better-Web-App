<?php
/* Panneau d'administration — accès restreint à l'admin connecté */
session_start();
require_once 'includes/connexion_bdd.php';

/* Redirige vers la page de login admin si la session admin n'est pas ouverte */
if (!isset($_SESSION['admin_connecte']) || !$_SESSION['admin_connecte']) {
    header('Location: connexion_admin.php');
    exit;
}


$message = isset($_SESSION['admin_message']) ? $_SESSION['admin_message'] : '';
$erreur  = '';
unset($_SESSION['admin_message']); // On vide le message après l'avoir lu

/* Action : réinitialiser un utilisateur */
if (isset($_POST['action']) && $_POST['action'] === 'reinit') {
    $cible = intval($_POST['cible_id']); // ID de l'utilisateur ciblé

    try {
        /* Remet le score à 0 et efface la date custom */
        $requete = $pdo->prepare("UPDATE utilisateur SET score = 0, date_custom = NULL WHERE id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        /* Supprime tout l'historique de quêtes accomplies */
        $requete = $pdo->prepare("DELETE FROM quete_accomplie WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        /* Supprime le streak */
        $requete = $pdo->prepare("DELETE FROM streak WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        /* Supprime tout l'historique d'humeurs */
        $requete = $pdo->prepare("DELETE FROM humeur WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        $_SESSION['admin_message'] = "Utilisateur réinitialisé avec succès.";
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit;
}

/* Action : changer la date simulée */
if (isset($_POST['action']) && $_POST['action'] === 'changer_date') {
    $nouvelle_date = trim($_POST['nouvelle_date']);
    $cible         = intval($_POST['cible_id']);

    try {
        
        $requete = $pdo->prepare("SELECT date_custom FROM utilisateur WHERE id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();
        $row = $requete->fetch();
        $ancienne_date = (!empty($row['date_custom'])) ? $row['date_custom'] : date('Y-m-d');

        /* Supprime uniquement l'humeur de l'ancienne date (pas tout l'historique) */
        $requete = $pdo->prepare("DELETE FROM humeur WHERE utilisateur_id = :id AND date_humeur = :date");
        $requete->bindValue(':id',   $cible, PDO::PARAM_INT);
        $requete->bindValue(':date', $ancienne_date);
        $requete->execute();

        /* Supprime uniquement les quêtes cochées à l'ancienne date */
        $requete = $pdo->prepare("DELETE FROM quete_accomplie WHERE utilisateur_id = :id AND date_accomplie = :date");
        $requete->bindValue(':id',   $cible, PDO::PARAM_INT);
        $requete->bindValue(':date', $ancienne_date);
        $requete->execute();

        /* Met à jour la date custom dans la table utilisateur */
        $requete = $pdo->prepare("UPDATE utilisateur SET date_custom = :date WHERE id = :id");
        $requete->bindValue(':date', $nouvelle_date);
        $requete->bindValue(':id',   $cible, PDO::PARAM_INT);
        $requete->execute();

        /* Mise à jour du streak selon la nouvelle date */
        $hier = date('Y-m-d', strtotime($nouvelle_date . ' -1 day'));

        /* Récupère le streak actuel de cet utilisateur */
        $requete = $pdo->prepare("SELECT * FROM streak WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();
        $streak_data = $requete->fetch();

        if (!$streak_data) {
            /* Pas encore de streak : on en crée un à 1 */
            $requete = $pdo->prepare("INSERT INTO streak (utilisateur_id, streak_actuel, derniere_date) VALUES (:id, 1, :date)");
            $requete->bindValue(':id',   $cible, PDO::PARAM_INT);
            $requete->bindValue(':date', $nouvelle_date);
            $requete->execute();
        } elseif ($streak_data['derniere_date'] == $hier) {
            /* La dernière activité était hier → jour consécutif, on incrémente */
            $requete = $pdo->prepare("UPDATE streak SET streak_actuel = streak_actuel + 1, derniere_date = :date WHERE utilisateur_id = :id");
            $requete->bindValue(':date', $nouvelle_date);
            $requete->bindValue(':id',   $cible, PDO::PARAM_INT);
            $requete->execute();
        } else {
            /* Rupture de série → on repart à 1 */
            $requete = $pdo->prepare("UPDATE streak SET streak_actuel = 1, derniere_date = :date WHERE utilisateur_id = :id");
            $requete->bindValue(':date', $nouvelle_date);
            $requete->bindValue(':id',   $cible, PDO::PARAM_INT);
            $requete->execute();
        }

        $_SESSION['admin_message'] = "Date mise à jour !";
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit;
}

/* Action : supprimer un utilisateur */
if (isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $cible = intval($_POST['cible_id']);

    try {
        
        $requete = $pdo->prepare("DELETE FROM quete_accomplie WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        $requete = $pdo->prepare("DELETE FROM humeur WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        $requete = $pdo->prepare("DELETE FROM streak WHERE utilisateur_id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        $requete = $pdo->prepare("DELETE FROM utilisateur WHERE id = :id");
        $requete->bindValue(':id', $cible, PDO::PARAM_INT);
        $requete->execute();

        $_SESSION['admin_message'] = "Utilisateur supprimé.";
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit;
}

/* Action : modifier le score manuellement */
if (isset($_POST['action']) && $_POST['action'] === 'modifier_score') {
    $cible         = intval($_POST['cible_id']);
    $nouveau_score = intval($_POST['nouveau_score']); // Valeur saisie dans l'input number

    try {
        $requete = $pdo->prepare("UPDATE utilisateur SET score = :score WHERE id = :id");
        $requete->bindValue(':score', $nouveau_score, PDO::PARAM_INT);
        $requete->bindValue(':id',    $cible,         PDO::PARAM_INT);
        $requete->execute();

        $_SESSION['admin_message'] = "Score modifié.";
    } catch (PDOException $e) {
        $_SESSION['admin_message'] = "Erreur : " . $e->getMessage();
    }

    header('Location: admin.php');
    exit;
}

/* Déconnexion admin */
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_connecte']); // Supprime uniquement la clé admin, pas toute la session
    header('Location: connexion_admin.php');
    exit;
}

/*
    Récupération des données pour l'affichage
    Date du jour côté PHP (cohérente avec les autres pages)
*/
$aujourd_hui = date('Y-m-d');

/* Tous les utilisateurs triés du meilleur score au moins bon */
$requete      = $pdo->query("SELECT * FROM utilisateur ORDER BY score DESC");
$utilisateurs = $requete->fetchAll();

/* Nombre total de comptes */
$total_users = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();


$requete = $pdo->prepare("
    SELECT COUNT(*) FROM quete_accomplie qa
    JOIN utilisateur u ON u.id = qa.utilisateur_id
    WHERE qa.date_accomplie = COALESCE(u.date_custom, :aujourdhui)
");
$requete->bindValue(':aujourdhui', $aujourd_hui);
$requete->execute();
$total_quetes = $requete->fetchColumn();

/* Meilleur streak toutes dates et tous utilisateurs confondus */
$record_streak = $pdo->query("SELECT MAX(streak_actuel) FROM streak")->fetchColumn();

/* Score moyen arrondi à l'entier */
$score_moyen_raw = $pdo->query("SELECT AVG(score) FROM utilisateur")->fetchColumn();
$score_moyen = $score_moyen_raw ? round($score_moyen_raw) : 0;

/* Catégories les plus cochées aujourd'hui, triées par nombre décroissant */
$requete = $pdo->prepare("
    SELECT q.categorie, COUNT(*) AS nb
    FROM quete_accomplie qa
    LEFT JOIN quete q ON q.id = qa.quete_id
    JOIN utilisateur u ON u.id = qa.utilisateur_id
    WHERE q.categorie IS NOT NULL
    AND qa.date_accomplie = COALESCE(u.date_custom, :aujourdhui)
    GROUP BY q.categorie ORDER BY nb DESC
");
$requete->bindValue(':aujourdhui', $aujourd_hui);
$requete->execute();
$top_categories = $requete->fetchAll();

/* Humeurs renseignées aujourd'hui, triées par fréquence décroissante */
$requete = $pdo->prepare("
    SELECT h.humeur, COUNT(*) AS nb
    FROM humeur h
    JOIN utilisateur u ON u.id = h.utilisateur_id
    WHERE h.date_humeur = COALESCE(u.date_custom, :aujourdhui)
    GROUP BY h.humeur ORDER BY nb DESC
");
$requete->bindValue(':aujourdhui', $aujourd_hui);
$requete->execute();
$humeurs_stats = $requete->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Better</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- Rafraîchissement auto toutes les 30s, annulé dès que l'admin interagit -->
    <script>
        var refreshTimer = setTimeout(function() { location.reload(); }, 30000);
        document.addEventListener('click',  function() { clearTimeout(refreshTimer); });
        document.addEventListener('change', function() { clearTimeout(refreshTimer); });
    </script>
</head>
<body class="admin_body">
<div class="admin_layout">

    <div class="admin_header">
        <h1>Panel Admin — Better</h1>
        <div class="admin_header_links">
            <a href="accueil.php">← Retour au site</a>
            <a href="admin.php?logout=1" class="lien_logout">Déconnexion admin</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message_ok">✅ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="message_err">❌ <?php echo htmlspecialchars($erreur); ?></div>
    <?php endif; ?>

    <!-- STATISTIQUES GLOBALES -->
    <div class="admin_grid">
        <div class="stat_card">
            <span class="stat_emoji">👥</span>
            <div class="stat_big"><?php echo $total_users; ?></div>
            <div class="stat_label">Utilisateurs</div>
        </div>
        <div class="stat_card">
            <span class="stat_emoji">⚔️</span>
            <div class="stat_big"><?php echo $total_quetes; ?></div>
            <div class="stat_label">Quêtes aujourd'hui</div>
        </div>
        <div class="stat_card">
            <span class="stat_emoji">🔥</span>
            <div class="stat_big"><?php echo $record_streak ?? 0; ?></div>
            <div class="stat_label">Record de série</div>
        </div>
        <div class="stat_card">
            <span class="stat_emoji">⭐</span>
            <div class="stat_big"><?php echo $score_moyen; ?></div>
            <div class="stat_label">Score moyen</div>
        </div>
    </div>

    <!-- CATÉGORIES LES PLUS POPULAIRES -->
    <div class="admin_section">
        <h2>📊 Catégories les plus populaires</h2>
        <div class="categories_grid">
            <?php foreach ($top_categories as $cat):
                /* Classe CSS spéciale pour "écologie" à cause de l'accent */
                $classe = 'cat_' . ($cat['categorie'] === 'écologie' ? 'ecologie' : $cat['categorie']);
            ?>
            <div class="categorie_badge <?php echo $classe; ?>">
                <?php echo mb_strtoupper($cat['categorie'], 'UTF-8'); ?> — <?php echo $cat['nb']; ?> quête<?php echo $cat['nb'] > 1 ? 's' : ''; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($top_categories)): ?>
                <p class="texte_vide">Pas encore de données pour aujourd'hui.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- HUMEURS DU JOUR -->
    <div class="admin_section">
        <h2>🌤 Humeurs du jour</h2>
        <div class="humeurs_grid">
            <?php
            /* Tableau de correspondance emoji → label lisible */
            $labels_humeurs = ['😴' => 'Fatigué(e)', '😊' => 'Bien', '💪' => 'Motivé(e)', '😤' => 'Stressé(e)', '😢' => 'Pas top'];
            foreach ($humeurs_stats as $h): ?>
            <div class="humeur_stat">
                <span class="h_emoji"><?php echo $h['humeur']; ?></span>
                <div class="h_nb"><?php echo $h['nb']; ?></div>
                <div class="h_label"><?php echo $labels_humeurs[$h['humeur']] ?? ''; ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($humeurs_stats)): ?>
                <p class="texte_vide">Aucune humeur enregistrée aujourd'hui.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- GESTION DES UTILISATEURS -->
    <div class="admin_section">
        <h2>👥 Gestion des utilisateurs</h2>
        <table class="user_table">
            <thead>
                <tr>
                    <th></th>
                    <th>NOM</th>
                    <th>EMAIL</th>
                    <th>SCORE</th>
                    <th>NIV.</th>
                    <th>RÔLE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $u):
                    /* Niveau calculé côté PHP (même formule que dans accueil.php) */
                    $niv = floor($u['score'] / 100) + 1;
                ?>
                <tr>
                    <td><img src="ressources/images/<?php echo $u['avatar']; ?>" class="user_avatar_mini" alt="Avatar"></td>
                    <td><strong><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></strong></td>
                    <td class="td_email"><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><strong><?php echo $u['score']; ?></strong></td>
                    <td><?php echo $niv; ?></td>
                    <td>
                        <?php if (isset($u['role']) && $u['role'] === 'admin'): ?>
                            <span class="badge_admin">Admin</span>
                        <?php else: ?>
                            <span class="badge_user">Joueur</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Réinitialiser : remet score à 0 et efface tout l'historique -->
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Réinitialiser cet utilisateur ?')">
                            <input type="hidden" name="action"   value="reinit">
                            <input type="hidden" name="cible_id" value="<?php echo $u['id']; ?>">
                            <button class="btn_action btn_reinit">Réinitialiser</button>
                        </form>

                        <!-- Modifier le score directement -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action"   value="modifier_score">
                            <input type="hidden" name="cible_id" value="<?php echo $u['id']; ?>">
                            <input type="number"  name="nouveau_score" class="input_mini" value="<?php echo $u['score']; ?>" min="0">
                            <button class="btn_action btn_score">Modifier</button>
                        </form>

                        <!-- Changer la date simulée (pour tester les streaks) -->
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action"   value="changer_date">
                            <input type="hidden" name="cible_id" value="<?php echo $u['id']; ?>">
                            <input type="date"   name="nouvelle_date" class="input_mini input_date"
                                   value="<?php echo !empty($u['date_custom']) ? $u['date_custom'] : date('Y-m-d'); ?>">
                            <button class="btn_action btn_date" type="submit">📅 Enregistrer</button>
                        </form>

                        <!-- Supprimer définitivement (bouton désactivé pour les admins) -->
                        <?php if (!isset($u['role']) || $u['role'] !== 'admin'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
                            <input type="hidden" name="action"   value="supprimer">
                            <input type="hidden" name="cible_id" value="<?php echo $u['id']; ?>">
                            <button class="btn_action btn_suppr">🗑️</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
