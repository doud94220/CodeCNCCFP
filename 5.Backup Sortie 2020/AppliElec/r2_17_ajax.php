<?php

session_start();

$key = "doc_".$_POST['numeroSignalement'];

if (isset($_FILES[$key]))
{
    move_uploaded_file($_FILES[$key]['tmp_name'],'tmp/'.$_FILES[$key]['name']);
    $_SESSION[$key]['fichier_uploade'] = 'tmp/'.$_FILES[$key]['name'];
}

echo '<a href="javascript:window.location = document.referrer;">Retour à la page précédente</a>'; //Au clic sur le lien, on retourne vers la page précédente et on la rafraichit (pour ne pas avoir le signalement "verouillé")

?>
