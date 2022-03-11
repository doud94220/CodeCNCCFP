<?php
/* historique

	20090907 : traitement des recours supplémentaires (après premier recours) cf. ../decision.php

	20090904 : création du fichier (extrait de ../decision.php)
		
*/

//Rajouté par EA (provient de l'export sur S8APP1)
function retirerAccents($str)
{
	$url = $str;
	$url = preg_replace('#Ç#', 'C', $url);
	$url = preg_replace('#ç#', 'c', $url);
	$url = preg_replace('#è|é|ê|ë#', 'e', $url);
	$url = preg_replace('#È|É|Ê|Ë#', 'E', $url);
	$url = preg_replace('#à|á|â|ã|ä|å#', 'a', $url);
	$url = preg_replace('#@|À|Á|Â|Ã|Ä|Å#', 'A', $url);
	$url = preg_replace('#ì|í|î|ï#', 'i', $url);
	$url = preg_replace('#Ì|Í|Î|Ï#', 'I', $url);
	$url = preg_replace('#ð|ò|ó|ô|õ|ö#', 'o', $url);
	$url = preg_replace('#Ò|Ó|Ô|Õ|Ö#', 'O', $url);
	$url = preg_replace('#ù|ú|û|ü#', 'u', $url);
	$url = preg_replace('#Ù|Ú|Û|Ü#', 'U', $url);
	$url = preg_replace('#ý|ÿ#', 'y', $url);
	$url = preg_replace('#Ý#', 'Y', $url);
	$url = preg_replace('#[^0-9a-z]+#i', '', $url);
	
	return ($url);
}


if ($POSTCOM != "oui") { // 20070402 exception postcom


//require("inclusions/fonctions_inc.php"); // 20080701


// print_r($_GET);
// print_r($_SESSION);
//$oublier_devolution = $_GET['devolution']; // 20061011 

// INTRA 03
//$numUtil = 3041;
//$numCandidat = 1489;
//$typeUtil = 3;
// URL TYPE :
// ?util=3041&type=3&cand=1489
// ?util=2634&type=3&cand=9356

// ############################ DEBUG MAX ##############################
// ############################ DEBUG MAX ##############################
// ############################ DEBUG MAX ##############################
// ############################ DEBUG MAX ##############################
// ############################ DEBUG MAX ##############################

//require("debug_champs/proced12.php");
//exit;

// ########################### FIN DEBUG MAX ###########################
// ########################### FIN DEBUG MAX ###########################
// ########################### FIN DEBUG MAX ###########################
// ########################### FIN DEBUG MAX ###########################
// ########################### FIN DEBUG MAX ###########################
// $numCandidat = 61; // (60 -> code décision : 3 / 61 -> code décision 4)
// $numUtil = 3523;
// $typeUtil = 3;

// ########## DONNEES pour PDF ############

// echo "<H1>DEBUG Candidat ".$numCandidat."</h1>"; // 20061222 debug
require('../fpdf153/fpdf.php'); // 20080702

if (isset($_POST['candidat'])) {

	$_GET['cand'] = $_POST['candidat'];
}

if (isset($_GET['cand'])) {

	$_POST['candidat'] = $_GET['cand'];
}


if (isset($_POST['id_recours'])) {

	$_GET['recours'] = $_POST['id_recours'];

}

if (isset($_GET['recours'])) {

	$_POST['id_recours'] = $_GET['recours'];

} 

if (isset($_POST['annee'])) {
	$_GET['an'] = $_POST['annee'];
}
if (isset($_GET['an'])) {
	$_POST['annee'] = $_GET['an'];
}


class PDF extends FPDF
{
function Footer()
{

	global $numCandidat;
	global $annee;
	global $_GET;


	// echo "<h1>cand. ".$_GET['cand']."</h1>";
	
	$datepasscomm = calcule_date_decision ($annee, $_GET['cand'], $numscrutin, $_GET['recours']); // 20111207 nouvelle fonction - déménagement 

// echo "<h1>date : ".$datepasscomm."</h1>";
		
	// echo
	$j = 0;
	$m = 0;
	$a = 0;
	
	if (strlen($datepasscomm) === 10) {
		
		list($j,$m,$a) = explode("/",$datepasscomm);
	
		// echo "jour : ".$j." mois : ".$m." an : ".$a;
	
		if ($a > 2011 or ($a == 2011 and ($m+0) > 11)) {
		
			$adresse = "36 rue du Louvre, 75042 Paris Cedex 1"; // devenu le 36 et non le 34-36 le 28 septembre à 12 h 17
		
		} else {
		
			$adresse = "36 rue du Louvre, 75042 Paris Cedex 1";	
		
		}
		
	} else {
	
		$adresse = "36 rue du Louvre, 75042 Paris Cedex 1"; // si aucune date
	
	}

	// FIN DÉMÉNAGEMENT
	






//Positionnement à 1,5 cm du bas
//$this->SetY(-15);
$this->SetY(-20);
//Arial italique 8
$this->SetFont('Arial','I',8);
//$this->SetFont('Times','',11);
//Couleur du texte en gris
//$this->SetTextColor(128);
$this->SetTextColor(43,0,146);
//Numéro de page
$this->SetX(0);
//$this->Cell(0, 10,'Commission nationale des comptes de campagne et des financemetns politiques - 33, av. de Wagram - 75017 Paris / Page '.$this->PageNo(),0,0,'C'.$this->Image('img/logo-M.png',185,275,15));
$this->SetFont('Times','I',10);
$this->Cell(0, 0, 'Commission nationale des comptes de campagne et des financements politiques',0,0,'C');
$this->SetX(0);
//$this->SetFont('Times','',9);
$this->SetFont('Arial','',8);
$this->Cell(0, 10, $adresse.' - Téléphone : 01 44 09 45 09 - Télécopie : 01 44 09 45 00 - www.cnccfp.fr',0,0,'C'.$this->Image('../fpdf153/img/logo-M.png',185,275,15,'','','javascript:parent.frames[0].history.go(-1)')); // 20080702
$this->SetX(10);
$this->SetTextColor(0);
// $this->Cell(0, 20,'page '.$this->PageNo().'/{nb} - réf. : '.$numCandidat.'',0,0,'C'); // 20071010 uniformisation des pieds post-com.php et decision imprimée seule (demande E. Tailly) :
$this->Cell(0, 20,'page '.$this->PageNo(),0,0,'C');
}
}

$rep = "R É P U B L I Q U E    F R A N Ç A I S E";
$testln = "\n  ";



} // 20070402 fin exception postcom

