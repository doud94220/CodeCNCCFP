<?php

ini_set('max_execution_time', 0); // 20 minutes // 3000 : 50 minutes
ini_set('sqlsrv.ClientBufferMaxKBSize', 100000);

if (session_status() == PHP_SESSION_NONE)
{
    session_start();
}

header('content-type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Paris');

require ('classes/class.phpmailer.php');
require ('classes/class.smtp.php');
require_once("inclusion/CONNEXION.php");
require_once("fonctions/FONCTIONS_bandeau.php");
require_once("edition/decision_pdf/ilyes_index.php");
require_once("fonctions/nombres.php");
require_once("fonctions/anti_injection_sql.php"); // indispensable pour éviter les injections, notamment lors de la vérification de login via  login_verif.php et lors de tous les GET ou POST reçus ici
require_once("fonctions/chaines.php");
require_once("fonctions/dates.php");
require_once("fonctions/nombres.php");
require_once("fonctions/creer_code_barre_GED.php");
require_once("classes/code128barcode.class.php");
require_once("fonctions/FONCTIONS_rapport_ilyes.php");
require_once("fonctions/EDITIONS_fonctions_candidat.php");
require_once("fonctions/EDITIONS_notifications.php");
require_once("fonctions/calcule_montant.php");
require_once("tfpdf/tfpdf.php");
require_once('edition/decision_pdf/inclusions/class_PDF_lrar.php');
require("fonctions/FONCTIONS_decision_texte_depot_pourExportDes344.php");

$dossier_tif = "cache/courrier_tif/";
$Outil_consultation_sp = new Outil_consultation_sp();
$texte_mail = "";


//////////////// EXECUTION DU TRAITEMENT D'EXPORT : 'QUOTIDIEN' OU 'DE MASSE'
$texte_mail = $Outil_consultation_sp->Traitement_masse();
//$texte_mail = $Outil_consultation_sp->Traitement_quotidien();

exit(0); //ON LE LAISSE TEMPORAIREMENT POUR NE PAS ENVOYER DE MAIL


//////////////// PREPARATION DU MAIL

if (trim($texte_mail) === "")
{
    $texte_mail = "AUCUN NOUVEAU FICHIER à importer n'a été enregistré en GED le ".date('d/m/Y')." à ".date('H:i:s')."<br />Aucun PDF n'a donc dû être créé.";
}

$texte_mail_head = "<!DOCTYPE html>\n
                    <html lang=\"fr\">\n
                    <head>\n
                        <meta charset=\"utf-8\" />\n
                        <title>Résultats du traitement d'export</title>\n
                    </head>
                    <body>
                    <h1 style=\"color:green\">PDF exportés depuis la GED le " . date('d/m/Y') . "</h1>\n\n
                    <p>";

$texte_mail_fin = "</p>\n
                    </body>\n
                    </html>";

$texte_mail_complet = $texte_mail_head . $texte_mail . $texte_mail_fin;

//Email destinataire
//$destinataire = "info@cnccfp.fr";
$destinataire = "edouard.anthony@cnccfp.fr"; //TEMPOOOOOOOO

// PHPMailer instance
$mail = new PHPMailer;

// PHPMailer utilisera le SMTP :
$mail->isSMTP();

// SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug = 0;

$mail->Debugoutput = 'html';
$mail->Host = "192.168.6.30";
$mail->Username = "application@cnccfp.fr";
$mail->Password = "Zorglub@15";
$mail->CharSet = "UTF-8";
$mail->setFrom("application@cnccfp.fr", "Application rapporteurs");

// Informations Destinataire
$mail->addAddress($destinataire, "Service informatique");

//Set the subject line
$mail->Subject = "[Export][Decisions] Confirmation De Bonne Execution";

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
$mail->msgHTML($texte_mail_complet);

$mail->AltBody = 'Message au format texte seul';


//////////////// ENVOI DU MAIL

if (!$mail->send()) //Envoi du mail et Affichage du message de succès ou d'échec
{
    echo "<br /><hr /><br />ERREUR : Message d'information non envoyé : " . $mail->ErrorInfo;
}
else
{
    echo "<br /><hr /><br />Message d'information envoyé !";
}

?>