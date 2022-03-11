<?php
header('Pragma: private');/// IE BUG + SSL
ini_set("error_reporting", E_WARNING);
// error_reporting('E_ALL');



/*
HISTORIQUE

20090907 - traitement des recours supplémentaires (après premier recours) enregistrés dans nouvelle table 'nouveaux_recours' via libre.php + libre_enregistrement.php - il existe un nouveau recours si $recours_statut est renseigné.

20090904 - ##### NB NOUVEAU FICHIER ##### parti création PDF sortie du fichier et placée dans le nouveau fichier : 'inclusions/decision_pdf.php'

20080728 - gestion des comptes réservés
20080630 - mise en majuscules du nom par fonction pour gestion des exceptions
20070205 - nuit : 
    1) les variables récupérées dans les tables +
    2) les constantes (considérants et décisions écrites en dur) :
    ont été isolées et placées dans deux fichiers externes.
    Ces deux fichiers (inclusions/recuperation.php et inclusions/paves.php) sont appelés (via require) à la fois par la page actuelle (decision.php) et par  la page projet.php (affichage en html du projet de décision avec changement dynamique des valeurs comptables et affichage du détail de chaque poste pris en compte).

version du 20061228.

200611201 - V2 - implantation des "suffrages OK".
*/


// echo "<pre>GET
// ";
// print_r($_GET);
// echo "</pre>";
// echo "<pre>POST
// ";
// print_r($_POST);
// echo "</pre>";
// echo "<pre>SESSION
// ";
// print_r($_SESSION);
// echo "</pre>";
// exit;

/*
exemple affichage recours PDF : 
GET
Array
(
    [cand] => 2008
    [util] => 59305
    [type] => 3
    [an] => 2011
    [recours] => o
)


Nouvelle version : 

Array
(
    [cand] => 406
    [an] => 2012
    [recours] => 764
)


*/


if ($POSTCOM != "oui") { // 20070402 exception postcom
// ini_set("error_reporting", E_ALL);

} // 20070402 fin exception postcom


$nb_consids_ref = 0; // 20080414

$RECOURS = "non";
$recours = "";
$recours_statut = 0; // 20090907 traitement recours de recours

if ($_GET['recours'] == "o" or $_POST['recours'] == "o" or $_GET['recours'] > 0 OR isset($_POST['demande'])) { // 20090907 ajout numéro de recours

	$RECOURS = "oui";
	$recours = "o";
	
	if (($_GET['recours']+0)>0 or isset($_POST['demande'])) { // 20090907 ajout
		
		$recours_statut = 1;
		
	}
}


// ini_set("error_reporting", E_WARNING & E_PARSE & E_ERROR);

//echo ini_get("error_reporting");
// error_reporting(E_ALL);

// echo "<pre>GET
// 
// ";
// print_r($_GET);
// echo "</pre>";


/*

// dépenses déclarées :
$totaldepenses
.milliers($totaldepenses).$monnaie.($totaldepenses <= 1 ? "" : "s").
// recettes déclarées :
$totalrecettes
.milliers($totalrecettes).$monnaie.($totalrecettes <= 1 ? "" : "s").
//apport personnel déclaré : 
$apport_personnel
.milliers($apport_personnel).$monnaie.($apport_personnel <= 1 ? "" : "s").

// dépenses réf.
$depenses_ref=$totaldepenses+$totaldepenses_ref;
.milliers($depenses_ref).$monnaie.($depenses_ref <= 1 ? "" : "s").

// dépenses réf.
$recettes_ref=$totalrecettes+$totalrecettes_ref;
.milliers($recettes_ref).$monnaie.($recettes_ref <= 1 ? "" : "s").

// V2 CALC2
// excédents.
$excedent = $recettes_ref - $depenses_ref;
.milliers($excedent).$monnaie.($excedent <= 1 ? "" : "s").
// V2 CALC3
// réformations
// Apport personnel
$apport_personnel_R = $apport_personnel+$apport_perso_reform;
.milliers($apport_personnel_R).$monnaie.($apport_personnel_R <= 1 ? "" : "s").

// Apport personnel après déduction de l'excédent.
$apport_personnel_RExc = $apport_personnel_R - $excedent;
.milliers($apport_personnel_RExc).$monnaie.($apport_personnel_RExc <= 1 ? "" : "s").

// V2 CALC4
// devolutions
$devolution2 = $excedent - $apport_personnel_R;
.milliers($devolution2).$monnaie.($devolution2 <= 1 ? "" : "s").


// plafond légal : 
$plafondlegal

//demi-plafond :
$DEMI_PLAFOND

*/

