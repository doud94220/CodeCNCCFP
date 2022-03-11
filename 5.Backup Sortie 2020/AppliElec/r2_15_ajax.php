<?php

session_start();

move_uploaded_file($_FILES['doc']['tmp_name'],'tmp/'.$_FILES['doc']['name']);

$_SESSION['fichier_uploade'] = 'tmp/'.$_FILES['doc']['name'];

echo '<a href="javascript:history.go(-1)">Retour à la page de contentieux</a>';
