<?php
/*
    Script d'installation : recrée toutes les tables depuis zéro
    À lancer une seule fois si on veut réinitialiser complètement la base
*/
require_once 'includes/connexion_bdd.php';

$pdo->exec("DROP TABLE IF EXISTS utilisateur");
$pdo->exec("DROP TABLE IF EXISTS quete_accomplie");
$pdo->exec("DROP TABLE IF EXISTS streak");
$pdo->exec("DROP TABLE IF EXISTS humeur");
$pdo->exec("DROP TABLE IF EXISTS quete");

/*
    Les tables seront recréées et les quêtes réinsérées au prochain chargement
    car connexion_bdd.php gère ça automatiquement avec CREATE TABLE IF NOT EXISTS
*/

echo "Base réinitialisée. Rechargez n'importe quelle page pour recréer les tables.";
?>
