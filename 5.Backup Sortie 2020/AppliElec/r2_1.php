<?php

$TITRE_HEAD = "Candidat - informations";
/*

historique : 

20140306 
- suppression des div.rangee 
- possibilité de masquer/afficher le contenu de chaque sous-partie (paragraphes <p> du <fieldset>) de formulaire
- gestion de la vérification du formulaire
- ajout dans les <label> de champs des class : ".requis" pour distinguer les champs à renseigner impérativement + ".controle" pour les champs auxquels se voit associé un contrôle du contenu (typiquement adresse email). Attention tous les contrôles posés sont bloquants (empêchent l'envoi du formulaire).

*/

if (session_status() == PHP_SESSION_NONE){
    session_start();
    
}

if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") or isset($_GET['aj'])) {
   	
   $DEBUG_alerte = "C'est de l'ajax !!!!!";
   	
   require("structure/requires.php");
   	
} else {
   
   	$DEBUG_alerte = "CE N'EST PAS de l'ajax !!!!!";
   	
   	require("structure/requires.php");
		
	require("structure/head.php");
 
}

// echo "<h1> DEBUG PRE FRAME</h1>";	
echo "<div id=\"frame\">\n";

/* echo "<pre>
GETTE2
";
print_r($_GET);
echo "</pre>";
echo "<h1 class=\"DEBUG\">url plus : ".$url_plus."</h1>";
*/

require("structure/navigation.php"); // affichage entête et navigation


$path_plus = "";

// exit;
	echo "<div id=\"entete\">\n";
	
	require("structure/entete.php"); // affichage entête (candidat ici)
	echo "<div class=\"br\">&nbsp;</div>\n";
	
	echo "</div>\n";
require_once("FONCTIONS/FONCTIONS_recuperation_date.php");
// require("structure/entete.php"); // affichage entête et navigation 
// bloc verifier rapport =======================================================	
require("fonctions/verif_rapport_termine.php");
require_once("fonctions/droit_utilisateur_page.php");
$hide_bouton_enregister = "";
if(verif_rapport_termine($conn, $DETAILS[$_GET['cand']]['id_compte'],"")== 1  and ($_SESSION['id_type_util']==4)){
echo 'ici';
	$hide_bouton_enregister = 'style="display:none;"';
}
//=============================================================================
$date_pass_seance = get_date_pass_seance($conn, $DETAILS[$_GET['cand']]['id_compte']);
$date_envoi_notif = get_date_envoi_notif($conn, $DETAILS[$_GET['cand']]['id_decision_initiale']);
$date_pass_seance = ($date_pass_seance!="")?affiche_date_fr($date_pass_seance):"";
$date_envoi_notif = ($date_envoi_notif!="")?affiche_date_fr($date_envoi_notif):"";


/**************** AUTOCOMPLETION (sur input text) du parti politique ********************************/
//important on pourrait include(fonctions\FONCTION_FORMULAIRES_ANNEXES.php qui comporte déja quelques unes de ces fonctions) mais celui require_once tellement de fichiers (déja appelé par r2_1.php , ex: require_once("inclusion/CONNEXION.php") que cela nuit à la visibilité) mais si cela ne pose pas de pb majeurs à priori
require("fonctions/autocompletion.php");


function get_date_pass_seance($conn, $id_compte){
	
	$date_pass_seance = "";
	$sql = "SELECT date_pass_com FROM dbo.decision WHERE id_type_decision = 3 and id_compte ='".$id_compte."'";
	$rs = sqlsrv_query($conn,$sql,array(), array("Scrollable"=>"buffered"));
	if( $rs === false) {
		die(print_r(sqlsrv_errors(), true));
	}
	$nb = sqlsrv_num_rows($rs);
	if($nb >0){
		while($row = sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC)) {
			$date_pass_seance = $row['date_pass_com'];
		}
	}
	return $date_pass_seance ;
}

require_once("FONCTIONS/FONCTIONS_liste.php");


// echo "<h1> DEBUG PRE MAIN (dans frame)</h1>";

echo "<div id=\"main\">";
echo "<h1 id=\"titre\"></h1>";
// echo "<h1>".$DEBUG_alerte."</h1>"; // renvoit alerte type "ce n'est pas de l'ajax"

?>


<?php

// #####################################################
// ################ début du formulaire ################

?>



<?php

// 1)  Déclaration de variables vides adaptées à un formulaire qui n'aurait pas encore été enregisté (convention pour nommer les variables : reprise du nom du champ correspondant dans la table en ajoutant simplement le signe dollar devant)


/*
$rs['id_liste'] = "";
$rs['id_parti'] = "";
$rs['id_candidat_associe'] = "";
$rs['ordre_cand'] = "";
$rs['chk_sortant'] = "";
$rs['id_civ_cand'] = "";
$rs['nom_cand'] = "";
$rs['prenom_cand'] = "";
$rs['particule_cand'] = "";
$rs['adresse1_cand'] = "";
$rs['adresse2_cand'] = "";
$rs['adresse3_cand'] = "";
$rs['adresse4_cand'] = "";
$rs['cp_cand'] = "";
$rs['ville_cand'] = "";
$rs['pays_cand'] = "";
$rs['mail_cand'] = "";
$rs['telephone1_cand'] = "";
$rs['telephone2_cand'] = "";
$rs['telecopie_cand'] = "";
$rs['adresse1_cand_post'] = "";
$rs['adresse2_cand_post'] = "";
$rs['adresse3_cand_post'] = "";
$rs['adresse4_cand_post'] = "";
$rs['cp_cand_post'] = "";
$rs['ville_cand_post'] = "";
$rs['pays_cand_post'] = "";
$rs['etiquette'] = "";
$rs['nuance'] = "";
$rs['nb_carnets'] = "";
$rs['date_declar_pref_cand'] = "";
$rs['nb_voix_1t'] = "";
$rs['nb_voix_2t'] = "";
$rs['pct_voix_1t'] = "";
$rs['pct_voix_2t'] = "";
$rs['num_avicand'] = "";
$rs['chk_elu'] = "";
$rs['chk_denonciation'] = "";
$rs['date_denonciation'] = "";
$rs['denonciateur'] = "";
$rs['ged'] = "";
$rs['numcand_mi'] = "";
*/
 
//echo "Expert : ".is_null($DETAILS[$_GET['cand']]['id_expert'])?'NULL':$DETAILS[$_GET['cand']]['id_expert'];
 // $checked_sortant = "";		
 // if ($DETAILS[$_GET['cand']]['chk_sortant']== 1) {
	// $checked_sortant = " checked=\"checked\"";
// }


//Variable permettant de savoir s'il s'agit de l'élection d'un binôme
$elec_binome = ($DETAILS[$_GET['cand']]['id_candidat_associe'] != null)?1:0;
 //print_r($DETAILS);exit;
  
//Checkbox pour les procurations
if($elec_binome){
	 $checked_procuration = "";		
	 if ($DETAILS[$_GET['cand']]['chk_procuration']== 1) {
		$checked_procuration = " checked=\"checked\"";
	}
	 $checked_procuration_associe = "";		
	 if ($DETAILS[$_GET['cand']]['chk_procuration_associe']== 1) {
		$checked_procuration_associe = " checked=\"checked\"";
	}
}

//Checkbox elu
 $checked_elu= "";		
 if ($DETAILS[$_GET['cand']]['chk_elu']== 1) {
	$checked_elu = " checked=\"checked\"";
}


//Checkbox elu sortant
 $checked_sortant= "";		
 if ($DETAILS[$_GET['cand']]['chk_sortant']== 1) {
	$checked_sortant = " checked=\"checked\"";
}
if($elec_binome){
	 $checked_sortant_associe= "";		
	 if ($DETAILS[$_GET['cand']]['chk_sortant_associe']== 1) {
		$checked_sortant_associe = " checked=\"checked\"";
	}
}

//date limite de dépôt dépassée
// print_r($DETAILS);exit;
$date_limite_depassee = false; 

// print_r($DETAILS);exit;
if (is_a($DETAILS[$_GET['cand']]['date_depot_cpte'], "DateTime") || is_a($DETAILS[$_GET['cand']]['date_limite_depot'], "DateTime") ){

	$date_depot = $DETAILS[$_GET['cand']]['date_depot_cpte'];
	$date_limite_depot = $DETAILS[$_GET['cand']]['date_limite_depot'];
	
// 	if ($date_limite_depot == "") {
// 	
// 		$date_limite_depot = calcul_date_limite_depot(affiche_date_fr($DETAILS[$_GET['cand']]['date_1t']), affiche_date_fr($DETAILS[$_GET['cand']]['date_2t']), $DETAILS[$_GET['cand']]['chk_existe_2t'], 0, 0, $DETAILS[$_GET['cand']]['chk_francais_etranger']);
// 	
// 	}
	
	// $date_depot_int = 
	// echo "<script>console.log('date dépôt : ".$date_depot." - date limite : ".$date_limite_depot.");</script>";
	
// 	echo "<pre>date dépôt
// 	";
// 	print_r($date_depot);
// 	echo "</pre>";
// 	
// 	echo "<pre>date limite de dépôt
// 	";
// 	print_r($date_limite_depot);
// 	echo "</pre>";
	
	
	$date_limite_depot_slash = affiche_date_fr($date_limite_depot);
	
	if ($date_depot > $date_limite_depot){
		$date_limite_depassee = true;
	}
} else {	

	$date_depot = new DateTime(substr($DETAILS[$_GET['cand']]['date_depot_cpte']['date'],0,10));
	
	$date_limite_depot = new DateTime(substr($DETAILS[$_GET['cand']]['date_limite_depot']['date'],0,10));
	
	$date_limite_depot_slash = 	affiche_date_fr($date_limite_depot);
	
	if ($date_depot > $date_limite_depot){
		$date_limite_depassee = true; 
	}
}

// echo "<h1>DEBUG date limite de dépôt : ".$date_limite_depot_slash."</h1>";
$bad_date = false;

if (is_a($DETAILS[0]['date_1t'], "DateTime")) {
	
	$date_1t_compare = $DETAILS[0]['date_1t'];
	
} else {

	$date_1t_compare = new DateTime(substr($DETAILS[0]['date_1t']['date'],0,10));
}



$date_actuelle = new DateTime(date('Y-m-d'));


$date_depot_compte_slash = affiche_date_fr($DETAILS[$_GET['cand']]['date_depot_cpte']);
	
	// echo "<h1>".print_r($date_1t_compare)."</h1>";
	
	// 	echo "<h1>".print_r($date_actuelle)."</h1>";
	


if ($date_depot < $date_1t_compare OR $date_depot > $date_actuelle) {
	
	$bad_date = true;
	
}

$date_declar_pref_cand_min = clone $date_1t_compare;

// ELM 20200729 Modification pour la date min de décla en préfecture : Passage à 6 mois avant le 1er tour
//$date_declar_pref_cand_min->sub(new DateInterval('P2M'));
$date_declar_pref_cand_min->sub(new DateInterval('P6M'));

if (is_a($DETAILS[$_GET['cand']]['date_declar_pref_cand'], "DateTime")) {
	
	$date_declar_pref_cand = $DETAILS[$_GET['cand']]['date_declar_pref_cand'];
	
} else {

	$date_declar_pref_cand = new DateTime(substr($DETAILS[$_GET['cand']]['date_declar_pref_cand']['date'],0,10));
}

$date_declar_pref_cand_slash = affiche_date_fr($DETAILS[$_GET['cand']]['date_declar_pref_cand']);



$bad_date_declar_pref_cand = 0;

// echo "<pre class=\"debug\">Date déclar min
// ";
// print_r($date_declar_pref_cand_min);
// echo "</pre>";
// 
// echo "<pre class=\"debug\">Date 1er tour 
// ";
// print_r($date_1t_compare);
// echo "</pre>";

if ($date_declar_pref_cand < $date_declar_pref_cand_min OR $date_declar_pref_cand > $date_1t_compare) {

	$bad_date_declar_pref_cand = 1;
	
}

$date_declar_pref_cand_min_slash = affiche_date_fr($date_declar_pref_cand_min);

//Partie ci-dessous commenté par EA le 02 01 2020 car non utilisé
/*
if (is_a($DETAILS[0]['date_denonciation'], "DateTime")) {
	
	$date_denonciation = $DETAILS[$_GET['cand']]['date_denonciation'];
	
} elseif (!empty($DETAILS[0]['date_denonciation'])) {

	$date_denonciation = new DateTime(substr($DETAILS[0]['date_1t']['date'],0,10));
}*/

$date_denonciation_min = clone $date_1t_compare;
$date_denonciation_min->sub(new DateInterval('P1Y'));

$date_denonciation_max = clone $date_1t_compare;
$date_denonciation_max->add(new DateInterval('P1Y'));

$bad_date_denonciation = 0;


if ($date_denonciation_min < $date_1t_compare OR $date_denonciation_min > $date_1t_compare) {

	$bad_date_denonciation = 1;
	
}

$date_denonciation_min_slash = affiche_date_fr($date_denonciation_min);
$date_denonciation_max_slash = affiche_date_fr($date_denonciation_max);