// ########## FIN DONNEES pour PDF ############
// récupération des variables à partir des tables
// NB. fichier commun avec projet.php !

// DEBUG if (strlen($_GET['t3']) > 0) { // 20090504

// require("inclusions/recuperation_TEST.php"); 

// } else {


//-------------------------------------------------------------------- Créé par EA pour export ---------------------------------------------------------------------
$tableauNumeroCandidatsPourExportDecIni = array();

//On filtre que les sur candidats qui ont dateCCFP = NULL
if($anneeGet == 2010)
{
	$tableauNumeroCandidatsPourExportDecIni = array(894, 941, 758, 839, 843);
}
elseif($anneeGet == 2011)
{
	$tableauNumeroCandidatsPourExportDecIni = array(1545, 1813, 1838, 2141, 2277, 2568, 2719, 2936, 3142, 3189, 3200, 3397, 3420, 3455, 3627, 3751, 3808, 3846, 3849, 3894, 3909, 4137, 4168, 4223, 4231, 4287, 4298, 4313, 4324, 4332, 4386, 4435, 4453, 4504, 4512, 4522, 4643, 4644, 4654, 4670, 4735, 4883, 4960, 5001, 5013, 5014, 5016, 5055, 5095, 5119, 5295, 5381, 5417, 5515, 5523, 5530, 5583, 5610, 5649, 5659, 5873, 5875, 5885, 5897, 6045, 6055, 6123, 6132, 6172, 6185, 6347, 6414, 6447, 6489, 6562, 6645, 6685, 6711, 6968, 6987, 7127, 7316, 7418, 7501, 7568, 7607, 7684, 7696, 7774, 7846, 7862, 7890, 7893, 7907, 8067, 8145, 8208, 8231, 8241, 8365, 8388, 8412, 8464, 8488, 8524, 8537, 8571);
}
elseif($anneeGet == 2012)
{
	$tableauNumeroCandidatsPourExportDecIni = array(2, 336, 353, 846, 852, 857, 1399, 1469, 1480, 1497, 1670, 1830, 1904, 1918, 1980, 1982, 2090, 2144, 2152, 2213, 2266, 2319, 2340, 2403, 2423, 2459, 2463, 2464, 2616, 2651, 2655, 2656, 2697, 2718, 2720, 2731, 2871, 2880, 2887, 2943, 3024, 3032, 3036, 3067, 3221, 3240, 3250, 3306, 3343, 3530, 3557, 3632, 3701, 3779, 3815, 3832, 3851, 3857, 3863, 3983, 4001, 4104, 4141, 4154, 4176, 4309, 4396, 4403, 4411, 4436, 4484, 4561, 4886, 4903, 4963, 5037, 5093, 5095, 5142, 5165, 5276, 5295, 5381, 5641, 5648, 5743, 5785, 5807, 5861, 5875, 5890, 5899, 5924, 6019, 6023, 6033, 6111, 6159, 6169, 6187, 6276, 6305, 6469, 6529, 6531, 6565, 6566, 6568, 6605, 6643, 6675, 6715, 6759, 6875, 6901, 6907, 6912, 6919, 6927, 6930, 6957, 6965, 6990, 7015);
}
elseif($anneeGet == 2013)
{
	$tableauNumeroCandidatsPourExportDecIni = array(43, 61, 72);
}
elseif($anneeGet == 2014)
{
	$tableauNumeroCandidatsPourExportDecIni = array(175, 351, 355, 507, 508, 724, 749, 871, 885, 896, 917, 921, 1099, 1118, 1132, 1148, 1212, 1230, 1233, 1237, 1254, 1278, 1301, 1329, 1356, 1389, 1399, 1401, 1429, 1444, 1451, 1645, 1691, 1693, 1706, 1712, 1713, 1718, 2050, 2218, 2356, 2357, 2368, 2370, 2395, 2415, 2471, 2522, 2598, 2726, 2782, 2806, 2816, 2894, 2895, 2915, 2943, 3012, 3027, 3096, 3126, 3205, 3270, 3294, 3481, 3508, 3515, 3540, 3548, 3589, 3641, 3790, 3872, 3883, 3904, 3938, 3950, 4004, 4007, 4069, 4181, 4255, 4392, 4599, 4663, 4705, 4813, 4862, 4898, 4970, 4984, 5175, 5238, 5344, 5347, 5365, 5388, 5448, 5481, 5654, 5717, 5763, 5775, 5784, 5789);
}
else
{
	die("erreur dans l'année dans le GET");
}