// ##################### HISTORIQUE ######################
/*
20061127 
        - 3 versions de rejet (R20, R24 et R24 BIS) -> FAITES.
        - vers 15 H 40 -> simplification - traitement des considérants automatiques via switch

20061122 ; TOUTE NOUVELLE VERSION
        - non dépôt (ou "absence de dépôt" AD 18) -> FAIT.
        - hors délai (HD 19) -> FAIT.

20061024 : ajout, en cas d'approbation : n'appelle pas "d'observation" / "pas d'autre observation" si considérant présent.

20060711 : gestion des connexions via fichier conf pour faciliter la migration des fichiers. Ajouts comparables à ceux de projet-decision/index.php conçu à la même date.

20060705 : nouvelle version -> prend en compte le tout nouveau
mode de gestion des considérants qui implique l'appel de tables
différentes à partir de 2006.
*/
// ################### FIN HISTORIQUE ####################
/* 
Revoir :
- récupération des paramètres envoyés à la procédure : via POST à prévoir
- articles du code cités / type d'élection ;

*/

if ($POSTCOM != "oui") { // 20070402 exception postcom

$numUtil = $_GET['util'];
$typeUtil = $_GET['type'];
$numCandidat = $_GET['cand'];
$anneeGet = $_GET['annee']; //RAJOUTE PAR EA
}

// $annee = $_GET['an'];

require_once("../connexion_intra.php");
// 	require_once("../FONCTIONS.php");
$dba = mssql_connect($host,$user,$pass) or die("Connexion impossible.");
	
// $numUtil_sql = "select MAX(No_CorrespondantCTX) as numUtil from WEB_ELECTIONS.dbo.Table_Intranet_Correspondant AS C
// LEFT JOIN WEB_CONST.dbo.Table_AnneeElection AS A 
// ON 
// 
// (A.CodeAnElec = C.CodeBD)
// 
//  WHERE A.Annee = '".$annee."'";
//  
// $res_numUtil = mssql_query($numUtil_sql);
// 		
// 		$nb_util = mssql_num_rows($res_numUtil);
// 		
// 		if ($nb_util === 1) {
// 		
// 			$rs_numUtil = mssql_fetch_assoc($res_numUtil);
// 			
// 			$numUtil = $rs_numUtil['numUtil'];
// 			
// 		}

if (($numCandidat != "" AND $numUtil != "" AND $typeUtil != "") OR $recours_statut > 0)
{

	require("inclusions/decision_pdf_pourExportPonctuel344_viaNavig.php"); //Changé par EA pour export
	

} else {
echo "<h1>Erreur - les paramètres passés dans l'url sont incomplets !</h1>";
// 	if (!$numCandidat or  $numCandidat== "") {
// 		echo "<p>Numéro de candidat non reçu.</p>";
// 	}
// 	if (!$numUtil or  $numUtil == "") {
// 		echo "<p>Numéro d'utilisateur non reçu.</p>";
// 	}
// 	
// 	if (!$typeUtil or $typeUtil == "") {
// 		echo "<p>Type d'utilisateur non reçu.</p>";
// 	}
// 	
// 	
// 	// DEBUG
// echo "<pre>GET
// ";
// print_r($_GET);
// echo "</pre>";
// echo "<pre>POST
// ";
// print_r($_POST);
// echo "</pre>";
// echo "<pre>SESSION
// ";
// print_r($_SESSION);
// echo "</pre>";
// // FIN DEBUG

exit;
}
?>