?>



 <form id="formulaire" class="formulaire" name="r2_1" action="r2_1_ajax.php" method="post">
    <div class="compte">
       <fieldset>
            <legend>Compte</legend>            
            <input type="hidden" name="annee" value="<?php echo $DETAILS[0]['annee']; ?>" />
            <input type="hidden" name="id_scrutin" value="<?php echo $DETAILS[0]['id_scrutin']; ?>" />
            <input type="hidden" name="id_candidat" value="<?php echo $DETAILS[$_GET['cand']]['id_candidat']; ?>" />
            <input type="hidden" name="elec_binome" value="<?php echo $elec_binome; ?>" />
			<?php if($elec_binome){?><input type="hidden" name="id_candidat_associe" value="<?php echo $DETAILS[$_GET['cand']]['id_candidat_associe']; ?>" /><?php } ?>
            <input type="hidden" name="id_mandataire" value="<?php echo adapte_fkey_null($DETAILS[$_GET['cand']]['id_mandataire']); ?>" />
            <input type="hidden" name="id_suppleant" value="<?php echo adapte_fkey_null($DETAILS[$_GET['cand']]['id_suppleant']); ?>" />
			<?php if($elec_binome){?><input type="hidden" name="id_suppleant_associe" value="<?php echo adapte_fkey_null($DETAILS[$_GET['cand']]['id_suppleant_associe']); ?>" /><?php } ?>
            <input type="hidden" name="id_expert" value="<?php echo adapte_fkey_null($DETAILS[$_GET['cand']]['id_expert']); ?>" />
			
            <p><label for="date_depot_cpte"> Date de dépôt du compte : </label> <input type="text" id="date_depot_cpte" name="date_depot_cpte" size='10' maxlength='10' pattern="<?php echo $date_fr_slashes; ?>" value="<?php 
			
			echo $date_depot_compte_slash;
			
			?>" />
			
			<?php // echo $date_fr_slashes_span_alert."\n"; ?>
			
			<span class="rouge" title="Format de date incorrect : jj/mm/aaaa demandé. " id="bad_date">
			
			<?php 
			
				if ($bad_date and $date_depot_compte_slash != "") {
				
					echo " ANOMALIE : date saisie incorrecte. Elle devrait être postérieure au premier tour et antérieure à la date de ce jour.";
					
				} elseif ($date_depot_compte_slash == "") {
				
					echo " Absence de dépôt.";
				}
			?>
			</span>
			</p>
			
			<p><label for="date_declar_pref_cand"> Date de déclaration en préfecture : </label> <input type="text" placeholder="<?php echo $date_fr_slashes_placeholder?>" id="date_declar_pref_cand" class="date_declar_pref_cand" name="date_declar_pref_cand" size="10" maxlength="10" pattern="<?php echo $date_fr_slashes; ?>" value="<?php 
			
			echo affiche_date_fr($DETAILS[$_GET['cand']]['date_declar_pref_cand']);
			
			?>" />
			<?php 
			
			if ($bad_date_declar_pref_cand and $date_declar_pref_cand_min_slash != "") {
				
					$anomalie = " ANOMALIE : date saisie incorrecte. Elle devrait être située en le ".$date_declar_pref_cand_min_slash." et la date du premier tour.";
					
					// $anomalie =  "Format de date incorrect : jj/mm/aaaa demandé. ";
					
			} else {
				
				$anomalie =  " Format de date incorrect : jj/mm/aaaa demandé. ";
				
				
			} 
			
			// echo $date_fr_slashes_span_alert; ?>
			
			<span class="rouge" title="<?php echo $anomalie; ?>" id="date_declar_pref_cand_span"></span>
			</p>
			
			<p><label for="nb_carnets"> Nombre de carnets <span style="white-space:nowrap">reçus-dons</span> délivrés :</label> <input type="text" id="nb_carnets" name="nb_carnets" disabled="disabled" readonly="readonly" value="<?php echo $DETAILS[$_GET['cand']]['nb_carnets']; ?>" /></p>   
			
			<p><label for="date_etiquetage_cpte"> Étiquetage du compte : </label> <input type="text" id="date_etiquetage_cpte" name="date_etiquetage_cpte" size='10' maxlength='10' disabled="disabled" readonly="readonly" value="<?php 
			echo affiche_date_fr($DETAILS[$_GET['cand']]['date_etiquetage_cpte']);			
			?>" /></p>
			
			<p><label for="date_ouverture_cpte"> Réception par le rapp. : </label> <input type="text" id="date_ouverture_cpte" name="date_ouverture_cpte" disabled="disabled" readonly="readonly" size='10' maxlength='10' value="<?php 
			echo affiche_date_fr($DETAILS[$_GET['cand']]['date_ouverture_cpte']);			
			?>" /></p>			
			
			<p><label for="date_retour_cpte"> Retour du compte : </label> <input type="text" id="date_retour_cpte" name="date_retour_cpte" disabled="disabled" readonly="readonly" size='10' maxlength='10' value="<?php 
			echo affiche_date_fr($DETAILS[$_GET['cand']]['date_retour_cpte']);			
			?>" /></p>			

			<p><label for="date_pass_seance"> Passage en commission : </label> <input type="text" id="date_pass_seance" name="date_pass_seance" disabled="disabled" readonly="readonly" size='10' maxlength='10' value="<?php 
			echo $date_pass_seance;
			// echo affiche_date_fr($DETAILS[$_GET['cand']]['date_pass_seance']);			
			?>" /></p>

			<p><label for="date_envoi_notif"> Notification : </label> <input type="text" id="date_envoi_notif" name="date_envoi_notif" disabled="disabled" readonly="readonly" size='10' maxlength='10' value="<?php 
			echo $date_envoi_notif;
			// echo affiche_date_fr($DETAILS[$_GET['cand']]['date_envoi_notif']);			
			?>" /></p>

			<!-- Par EA le 13 12 2019 pour ano 560 : Gestion de la date de debut de financement -->
			<?php
				$disableDateDebutFinancement = "";

				if ($DETAILS[$_GET['cand']]['abrev_type_elec'] == 'G')
				{
					$disableDateDebutFinancement = "disabled='disabled'";
				}
			?>
			<p>
				<label for="date_debut_financement"> Date debut financement : </label>
				<input type="text" placeholder="jj/mm/aaaa" id="date_debut_financement" name="date_debut_financement" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size='10' maxlength='10' <?php echo $disableDateDebutFinancement ?> value="<?php echo affiche_date_fr($DETAILS[$_GET['cand']]['date_debut_financement']); ?>"><span style="color:red" id="msgRgDateDbtFinancement"></span>
			</p>

        </fieldset>
   </div> 
	<div class="etatcivil<?php if($elec_binome){echo " binome1";}?>">
		<fieldset>
			<legend>Identité <?php if($elec_binome) echo " binôme 1";?></legend>					
			<p><label for="id_civ_cand"> Civilité :</label>
			<?php 
		//	print_r($DETAILS);exit;
			echo combo_dynamique('civilite','id_civ_cand','id_civilite','libelle_civ',$DETAILS[$_GET['cand']]['id_civ_cand'],'','','','required','');
			?></p>
			
			<p><label for="nom_cand">Nom :</label> <input required="required" class="pattern_texte_2_60" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÊÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}"  type="text" id="nom_cand" name="nom_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['nom_cand']; ?>" />
			<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>

			<p><label for="particule_cand">Particule :</label> <input type="text"  class="pattern_texte_1_10" id="particule_cand" name="particule_cand" size='10' maxlength='10' pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{1,10}" value="<?php echo $DETAILS[$_GET['cand']]['particule_cand']; ?>" /> <span title="1 caractère minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>

			<p><label for="prenom_cand">Prénom :</label> <input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏ&'’ ]{1,60}" id="prenom_cand" name="prenom_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['prenom_cand']; ?>" />
			<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
			</p>
			<?php if ($elec_binome){ ?>
			<p  class="procuration">	
				<label for="chk_procuration"></label>
				<input type="checkbox" id="chk_procuration" name="chk_procuration"  <?php echo $checked_procuration; ?>/>
			</p>
			<?php } ?>
		</fieldset>
	</div>
<?php if($elec_binome){?>
	<div  class="etatcivil binome2" >
		<fieldset>
			<legend>Identité binôme 2</legend>						
				<p><label for="id_civ_cand"> Civilité :</label>
				<?php 
				echo combo_dynamique('civilite','id_civ_cand_associe','id_civilite','libelle_civ',$DETAILS[$_GET['cand']]['id_civ_cand_associe'],'','','','required','');
				?></p>				
				<p><label for="nom_cand_associe">Nom :</label> <input required="required" class="pattern_texte_2_60" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" type="text" id="nom_cand_associe" name="nom_cand_associe" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['nom_cand_associe']; ?>" />
				 <span title="2 lettres minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
				 </p>
				 
				<p><label for="particule_cand_associe">Particule :</label> <input type="text" class="pattern_texte_1_10" id="particule_cand_associe" name="particule_cand_associe" size='10' maxlength='10' pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{1,10}" value="<?php echo $DETAILS[$_GET['cand']]['particule_cand_associe']; ?>" /><span title="Lettres, tirets, apostrophes et espaces acceptés."></span></p>
				
				<p><label for="prenom_cand_associe">Prénom :</label> <input type="text" class="pattern_texte_2_60" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" id="prenom_cand_associe" name="prenom_cand_associe" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['prenom_cand_associe']; ?>" />
				<span title="2 lettres minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
				</p>
				<p class="procuration_associe" >	
					<label for="chk_procuration_associe"></label>
					<input type="checkbox" id="chk_procuration_associe" name="chk_procuration_associe" <?php echo $checked_procuration_associe; ?>/>
				</p>
		</fieldset>
	</div>
	<div class="adresse binome1">
		<fieldset>
			<legend>Coordonnées binôme 1</legend>
			<p><label for="adresse1_cand"> Adresse :</label> <input type="text" id="adresse1_cand" name="adresse1_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse1_cand']; ?>" /></p>

			<p><label for="adresse2_cand"> Adresse (suite) :</label> <input type="text" id="adresse2_cand" name="adresse2_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse2_cand']; ?>" /></p>

			<p><label for="adresse3_cand"> Adresse (suite 2) :</label> <input type="text" id="adresse3_cand" name="adresse3_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse3_cand']; ?>" /></p>

			<p><label for="adresse4_cand"> Adresse (suite 3) :</label> <input type="text" id="adresse4_cand" name="adresse4_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse4_cand']; ?>" /></p>

			<p><label for="cp_cand">Code postal :</label> <input type="text" required="required" id="cp_cand" name="cp_cand" size='20' maxlength='20' value="<?php echo $DETAILS[$_GET['cand']]['cp_cand']; ?>" />
			<span title="5 chiffres pour la France, code libre pour les autres pays. Choisir un pays pour débloquer cette zone."></span>
			</p>

			<p><label for="ville_cand"> Ville :</label> <input required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,60}" type="text" class="pattern_texte_2_80"  id="ville_cand" name="ville_cand" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['ville_cand']; ?>" />
			<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>
			
			<p><label for="pays_cand">Pays :</label><input type="text" class="pattern_alphanum_2_80" id="pays_cand" name="pays_cand" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,80}" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['pays_cand']; ?>" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>

			<p><label class="controle" for="mail_cand">Courriel :</label>
			<input type="email" class="pattern_mail" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_cand" name="mail_cand" size='50' maxlength='50' value="<?php echo $DETAILS[$_GET['cand']]['mail_cand']; ?>" /><span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>

			<p><label for="telephone1_cand">Téléphone 1 :</label>
			<input type="text" class="pattern_telephone" id="telephone1_cand" name="telephone1_cand" pattern="" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telephone1_cand']; ?>" />
			<span title="Les chiffres, signe +, le point et les espaces sont acceptés."></span></p>

			<p><label for="telephone2_cand">Téléphone 2 :</label>
			<input type="text" class="pattern_telephone" pattern="" placeholder="debut" id="telephone2_cand" name="telephone2_cand" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telephone2_cand']; ?>" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

			<p><label for="telecopie_cand">Fax :</label>
			<input type="text" class="pattern_telephone" pattern="" placeholder="debut" id="telecopie_cand" name="telecopie_cand" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telecopie_cand']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>
		</fieldset>
	</div>
	<div class="adresse binome2">
		<fieldset>
			<legend>Coordonnées binôme 2</legend>
			<p><label for="adresse1_cand_associe"> Adresse :</label> <input type="text" id="adresse1_cand_associe" name="adresse1_cand_associe" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse1_cand_associe']; ?>" /></p>

			<p><label for="adresse2_cand_associe"> Adresse (suite) :</label> <input type="text" id="adresse2_cand_associe" name="adresse2_cand_associe" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse2_cand_associe']; ?>" /></p>

			<p><label for="adresse3_cand_associe"> Adresse (suite 2) :</label> <input type="text" id="adresse3_cand_associe" name="adresse3_cand_associe" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse3_cand_associe']; ?>" /></p>

			<p><label for="adresse4_cand_associe"> Adresse (suite 3) :</label> <input type="text" id="adresse4_cand_associe" name="adresse4_cand_associe" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse4_cand_associe']; ?>" /></p>

			<p><label for="cp_cand_associe">Code postal :</label> <input type="text" required="required" id="cp_cand_associe" name="cp_cand_associe" size='20' maxlength='20' value="<?php echo $DETAILS[$_GET['cand']]['cp_cand_associe']; ?>" />
			<span title="5 chiffres pour la France, code libre pour les autres pays. Choisir un pays pour débloquer cette zone."></span>
			</p>

			<p><label for="ville_cand_associe"> Ville :</label> <input required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,60}" type="text" id="ville_cand_associe" name="ville_cand_associe" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['ville_cand_associe']; ?>" />
			<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>
			
			<p><label for="pays_cand_associe">Pays :</label><input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,80}" id="pays_cand_associe" name="pays_cand_associe" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['pays_cand_associe']; ?>" /><span title="2 caractères minimum. Lettres,tirets, apostrophes et espaces acceptés."></span></p>

			<p><label class="controle" for="mail_cand_associe">Courriel :</label>
			<input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_cand_associe" name="mail_cand_associe" size='50' maxlength='50' value="<?php echo $DETAILS[$_GET['cand']]['mail_cand_associe']; ?>" /><span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>

			<p><label for="telephone1_cand_associe">Téléphone 1 :</label>
			<input type="text"  id="telephone1_cand_associe" name="telephone1_cand_associe" class="pattern_telephone" pattern="" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telephone1_cand_associe']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

			<p><label for="telephone2_cand_associe">Téléphone 2 :</label>
			<input type="text" class="pattern_telephone" pattern="" placeholder="debut" id="telephone2_cand_associe" name="telephone2_cand_associe" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telephone2_cand_associe']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

			<p><label for="telecopie_cand_associe">Fax :</label>
			<input type="text" class="pattern_telephone" pattern="" placeholder="debut" id="telecopie_cand_associe" name="telecopie_cand_associe" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telecopie_cand_associe']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>
		</fieldset>
	</div>
<?php }?>

<?php if(!$elec_binome){?>    
<div class="adresse ">
    <fieldset>
		<legend>Coordonnées</legend>
		<p><label for="adresse1_cand"> Adresse :</label> <input type="text" id="adresse1_cand" name="adresse1_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse1_cand']; ?>" /></p>

		<p><label for="adresse2_cand"> Adresse (suite) :</label> <input type="text" id="adresse2_cand" name="adresse2_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse2_cand']; ?>" /></p>

		<p><label for="adresse3_cand"> Adresse (suite 2) :</label> <input type="text" id="adresse3_cand" name="adresse3_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse3_cand']; ?>" /></p>

		<p><label for="adresse4_cand"> Adresse (suite 3) :</label> <input type="text" id="adresse4_cand" name="adresse4_cand" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse4_cand']; ?>" /></p>

		<p><label for="cp_cand">Code postal :</label> <input type="text" required="required" id="cp_cand" name="cp_cand" size='20' maxlength='20' value="<?php echo $DETAILS[$_GET['cand']]['cp_cand']; ?>" />
		<span title="5 chiffres pour la France, code libre pour les autres pays"></span>
		</p>

		<p><label for="ville_cand"> Ville :</label> <input required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,60}" type="text" id="ville_cand" name="ville_cand" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['ville_cand']; ?>" />
		<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés"></span>
		</p>
		
		<p><label for="pays_cand">Pays :</label><input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,80}" id="pays_cand" name="pays_cand" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['pays_cand']; ?>" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>

		<p><label class="controle" for="mail_cand">Courriel :</label>
		<input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_cand" name="mail_cand" size='50' maxlength='50' value="<?php echo $DETAILS[$_GET['cand']]['mail_cand']; ?>" /><span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>

		<p><label for="telephone1_cand">Téléphone 1 :</label>
		<input type="text"  id="telephone1_cand" name="telephone1_cand" pattern="" placeholder="Numéro de téléphone" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telephone1_cand']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

		<p><label for="telephone2_cand">Téléphone 2 :</label>
		<input type="text" pattern="" placeholder="Numéro de téléphone" id="telephone2_cand" name="telephone2_cand" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telephone2_cand']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

		<p><label for="telecopie_cand">Fax :</label>
		<input type="text" pattern="" placeholder="Numéro de fax" id="telecopie_cand" name="telecopie_cand" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telecopie_cand']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>
		
<!-- 20170917 il n'y a pas lieu de remplir ces champs...

		<p><label for="adresse1_cand_post">Adresse :</label>
		<input type="text" id="adresse1_cand_post" name="adresse1_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse1_cand_post']; ?>" /></p>
		
		<p><label for="adresse2_cand_post">Adresse (suite) :</label>
		<input type="text" id="adresse2_cand_post" name="adresse2_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse2_cand_post']; ?>" /></p>
		
		<p><label for="adresse3_cand_post">Adresse (suite 2) :</label> <input type="text" id="adresse3_cand_post" name="adresse3_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse3_cand_post']; ?>" /></p>
		
		<p><label for="adresse4_cand_post">Adresse (suite 3) :</label> <input type="text" id="adresse4_cand_post" name="adresse4_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse4_cand_post']; ?>" /></p>
		
		<p><label for="cp_cand_post">Code postal :</label> <input type="text" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_cand_post" name="cp_cand_post" size='12' maxlength='12' value="<?php echo $DETAILS[$_GET['cand']]['cp_cand_post']; ?>" />
		<span title="5 chiffres pour la France, code libre pour les autres pays"></span>
		</p>

		<p><label for="ville_cand_post">Ville :</label> <input type="text" id="ville_cand_post" name="ville_cand_post" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['ville_cand_post']; ?>" /></p>

		<p><label for="pays_cand_post">Pays :</label> <input type="text" id="pays_cand_post" name="pays_cand_post" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['pays_cand_post']; ?>" /></p>
 -->
	</fieldset>