$hostGed = "192.168.6.5,1433";
$userGed = "sa";
$passGed = "ykjb003340";
$dbaGed = mssql_connect($hostGed, $userGed, $passGed) or die("Connexion impossible.");

for($cptrBoucleExport=0;$cptrBoucleExport<count($tableauNumeroCandidatsPourExportDecIni);$cptrBoucleExport++)
{
$_GET['cand'] = $tableauNumeroCandidatsPourExportDecIni[$cptrBoucleExport];
$numCandidat = $tableauNumeroCandidatsPourExportDecIni[$cptrBoucleExport];

//-------------------------------------------------------------------------------------------------------------------------------------------------------------------

if ($recours_statut === 0) { // TRAITEMENT PREMIÈRE DÉCISION && DÉCISION PREMIER RECOURS - 20090907
	require("inclusions/recuperation.php");



} else { // 20090907 TRAITEMENT RECOURS SUPPLÉMENTAIRES cf. commentaire plus haut / date - RÉCUPÉRATION DES ÉLÉMENTS POSTÉS (prévisualisation) OU RÉCUPÉRÉS :

// 	########################################################################
// 	########################################################################
//  ############# RECOURS SUPPLÉMENTAIRES (après 1er recours) ##############
	
 	require_once("../connexion_intra.php");
	require_once("../FONCTIONS.php");
	$dba = mssql_connect($host,$user,$pass) or die("Connexion impossible.");
	
	/* 
	champs postés :
	
	['id_recours'] => 
	['precedent_id'] => 1
	['candidat'] => 666
	['annee'] => 2008
	['pre_date'] => Décision du
	['date'] => 10/09/2009
	['dec_titre'] => Décision
	['dec_chapeau'] => Relative au compte de campagne de
	['dec_texte1'] => considérants
	['decide'] => 1
	['dec_texte2'] => - Article 1 : - Article 2 : - Article 3 :
	['dec_signature1'] => Pour la commission,
	['dec_signature2'] => Le président
	['signataire'] => François Logerot
	['notif_signataire_titre'] => Le secrétaire général
	['notif_signataire'] => Régis Lambert
	['notif_ref_texte'] => Nos réf. :
	['notif_ref'] => SJ/
	['notif_objet_texte'] => Objet :
	['notif_objet'] => notification de la décision relative....
	['notif_texte'] => texte notif
	['notif_notes'] => notes en bas de page
	['demande'] => enregistrer
	*/
	
// 	if (isset($_POST['annee'])) { 
// 		$_GET['an'] = $_POST['annee'];
// 	}
// 	if (isset($_POST['id_recours'])) {
// 		$_GET['recours'] = $_POST['id_recours'];
// 	}
// 	if (isset($_POST['candidat'])) {
// 		$_GET['cand'] = $_POST['candidat'];
// 	}

	// echo "<p>année : ".$_GET['an']." - recours : ".$_GET['recours']."</p>";

	if (isset($_GET['an']) and $_GET['an'] > 0 and isset($_GET['recours']) and $_GET['recours'] > 0 and $_POST['demande']!= "apercu") { // récupération de l'enregistrement -> affichage
	
	// ####################################################################
	// ################# édition du recours enregistré ####################
	
		// requête de récupération du contenu du recours pour inclusions dans POST (sinon - en cas d'aperçu avant enregistrement - utilisation ensuite des authentiques POST reçus...)
		$sql_recours_sup = "select * from BD_ELEC_".$_GET['an'].".dbo.nouveaux_recours AS TREC LEFT JOIN BD_ELEC_".$_GET['an'].".dbo.Table_Candidat_Rectifie AS TCAND ON (TREC.candidat = TCAND.NumCand) WHERE id_recours = '".$_GET['recours']."'";
		
		$res_recours_sup = mssql_query($sql_recours_sup);
		
		$nb_recours_sup = mssql_num_rows($res_recours_sup);
		
		if ($nb_recours_sup === 1) {
		
			$rs_rec_sup = mssql_fetch_assoc($res_recours_sup);
		
			$_POST['dec_date_texte'] = $rs_rec_sup['dec_date_texte'];
		
			if (strlen($rs_rec_sup['dec_date']) === 8) {
				$_POST['dec_date'] = date_int_vers_slash_fr($rs_rec_sup['dec_date']);
			} else {
				$_POST['dec_date'] = "??/??/????";
			}

			$_POST['dec_titre'] = $rs_rec_sup['dec_titre'];
			$_POST['dec_chapeau'] = $rs_rec_sup['dec_chapeau'];
			$_POST['dec_texte1'] = $rs_rec_sup['dec_texte1'];
			$_POST['decide'] = $rs_rec_sup['decide'];
			$_POST['dec_texte2'] = $rs_rec_sup['dec_texte2'];
			$_POST['dec_texte2'] = $rs_rec_sup['dec_texte2'];
			
			// echo "<p>".$rs_rec_sup['dec_delibere_par_auto']."</p>";
			
			if ($rs_rec_sup['dec_delibere_par_auto'] == 1) {
				$_POST['dec_delibere_par']=delibere_par($_GET['an'],$_GET['cand'],$_GET['recours']);
				
			} else {
			
				$_POST['dec_delibere_par'] = $rs_rec_sup['dec_delibere_par'];
				
			}
			
			// echo "<p>".$_POST['dec_delibere_par']."</p>";
			
			
			$_POST['dec_signature1'] = $rs_rec_sup['dec_signature1'];
			$_POST['dec_signature2'] = $rs_rec_sup['dec_signature2'];
			$_POST['dec_signataire'] = $rs_rec_sup['dec_signataire'];

		} else {
		
			$_POST['dec_date_texte'] = "";
			$_POST['dec_date'] = "";
			$_POST['dec_titre'] = "ERREUR";
			$_POST['dec_chapeau'] = "Recours supplémentaire introuvable\n\n(numéro du recours demandé : ".$_GET['recours']." - année du scrutin : ".$_GET['an'].")";
			$_POST['dec_texte1'] = "";
			$_POST['decide'] = 0;
			$_POST['dec_texte2'] = "";
			$_POST['dec_delibere_par'] = "";
			$_POST['dec_signature1'] = "Pour le service informatique,";
			$_POST['dec_signature2'] = "Le serveur";
			$_POST['dec_signataire'] = "Intra ÈNEZÉ-RODEUX";
			
			// anomalie. 
		}
		
	
	// ####################################################################
	// ############### FIN - édition du recours enregistré ################
	
	}
	
	
	
	$DATE = trim($_POST['dec_date_texte'])." ".date_en_lettres(trim($_POST['dec_date']));
	$DATE = format_quotes($DATE);
	
	$TITRE_DECISION = format_quotes($_POST['dec_titre']);
	
	$TITRE = format_quotes($_POST['dec_chapeau']);
	
	$TXT = "";
	// $TXT."".strip_tags($CONSID)
	
	$CONSID_DECISION_SPECIALE = format_quotes($_POST['dec_texte1']);
	
	// décide traité plus bas
	$DECISION_RECOURS = format_quotes($_POST['dec_texte2'])."\n\n";
	
	// echo "<p>année : ".$_POST['annee']."</p>";
	
	// $_POST['dec_delibere_par_auto'] = 1;
	
	if ($_POST['dec_delibere_par_auto'] == 1) {
	
		// echo "<p>année : ".$_GET['an']." - recours : ".$_GET['recours']."</p>";
		
		//$_POST['dec_delibere_par']=delibere_par($_GET['an'],($_GET['cand']+0),$_GET['recours']+0); // 20121211 : 
		$_POST['dec_delibere_par']=delibere_par($_POST['annee'],($_POST['candidat']+0),$_POST['id_recours']+0);
				
	}

	
	$DECISION_RECOURS.= format_quotes($_POST['dec_delibere_par']);
	
	$SIGNATURE ="                                                                                                              ".format_quotes($_POST['dec_signature1'])."\n";
		$SIGNATURE.="                                                                                                              ".format_quotes($_POST['dec_signature2']);
	$SIGNATURE.="\n\n\n";
	$SIGNATURE.="                                                                                                              ".format_quotes($_POST['dec_signataire']);



	
	// echo "<p>".$DECISION_RECOURS."</p>";
	

//  ########## FIN - RECOURS SUPPLÉMENTAIRES (après 1er recours) ###########
// 	########################################################################
// 	########################################################################
}

//}


// DEBUG
// echo "<pre>### tabReformations";
// print_r($tabReformations);
// echo "</pre>";
// 
// 
// echo "<pre>### tabDepnomremb";
// print_r($tabDepnomremb);
// echo "</pre>"; // 20061204 dépenses non remboursables.
// 
// 
// echo "<h1>apport_personnel : ".$apport_personnel."</h1>";
// echo "<h1>apport_perso_reform : ".$apport_perso_reform."</h1>";
// echo "<h1>apport_perso_nr : ".$apport_perso_nr."</h1>";
// echo "<h1>totaldepenses_nr : ".$totaldepenses_nr."</h1>";
// echo "<h1>totalrecettes_nr : ".$totalrecettes_nr."</h1>";
// 
// exit;

/* Fonctionnement : 
		la dévolution est ici déterminée par le rapporteur.
		0 correspond à oui (sic) ; ensuite, si le montant $tabFixe[1]['MTDEVOL'] est aussi égal 0 : aucune dévolution ne doit être appliquée. Si le montant $tabFixe[1]['MTDEVOL'] est différent de 0, une dévolution de ce montant est appliquée.
*/
$modifier_devolution = $tabFixe[1]['Devolution']; // 20060711
$devolution_montant = $tabFixe[1]['MTDEVOL']; // 20061226


// inclusions des constantes : considérants "automatiques"
// NB. là encore : fichier commun avec projet.php !

if ($DECISION_SPECIALE != "oui" && $recours != "o" && $recours_statut === 0) { // PREMIÈRE DÉCISION cf. recuperation.php // 20071022 'sp' envoyé depuis liste.php des RECOURS // 20080627 ajout && $recours != "o" trouvé sur intra-03 -> pas le temps de vérifier si cet ajout est vraimement nécessaire... // -> && $_GET['recours'] != "o" coupé // 20090910 exclusion du recours supplémentaire ( $recours_statut === 0)

	require("inclusions/paves.php");

} else {

	// DÉCISION PREMIER RECOURS

	$TXT = "";
	$CONSID = $CONSID_DECISION_SPECIALE;
	
	

// DEBUG
// echo "<h1>CONSID ".$CONSID."</h1>";
// echo "<h1>DECISION_SPECIALE ".$DECISION_SPECIALE."</h1>";
// echo "<h1>recours ".$recours."</h1>";


	if ($recours != "o" and $RECOURS != "oui" and $recours_statut === 0) { // 20071105 // repris ici le 20080627 (ajout de cette condition trouvée sur intra-03) // 20090910 recours supplémentaire exclus (ajout $recours_statut === 0)
	
				$siegeant = str_replace("Le vice","vice",$siegeant); // 20110704 - revoir
				// dans la séance
            	
				if($numcand = 777){ //demande Aldophe MAKONGO
					$DECISION.="\nDélibéré par la Commission nationale des comptes de campagne et des financements politiques le ".trim($tabFixe[0]['DatePassComm'] != "" ? date_en_lettres($tabFixe[0]['DatePassComm'])."" : "XX/XX/XXXX")." par ".$siegeant."\n"; // 20100218 suppresson du "MM. " devant $siegeant (nouvelle version dans recuperation.php)
					}else{
						$DECISION.="\nDélibéré par la Commission nationale des comptes de campagne et des financements politiques dans la séance du ".trim($tabFixe[0]['DatePassComm'] != "" ? date_en_lettres($tabFixe[0]['DatePassComm'])."" : "XX/XX/XXXX")." où siégeaient ".$siegeant."\n"; // 20100218 suppresson du "MM. " devant $siegeant (nouvelle version dans recuperation.php)
						}
		$SIGNATURE ="                                                                                                              Pour la commission,\n";
		$SIGNATURE.="                                                                                                              ".$tabFixe[3]['TitreSignature'];
		$SIGNATURE.="\n\n\n";
		$SIGNATURE.="                                                                                                              ".$tabFixe[3]['PrenomSignature']." ".strtoupper($tabFixe[3]['NomSignature'])."
		";
	} // 20071105
}


// echo "<pre>";
// print_r($GLOBALS);
// echo "</pre>";
// exit;
// ####################################################################
// ####################################################################
// ####################################################################
// ####################################################################
// ####################################################################
// ####################################################################
// ########################### EDITION PDF ############################

// 20070402 nouveau - from rapport_pdf.php

if ($recours != "o" AND $RECOURS != "oui") { // 20071105 calcul du saut de page éventuel - sauf pour recours. // 20080627 condition seule en provenance d'intra-3

$pdf2=new PDF('P','mm','A4');
// $pdf2->AliasNbPages(); // pour renvoyer nb total de pages avec {nb}
$pdf2->SetAutoPageBreak(auto, 30); // hauteur pied de page !
$pdf2->AddPage();
$pdf2->AliasNbPages(); 
$pdf2->SetFont('Times','',12);
//$pdf2->Image('../decision/fpdf153/img/logo-M-texte.png',8,8,35,'',''); // 20080702
$pdf2->Image('../fpdf153/img/logo-M-texte.png',8,8,35,'',''); // 20080702

$pdf2->SetTextColor(43,0,146);
$pdf2->Cell(0,4,$rep,0,1,'C');
$pdf2->SetTextColor(0);
$pdf2->Ln(5); // décalage paramétrable (par la suite) - 20070228
$pdf2->SetFont('arial','',10);
$pdf2->MultiCell(0,5.5,$DATE,0,'R'); // 5,5 au lieu de 7 20060404
$pdf2->Ln(5); // décalage paramétrable (par la suite) - 20070228
$pdf2->SetFont('arial','B',12);
$pdf2->SetTextColor(43,0,146);
$pdf2->Cell(0,7,"Décision",0,1,'C');
$pdf2->SetTextColor(0);
$pdf2->SetFont('arial','',10);
//$pdf2->SetFont('Times','',11);
$pdf2->MultiCell(0,5.5,$TITRE,0,'C'); // 5,5 au lieu de 7 20060404
$pdf2->Ln(5); // décalage paramétrable (par la suite) - 20070228
$pdf2->SetTopMargin(20); // augmente marge haute des pages suivantes
$pdf2->SetLeftMargin(15);
$pdf2->SetRightMargin(15);

if ($ALERTE_SOLDE_NEGATIF != "") {
$pdf2->SetFont('Courier','B',14);
$pdf2->MultiCell(0,5,$ALERTE_SOLDE_NEGATIF."\n\n");
}
$pdf2->SetFont('Arial','',10);
// calcul de décision :
$pdf2->MultiCell(0,5.5,$TXT."".strip_tags($CONSID)); // 5,5 au lieu de 7 20060404
/* 
20070206 - strip_tags pour suppression des tags considérants non remboursables - cf. $CONSID_NONREMB dans paves.php - utilisés pour la l'affichage du projet de décision.
*/

$decision_long=$pdf2->GetStringWidth($DECISION);
$signature_long_max = 7*180; // + la signature occupe 7 lignes.
$paragraphe_long_max = $decision_long + $signature_long_max;
//echo "<h1>".$paragraphe_long_max."</h1>";
if ($paragraphe_long_max < 3600) { 
//3600 est une longueur moyenne pour 20 lignes
// Ne fonctionne donc que pour les paragraphes d'une longueur inférieure.
	$test=$pdf2->GetY(); // vérif hauteur de page (en mm)
	$reste_h = 267 - $test;
	$reste_lignes = $reste_h/8; // ########## VERIFIER le 10 !
	$reste_lignes = floor($reste_lignes);
	$doitentrer = $paragraphe_long_max/180; // 180 mm correspond à la largeur hors marges --> NB de ligne obtenu !
	
	if ($reste_lignes < $doitentrer)
	{
		$pdf2->Cell(0,0,'... / ...',0,0,'R');
		$saut_de_page = "oui";
		$pdf2->AddPage('P','mm','A4');
		$pdf2->SetY(22); // décalage haut de page suivante
		if ($doitentrer > 4 and $dec=="") { // suggestion de décalage si important.
			$decalage=floor(($reste_lignes*7)/4); // 20060406 - chg. /3 en /4
			$dec = $decalage; // VALEUR TROUVEE !!!
			$dec2 = $dec / 2; // VOIR SI UTILISEE ?
		}
	} else {
		$dec = 5; // décalage standard minimum - 20070228
	}
}

$pdf2->Cell(0,0,'dec = '.$dec,0,0,'R');
// $pdf2->Close();
} else { // cas : recours gracieux // 200080627 Condition *complète* (jusqu'à fin d'accolade else) ajoutée, en provenance d'intra-03
	$saut_de_page = "non";
	$dec = 12; // 20071105 espacement par défaut des principaux paragraphes.
} // 20071105 fin du calcul du saut de page éventuel - sauf pour recours.


$numpagepost = 0; // placer ici sinon pages précédentes comptées !

// // 20070402 nouveau fin - from rapport_pdf.php

// for ($tonus=0; $tonus < 2; $tonus++) { // Mise en page ajustée après un tour de boucle forcé. // 20070402 inspiré de rapport.pdf

// ############## fin du CALCUL LONGUEUR DECISION... ##################
// ####################################################################
// ####################################################################
// ####################################################################

if ($POSTCOM != "oui") { // 20070402 exception postcom
$pdf=new PDF('P','mm','A4');
$pdf->AliasNbPages(); // pour renvoyer nb total de pages avec {nb}

$titrepdf='Décision de la CNCCFP - réf. : '.$numCandidat.'';
$pdf->SetTitle($titrepdf);
$pdf->SetAuthor('Commission nationale des comptes de campagne et des financements politiques');
$pdf->SetCreator('Réf. : '.$numUtil.'');


} // 20070402 fin exception postcom


$pdf->SetAutoPageBreak(auto, 30); // hauteur pied de page !
$pdf->AddPage();
$numpagepost = 0; // 20071004 demande de M. Lambert. la numérotation doit commencer à 1. Auparavant, sans cet ajout, la numérotation suivait celle de la notification.

// $pdf->SetKeywords($tabFixe[2]['PrenomCand'].' '.$tabFixe[2]['NomCand'].' - Circonscription : '.$tabFixe[0]['NomCircons'].' - '.$DATE);



//$pdf->AddFont('arial','I','ariali.php');
//$pdf->AddFont('arial','','arial.php');
//$pdf->AddFont('arial','B','arialb.php');
//$pdf->SetFont('Arial','',12);
$pdf->SetFont('Times','',12);
//$pdf->Ln(0); // saut de ligne
//$pdf->Image('fpdf153/img/logo-M-texte.png',8,8,35,'','','javascript:parent.frames[0].history.go(-1)');
$pdf->Image('../fpdf153/img/logo-M-texte.png',8,8,35,'',''); // 20080702
$pdf->SetY(10);
$pdf->SetTextColor(43,0,146);
$pdf->Cell(0,4,$rep,0,1,'C');
$pdf->SetTextColor(0);
// $pdf->Ln(7); // saut de ligne

$pdf->Ln($dec); // décalage paramétrable. 20060404
$pdf->SetFont('arial','',10);
$pdf->MultiCell(0,5.5,$DATE,0,'R'); // 5,5 au lieu de 7 20060404
//$pdf->MultiCell(250,10,$adresse);
//$pdf->SetX(20);
// $pdf->Ln(7); // saut de ligne
$pdf->Ln($dec); // décalage paramétrable.
$pdf->SetFont('arial','B',12);
//$pdf->SetFont('Times','B',14);
$pdf->SetTextColor(43,0,146);
// $pdf->Cell(0,7,"Décision",0,1,'C'); // 20080627
$pdf->Cell(0,7,$TITRE_DECISION,0,1,'C'); // pour recours gracieux, rectification, ou décision simple // 20080627 from intra-03
$pdf->SetTextColor(0);
$pdf->SetFont('arial','',10);
//$pdf->SetFont('Times','',11);
$pdf->MultiCell(0,5.5,$TITRE,0,'C'); // 5,5 au lieu de 7 20060404
$pdf->Ln($dec); // décalage paramétrable.
$pdf->SetTopMargin(20); // augmente marge haute des pages suivantes
$pdf->SetLeftMargin(15);
$pdf->SetRightMargin(15);


if ($ALERTE_SOLDE_NEGATIF != "") { // ajout 20071017 demande M. Raynaud
$pdf->SetFont('Courier','B',14);
$pdf->MultiCell(0,5,$ALERTE_SOLDE_NEGATIF."\n\n");
}
//$pdf->SetFont('Times','',11);

// $pdf->Cell(40,10,'Hello World !');

// REVOIR FONCTION CALCUL...
// Nouvelle version !
// calcul de décision :

$pdf->SetFont('Arial','',10);
if (trim($CONSID) != "") { // peut être vide en cas de décision de recours lorsque le considérant 99 (décision spéciale + recours) n'a pas encore été enregistré. // 20080627 condition seule ajoutée from intra-03
$pdf->MultiCell(0,5.5,$TXT."".strip_tags($CONSID)); // 5,5 au lieu de 7 20060404
} else { // décision spéciale et recours non enregistrés. // 20080627 suite condition ajoutée (else complet cette fois)
// $pdf->MultiCell(0,5.5,$TXT."".strip_tags($CONSID)); 
$pdf->SetFont('Courier','B',14);
$pdf->MultiCell(0,5,"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\nXXXXXXXXXXXXXXX  ATTENTION  XXXXXXXXXXXXXXXX\nXXXXXXX   CONTENU NON ENREGISTRÉ     XXXXXXX\nXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX\n",0,'C');
$pdf->SetFont('Arial','',10);
}
/* 
20070206 - strip_tags pour suppression des tags considérants non remboursables - cf. $CONSID_NONREMB dans paves.php - utilisés pour la l'affichage du projet de décision.
*/

/* 
20070206 - strip_tags pour suppression des tags considérants non remboursables - cf. $CONSID_NONREMB dans paves.php - utilisés pour la l'affichage du projet de décision.
*/


// 	echo "<pre>";
// 	print_r($_POST);
// 	echo "</pre>";

if ($saut_de_page == "oui") { // 20070228
	$pdf->Ln($dec);
	$pdf->Cell(0,0,'... / ...',0,0,'R');
	$pdf->AddPage();
	$pdf->AliasNbPages(); 
	$couche = "non";
	$pdf->SetY(22); // décalage haut de page suivante
	$saut_de_page = "non"; // 20070322 initialisation -> boucles
}
//echo "<h1>".$paragraphe_long_max."</h1>";

if (($CodeDecision != 1 AND $CodeDecision != 2 and $DECISION_SPECIALE != "oui" and $recours_statut === 0) OR (isset($_POST['dec_titre']) AND isset($_POST['decide']) and $_POST['decide'] == 1)) { // 20090910 exclusion des recours supplémentaires (ajout $recours_statut === 0) SAUF si (isset($_POST['decide']) and $_POST['decide'] == 1)

	$pdf->MultiCell(0,6,"\nDÉCIDE",0,'C');
	
	
// 	echo "<p>DS : ".$DECISION_SPECIALE." - CodeDecision : ".$CodeDecision." - recours_statut : ".$recours_statut."</p>";
// 	echo "<pre>";
// 	print_r($_POST);
// 	echo "</pre>";

}

if ($recours == "o" or $RECOURS == "oui") { // 20071105 // 20080627 ajout if complet from intra-03

	$DECISION_RECOURS = str_replace("\n\n","\n",$DECISION_RECOURS); // 20121211 suppression d'un saut de ligne qui pourrait avoir été ajouté en fin de texte saisi, avant le "Délibéré par"...
	
	$DECISION = $DECISION_RECOURS;
	
	if ($recours_statut === 0) { // exclusion du recours supplémentaire
		$SIGNATURE = "";
	}
}
$pdf->MultiCell(0,5.5,$DECISION."\n".$pdf->Ln(3).$SIGNATURE); // 5,5 au lieu de 7 20060404

//$pdf->MultiCell(0,5.5,$DECISION."\n".$pdf->Ln($dec2).$SIGNATURE); // 5,5 au lieu de 7 20060404
// } // fin du tour de boucle forcé pour ajustement de la mise en page. // 20070402 fin suppression boucle forcée pour calcul hauteur !


if ($POSTCOM != "oui")
{ // 20070402 exception postcom

	//---------------------------------------------------------------- PAR EA POUR NOMMAGE PDF TEXTE EN SORTIE ----------------------------------------------------------------

	//Requete sur INTRAN02 pour récupérer des informations sur le candidat
	$sqlCandidat = "
					SELECT NomCandPref, PrenomCandPref
					FROM [BD_ELEC_".$anneeGet."].[dbo].[Table_Candidat_Rectifie]
					where NumCand = ".$numCandidat
					;

	$reqCandidat = mssql_query($sqlCandidat, $dba);

	if ($reqCandidat === false)
	{
		echo odbc_errormsg();
		die(999);
	}

	$arrayInfosCand = mssql_fetch_array($reqCandidat, $dba);
	$nomCandidat = mb_strtoupper($arrayInfosCand[0]);
	$prenomCandidat = $arrayInfosCand[1];

	//Requete sur la GED pour récupérer le numéro de la lettre
	$numeroLettre = '';

	try
	{
		$sqlGed = "
					SELECT FD_3A5E7E76 AS NumeroLettre
					FROM [FD_C66DBCEA].[dbo].[FD_Documents]
					LEFT JOIN [FD_C66DBCEA].[dbo].[FD_Images] ON (GUID = DocGUID)
					WHERE (FD_C6004355 = 'N')
					AND FD_E7787B05=".$anneeGet."
					AND  ActRevision = RevNo and Deleted <> '1'
					AND PageNo=1 
					AND FD_09A9C5FE =".$numCandidat
			       ;

		$reqGed = mssql_query($sqlGed, $dbaGed);

		if ($reqGed === false)
		{
			echo odbc_errormsg();
			die(888);
		}

		$arrayInfosGed = mssql_fetch_array($reqGed, $dbaGed);
		$numeroLettre = $arrayInfosGed['NumeroLettre'];

		//Debug dans fichier texte
		// $ressourceFichierEnv = fopen('inclusions/logs_pourExportPonctuelCons99_viaNavig.txt', 'a+');
		// fwrite($ressourceFichierEnv, $arrayInfosGed[0]);
		// fclose($ressourceFichierEnv);
		// die();
	}
	catch(Exception $e)
	{
		echo 'dans le catch';echo '<br>';
		echo'<pre class="debug">';print_r($e->getMessage());echo'</pre>';
		die();
	}

	//Creation du nom du fichier  !!!!!!!!! L'ALGO COMPORTE DES ERREURS : IL CONSERVE LES ACCENTS DANS LE NOM DU CANDIDAT, IL GERE MAL LES ESPACES DANS LES NOM DES CANDIDATS A PARTICULES !!!!!!!!!
	$nomFichier = 'Decision_'; //début du nom

	$array_prenom_candidat = explode("-", $prenomCandidat);
	$prenomAbrege = '';

	if(count($array_prenom_candidat) > 1)
	{
		$prenomAbrege = strtoupper(substr(retirerAccents($array_prenom_candidat[0]),0,1))."-".strtoupper(substr(retirerAccents($array_prenom_candidat[1]),0,1));
	}
	else
	{
		$prenomAbrege = strtoupper(substr(retirerAccents($prenomCandidat),0,1));
	}
	
	$nomPrenomAbrege = $prenomAbrege."_".$nomCandidat;
	$nomFichier .= $nomPrenomAbrege;

	$annee = "";

	if($anneeGet == 2010)
	{
		$annee = "201000000";
	}
	elseif($anneeGet == 2011)
	{
		$annee = "201100000";		
	}
	elseif($anneeGet == 2012)
	{
		$annee = "201200000";	
	}
	elseif($anneeGet == 2013)
	{
		$annee = "201300000";	
	}
	elseif($anneeGet == 2014)
	{
		$annee = "201400000";	
	}
	else
	{
		die("erreur dans l'année dans le GET");
	}

	$numeroCandidatSurNeufDigit = $annee + $numCandidat;
	$nomFichier .= "_".$numeroCandidatSurNeufDigit."_".$numeroLettre;
	//-------------------------------------------------------------------------------------------------------------------------------------------------------------

	$pdf->Output('dossierExportPonctuelPdf344/DecIni'.$anneeGet.'/'.$nomFichier.'.pdf'); //MODIFIE PAR EA, CAR ON VA FAIRE UNE ECRITURE SUR LE FILESYSTEM
} // 20070402 fin exception postcom

// ######################### FIN EDITION PDF ##########################
// ####################################################################
// ####################################################################
// ####################################################################
// ####################################################################
// ####################################################################
// ####################################################################

$DATE = "";
$TITRE = "";
$TXT = "";
$CONSID = "";
$DECISION = "";
$SIGNATURE = "";
$siegeant = "";

$DECISION_SPECIALE = "";
$CONSID_DECISION_SPECIALE = "";
$DECISION = "";
$CONSID_NONREMB="";

$liste2 = array(); // 20070930

} //Fin de la boucle for //Par EA


?>
