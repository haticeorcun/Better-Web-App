<?php
session_start();
require_once 'includes/connexion_bdd.php';

/* Redirige vers l'accueil si l'utilisateur est déjà connecté */
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: accueil.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);        // trim() supprime les espaces en début/fin
    $mdp   = trim($_POST['mot_de_passe']);

    /* Cherche l'utilisateur par email dans la base */
    $requete = $pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
    $requete->bindValue(':email', $email);
    $requete->execute();
    $utilisateur = $requete->fetch();

    /* password_verify() compare le mot de passe saisi avec le hash stocké en base */
    if ($utilisateur && password_verify($mdp, $utilisateur['mot_de_passe'])) {
        $_SESSION['utilisateur_id'] = $utilisateur['id'];
        $_SESSION['nom']            = $utilisateur['nom'];
        $_SESSION['prenom']         = $utilisateur['prenom'];
        header('Location: accueil.php');
        exit;
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Better</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<main class="page_auth">
    <div class="carte_auth">
        <h1>BETTER</h1>
        <h2>Connexion</h2>

        <?php if ($erreur): ?>
            <p class="erreur_auth"><?php echo $erreur; ?></p>
        <?php endif; ?>

        <form method="POST" action="connexion.php">
            <label>Email</label>
            <input type="email" name="email" required placeholder="ton@email.com">

            <label>Mot de passe</label>
            <input type="password" name="mot_de_passe" required placeholder="••••••••">

            <button type="submit">Se connecter</button>
        </form>

        <p class="lien_auth">Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>
    </div>
</main>

</body>
</html>