</div>
<?php } else {?>


<div class="adresse_commune">
	<fieldset>
		<legend>Adresse Commune</legend>
		<p>
			<label for="adresse_b1">Adresse du binôme 1</label>
			<input type="radio" name="adresses_pre_definies" id="adresse_b1"> 
			<label for="adresse_b2">Adresse du binôme 2</label>
			<input type="radio" name="adresses_pre_definies" id="adresse_b2" >
			<label for="adresse_autre">Autre</label>
			<input type="radio" name="adresses_pre_definies" id="adresse_autre">
		</p>
		
		<p><label for="destinataire_cand_post">Destinataire :</label> <input pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}"  type="text" id="destinataire_cand_post" name="destinataire_cand_post" size='40' maxlength='180' value="<?php echo $DETAILS[$_GET['cand']]['destinataire_cand_post']; ?>" />
		<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
		</p>
		<p><label for="adresse1_cand_post">Adresse :</label>
		<input type="text" id="adresse1_cand_post" name="adresse1_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse1_cand_post']; ?>" /></p>
		
		<p><label for="adresse2_cand_post">Adresse (suite) :</label>
		<input type="text" id="adresse2_cand_post" name="adresse2_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse2_cand_post']; ?>" /></p>
		
		<p><label for="adresse3_cand_post">Adresse (suite 2) :</label> <input type="text" id="adresse3_cand_post" name="adresse3_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse3_cand_post']; ?>" /></p>
		
		<p><label for="adresse4_cand_post">Adresse (suite 3) :</label> <input type="text" id="adresse4_cand_post" name="adresse4_cand_post" size='40' maxlength='60' value="<?php echo $DETAILS[$_GET['cand']]['adresse4_cand_post']; ?>" /></p>
		
		<p><label for="cp_cand_post">Code postal :</label> <input type="text" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_cand_post" name="cp_cand_post" size='20' maxlength='20' value="<?php echo $DETAILS[$_GET['cand']]['cp_cand_post']; ?>" />
		<span title="5 chiffres pour la France, code libre pour les autres pays"></span>
		</p>

		<p><label for="ville_cand_post">Ville :</label> <input type="text" id="ville_cand_post" name="ville_cand_post" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,60}" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['ville_cand_post']; ?>" />
		<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>

		<p><label for="pays_cand_post">Pays :</label> <input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,80}" id="pays_cand_post" name="pays_cand_post" size='40' maxlength='80' value="<?php echo $DETAILS[$_GET['cand']]['pays_cand_post']; ?>" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>
		
		<p><label class="controle" for="mail_cand_post">Courriel :</label>
		<input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_cand_post" name="mail_cand_post" size='50' maxlength='50' value="<?php echo $DETAILS[$_GET['cand']]['mail_cand_post']; ?>" /><span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>

		<p><label for="telephone_cand_post">Téléphone :</label>
		<input type="text"  id="telephone_cand_post" name="telephone_cand_post" pattern="" size='25' maxlength='25' placeholder="numéro de téléphone" value="<?php echo $DETAILS[$_GET['cand']]['telephone_cand_post']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

		<p><label for="telecopie_cand_post">Fax :</label>
		<input type="text" pattern="" placeholder="numéro de fax" id="telecopie_cand_post" name="telecopie_cand_post" size='25' maxlength='25' value="<?php echo $DETAILS[$_GET['cand']]['telecopie_cand_post']; ?>" /><span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>
	</fieldset>
</div>        
<?php } ?>       


<!-- -------------------------------- DEBUT AFFICHAGE DES LISTES -------------------------------- -->
<?php if ($DETAILS[$_GET['cand']]['id_liste'] != null) { ?>

<script>
	var _showListe = true;

	function modifieAffichageListe() {
		_showListe = !_showListe;
		if (_showListe) {
			$("#liste_tour_1").show();
			$("#download_liste_1_xlsx").show();
			$("#liste_tour_2").show();
			$("#download_liste_2_xlsx").show();
		} else {
			$("#liste_tour_1").hide();
			$("#download_liste_1_xlsx").hide();
			$("#liste_tour_2").hide();
			$("#download_liste_2_xlsx").hide();
		}
	}
</script>

<fieldset id="scrutin_liste">
	<legend onclick="modifieAffichageListe()">Liste</legend>
	
	<div id="liste_tour_1"></div>
	<div><button id="download_liste_1_xlsx" onclick="return false">Télécharger liste 1er tour (xlsx)</button></div>
    <!-- Le onclick="return false" permet d'éviter de faire un submit du formulaire -->

<?php       if ($DETAILS[$_GET['cand']]['id_liste_2t'] != null) { ?>
	<div id="liste_tour_2"></div>
	<div><button id="download_liste_2_xlsx" onclick="return false">Télécharger liste 2em tour (xlsx)</button></div>
    <!-- Le onclick="return false" permet d'éviter de faire un submit du formulaire -->

<?php       } ?>

</fieldset>	 

<?php } ?>
<!-- -------------------------------- FIN AFFICHAGE DES LISTES -------------------------------- -->


<?php
// 20180830 BL récupération de toutes les nuances déjà enregistrées pour l'élection en rapport avec la circonscription du candidat en cours de traitement >>> affichage d'un combo permettant de choisir parmi elles. Évolution 887 http://ccfp.cnccfp.local/sites/si/nouvAppliElec/_layouts/15/FormServer.aspx?XmlLocation=/sites/si/nouvAppliElec/Fiches%20anomalie/fiche_ano_887_de_DH.xml

$nuances_existent = 0;

function sql_recup_combo_nuances($conn,$id_election){ // 20180830 BL

	$combo_nuance = "Aucune nuance n'a été enregistrée pour cette élection (id : ".$id_election.").";
	
	$sql = "SELECT distinct(nuance) 
FROM dbo.candidat AS C 
LEFT JOIN dbo.scrutin AS S ON 
(C.id_scrutin = S.id_scrutin)

WHERE C.nuance IS NOT NULL 
AND C.nuance <> '' 
AND S.id_election = '".$id_election."' 
ORDER BY nuance";

	$req = sqlsrv_query($conn, $sql, array(), array("Scrollable" => "buffered"));
	
	if (!$req) {
		// ne fait rien
	} else {
	
		$nb = sqlsrv_num_rows($req);
		if ($nb > 0) {
		
			$i = 0;
			$combo_nuance ="<span> (saisie libre ou choisir dans le menu suivant)</span><label for=\"nuances_enregistrees\">Nuances déjà enregistrées :</label>\n";
			
			$combo_nuance.="<select id=\"nuances_enregistrees\">\n";
			
			while ($res = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC)) {
		
			// if ($nb > 1) {
				if ($i === 0) {
					
					$combo_nuance.="<option>Choisir une nuance déjà enregistrée...</option>\n";
					
					$i = 1;
					
				} 
				
				$combo_nuance.="<option value=\"".$res['nuance']."\">".$res['nuance']."</option>\n";
		
			}
			
			$combo_nuance.="</select>\n";
			
			$combo_nuance.="<script>";
			$combo_nuance.='$(document).ready(function() {';
			$combo_nuance.='$("#nuances_enregistrees").change(function() {';
			$combo_nuance.='$("#nuance").val($(this).val());';
			$combo_nuance.='});';
			$combo_nuance.='});';
			$combo_nuance.="</script>";
		
		}

	
	}
	
	echo $combo_nuance;


}

// echo "<h2>".$DETAILS[$_GET['cand']]['nom_parti']."</h2>";

?>
	 
	 
	 
<div class="parti">
    <fieldset>
        <legend>Parti</legend>

		<p><label for="etiquette"> Sigle :</label> <input type="text" id="etiquette" name="etiquette" size='30' maxlength='50' value="<?php echo $DETAILS[$_GET['cand']]['etiquette']; ?>" /></p>

		<p><label for="nuance"> Nuance :</label> <input type="text" id="nuance" name="nuance" size='10' maxlength='50' value="<?php echo $DETAILS[$_GET['cand']]['nuance']; ?>" />
		
		<?php 
		
		// 20180830 BL
		
		sql_recup_combo_nuances($conn,$DETAILS[0]['id_election']);
		?>
		
		</p>
		
		

		<p><label for="id_parti"> Parti politique :</label> 
			
			
			<input type="text" style="text-transform:uppercase" id="parti_affich" maxlength='150' size="80" <?php /* on considère qu'un id_parti=0 en bdd, equivaut à pas de parti (même si sur dev uniquement, j'ai trouvé un parti existant avec id_parti à 0) donc utilisation d'empty() au lieu de isset() */if (!empty($DETAILS[$_GET['cand']]['id_parti']) && false) { ?>  disabled="disabled" <?php } ?> value="<?php echo $DETAILS[$_GET['cand']]['nom_parti']; ?>" />
			
			<input type="text" id="id_parti" name="id_parti" style="display:none" value="<?php echo $DETAILS[$_GET['cand']]['id_parti']; ?>" />
			
			<div style="margin: 5px 0 0 272px;"> Si vous ne trouvez pas le parti parmi les suggestions automatiques, vous pouvez le saisir directement, on vous proposera de le créer.</div>
		
		</p>

    </fieldset>
</div>
    
<div class="suffrages">
    <fieldset>
        <legend>Suffrages obtenus</legend> 

		<p><label for="nb_voix_1t"> Nombre de voix au 1er tour :</label> <input type="text" id="nb_voix_1t" required name="nb_voix_1t" pattern="<?php echo $nombre_entier_12; ?>" value="<?php echo $DETAILS[$_GET['cand']]['nb_voix_1t']; ?>" /><?php echo $nombre_entier_12_span_alert; ?></p>

		<p><label for="nb_voix_2t"> Nombre de voix au 2nd tour :</label> <input type="text" id="nb_voix_2t" required name="nb_voix_2t" pattern="<?php echo $nombre_entier_12; ?>" value="<?php echo $DETAILS[$_GET['cand']]['nb_voix_2t']; ?>" /><?php echo $nombre_entier_12_span_alert; ?></p>
  
		<p><label for="pct_voix_1t"> Pourcentage de voix au 1er tour :</label> <input type="text" id="pct_voix_1t" required name="pct_voix_1t" pattern="<?php echo $nombre_decimal_avec_point; ?>" value="<?php echo $DETAILS[$_GET['cand']]['pct_voix_1t']; ?>" /><?php echo $nombre_decimal_avec_point_span_alert; ?></p>
   
		<p><label for="pct_voix_2t"> Pourcentage de voix au 2nd tour :</label> <input type="text" id="pct_voix_2t" required name="pct_voix_2t" pattern="<?php echo $nombre_decimal_avec_point; ?>" value="<?php echo $DETAILS[$_GET['cand']]['pct_voix_2t']; ?>" /><?php echo $nombre_decimal_avec_point_span_alert; ?></p>
	
		<p><label for="chk_elu"> Élu :</label> <input type="checkbox" id="chk_elu" name="chk_elu" <?php echo $checked_elu;?> /></p>    <!-- COMBO !!!!!! -->
		
		<p>
			<label for="chk_sortant"><?php
				if($elec_binome){ 
					echo "Binôme 1 | Élu sortant :";
				}else{
					echo "Élu sortant :";					
				}
				?>
			</label>
			<input type="checkbox" id="chk_sortant" name="chk_sortant"  <?php echo $checked_sortant; ?>/>	
			<?php if($elec_binome){?> 
				<label for="chk_sortant_associe">Binôme 2 | Élu sortant :</label>
				<input type="checkbox" id="chk_sortant_associe" name="chk_sortant_associe"  <?php echo $checked_sortant_associe; ?>/>
			<?php } ?>
		</p>

		
		<!-- <p><label for="chk_sortant"> Elu sortant :</label> 
		
		<?php
		//echo combo_dynamique('oui_non','chk_sortant','id_oui_non','libelle_oui_non',$DETAILS[$_GET['cand']]['chk_sortant']);
		
		?>
		</p>  -->     
    </fieldset>
</div>
<div class="denonciation" style="cursor: text;">
    <fieldset>
        <legend style="border: none; background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);"><span class="fleche">▼&nbsp;</span>Signalement</legend>
		<p><label for="chk_denonciation"> Dénonciation :</label>
<?php
		echo combo_dynamique('oui_non','chk_denonciation','id_oui_non','libelle_oui_non',$DETAILS[$_GET['cand']]['chk_denonciation']);
		
?>
		</p>
    </fieldset>
</div>
<p><input type="submit" class="enregistrer" value="Enregistrer" <?php echo $hide_bouton_enregister; ?>></p>

</form>

<!--
<div>
	<fieldset>
        <legend>Divers</legend>-->

    	<!--<p><label for="id_candidat_associe"> Candidat associé ??? :</label> <input type="text" id="id_candidat_associe" name="id_candidat_associe" value="<?php echo $DETAILS[$_GET['cand']]['id_candidat_associe']; ?>" /></p>   <!-- ??????????? quelles infos mettre / ou le placer ? ??????????????? -->
		<!--
		<p><label for="ordre_cand"> Candidat principal ??? :</label> 
		
		<?php
		
// $checked = "";		
// if ($DETAILS[$_GET['cand']]['ordre_cand'] == 1) {
//	$checked = " checked=\"checked\"";
// }
		//echo combo_dynamique('oui_non','ordre_cand','id_oui_non','libelle_oui_non',$DETAILS[$_GET['cand']]['ordre_cand']);
		?>
		-->
<!-- </p> -->
		<!-- <input type="checkbox" id="ordre_cand" name="ordre_cand" value="1"  /> -->
		<!-- ??????????? quelles infos mettre / ou le placer ? ??????????????? -->
        
<!-- 

        <p><label for="numcand_mi"> Identifiant du Minstère de l'Intérieur :</label> <input type="text" id="numcand_mi" name="numcand_mi" size='30' maxlength='30' value="" /></p> 
 -->
 <!-- ??????????? quelles infos mettre / ou le placer ? ??????????????? -->

        <!--<p><label for="nb_carnets"> Nombre de carnets reçu-dons :</label> <input type="text" id="nb_carnets" name="nb_carnets" size='30' maxlength='30' value=" echo $DETAILS[$_GET['cand']]['nb_carnets']; ?>" /></p> <!-- ??????????? quelles infos mettre / ou le placer ? hidden ??? ??????????????? -->
<!--
        <p><label for="num_avicand"> Numéro d'avis-cand :</label> <input type="text" id="num_avicand" name="num_avicand" size='30' maxlength='30' disabled="disabled" readonly="readonly" value="<?php //echo $DETAILS[$_GET['cand']]['num_avicand']; ?>" /></p> <!-- ??????????? quelles infos mettre / ou le placer ? hidden  ??????????????? -->
<!--  </fieldset>
</div>-->

<?php 

$sql_signal = "
	 	SELECT *
		FROM [ELEC].[dbo].[signalement] AS SIGNAL
		INNER JOIN [ELEC].[dbo].[candidat_signalement] AS MAPPING
		ON SIGNAL.id_signal = MAPPING.id_signalement
		WHERE MAPPING.id_candidat = '".$DETAILS[$_GET['cand']]['id_compte']."'";

$req_signal = sqlsrv_query($conn, $sql_signal);

if ($req_signal === false)
{
    header("Content-Type: text/json");
    echo '{"success":false,"message":"Erreur de requête sur les signalements du candidat"}';
    exit;
}

$SIGNALEMENTS = array();

if(sqlsrv_has_rows($req_signal))
{
    while($unSignalement = sqlsrv_fetch_array($req_signal))
    {
        array_push($SIGNALEMENTS, $unSignalement);
    }
}

?>

<div id="signalements">
<h1>Signalement(s) du candidat :</h1>

<?php

if(count($SIGNALEMENTS) == 0)
{
    echo 'Aucun signalement pour ce candidat<br>';
}

