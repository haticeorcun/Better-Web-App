<?php
session_start();
require_once 'includes/connexion_bdd.php';

/* Redirige si déjà connecté */
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: accueil.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email  = trim($_POST['email']);
    $mdp    = trim($_POST['mot_de_passe']);

    /* Vérifie si l'email est déjà utilisé par un autre compte */
    $requete = $pdo->prepare("SELECT id FROM utilisateur WHERE email = :email");
    $requete->bindValue(':email', $email);
    $requete->execute();

    if ($requete->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } else {
        /* password_hash() hache le mot de passe avec bcrypt avant de le stocker */
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);

        $requete = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe) VALUES (:nom, :prenom, :email, :mdp)");
        $requete->bindValue(':nom',    $nom);
        $requete->bindValue(':prenom', $prenom);
        $requete->bindValue(':email',  $email);
        $requete->bindValue(':mdp',    $mdp_hash);
        $requete->execute();

        /* Connexion automatique après inscription avec l'ID généré par l'auto-increment */
        $_SESSION['utilisateur_id'] = $pdo->lastInsertId();
        $_SESSION['nom']            = $nom;
        $_SESSION['prenom']         = $prenom;
        header('Location: accueil.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Better</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<main class="page_auth">
    <div class="carte_auth">
        <h1>BETTER</h1>
        <h2>Inscription</h2>

        <?php if ($erreur): ?>
            <p class="erreur_auth"><?php echo $erreur; ?></p>
        <?php endif; ?>

        <form method="POST" action="inscription.php">
            <label>Nom</label>
            <input type="text" name="nom" required placeholder="Dupont">

            <label>Prénom</label>
            <input type="text" name="prenom" required placeholder="Marie">

            <label>Email</label>
            <input type="email" name="email" required placeholder="ton@email.com">

            <label>Mot de passe</label>
            <input type="password" name="mot_de_passe" required placeholder="••••••••">

            <button type="submit">S'inscrire</button>
        </form>

        <p class="lien_auth">Déjà un compte ? <a href="connexion.php">Se connecter</a></p>
    </div>
</main>

</body>
</html>
