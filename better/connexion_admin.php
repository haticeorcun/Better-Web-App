<?php
/* Connexion à l'espace admin via un code secret (pas de compte en base) */
session_start();

/* Redirige vers le panel si l'admin est déjà connecté */
if (isset($_SESSION['admin_connecte']) && $_SESSION['admin_connecte']) {
    header('Location: admin.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code_admin']);

    /* Comparaison du code saisi avec le code secret en dur */
    if ($code === 'BETTER_ADMIN_2026') {
        $_SESSION['admin_connecte'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $erreur = "Code admin incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Better</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page_admin_login {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f0f1a;
        }
        .carte_admin_login {
            background: #1a1a2e;
            border: 1px solid #6c5ce7;
            border-radius: 16px;
            padding: 40px;
            width: 340px;
            text-align: center;
            color: white;
        }
        .admin_logo { font-size: 2.5em; margin-bottom: 6px; }
        .carte_admin_login h1 { color: #6c5ce7; font-size: 1.4em; margin-bottom: 4px; }
        .carte_admin_login p  { color: #aaa; font-size: 13px; margin-bottom: 24px; }
        .admin_input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #333;
            background: #0f0f1a;
            color: white;
            font-size: 15px;
            margin-bottom: 14px;
            box-sizing: border-box;
        }
        .admin_input:focus { outline: none; border-color: #6c5ce7; }
        .admin_btn {
            width: 100%;
            padding: 12px;
            background: #6c5ce7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: transform .15s;
        }
        .admin_btn:hover { transform: translateY(-2px); }
        .erreur_admin { color: #e74c3c; font-size: 13px; margin-bottom: 12px; }
    </style>
</head>
<body>
<main class="page_admin_login">
    <div class="carte_admin_login">
        <div class="admin_logo">🛡️</div>
        <h1>Espace Admin</h1>
        <p>Entre le code secret pour accéder au panel</p>

        <?php if ($erreur): ?>
            <p class="erreur_admin">❌ <?php echo $erreur; ?></p>
        <?php endif; ?>

        <form method="POST" action="connexion_admin.php">
            <input type="password" name="code_admin" class="admin_input" placeholder="••••••••••••" required autofocus>
            <button type="submit" class="admin_btn">Accéder au panel</button>
        </form>
    </div>
</main>
</body>
</html>