for ($i=0;$i<count($SIGNALEMENTS);$i++)
{
    $numeroSignalement = $SIGNALEMENTS[$i]["id_signal"];
    $key = "doc_".$numeroSignalement;
    
    echo'
   <h2 class="titre_signalement"><u>Signalement n°'.$numeroSignalement.'</u></h2>
   <fieldset>
     <form class="formSignalement" name="signalement_'.$numeroSignalement.'" action="r2_11_ajax.php" method="post">
        <input type="hidden" id="id_signal_'.$numeroSignalement.'" name="id_signal_'.$numeroSignalement.'" value="'.$numeroSignalement.'">
        <p class="paragrapheReduit">
			<label for="chk_denonciation_abandon_'.$numeroSignalement.'"> Abandonnée ? :</label> 
			<input type="checkbox" id="chk_denonciation_abandon_'.$numeroSignalement.'" name="chk_denonciation_abandon_'.$numeroSignalement.'"'; if ($SIGNALEMENTS[$i]["chk_denonciation_abandon"]) { echo " checked='checked'";} echo'/>
		</p>
		
		<p>
            <label for="date_denonciation_'.$numeroSignalement.'"> Date de la dénonciation :</label>
            <input type="text" id="date_denonciation_'.$i.'" pattern="'.$date_fr_slashes.'" name="date_denonciation_'.$numeroSignalement.'" size="10" maxlength="10" value="';echo affiche_date_fr($SIGNALEMENTS[$i]['date_denonciation']);echo'" />';

    		if ($bad_date_denonciation and $date_denonciation_min_slash != "")
    		{
    			$anomalie2 = "ANOMALIE : date saisie incorrecte. Elle devrait être située en le ".$date_denonciation_min_slash." et la date du premier tour.";
    		}
    		else
    		{
    			$anomalie2 =  "Format de date incorrect : jj/mm/aaaa demandé. ";
    		}
    
    		echo'
		    <span class="rouge" title="'.$anomalie2.'" id="date_declar_pref_cand_span_'.$numeroSignalement.'"></span>
		</p>
		
		<p>
			<label for="denonciation_anonyme_'.$numeroSignalement.'"> Anonyme :</label>
			<input type="checkbox" id="chk_denonciation_anonyme_'.$numeroSignalement.'"';if (trim(strtolower($SIGNALEMENTS[$i]['denonciateur']))=='') {echo ' checked="checked"';} echo'/>
			<span class="rouge" id="denonciation_anonyme_cand_span_'.$numeroSignalement.'"></span>
		</p>
		
		<p>
			<label for="denonciateur_'.$numeroSignalement.'"> Dénonciateur :</label>
			<textarea class="denonciateur" id="denonciateur_'.$numeroSignalement.'" name="denonciateur_'.$numeroSignalement.'" cols="50" rows="1" maxlength="50">'.$SIGNALEMENTS[$i]["denonciateur"].'</textarea>
			<span class="rouge" id="denonciateur_cand_span_'.$numeroSignalement.'"></span>
		</p>
		
		<p>
            <label for="ged_'.$numeroSignalement.'"> Référence GED :</label>
            <input type="text" id="ged_'.$numeroSignalement.'" name="ged_'.$numeroSignalement.'" size="30" maxlength="30" value="'.$SIGNALEMENTS[$i]['ged'].'" />
        </p>
    	<p><input type="submit" class="enregistrer" value="Enregistrer"></p>
        <button type="button" class="annuler" value="Supprimer signalement" onclick="supprimer_signalement('.$numeroSignalement.')">Supprimer signalement</button>
    </form>
    <form class="upload" name="upload_'.$numeroSignalement.'" action="r2_17_ajax.php" method="post" enctype="multipart/form-data">
       <input type="file" name="doc_'.$numeroSignalement.'" '; if(isset($_SESSION[$key]['fichier_uploade'])){echo 'disabled';} echo'>
       <input type="hidden" name="numeroSignalement" value="'.$numeroSignalement.'">
       <input type="submit" value="Uploader" '; if(isset($_SESSION[$key]['fichier_uploade'])){echo 'disabled';} echo'>';
    		
    	if(isset($_SESSION[$key]['fichier_uploade']))
    	{
    	    $array = explode('/',$_SESSION[$key]['fichier_uploade']);
    	    $nom_fichier = end($array);
    	    echo '<a href="'.$_SESSION[$key]['fichier_uploade'].'">'.$nom_fichier.'</a>';
    	    echo '<button class="suppr_upload" type="button" value="Supprimer fichier uploadé">Supprimer fichier uploadé</button>';
    	}
       
    	echo '
    </form>
   </fieldset>
    '; 
}

?>

	<br>
	<button id="bouton_creation" type="button" value="Nouveau Signalement">Nouveau Signalement</button>
</div>

<div id="zone_creation">
	<fieldset>
	 <legend>Création d'un signalement</legend>
      <form id="form_creation" name="signalement_creation" action="r2_13_ajax.php" method="post">
      		<p>
            	<input type="hidden" name="id_candidat_creation" value="<?php echo $DETAILS[$_GET['cand']]['id_candidat']; ?>">
        	</p>
            <p>
    			<label for="chk_denonciation_abandon_creation"> Abandonnée ? :</label> 
    			<input type="checkbox" id="chk_denonciation_abandon_creation" name="chk_denonciation_abandon_creation"/>
    		</p>
    		
    		<p>
                <label for="date_denonciation_creation"> Date de la dénonciation :</label>
                <input type="text" id="date_denonciation_creation" pattern="<?php echo $date_fr_slashes; ?>" name="date_denonciation_creation" size="10" maxlength="10" value="" />
    		    <span class="rouge" title="" id="date_declar_pref_cand_span_creation"></span>
    		</p>
    		
    		<p>
    			<label for="denonciation_anonyme_creation"> Anonyme :</label>
    			<input type="checkbox" id="chk_denonciation_anonyme_creation"/>
    			<span class="rouge" id="denonciation_anonyme_cand_span_creation"></span>
    		</p>
    		
    		<p>
    			<label for="denonciateur_creation"> Dénonciateur :</label>
    			<textarea class="denonciateur" id="denonciateur_creation" name="denonciateur_creation" cols="50" rows="1" maxlength="50"></textarea>
    			<span class="rouge" id="denonciateur_cand_span_creation"></span>
    		</p>
    		
    		<p>
                <label for="ged_creation"> Référence GED :</label>
                <input type="text" id="ged_creation" name="ged_creation" size="30" maxlength="30" value="" />
            </p>
        	<p>
        		<input type="submit" class="enregistrer" value="Enregistrer">
        	</p>
            <button id="annuler_creation" type="button" class="annuler" value="Annuler création">Annuler création</button>
        </form>
     </fieldset>
</div>
<div id="dialog"></div>

<style>
    /* Fonds bleu si formulaire création */
	#form_creation
	{
        background-color:#BBDEFB;
	}
	
	/* Ecraser le "user agent stylesheet" de p (pour prendre moins de place dans les formulaires de signalement) */
	p
	{
	   margin-block-start:0px;
	   margin-block-end:0px;
	}
	
	textarea.denonciateur
	{
	   width:auto;
	   margin:0;
	   display:inline;
	   vertical-align: middle;
	}
	
	h1
	{
	   font-size: 1.3em;
	   font-weight: lighter;
	}
	
	h2
	{
	   font-size: 1.1em;
	   font-weight: lighter;
	}
	
	h2.titre_signalement
	{
	   cursor: pointer;
    }
</style>

<!-- affichage masquage du formulaire au click sur <legend> correspondant, en fonction de l état de la vérification : -->



<?php
// ################# fin du formulaire ##################
// ######################################################


?>

<script src="js/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>

<script src="js/fonction_getParam.js"></script>

	<!-- plugin de validation de formulaire - source : http://bassistance.de/jquery-plugins/jquery-plugin-validation/ -->
	<!-- <script src="js/jquery.validate.min.js"></script> -->
	<!-- <script src="js/jquery.validate.min_messages_fr.js"></script> -->



<?php 
// ELM20191023 On ne vérifie pas les formats de téléphone pour les candidats de Polynésie Française, Nouvelle-Calédonie et français de l'étranger
	if ($DETAILS[$_GET['cand']]['id_dpt']!='987' 
	&&	$DETAILS[$_GET['cand']]['id_dpt']!='988' 
	&&	$DETAILS[$_GET['cand']]['chk_francais_etranger']=='2') { ?>

<script>

// 20140402 PARFAIT (mise en jaune des champs indispensables et vérification contenu via module validate()) si chargement hors frame uniquement (pas besoin de $(document).ready pour cela) :

// DEBUG alert('NOM DU FORMULAIRE : '+$('#formulaire').attr('name'));

$("#pays_cand,#pays_cand_associe").on('change', function() {

	var pays=$(this).val().trim();
	var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	
	var tel1='#'+(($(this).attr('id')).replace('pays','telephone1'));
	var tel2='#'+(($(this).attr('id')).replace('pays','telephone2'));
	var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));

	if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france") {
		$(cp).attr('pattern','[0-9]{5}');
		$(cp).attr('placeholder','5 chiffres');		
		$(tel1+','+tel2+','+telc).attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8}))$');		
	} else {		
		$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ& ]{2,20}');
		$(cp).attr('placeholder','code libre');		
		$(tel1+','+tel2+','+telc).attr("pattern","[0-9 \\+\\-\\.]{8,20}");
	}
	
});

$("#pays_cand_post").on('change', function() {

	var pays=$(this).val().trim();
	var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	
	var tel1='#'+(($(this).attr('id')).replace('pays','telephone'));
	var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));

	if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france") {
		$(cp).attr('pattern','[0-9]{5}');
		$(cp).attr('placeholder','5 chiffres');		
		$(tel1+','+telc).attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8}))$');		
	} else {		
		$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ& ]{2,20}');
		$(cp).attr('placeholder','code libre');		
		$(tel1+','+telc).attr("pattern","[0-9 \\+\\-\\.]{8,20}");
	}
});
	
$("#pays_cand,#pays_cand_associe").each(function() {
	var pays=$(this).val().trim();
	var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	var tel1='#'+(($(this).attr('id')).replace('pays','telephone1'));
	var tel2='#'+(($(this).attr('id')).replace('pays','telephone2'));
	var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));
	if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france") {	
		$(cp).attr('pattern','[0-9]{5}');
		$(cp).attr('placeholder','5 chiffres');
		$(tel1+','+tel2+','+telc).attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
	} else {	
		$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏ& ]{2,20}');
		$(cp).attr('placeholder','code libre');
		$(tel1+','+tel2+','+telc).attr('pattern','[0-9 \\+\\-\\.]{8,20}');
	}
});

$("#pays_cand_post").each(function() {
	var pays=$(this).val().trim();
	var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	var tel1='#'+(($(this).attr('id')).replace('pays','telephone'));
	var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));
	if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france") {	
		$(cp).attr('pattern','[0-9]{5}');
		$(cp).attr('placeholder','5 chiffres');
		$(tel1+','+telc).attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
	} else {	
		$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏ& ]{2,20}');
		$(cp).attr('placeholder','code libre');
		$(tel1+','+telc).attr('pattern','[0-9 \\+\\-\\.]{8,20}');
	}
});
</script>

<?php } else { // département 987 ou 988 ou chk_francais_etranger != 2 ?>

<script>

$("#pays_cand,#pays_cand_associe").each(function() {
	var pays=$(this).val().trim();
	var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	var tel1='#'+(($(this).attr('id')).replace('pays','telephone1'));
	var tel2='#'+(($(this).attr('id')).replace('pays','telephone2'));
	var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));

	$(tel1+','+tel2+','+telc).removeAttr('pattern');
	$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ& ]{2,20}');
	$(cp).attr('placeholder','code libre');		
});

$("#pays_cand_post").each(function() {
	var pays=$(this).val().trim();
	var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	var tel1='#'+(($(this).attr('id')).replace('pays','telephone'));
	var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));

	$(tel1+','+telc).removeAttr('pattern');
	$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ& ]{2,20}');
	$(cp).attr('placeholder','code libre');		
});

</script>

<?php } ?>


<script>
$("#formulaire input:text[required],#formulaire textarea[required]").css('background-color','yellow');

document.title='Candidat - informations';	
$('h1#titre').text("Candidat | informations");
if($( "#date_declar_pref_cand" ).length>0){
	$( "#date_declar_pref_cand" ).datepicker($.datepicker.regional[ "fr" ] );
}

// alert($("#date_depot_cpte").val().length);

