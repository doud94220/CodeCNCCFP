<?php

session_start();

$chemin_fichier = $_GET['cheminFichier'];

if(unlink($chemin_fichier))
{
    echo 'ok';
    unset($_SESSION['fichier_uploade']);
}
else
{
    echo 'erreur';
    unset($_SESSION['fichier_uploade']);
}
