<?php

session_start();

$chemin_fichier = $_GET['cheminFichier'];
$numero_signalement = $_GET['numeroSignalement'];
$key = 'doc_'.$numero_signalement;

if(unlink($chemin_fichier))
{
    echo 'ok';
    unset($_SESSION[$key]['fichier_uploade']);
}
else
{
    echo 'erreur';
    unset($_SESSION[$key]['fichier_uploade']);
}

echo $_SESSION[$key]['fichier_uploade'];