if($("#date_depot_cpte").length>0){

	$("#date_depot_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
	
}

//Modifié par EA le 03 01 2020 car désormais n signalement

// if($( "#date_denonciation" ).length>0)
// {
// 	$( "#date_denonciation" ).datepicker($.datepicker.regional[ "fr" ] );
// }

var nbsignalement = '<?php echo count($SIGNALEMENTS); ?>';

for(var i=0; i<nbsignalement; i++)
{
	var selecteurJquerySurDateDenonciation = '#date_denonciation_'+i;

	if($(selecteurJquerySurDateDenonciation).length>0)
	{
		$(selecteurJquerySurDateDenonciation).datepicker($.datepicker.regional["fr"]);
	}
}

if($( "#date_etiquetage_cpte" ).length>0){
	$( "#date_etiquetage_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
}
if($( "#date_ouverture_cpte" ).length>0){
	$( "#date_ouverture_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
}
if($( "#date_retour_cpte" ).length>0){
	$( "#date_retour_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
}
if($( "#date_pass_seance" ).length>0){
	$( "#date_pass_seance" ).datepicker($.datepicker.regional[ "fr" ] );
}
if($( "#date_envoi_notif" ).length>0){
	$( "#date_envoi_notif" ).datepicker($.datepicker.regional[ "fr" ] );
}

//Par EA le 13 12 2019 pour ano 560
if($( "#date_debut_financement" ).length>0)
{
	$( "#date_debut_financement" ).datepicker($.datepicker.regional[ "fr" ] );
}



</script>

<?php

require("inclusion/redirection_AR_rapporteur.php");

?> 


<!-- bibliothèques jquery -->
	
	
	<!-- https://github.com/browserstate/history.js -->
	<!--<script src="js/jquery.history.js"></script>     ilyes-->

	<!-- extrait de code_deroulant/index_bl3.html : -->
	
	<script src="js/menus/jquery.dropdownPlain.js"></script>
	
	<script src="js/jquery_NAVIGATION.js"></script>
	<!-- <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" /> 
	-->
	<script src="js/jquery_ENTETE.js"></script>
	
	<script src="code_popup_div/code_popup_div21.js"></script>
	
	<script src="js/jquery.saisie.php.js"></script>
	
	<script src="js/verif_formulaire.js" type="text/javascript"></script>
	<script src="js/sorttable.js" type="text/javascript"></script>
	<script src="js/jquery_enregistre_formulaire.js"></script>
	
	<script src="js/date_validation.js" type="text/javascript"></script>
	
	<script src="js/disable_champs.js"></script>

<script src="js/jquery.affichage_fieldset_h5.js"></script> 
<script src="js/jquery.ui.datepicker-fr.js"></script>
<script src="js/droit_utilisateur_page.js"></script>


<!-- Insertion des JS pour les tabulators -->
<script src="js/moment.js"></script>
<link href="js/tabulator/css/tabulator_site.css" rel="stylesheet" />
<script src="js/tabulator/js/tabulator.js"></script>
<script src="js/tabulator/xlsx.full.min.js"></script>


<script>
function mettre_ajour_navigation(id_util_rg,id_groupe_rapporteur,id_scrutin,id_candidat){
	    var annee = getParam('an');
		var url = "tdb_suivi.php?p=tdb_suivi&an="+annee;
		var url_rg = "tdb_suivi.php?p=tdb_suivi&an="+annee+"&rg="+id_util_rg;
		var url_rap = "tdb_suivi.php?p=tdb_suivi&an="+annee+"&rg="+id_util_rg+"&rap="+id_groupe_rapporteur;
		var url_scrutin = "tdb_suivi.php?p=tdb_suivi&an="+annee+"&rg="+id_util_rg+"&rap="+id_groupe_rapporteur+"&scr="+id_scrutin;
		//$("a.mylink").attr("href", "http://cupcream.com");
		var rg = $("#navigation").find("#rg");
		var rapporteurs = $("#navigation").find("#rapporteurs");
		var scrutins =  $("#navigation").find("#scrutins");
		var candidats =  $("#navigation").find("#candidats");
		if(rg.length){
			rg.attr("href", url_rg);
		}
		if(rapporteurs.length){
			rapporteurs.attr("href", url_rg);
		}
		if(scrutins.length){
			scrutins.attr("href", url_rap);
		}
		if(candidats.length){
			candidats.attr("href", url_scrutin);
		}
		
}


function verif_date_depot_compte() {
	
		var aucune_date = 0; // 20171102 complément fiche anomalie 127 - nouvelle variable - quand la date de dépôt est absente, l'anomalie n'est pas surlignée en rouge alors qu'elle l'est quand la date_depot > date_limite_depot
		
		var mauvaise_date = 0; // 20171102 la date est considérée comme mauvaise si elle est antérieure à la date du 1er tour ou postérieure à la date actuelle (celle de la consultation de la page)
		
		var mauvais_format_date = 0; // 20171107 nouveau - initialisation 
		
		var date_depot_cpte3 = document.getElementById("date_depot_cpte");
		
		var date_actuelle=<?php echo substr(date_sql_objet_ou_array_vers_int($date_actuelle),0,8) //echo $date_actuelle->format('Ymd'); ?>;
		var date_1t=<?php echo substr(date_sql_objet_ou_array_vers_int($date_1t_compare),0,8); ?>;
		
		var date_depot_int = $('#date_depot_cpte').val().substr(6,4)+''+$('#date_depot_cpte').val().substr(3,2)+''+$('#date_depot_cpte').val().substr(0,2);
		
			var date_limite_depot_slash="";
	
	var date_limite_depot_slash=<?php
	
	if ($date_limite_depot_slash != "") { 
		echo "' (".$date_limite_depot_slash.")'"; 
	}else {
		echo "''";
		
	}
		
	?>;
		
// 		var date_1t = parseInt(date_1t);
// 		var date_actuelle = parseInt(date_actuelle);
// 		var date_depot2 = parseInt(date_depot2);
		
		// DEBUG alert('date jour '+date_actuelle+" | date 1er tour : "+date_1t+" | "+date_depot2);
		
		if ($('#date_depot_cpte').val() == '') {  // 20171102
			aucune_date = 1;
		}
		// alert($('#date_depot_cpte').val());
		// 20180719 suppression de la variable 'date_depot' ci-dessous pour simplification et résolution du bug de la fiche 885 
		
		// var date_depot = new Date($('#date_depot_cpte').val().substr(6,4),$('#date_depot_cpte').val().substr(3,2),$('#date_depot_cpte').val().substr(0,2));
		
		// alert('date 1t : '+date_1t+' - actuelle : '+date_actuelle+'  - date dépôt int : '+date_depot_int+' - date dépôt val récupérée : '+$('#date_depot_cpte').val()+' --- 1er segment : '+$('#date_depot_cpte').val().substr(6,4)+' - 2e segment : '+$('#date_depot_cpte').val().substr(3,2)+' - 3e segment : '+$('#date_depot_cpte').val().substr(0,2));
		
		// date 1t : 20170610 - actuelle : 20180719 - date dépôt : Thu Oct 12 2017 00:00:00 GMT+0200 (CEST) - date dépôt 2 : 20170912 - date dépôt val récupérée : 12/09/2017 --- 1er segment : 2017 - 2e segment : 09 - 3e segment : 12 BAD date_depot !
		
				
		//alert('date 1t : '+date_1t+' - actuelle : '+date_actuelle+' - date dépôt : '+date_depot_int);
				
		var blocage = 0; // destiné à déclencher une erreur blocante pour l'enregistrement dans le cas où la date serait < date_1t (impossible)
		
		if (date_depot_int < date_1t || date_depot_int > date_actuelle) {
		
			mauvaise_date = 1;
			
			if (date_depot_int < date_1t) {
			
				blocage = 1;
			
			}
			
		
		}
		
		
		var mauvais_format_date = 0;
		
		if($('#date_depot_cpte').val().length!=10 && $('#date_depot_cpte').val().length>0){
		
			mauvais_format_date = 1;
			
		}
		
		<?php 
		
		
			$date_limite_depot_pre_int = "";
			
			if (is_a($DETAILS[$_GET['cand']]['date_limite_depot'], "DateTime")) {
				$date_limite_depot_pre_int = $DETAILS[$_GET['cand']]['date_limite_depot']->format('Ymd');
			} else {
			
				$date_limite_depot_pre_int  = $DETAILS[$_GET['cand']]['date_limite_depot']['date'];
			}
			
			$date_limite_depot_int = substr($date_limite_depot_pre_int,0,10);
			$date_limite_depot_int = str_replace("-","",$date_limite_depot_int);			
		
		
		// 20180719 fiche 885 : Supression, ensuite, de 'date_limite_depot' (comme de 'date_depot' précédemment jadis construite via new Date(...) au profit de comparaisons entre versions int des dates
		
		?>
		
// var date_limite_depot2 = new Date('2018-08-18');
// 		alert(date_limite_depot2);
		
		var date_limite_depot_int = <?php echo $date_limite_depot_int; ?>;
		
		// alert('(2) date 1t : '+date_1t+' - actuelle : '+date_actuelle+' - date limite de dépôt INT : '+date_limite_depot_int+' - date dépôt int : '+date_depot_int+' - date dépôt val récupérée : '+$('#date_depot_cpte').val()+' --- 1er segment : '+$('#date_depot_cpte').val().substr(6,4)+' - 2e segment : '+$('#date_depot_cpte').val().substr(3,2)+' - 3e segment : '+$('#date_depot_cpte').val().substr(0,2));
		
		// renvoit : (2) date 1t : 20170610 - actuelle : 20180719 - date limite de dépôt INT : 20170818 - date dépôt int : 20170912 - date dépôt val récupérée : 12/09/2017 --- 1er segment : 2017 - 2e segment : 09 - 3e segment : 12
		

		if ($('#date_depot_cpte').is(":invalid")) {
		
			$('#bad_date').text('');
			$('#date_depot_cpte').css("background-color","#FF434A");
			
			// rien 
			
		} else if (blocage == 1 && aucune_date != 1) { // 20180808
		
			$('#bad_date').text('');
			
			// alert('blocage');
			
			$('#date_depot_cpte').next('span').attr('title','ANOMALIE : la date de dépôt ne peut pas être antérieure à la date du 1er tour.');
			
			date_depot_cpte3.setCustomValidity("ANOMALIE : la date de dépôt ne peut pas être antérieure à la date du 1er tour.");
			
			$('#date_depot_cpte').css("border-color","#FF434A");
			
			blocage = 0;
		
		} else if (date_depot_int > date_limite_depot_int || aucune_date == 1 || mauvaise_date == 1 || mauvais_format_date == 1){  // 20171102
			//alert('problème');
			
			date_depot_cpte3.setCustomValidity("");
			
			$('#date_depot_cpte').css("background-color","#FF434A");
			
			
			if (date_depot_int > date_limite_depot_int && mauvais_format_date == 0) {
				
				$('#bad_date').text(' REMARQUE : la date de dépôt est postérieure à la date limite de dépôt'+date_limite_depot_slash+'.');
				
			} else if (mauvaise_date == 1 && mauvais_format_date == 0) {
			
				if (date_depot_int > date_limite_depot_int) {
					$('#bad_date').text(' ANOMALIE : date saisie incorrecte. Elle devrait être située entre la date du premier tour et la date de ce jour.');
					
				} else if (aucune_date != 1) {
					
					$('#bad_date').text(' ANOMALIE : date saisie incorrecte. Elle devrait être située entre la date du premier tour et la date limite de dépôt '+date_limite_depot_slash+'.');
				
				}
					
			} else if (mauvais_format_date == 1 && aucune_date == 0) {
			
				$('#bad_date').text(' Le format de date est incorrecte. Merci de bien vouloir le corriger.');
				
			} else if (aucune_date == 1) {
				
				$('#bad_date').text(' ATTENTION : aucune date de dépôt n\'a été saisie. Si vous enregistrez ce changement, le compte sera considéré comme non déposé.');
			
			} else {
			
				$('#bad_date').text('');
			}
			
		}else{
		
			//alert('normal');
			// $('#date_depot_cpte').attr("style","background-color:none");
			$('#date_depot_cpte').css("border-color","unset");
			$('#date_depot_cpte').css("background-color","transparent");
			$('#bad_date').text('');
			
		}
	


}


function verif_date_declar_pref() {
	
	var date_declar_pref_cand3 = document.getElementById("date_declar_pref_cand");
	
	var verification = true;
	
	if ($("#date_declar_pref_cand").val().length == 10) {

		verification = date_validation($('#date_declar_pref_cand').val());
		
	}
			
	if (verification) {
			
		//alert('date ok');
		// alert($(this).prop(':invalid'));
				
		// $("#date_declar_pref_cand").next('span').html('');
		
		if ($("#date_declar_pref_cand").val().length > 0) {
		
			$("#date_declar_pref_cand_span").attr('title','Format de date incorrect : jj/mm/aaaa demandé. ');	
		
			var aucune_date = 0; // 20171102 complément fiche anomalie 127 - nouvelle variable - quand la date de dépôt est absente, l'anomalie n'est pas surlignée en rouge alors qu'elle l'est quand la date_depot > date_limite_depot
	
			var mauvaise_date = 0; // 20171102 la date est considérée comme mauvaise si elle est antérieure à la date du 1er tour ou postérieure à la date actuelle (celle de la consultation de la page)
	
			var mauvais_format_date = 0; // 20171107 nouveau - initialisation 
	
			var date_1t=<?php echo substr(date_sql_objet_ou_array_vers_int($date_1t_compare),0,8); ?>;
	
			var date_declar_pref_cand_min_int=<?php echo substr(date_sql_objet_ou_array_vers_int($date_declar_pref_cand_min),0,8); ?>;
	
			var date_declar_pref_cand_min_slash="<?php echo $date_declar_pref_cand_min_slash; ?>";

	
			if ($('#date_declar_pref_cand').val() == '') {  // 20171102
	
				aucune_date = 1;
				var date_declar_pref_cand_int = 0;
		
			} else if ($('#date_declar_pref_cand').val().length == 10) {
	
				var date_declar_pref_cand_int = $('#date_declar_pref_cand').val().substr(6,4)+''+$('#date_declar_pref_cand').val().substr(3,2)+''+$('#date_declar_pref_cand').val().substr(0,2);
			}
			
			if (date_declar_pref_cand_min_int > date_declar_pref_cand_int || date_declar_pref_cand_int > date_1t) {
	
				// date_declar_pref_cand3.addEventListener("keyup", function (event) {
	
				mauvaise_date = 1;
		
				if (date_declar_pref_cand_min_int > date_declar_pref_cand_int) {
		
					$('#date_declar_pref_cand').next('span').attr('title','ANOMALIE : la date de déclaration en préfecture devrait être située entre le '+date_declar_pref_cand_min_slash+' et la date du 1er tour.');
			
					date_declar_pref_cand3.setCustomValidity('ANOMALIE : la date de déclaration en préfecture devrait être située entre le '+date_declar_pref_cand_min_slash+' et la date du 1er tour.');
			
				} else {
		
					$('#date_declar_pref_cand').next('span').attr('title','ANOMALIE : la date de déclaration ne peut pas être postérieure à la date du 1er tour.');
			
					date_declar_pref_cand3.setCustomValidity("ANOMALIE : la date de déclaration ne peut pas être postérieure à la date du 1er tour.");
				}
		
				// });
	
			} else {
	
				date_declar_pref_cand3.setCustomValidity("");
				$('#date_declar_pref_cand').css('border-color','none');
	
			}
	
	
			var mauvais_format_date = 0;
	
			if($('#date_declar_pref_cand').val().length!=10 && $('#date_declar_pref_cand').val().length>0){
	
				mauvais_format_date = 1;
		
			}
		
		}
	
	} else {
			
		// alert($(this).prop(':invalid'));
		//var date_declar_pref_cand3 = document.getElementById("date_declar_pref_cand");
			
		//alert('mauvaise date');
		// $("#date_declar_pref_cand").next('span').html('  <span style="color:red">Un telle date n\'existe pas.</span>');
				
		$("#date_declar_pref_cand_span").attr('title','Une telle date n\'existe pas. ');
				
		date_declar_pref_cand3.setCustomValidity("La date est invalide.");
		
				
	}
	
	if ($('#date_declar_pref_cand').is(":invalid")) {
	
		$('#date_declar_pref_cand').css("border-color","#FF434A");
		
	}else{
	
		//alert('normal');
		// $('#date_depot_cpte').attr("style","background-color:none");
		$('#date_declar_pref_cand').css("border-color","unset");
		
		
	}
}

function verif_date_denonciation(id) //id en argument ajouté par EA le 02 01 2020 pour ano 875
{
	var date_denonciation3 = document.getElementById(id);
	
	var verification = true;

	var selecteurJquerySurElementVerifie = "#"+id;

	if ($(selecteurJquerySurElementVerifie).val().length == 10)
	{
		verification = date_validation($(selecteurJquerySurElementVerifie).val());
	}
			
	if (verification)
	{
		if ($(selecteurJquerySurElementVerifie).val().length > 0)
		{
			$(selecteurJquerySurElementVerifie).next('span').attr('title','Format de date incorrect : jj/mm/aaaa demandé. ');
		
			var aucune_date = 0; // 20171102 complément fiche anomalie 127 - nouvelle variable - quand la date de dépôt est absente, l'anomalie n'est pas surlignée en rouge alors qu'elle l'est quand la date_depot > date_limite_depot
	
			var mauvaise_date = 0; // 20171102 la date est considérée comme mauvaise si elle est antérieure à la date du 1er tour ou postérieure à la date actuelle (celle de la consultation de la page)
	
			var mauvais_format_date = 0; // 20171107 nouveau - initialisation 
	
			var date_1t=<?php echo substr(date_sql_objet_ou_array_vers_int($date_1t_compare),0,8); ?>;
	
			var date_denonciation_min_int=<?php echo substr(date_sql_objet_ou_array_vers_int($date_denonciation_min),0,8); ?>;
			
			var date_denonciation_max_int=<?php echo substr(date_sql_objet_ou_array_vers_int($date_denonciation_max),0,8); ?>;
	
			var date_denonciation_min_slash="<?php echo $date_denonciation_min_slash; ?>";
			
			var date_denonciation_max_slash="<?php echo $date_denonciation_max_slash; ?>";

			if ($(selecteurJquerySurElementVerifie).val() == '')
			{
				aucune_date = 1;
				var date_denonciation_int = 0;
			}
			else if ($(selecteurJquerySurElementVerifie).val().length == 10)
			{
				var date_denonciation_int = $(selecteurJquerySurElementVerifie).val().substr(6,4)+''+$(selecteurJquerySurElementVerifie).val().substr(3,2)+''+$(selecteurJquerySurElementVerifie).val().substr(0,2);
			}
			
			if (date_denonciation_int < date_denonciation_min_int || date_denonciation_int > date_denonciation_max_int)
			{
				mauvaise_date = 1;
		
				$(selecteurJquerySurElementVerifie).next('span').attr('title','ANOMALIE : la date de dénonciation devrait être située entre le '+date_denonciation_min_slash+' et le '+date_denonciation_max_slash+'.');
			
				date_denonciation3.setCustomValidity('ANOMALIE : la date de dénonciation devrait être située entre le '+date_denonciation_min_slash+' et le '+date_denonciation_max_slash+'.');
			}
			else
			{
				date_denonciation3.setCustomValidity("");
				$(selecteurJquerySurElementVerifie).css('border-color','none');
			}

			var mauvais_format_date = 0;
	
			if($(selecteurJquerySurElementVerifie).val().length!=10 && $(selecteurJquerySurElementVerifie).val().length>0)
			{
				mauvais_format_date = 1;
			}
		}
	}
	else
	{			
		$(selecteurJquerySurElementVerifie).next('span').attr('title','Une telle date n\'existe pas. ');	
		date_denonciation3.setCustomValidity("La date est invalide.");		
	}
	
	if ($(selecteurJquerySurElementVerifie).is(":invalid"))
	{
		$(selecteurJquerySurElementVerifie).css("border-color","#FF434A");
	}
	else
	{
		$(selecteurJquerySurElementVerifie).css("border-color","unset");
	}
}

function hide_denonciation_fields(){
	//$("div.denonciation checkbox, div.denonciation input[type=text], div.denonciation textarea").prop('disabled',true);
	//$("#chk_denonciation").parent('p').siblings().hide();
	//$("#chk_denonciation").closest('fieldset').find('legend').show(); //Par EA le 26 12 2019 pour résoudre un bug
	//TOUT COMMENTE PAR EA LE 02 01 2020 CAR OBSOLETE APRES TRAVAIL SUR EVOL 875
}

function denonciation_update_hidden_abandon(i) //Argument ajouté par EA le 03 01 2020 pour évolution 875
{
	var selecteurJquery = "#checkbox_denonciation_abandon_"+i
	
	if ($(selecteurJquery).prop('checked'))
	{
		$(selecteurJquery).val('1');
	} 
	else
	{
		$(selecteurJquery).val('0');
	}
}

//Par EA le 10 01 2020 pour evol 875
function supprimer_signalement(id_signal)
{
	$("#dialog").text("Voulez-vous vraiment supprimer définitivement ce signalement ?");	

	$("#dialog").dialog({
        					title : 'Confirmation',
        					buttons :
        					{
        						'Confirmer' : function()
        						{
        							$.ajax({
        										url:'r2_12_ajax.php', 
        										type: "post",
        										data : {'id_signal' : id_signal},
        										success:function(result)
        										{
        											window.location.reload(true);
        										},
        										error:function(error)
        										{
            										alert(error);
        										}
        									});
        							$(this).dialog("close");
        						},
        						'Annuler' : function()
        						{
        							$(this).dialog("close");
        						}
        					}
    					});
}



$(document).ready(function()
{
	//EA le 03 03 2020
	$('.upload').on('submit', function(e)
	{
		$.ajax(
	     		{
		            url: $(this).attr('action'),
		            data: $(this).serialize(),
		            type: 'POST',
		            success: function(resp)
		            {
		            	
	            	},
	            	error: function()
	            	{
	            		alert('erreur lors de l upload du fichier');
	            	}
	            });
	});

	$('.suppr_upload').on('click', function(e)
	{
		$.ajax(
	     		{
		            url: 'r2_14_ajax.php',
		            data: 
			            {cheminFichier : $(this).prev('a').attr('href'),
			             numeroSignalement : $(this).closest('form').find('input[name="numeroSignalement"]').val()
			            },
		            type: 'GET',
		            success: function(resp)
		            {
		            	window.location.reload(true);
	            	},
	            	error: function()
	            	{
	            		alert('erreur lors de la suppression du fichier uploadé');
	            	}
	            });
	});

//Par EA le 21 01 2020 pour evol 875
if($('#chk_denonciation').val() !== '1')
{
	$('#signalements').hide();
}
else
{
	$('#signalements').show();
}

//Par EA le 22 01 2020 pour evol 875
$('#chk_denonciation').on('change', function()
{
	if($(this).val() == '0') //Si on a coché non
	{
		var nombre_signalements = <?php echo count($SIGNALEMENTS) ?>;
		
		if (nombre_signalements > 0)
		{
			alert('Vous ne pouvez pas mettre non alors qu\'il y a des signalements en bas de page');
			$(this).val('1');
			return false; //On sort de la fonction
		}
	}
});

////Par EA le 17 01 2020 pour evol 875
//Initialiser des variables pour détecter des changement dans les formulaires (candidat + signalements) pour que l'utilisateur enregistre bien son formulaire avant de passer à un autre
var unFormulaireEstModifie = false;
var formulaireModifie = ''; //On n'aura jamais deux formulaires modifiés en même temps, car le code contrôle qu'on ne saisisse pas en même temps sur plusieurs formulaires

//Détecter le formulaire qui a été modifié
$('form').on('change', function()
{
	unFormulaireEstModifie = true;
	var formName = $(this).attr('name');
	var formPreciseName = '';
	
	if (formName == 'r2_1')
	{
		formPreciseName = 'candidat';
	}
	else if (formName == 'signalement_creation')
	{
		formPreciseName = 'de creation de signalement';	
	}
	else if (formName.startsWith('upload'))
	{
		var array = formName.split('_');
		var numeroUpload = parseInt(array[1],10);
		formPreciseName = 'd upload numéro '+numeroUpload;
	}
	else
	{
		var array = formName.split('_');
		var numeroSignalement = parseInt(array[1],10);
		formPreciseName = 'du signalement numéro '+numeroSignalement;
	}

	formulaireModifie = formPreciseName;
});

//Par EA le 17 01 2020 pour evol 875
$('.titre_signalement').next().hide(); //cacher par défault le détail des signalements

//Par EA le 16 01 2020 pour evol 875
$('.titre_signalement').on('click', function()
{
	if(unFormulaireEstModifie == true)
	{
		alert('Avant d\'ouvrir ou fermer un signalement, merci de sauver vos modifications sur le formulaire '+formulaireModifie);
		return false; //On sort de la fonction
	}
	else
	{
		var displayZoneCreationSignalement = $('#zone_creation').css('display');
		
		if(displayZoneCreationSignalement != 'none')
		{
			alert('Avant d\'ouvrir un signalement, merci de finir votre création de signalement');
			return false; //On sort de la fonction
		}
		else
		{
    		var displaySignalClique = $(this).next('fieldset').css('display'); //Récupérer le "display" sur le signalement cliqué
    		$('.titre_signalement').next('fieldset').hide(); //replier tous les signalements
    
    		if(displaySignalClique == 'none') //déplier le signalement s'il était caché
    		{
    			$(this).next('fieldset').show(); //déplier le signalement
    			$('#formulaire').hide(); //cacher le formulaire candidat
    		}
    		else
    		{
    			$('#formulaire').show(); //ré-afficher le formulaire candidat
    			$('html,body').animate({scrollTop: $(this).offset().top}, 'slow'); //Placer les signalements le plus haut possible
    		}
		}
	}
});

//Par EA le 13 01 2020 pour evol 875
$('#zone_creation').hide(); //cachée par défault

//Par EA le 13 01 2020 pour evol 875
$("#bouton_creation").on('click', function()
{
	//Vérifier si on a formulaire a des modifications non enregistrées
	if(unFormulaireEstModifie == true)
	{
		alert('Avant de creer un signalement, merci de sauver vos modifications sur le formulaire '+formulaireModifie);
		return false; //On sort de la fonction
	}
	else
	{
		//Cacher le formulaire candidat
		$('#formulaire').hide(); //cacher le formulaire candidat

		//Replier les formulaires des signalements
		$('.titre_signalement').next('fieldset').hide();
		
    	//Afficher la zone de création de signalement
    	$('#zone_creation').show();
    	$('html,body').animate({scrollTop: $(this).offset().top}, 'slow'); //Placer le début du fomulaire de création de signalement le plus haut possible
    	//$('#chk_denonciation_abandon_creation').prop('checked','false'); //Cette case doit être décochée par défault

    	//Rendre inactif le bouton 'Nouveau Signalement'
    	$("#bouton_creation").prop("disabled",true);
	}
});

//Par EA le 13 01 2020 pour evol 875
$('#annuler_creation').on('click', function()
{
	//Vider les champs de la zone de création de signalement
	$('#chk_denonciation_abandon_creation').prop('checked','false');
	$('#date_denonciation_creation').val('');
	$('#date_declar_pref_cand_span_creation').text('');
	$('#chk_denonciation_anonyme_creation').prop('checked','false');
	$('#denonciation_anonyme_cand_span_creation').text('');
	$('#denonciateur_creation').val('');
	$('#denonciateur_cand_span_creation').text('');
	$('#ged_creation').val('');

	//Cacher la zone de création de signalement
	$('#zone_creation').hide();

	//On ré-initialise les variables qui surveillent la modification des formulaires de la page
	unFormulaireEstModifie = false;
	formulaireModifie = '';

	//Réactiver le bouton 'Nouveau Signalement'
	$("#bouton_creation").prop("disabled",false);
});

//Par EA le 08 01 2020 pour évol 875
$(".formSignalement input[type=submit]").on('click', function(e)
{
	e.preventDefault; //Ne pas aller sur r2_11_ajax.php
	
	var nb_erreurs = 0;
 	nb_erreurs = $(this).closest(".formSignalement").find(":invalid").length;

 	if (nb_erreurs == 0)
 	{
 		$.ajax(
 		{
            url: $(this).closest(".formSignalement").attr('action'),
            data: $(this).closest(".formSignalement").serialize(),
            type: 'POST',
            success: function(resp)
            {
				if (resp.indexOf('réussi') !== -1)
            	{
            		window.location.reload(true);
            	}
        	}
        });
        
        return false;
 	}
 	else
 	{
 		alert('Modification impossible.\nLe formulaire contient des erreurs.\nMerci de bien vouloir les corriger.');
 		return false;
 	}
});

//Par EA le 14 01 2020 pour évol 875
$("#form_creation input[type=submit]").on('click', function(e)
{
	e.preventDefault; //Ne pas aller sur r2_13_ajax.php
	
	var nb_erreurs = 0;
 	nb_erreurs = $("#form_creation").find(":invalid").length;

 	if (nb_erreurs == 0)
 	{
 		$.ajax(
 		{
            url: $("#form_creation").attr('action'),
            data: $("#form_creation").serialize(),
            type: 'POST',
            success: function(resp)
            {
				if (resp.indexOf('réussi') !== -1)
            	{
            		window.location.reload(true);
            	}
        	}
        });
        
        return false;
 	}
 	else
 	{
 		alert('Création impossible.\nLe formulaire contient des erreurs.\nMerci de bien vouloir les corriger.');
 		return false;
 	}
});

//Par EA le 26 12 2019 pour ano 979 - Risque de ne pas être utilisé, car un bout de code au chargement de la page coche le champ 'Anonyme' si le champ 'Dénonciateur' est vide
/*var nb_signalement_3 = '<?php //echo count($SIGNALEMENTS); ?>';

for(var i=0; i<nb_signalement_3; i++)
{
	if(!$('#chk_denonciation_anonyme_'+i).is(':checked')&&($('#denonciateur_'+i).val() == ''))
	{
		var anonymeElement = document.getElementById("chk_denonciation_anonyme_"+i);
		var denonciateurElement = document.getElementById("denonciateur_"+i);
		anonymeElement.setCustomValidity('une des deux cases doit être renseignée');
		denonciateurElement.setCustomValidity('une des deux cases doit être renseignée');
	}
}*/

// --------------------------------------------------- Par EA le 16 12 2019 pour ano 560 ---------------------------------------------------
//Contrôler la date date_debut_financement au chargement : en même temps c'est impossible de mettre une date erronée par un update ou une modification du champ sous SQL SERVER

var elementDateDbtFinancement = document.getElementById("date_debut_financement");

//Controle du FORMAT de la date
if ($('#date_debut_financement').val().length > 0 && $('#date_debut_financement').val().length < 10)
{
	$('#msgRgDateDbtFinancement').attr('title','Problème de format de date');	
	elementDateDbtFinancement.setCustomValidity("Problème de format de date.");
}
else if ($('#date_debut_financement').val().length == 10)
{
	verification = date_validation($('#date_debut_financement').val());

	if (!verification)
	{
		$('#msgRgDateDbtFinancement').attr('title','La date est invalide.');
		elementDateDbtFinancement.setCustomValidity("La date est invalide.");
	}
}

//Contrôle de la PERTINENCE de la date
if(elementDateDbtFinancement.checkValidity() == true) //Si aucun problème de format détécté plus haut, alors on peut tester la pertinence de la date de debut de financement
{
	if($('#date_debut_financement').val() !== '')
	{
		var dateDebutFinancement = $('#date_debut_financement').val();

		var dateDebutFinancementNouveauFormat = dateDebutFinancement.substr(6,4)+dateDebutFinancement.substr(3,2)+dateDebutFinancement.substr(0,2);
		var dateDebutFinancementNouveauFormatNumber = Number(dateDebutFinancementNouveauFormat);

		var dateT2Election = '<?php echo affiche_date_fr($DETAILS[$_GET['cand']]['date_2t']); ?>';
		var dateT2ElectionNouveauFormat = dateT2Election.substr(6,4)+dateT2Election.substr(3,2)+dateT2Election.substr(0,2);
		var dateT2ElectionNouveauFormatNumber = Number(dateT2ElectionNouveauFormat);

		var borneInferieure =  String(Number(dateT2Election.substr(6,4))-1)+dateT2Election.substr(3,2)+'01';
		var borneInferieureNumber = Number(borneInferieure);

		var dateT1Election = '<?php echo affiche_date_fr($DETAILS[$_GET['cand']]['date_1t']); ?>';
		var dateT1ElectionNouveauFormat = dateT1Election.substr(6,4)+dateT1Election.substr(3,2)+dateT1Election.substr(0,2);
		var dateT1ElectionNouveauFormatNumber = Number(dateT1ElectionNouveauFormat);

		if(dateDebutFinancementNouveauFormatNumber < borneInferieureNumber) //RG - La date de début de financement ne peut être inférieure à la date du 1er jour du 12e mois qui précède le mois du 2e tour de cette election partielle
		{
			$('#msgRgDateDbtFinancement').text(" La date de début de financement ne peut être inférieure à la date du 1er jour du 12e mois qui précède le mois du 2e tour de cette election");
		}
		else if(dateDebutFinancementNouveauFormatNumber >= dateT1ElectionNouveauFormatNumber) //RG - La date de début de financement ne peut être supérieure ou égale à la date du 1er tour de l'election partielle considérée
		{
			$('#msgRgDateDbtFinancement').text(" La date de début de financement ne peut être supérieure ou égale à la date du 1er tour de l'election partielle considérée");
		}
	}
}
// ---------------------------------------------------------------------------------------------------------------------------------------------

	var date_depot_cpte4 = document.getElementById("date_depot_cpte");
	date_depot_cpte4.addEventListener("change", function (event) {
  		
		// alert(date_declar_pref_cand2.length);
		if(date_depot_cpte4.validity.typeMismatch) {
		
			date_depot_cpte4.setCustomValidity("Une telle date n'existe pas.");
			$("#date_depot_cpte").next('span').attr('title','Une telle date n\'existe pas. ');
			
		} else {
   				
			// date_declar_pref_cand4.setCustomValidity("");
			$("#date_depot_cpte").next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');
			
			verif_date_depot_compte();
  		}
	});
	
	
	
	$( "#date_declar_pref_cand" ).datepicker($.datepicker.regional[ "fr" ] );
	
	// 20180725 ajout pour fiche 810
	var date_declar_pref_cand2 = document.getElementById("date_declar_pref_cand");
	
	date_declar_pref_cand2.addEventListener("keyup", function (event) {
  		
		// alert(date_declar_pref_cand2.length);
		if(date_declar_pref_cand2.validity.typeMismatch) {
		
			date_declar_pref_cand2.setCustomValidity("Une telle date n'existe pas.");
			$("#date_declar_pref_cand_span").attr('title','Une telle date n\'existe pas. ');
			
		} else {
   				
			date_declar_pref_cand2.setCustomValidity("");
			$("#date_declar_pref_cand_span").attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');
			
			verif_date_declar_pref();
  		}
	});

	var date_declar_pref_cand4 = document.getElementById("date_declar_pref_cand");
	date_declar_pref_cand4.addEventListener("change", function (event) {
  		
		// alert(date_declar_pref_cand2.length);
		if(date_declar_pref_cand4.validity.typeMismatch) {
		
			date_declar_pref_cand4.setCustomValidity("Une telle date n'existe pas.");
			$("#date_declar_pref_cand_span").attr('title','Une telle date n\'existe pas. ');
			
		} else {
   				
			// date_declar_pref_cand4.setCustomValidity("");
			$("#date_declar_pref_cand_span").attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');
			
			verif_date_declar_pref();
  		}
	});
	$("#date_declar_pref_cand").on('change keyup paste', function() {
	
		if ($(this).val().length == 10 || $(this).val().length == 0) {
			var verification = date_validation($(this).val());
			
			if (verification) {
			
				//alert('date ok');
				// alert($(this).prop(':invalid'));
				
				// $("#date_declar_pref_cand").next('span').html('');
				$("#date_declar_pref_cand_span").attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');
				
				
			} else {
			
				// alert($(this).prop(':invalid'));
				//var date_declar_pref_cand3 = document.getElementById("date_declar_pref_cand");
			
				//alert('mauvaise date');
				// $("#date_declar_pref_cand").next('span').html('  <span style="color:red">Un telle date n\'existe pas.</span>');
				
				$("#date_declar_pref_cand_span").attr('title','Une telle date n\'existe pas. ');
				
				date_declar_pref_cand2.setCustomValidity("La date est invalide.");
				
				
			}
		}
		
	});
	
	// 20180725 ajout pour fiche 810
	//Modifié par EA le 03 01 2020 pour évol 875
    var nb_signalement_2 = '<?php echo count($SIGNALEMENTS); ?>';
    
    for(var i=0; i<nb_signalement_2; i++)
    {
    	var date_denonciation2 = document.getElementById("date_denonciation_"+i);

    	date_denonciation2.addEventListener("keyup", function (event)
    	{
    		if(date_denonciation2.validity.typeMismatch)
    		{
    			date_denonciation2.setCustomValidity("Une telle date n'existe pas.");
    			$("#date_denonciation_"+i).next('span').attr('title','Une telle date n\'existe pas. ');	
    		}
    		else
    		{	
    			date_denonciation2.setCustomValidity("");
    			$("#date_denonciation_"+i).next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');
      		}
    	});
    }

	$('input[id^="date_denonciation_"').on('change', function()
	{
		if ($(this).val().length == 10 || $(this).val().length == 0)
		{
			var verification = date_validation($(this).val());
			
			if (verification)
			{
				$(this).next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');	
			}
			else
			{
				$(this).next('span').attr('title','Une telle date n\'existe pas. ');
				var idDateDenonciation = $(this).attr('id');
				var dateDenonciationElement = document.getElementById(idDateDenonciation);
				dateDenonciationElement.setCustomValidity("La date est invalide.");
			}
		}
	});
	
	var date_depot_cpte2 = document.getElementById("date_depot_cpte");
	
	date_depot_cpte2.addEventListener("keyup", function (event) {
  			
		if(date_depot_cpte2.validity.typeMismatch) {
		
			date_depot_cpte2.setCustomValidity("Une telle date n'existe pas.");
			
		} else {
   				
			date_depot_cpte2.setCustomValidity("");
  		}
	});
	
	
	
	$("#date_depot_cpte").on('change keyup', function()
	{
		if ($(this).val().length == 10)
		{
			var verification = date_validation($(this).val());
			
			if (verification)
			{
				$("#date_depot_cpte").next('span').html('');

			}
			else
			{
				$("#date_depot_cpte").next('span').attr('title','Un telle date n\'existe pas.');
				date_depot_cpte2.setCustomValidity("La date est invalide.");
			}
		}
	});
	
	// 20180725 ajout pour fiche 809 - fin
	
	$( "#date_depot_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
	$( "#date_etiquetage_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
	$( "#date_ouverture_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
	
	if ($("#date_depot_cpte").val().length == 0) {
	
		$("#date_depot_cpte").css("background-color","#FF434A");
	}
	
	var date_limite_depot_slash="";
	
	var date_limite_depot_slash=<?php
	
	if ($date_limite_depot_slash != "") { 
		echo "' (".$date_limite_depot_slash.")'"; 
	}else {
		echo "''";
		
	}
		
	?>;
	
	
	
	// var date_depot_depassee=<?php echo $date_limite_depassee; ?>; // 20180314 erreur !!! Variable inexistante !!! :
	// var date_depot_depassee=false;
// 	
// 	var date_limite_depot_slash="";
// 	
// 	var date_limite_depot_slash=<?php
// 	
// 	if ($date_limite_depot_slash != "") { 
// 		echo "' (".$date_limite_depot_slash.")'"; 
// 	}else {
// 		echo "''";
// 		
// 	}
// 		
// 	?>;
// 	
// 	
// 	if (date_depot_depassee == true && $("#date_depot_cpte").val().length > 0) {
// 	
// 		$("#date_depot_cpte").css("background-color","#FF434A");
// 		
// 		$('#bad_date').text(' REMARQUE : la date de dépôt est postérieure à la date limite de dépôt'+date_limite_depot_slash+'.');
// 		
// 	
// 	}

	verif_date_depot_compte();
	// A SUIVRE 20171106 : message si date dépassée 
	
	verif_date_declar_pref();

	//Modifié par EA le 06 01 2020 pour évol 875
	var nbsignalement = '<?php echo count($SIGNALEMENTS); ?>';

	for(var i=0; i<nbsignalement; i++)
	{
		var idDateDenonciation = "date_denonciation_"+i;
		verif_date_denonciation(idDateDenonciation);
	}
	
	$( "#date_retour_cpte" ).datepicker($.datepicker.regional[ "fr" ] );
	$( "#date_pass_seance" ).datepicker($.datepicker.regional[ "fr" ] );
	$( "#date_envoi_notif" ).datepicker($.datepicker.regional[ "fr" ] );
	droit_type_util_page(<?php echo $page_droit_util_values_json; ?>);
	mettre_ajour_navigation(<?php echo $DETAILS[$_GET['cand']]['id_util_rg'].",".$DETAILS[$_GET['cand']]['id_groupe_rapporteur'].",".$DETAILS[$_GET['cand']]['id_scrutin'].",".$DETAILS[$_GET['cand']]['id_candidat']; ?>);
	
	var page_numero = "";
	var an = "";
	var cand = "";
	var scr = "";
	var rap = "";
	var rg = "";
	var dep = "";
	var el = "";
	
	var url_plus = '';
	
	page_numero = getParam('p');
	// 201403147 obsolète 
	// if (page_numero == "suivi") {
// 	
// 		page_numero = "tdb_suivi";
// 		
// 	}
	
	p = getParam('p');
	cand = getParam('cand');
	// alert('Candidat : '+cand);
	scr = getParam('scr');
	dep = getParam('dep');
	rap = getParam('rap');
	rg = getParam('rg');
	el = getParam('el');
	
	an1 = $('#annee').val();
	
	if (getParam('an') != "") {
	
		an = getParam('an');
		
	} else if (an1 != "") {
	
		an = an1;
		
	} else {
	
		alert('paramètre \'an\' vide');
	}
	
	// $('#annee').val(an);
	
	if (p != "") {
		url_plus+= "&p="+p;
	}
	
	if (an != "") {
		url_plus+= "&an="+an;
	}
	if (cand != "") {
		url_plus+= "&cand="+cand;
	}
	if (scr != "") {
		url_plus+= "&scr="+scr;
	}
	if (el != "") {
		url_plus+= "&el="+el;
	}
	if (rap != "") {
		url_plus+= "&rap="+rap;
	}
	if (rg != "") {
		url_plus+= "&rg="+rg;
	}
	if (dep != "") {
		url_plus+= "&dep="+dep;
	} 
	// DEBUG alert('url_plus : '+url_plus);
	
	//var href = location.pathname;
	
	// modifie le filtre d'autocomplete par défaut : n'affiche que les termes qui COMMENCENT par l'expression recherchée :
	
	
	$.ui.autocomplete.filter = function (array, term) {
    	var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex(term), "i");
    	return $.grep(array, function (value) {
        	return matcher.test(value.label || value.value || value);
    	});
	};
 
	var url = 'ref/communes_insee_2014_short.php';
	
	var accentMap = {
      "é": "e",
      "è": "e",
      "ê": "e",
      "î": "i",
      "ô": "o",
      "û": "u",
      "ï": "i",
      "ü": "o",
      "ë": "e",
      "ö": "o"
    };
    var normalize = function( term ) {
      var ret = "";
      for ( var i = 0; i < term.length; i++ ) {
        ret += accentMap[ term.charAt(i) ] || term.charAt(i);
      }
      return ret;
    };
    
    $.getJSON(url, function(data) { 
         //autocomplete           
         $( "#ville_cand,#ville_cand_associe,#ville_cand_post" ).autocomplete({
            minLength: 2,
            source: data
          })
    });
    
	
	var nom_complet_associe = "";
	if ( $('input[name="particule_cand_associe"]').val() == "" ) {
		nom_complet_associe = $('select[name="id_civ_cand_associe"] option:selected').html()+" "+$('input[name="nom_cand_associe"]').val()+" "+$('input[name="prenom_cand_associe"]').val();
	}
	else {
		nom_complet_associe = $('select[name="id_civ_cand_associe"] option:selected').html()+" "+$('input[name="particule_cand_associe"]').val()+" "+$('input[name="nom_cand_associe"]').val()+" "+$('input[name="prenom_cand_associe"]').val();
	}
	$('label[for="chk_procuration"]').html("A fait une procuration pour <i>(Annexe 7)</i><br /><b>"+nom_complet_associe+"</b>");
	
	var nom_complet = "";
	if ( $('input[name="particule_cand"]').val() == "" ) {
		nom_complet = $('select[name="id_civ_cand"] option:selected').html()+" "+$('input[name="nom_cand"]').val()+" "+$('input[name="prenom_cand"]').val();
	}
	else {
		nom_complet = $('select[name="id_civ_cand"] option:selected').html()+" "+$('input[name="particule_cand"]').val()+" "+$('input[name="nom_cand"]').val()+" "+$('input[name="prenom_cand"]').val();
	}	
	$('label[for="chk_procuration_associe"]').html("A fait une procuration pour <i>(Annexe 7)</i><br /><b>"+nom_complet+"</b>");
	
	$('#id_civ_cand,#nom_cand,#particule_cand,#prenom_cand').change(function() {
		var nom_complet = "";
		if ( $('input[name="particule_cand"]').val() == "" ) {
			nom_complet = $('select[name="id_civ_cand"] option:selected').html()+" "+$('input[name="nom_cand"]').val()+" "+$('input[name="prenom_cand"]').val();
		}
		else {
			nom_complet = $('select[name="id_civ_cand"] option:selected').html()+" "+$('input[name="particule_cand"]').val()+" "+$('input[name="nom_cand"]').val()+" "+$('input[name="prenom_cand"]').val();
		}
		$('label[for="chk_procuration_associe"]').html("A fait une procuration pour <i>(Annexe 7)</i><br /><b>"+nom_complet+"</b>");
	});

	$('#id_civ_cand_associe,#nom_cand_associe,#particule_cand_associe,#prenom_cand_associe').change(function() {
		var nom_complet = "";
		if ( $('input[name="particule_cand_associe"]').val() == "" ) {
			nom_complet = $('select[name="id_civ_cand_associe"] option:selected').html()+" "+$('input[name="nom_cand_associe"]').val()+" "+$('input[name="prenom_cand_associe"]').val();
		}
		else {
			nom_complet = $('select[name="id_civ_cand_associe"] option:selected').html()+" "+$('input[name="particule_cand_associe"]').val()+" "+$('input[name="nom_cand_associe"]').val()+" "+$('input[name="prenom_cand_associe"]').val();
		}
		$('label[for="chk_procuration"]').html("A fait une procuration pour <i>(Annexe 7)</i><br /><b>"+nom_complet+"</b>");
	});
	
	$('#chk_procuration').change(function() {
		if($(this).is(':checked')){
			$('#chk_procuration_associe').attr("disabled", true);
			$('.procuration_associe').attr("title", "Attention : il ne peut y avoir qu'une procuration par binôme.");
		}else{
			$('#chk_procuration_associe').removeAttr("disabled");
			$('.procuration_associe').removeAttr("title");
		}
	});

	$('#chk_procuration_associe').change(function() {
		if($(this).is(':checked')){
			$('#chk_procuration').attr("disabled", true);
			$('.procuration').attr("title", "Attention : il ne peut y avoir qu'une procuration par binôme.");
		}else{
			$('#chk_procuration').removeAttr("disabled");
			$('.procuration').removeAttr("title");
		}
	});
	
	if($('#chk_procuration').is(':checked')){
		$('#chk_procuration_associe').attr("disabled", true);
		$('.procuration_associe').attr("title", "Attention : il ne peut y avoir qu'une procuration par binôme.");
	}
	if($('#chk_procuration_associe').is(':checked')){
		$('#chk_procuration').attr("disabled", true);
		$('.procuration').attr("title", "Attention : il ne peut y avoir qu'une procuration par binôme.");
	} 
	$('.procuration, .procuration_associe').tooltip({
        placement: 'bottom',
		track: false
    });	
	
	//choix de l'adresse commune
	$('#adresse_b1').click(function() {
		if ($(this).is(':checked')){
			  $('#adresse1_cand_post').val($('#adresse1_cand').val());
			  $('#adresse2_cand_post').val($('#adresse2_cand').val());
			  $('#adresse3_cand_post').val($('#adresse3_cand').val());
			  $('#adresse4_cand_post').val($('#adresse4_cand').val());
			  $('#cp_cand_post').val($('#cp_cand').val());
			  $('#ville_cand_post').val($('#ville_cand').val());
			  $('#pays_cand_post').val($('#pays_cand').val());			  
			  $('#mail_cand_post').val($('#mail_cand').val());			  
			  $('#telephone_cand_post').val($('#telephone1_cand').val());			  
			  $('#telecopie_cand_post').val($('#telecopie_cand').val());			  
		}
	});
	
	$('#adresse_b2').click(function() {
		if ($(this).is(':checked')){
			  $('#adresse1_cand_post').val($('#adresse1_cand_associe').val());
			  $('#adresse2_cand_post').val($('#adresse2_cand_associe').val());
			  $('#adresse3_cand_post').val($('#adresse3_cand_associe').val());
			  $('#adresse4_cand_post').val($('#adresse4_cand_associe').val());
			  $('#cp_cand_post').val($('#cp_cand_associe').val());
			  $('#ville_cand_post').val($('#ville_cand_associe').val());
			  $('#pays_cand_post').val($('#pays_cand_associe').val());
			  $('#mail_cand_post').val($('#mail_cand_associe').val());			  
			  $('#telephone_cand_post').val($('#telephone1_cand_associe').val());			  
			  $('#telecopie_cand_post').val($('#telecopie_cand_associe').val());				  
		}
	});
	
	$('#adresse_autre').click(function() {
		if ($(this).is(':checked')){
			  $('#adresse1_cand_post').val("");
			  $('#adresse2_cand_post').val("");
			  $('#adresse3_cand_post').val("");
			  $('#adresse4_cand_post').val("");
			  $('#cp_cand_post').val("");
			  $('#ville_cand_post').val("");
			  $('#pays_cand_post').val("");
			  $('#mail_cand_post').val("");	  
			  $('#telephone_cand_post').val(""); 
			  $('#telecopie_cand_post').val("");	  
		}
	});
	
	$('#adresse1_cand_post, #adresse2_cand_post, #adresse3_cand_post, #adresse4_cand_post, #cp_cand_post, #ville_cand_post,  #pays_cand_post,#mail_cand_post,#telephone_cand_post,#telecopie_cand_post').on('keypress change', function() {
		if ($('#adresse_autre').not(':checked')){
			$('#adresse_autre').attr('checked',true);
		}
	});
		
	//Cochage des adresses
	if ( $('#adresse1_cand_post').val() == $('#adresse1_cand').val() && 
		  $('#adresse2_cand_post').val() == $('#adresse2_cand').val() && 
		  $('#adresse3_cand_post').val() == $('#adresse3_cand').val() &&  
		  $('#adresse4_cand_post').val() == $('#adresse4_cand').val() && 
		  $('#cp_cand_post').val() == $('#cp_cand').val() && 
		  $('#ville_cand_post').val() == $('#ville_cand').val() && 
		  $('#pays_cand_post').val() == $('#pays_cand').val() && 
		  $('#mail_cand_post').val() == $('#mail_cand').val() && 
		  $('#telephone_cand_post').val() == $('#telephone1_cand').val() && 
		  $('#telecopie_cand_post').val() == $('#telecopie_cand').val()
    ){
		if ($('#adresse_b1').not(':checked')){
			$('#adresse_b1').attr('checked',true);
		}		
	} else if ( $('#adresse1_cand_post').val() == $('#adresse1_cand_associe').val() && 
		  $('#adresse2_cand_post').val() == $('#adresse2_cand_associe').val() && 
		  $('#adresse3_cand_post').val() == $('#adresse3_cand_associe').val() &&  
		  $('#adresse4_cand_post').val() == $('#adresse4_cand_associe').val() && 
		  $('#cp_cand_post').val() == $('#cp_cand_associe').val() && 
		  $('#ville_cand_post').val() == $('#ville_cand_associe').val() && 
		  $('#pays_cand_post').val() == $('#pays_cand_associe').val() && 
		  $('#mail_cand_post').val() == $('#mail_cand_associe').val() && 
		  $('#telephone_cand_post').val() == $('#telephone1_cand_associe').val() && 
		  $('#telecopie_cand_post').val() == $('#telecopie_cand_associe').val()
    ){
		if ($('#adresse_b2').not(':checked')){
			$('#adresse_b2').attr('checked',true);
		}		
	} else if ( $('#adresse1_cand_post').val() != "" ||
		  $('#adresse2_cand_post').val() != "" ||
		  $('#adresse3_cand_post').val() != "" ||
		  $('#adresse4_cand_post').val() != "" ||
		  $('#cp_cand_post').val() != "" ||
		  $('#ville_cand_post').val() != "" ||
		  $('#pays_cand_post').val() != "" ||
		  $('#mail_cand_post').val() != "" ||
		  $('#telephone_cand_post').val() != "" ||
		  $('#telecopie_cand_post').val() != ""
    ){
		if ($('#adresse_autre').not(':checked')){
			$('#adresse_autre').attr('checked',true);
		}		
	}
	
	$('#date_depot_cpte').on('change keyup paste',function() {
		verif_date_depot_compte(); // 20180719 simplification suite à la réécriture liée à la fiche 885
	
	});
	
	$('#date_declar_pref_cand').on('change keyup paste',function() {
		verif_date_declar_pref(); // 20180719 simplification suite à la réécriture liée à la fiche 885
	
	});
	
	$('input[id^="date_denonciation_"').on('change keyup paste',function() //Modifié par EA le 03 01 2020 pour évol 875
	{
		var id = $(this).attr('id');
		verif_date_denonciation(id); // 20180719 simplification suite à la réécriture liée à la fiche 885
	});

//COMMENTE PAR EA LE 03 01 2020 POUR ANO 875
// 	$("#chk_denonciation").change(function ()
// 	{
// 		if ($(this).val() === '1')
// 		{
// 			$("div.denonciation checkbox, div.denonciation input[type=text], div.denonciation textarea").prop('disabled',false);
// 			$(this).parent('p').siblings().show();
// 		}
// 		else
// 		{
// 			hide_denonciation_fields();
// 		}
// 	});

	/*** Concordance (textarea, checkbox) DENONCIATION Anonyme ***/
	//Modifié par EA le 03 01 2020 pou évol 875
	$('input[id^="chk_denonciation_anonyme_"]').click(function ()
	{	
		if ($(this).prop('checked'))
		{
			$(this).closest('p').next('p').find('.denonciateur').val('');
		}
	});

	//Modifié par EA le 03 01 2020 pou évol 875 //Je commente car marche sur les pieds d'un autre bout de code
	/*
	$('.denonciateur').change(function ()
	{
		if ($(this).val().trim() === '')
		{
			$(this).closest('p').prev('p').find('input[id^="chk_denonciation_anonyme_"]').prop('checked', true);
		}
		else
		{
			$(this).closest('p').prev('p').find('input[id^="chk_denonciation_anonyme_"]').prop('checked', false);
		}
	});*/

	/*** DENONCIATION ABANDON ***/
	//Modifié par EA le 03 01 2020 pour evol 875
    var nb_signalement = '<?php echo count($SIGNALEMENTS); ?>';
    
    for(var i=0; i<nb_signalement; i++)
    {
		denonciation_update_hidden_abandon(i);
    }

    //Modifié par EA le 03 01 2020 pour evol 875
	$('input[id^="checkbox_denonciation_abandon_"]').change(function()
	{
		var id = $(this).attr('id');
		var tableau = id.split('_');
		var numéro = tableau[3];
		denonciation_update_hidden_abandon(numéro);
	});
	
	/*** PARTI POLITIQUE ***/
	parti_politique_autocompletion('#parti_affich', src_parti_politique_json,true,'r2_1');
	
});

//Par EA le 13 12 2019 pour ano 560
//Contrôler la date date_debut_financement si elle est modifiée (ne concerne que les elections 'Partielles')
$('#date_debut_financement').on('change',function()
{
	//Nettoyage avant contrôles
	var elementDateDbtFinancement = document.getElementById("date_debut_financement");
	elementDateDbtFinancement.setCustomValidity(""); //On remet la case valide
	$('#msgRgDateDbtFinancement').text(''); //On efface la valeur de la zone de message au cas où elle en contiendrait une
	$('#msgRgDateDbtFinancement').attr('title',''); //idem = sécurité
	$('#msgRgDateDbtFinancement').css('border-color','none'); //On retire (eventuellement) la bordure rouge

	//Controle du FORMAT de la date
	if ($('#date_debut_financement').val().length > 0 && $('#date_debut_financement').val().length < 10)
	{
		$('#msgRgDateDbtFinancement').attr('title','Problème de format de date');	
		elementDateDbtFinancement.setCustomValidity("Problème de format de date.");
	}
	else if ($('#date_debut_financement').val().length == 10)
	{
		verification = date_validation($('#date_debut_financement').val());

		if (!verification)
		{
			$('#msgRgDateDbtFinancement').attr('title','La date est invalide.');
			elementDateDbtFinancement.setCustomValidity("La date est invalide.");
		}
	}

	//Contrôle de la PERTINENCE de la date
	if(elementDateDbtFinancement.checkValidity() == true) //Si aucun problème de format détécté plus haut, alors on peut tester la pertinence de la date de debut de financement
	{
		if($('#date_debut_financement').val() !== '')
		{
			var dateDebutFinancement = $(this).val();

			var dateDebutFinancementNouveauFormat = dateDebutFinancement.substr(6,4)+dateDebutFinancement.substr(3,2)+dateDebutFinancement.substr(0,2);
			var dateDebutFinancementNouveauFormatNumber = Number(dateDebutFinancementNouveauFormat);

			var dateT2Election = '<?php echo affiche_date_fr($DETAILS[$_GET['cand']]['date_2t']); ?>';
			var dateT2ElectionNouveauFormat = dateT2Election.substr(6,4)+dateT2Election.substr(3,2)+dateT2Election.substr(0,2);
			var dateT2ElectionNouveauFormatNumber = Number(dateT2ElectionNouveauFormat);

			var borneInferieure =  String(Number(dateT2Election.substr(6,4))-1)+dateT2Election.substr(3,2)+'01';
			var borneInferieureNumber = Number(borneInferieure);

			var dateT1Election = '<?php echo affiche_date_fr($DETAILS[$_GET['cand']]['date_1t']); ?>';
			var dateT1ElectionNouveauFormat = dateT1Election.substr(6,4)+dateT1Election.substr(3,2)+dateT1Election.substr(0,2);
			var dateT1ElectionNouveauFormatNumber = Number(dateT1ElectionNouveauFormat);

			if(dateDebutFinancementNouveauFormatNumber < borneInferieureNumber) //RG - La date de début de financement ne peut être inférieure à la date du 1er jour du 12e mois qui précède le mois du 2e tour de cette election partielle
			{
				$('#msgRgDateDbtFinancement').text(" La date de début de financement ne peut être inférieure à la date du 1er jour du 12e mois qui précède le mois du 2e tour de cette election");
				$(this).focus();
			}
			else if(dateDebutFinancementNouveauFormatNumber >= dateT1ElectionNouveauFormatNumber) //RG - La date de début de financement ne peut être supérieure ou égale à la date du 1er tour de l'election partielle considérée
			{
				$('#msgRgDateDbtFinancement').text(" La date de début de financement ne peut être supérieure ou égale à la date du 1er tour de l'election partielle considérée");
				$(this).focus();
			}
		}
	}
});

//Par EA le 26 12 2019 pour ano 979 // => Commenté par EA le 03 01 2020 car code obsolète
// $('#chk_denonciation').on('change',function()
// {
// 	if($('#chk_denonciation').val() == 0)
// 	{
// 		$('#checkbox_denonciation_abandon').removeProp('checked');
// 		$('#date_denonciation').val('');
// 		$('#denonciation_anonyme').removeProp('checked');
// 		$('#denonciateur').val('');
// 		$('#ged').val('');
// 	}
// });

//Par EA le 26 12 2019 pour ano 979 et evol 875
$('textarea[id^="denonciateur_"], input[id^="chk_denonciation_anonyme_"]').on('change',function()
{
	var idClique = $(this).attr('id');
	var tableau = idClique.split('_');
	var tableau2 = tableau.reverse();

	if (tableau2[0] == 'creation') //formulaire creation signalement
	{
		var anonymeElement = document.getElementById("chk_denonciation_anonyme_creation");
    	var denonciateurElement = document.getElementById("denonciateur_creation");

    	if(!$('#chk_denonciation_anonyme_creation').is(':checked')&&($('#denonciateur_creation').val() == ''))
    	{
			//Mettre msgs d'erreur
    		anonymeElement.setCustomValidity('une des deux cases doit être renseignée');
    		denonciateurElement.setCustomValidity('une des deux cases doit être renseignée');
    		$('#denonciation_anonyme_cand_span_creation').text('une des deux cases doit être renseignée');
    		$('#denonciateur_cand_span_creation').text('une des deux cases doit être renseignée');
    	}
    	else if($('#chk_denonciation_anonyme_creation').is(':checked')&&($('#denonciateur_creation').val() !== ''))
    	{
    		//Mettre tout OK
    		anonymeElement.setCustomValidity('');
    		denonciateurElement.setCustomValidity('');
    		$('#denonciation_anonyme_cand_span_creation').text('');
    		$('#denonciateur_cand_span_creation').text('');

    		//Décocher la case 'Anonyme'
    		$('#chk_denonciation_anonyme_creation').prop('checked', false);
    	}
    	else
    	{
    		//Mettre tout OK
    		anonymeElement.setCustomValidity('');
    		denonciateurElement.setCustomValidity('');
    		$('#denonciation_anonyme_cand_span_creation').text('');
    		$('#denonciateur_cand_span_creation').text('');
    	}
	}
	else //formulaire edition signalement
	{
    	var numeroDenonciation = tableau2[0];
    	var anonymeElement = document.getElementById("chk_denonciation_anonyme_"+numeroDenonciation);
    	var denonciateurElement = document.getElementById("denonciateur_"+numeroDenonciation);
    	
    	if(!$('#chk_denonciation_anonyme_'+numeroDenonciation).is(':checked')&&($('#denonciateur_'+numeroDenonciation).val() == ''))
    	{
    		//Mettre msgs d'erreur
    		anonymeElement.setCustomValidity('une des deux cases doit être renseignée');
    		denonciateurElement.setCustomValidity('une des deux cases doit être renseignée');
    		$('#denonciation_anonyme_cand_span_'+numeroDenonciation).text('une des deux cases doit être renseignée');
    		$('#denonciateur_cand_span_'+numeroDenonciation).text('une des deux cases doit être renseignée');
    	}
    	else if($('#chk_denonciation_anonyme_'+numeroDenonciation).is(':checked')&&($('#denonciateur_'+numeroDenonciation).val() !== ''))
    	{
			//Mettre tout OK
    		anonymeElement.setCustomValidity('');
    		denonciateurElement.setCustomValidity('');
    		$('#denonciation_anonyme_cand_span_'+numeroDenonciation).text('');
    		$('#denonciateur_cand_span_'+numeroDenonciation).text('');

    		//Décocher la case 'Anonyme'
    		$('#chk_denonciation_anonyme_'+numeroDenonciation).prop('checked', false);
    	}
    	else
    	{
    		//Mettre tout OK
    		anonymeElement.setCustomValidity('');
    		denonciateurElement.setCustomValidity('');
    		$('#denonciation_anonyme_cand_span_'+numeroDenonciation).text('');
    		$('#denonciateur_cand_span_'+numeroDenonciation).text('');
    	}
	}
});

// -------------------------------- DEBUT AFFICHAGE DES LISTES --------------------------------
<?php   if ($DETAILS[$_GET['cand']]['id_liste'] != null) { ?>

// Tableau de la liste du 1er tour
var _liste_1_data = <?php echo tableau_liste_json($conn, $_GET['cand'], 1); ?>;

var _table_liste_1 = new Tabulator("#liste_tour_1", {
    data:_liste_1_data,
    columns:[
{title:"Liste 1er tour",		field:"nom_liste"},
{title:"Tour",					field:"tour", visible:false, download:true},
{title:"Civ.",					field:"abrege_civ"},
{title:"Prénom",				field:"prenom_colistier"},
{title:"Nom",					field:"nom_colistier"},
{title:"Date de<br/>naissance",	field:"date_naissance",	formatter:"datetime",	downloadTitle:"Date de naissance",	formatterParams:{inputFormat:"YYYY-MM-DD", outputFormat:"DD/MM/YYYY"}},
{title:"Lieu de<br/>naissance",	field:"lieu_naissance",		downloadTitle:"Lieu de naissance"},
{title:"Elu",					field:"chk_elu",		formatter:"tickCross", align:"center"},
{title:"Ordre sur<br/>la liste",field:"ordre_colistier",	downloadTitle:"Ordre sur la liste"}
	]});

//Téléchargement du fichier en XLSX
$("#download_liste_1_xlsx").click(function(){
	_table_liste_1.download("xlsx", "Liste_<?= $_GET['cand'] ?>_tour_1.xlsx", {sheetName:"liste 1er tour"});
});

<?php       if ($DETAILS[$_GET['cand']]['id_liste_2t'] != null) { ?>

//Tableau de la liste du 2eme tour
var _liste_2_data = <?php echo tableau_liste_json($conn, $_GET['cand'], 2); ?>;

var _table_liste_2 = new Tabulator("#liste_tour_2", {
    data:_liste_2_data,
    columns:[
{title:"Liste 2em tour",		field:"nom_liste"},
{title:"Tour",					field:"tour", visible:false, download:true},
{title:"Civ.",					field:"abrege_civ"},
{title:"Prénom",				field:"prenom_colistier"},
{title:"Nom",					field:"nom_colistier"},
{title:"Date de<br/>naissance",	field:"date_naissance",	formatter:"datetime",	downloadTitle:"Date de naissance", 	formatterParams:{inputFormat:"YYYY-MM-DD", outputFormat:"DD/MM/YYYY"}},
{title:"Lieu de<br/>naissance",	field:"lieu_naissance",		downloadTitle:"Lieu de naissance"},
{title:"Elu",					field:"chk_elu",		formatter:"tickCross", align:"center"},
{title:"Ordre sur<br/>la liste",field:"ordre_colistier",	downloadTitle:"Ordre sur la liste"}
	]});

//Téléchargement du fichier en XLSX
$("#download_liste_2_xlsx").click(function(){
	_table_liste_2.download("xlsx", "Liste_<?= $_GET['cand'] ?>_tour_2.xlsx", {sheetName:"liste 2em tour"});
});

<?php
           }
	   }
?>
// -------------------------------- FIN AFFICHAGE DES LISTES --------------------------------

</script>


</div> <!-- // fin du main -->
</div> <!-- // fin du frame -->

<?php

// if ((!isset($_SERVER['HTTP_X_REQUESTED_WITH']) or $_SERVER['HTTP_X_REQUESTED_WITH'] != "XMLHttpRequest") and !isset($_GET['aj'])) {
//    	
//    $DEBUG_alerte = "Ce n'est pas de l'ajax !!!!!";
//    
//    echo "\n<script>\n";
//    echo "$.getScript('js/jquery.affichage_fieldset_h5.js');\n";
//    echo "</script>\n";
//    
//    	
// 
//    	
// }

require("structure/pied.php");

?>
