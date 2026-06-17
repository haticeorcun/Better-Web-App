<?php
session_start();        // obligatoire avant session_destroy()
session_destroy();      // supprime toutes les données de session
header('Location: connexion.php');
exit;
?>
