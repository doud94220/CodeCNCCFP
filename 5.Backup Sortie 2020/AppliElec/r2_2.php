<?php

$TITRE_HEAD = "Mandataire actif - informations";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
   	
   $DEBUG_alerte = "C'est de l'ajax !!!!!";
   	
   require("structure/requires.php");
   	
} else {
   
   	
   	$DEBUG_alerte = "CE N'EST PAS de l'ajax !!!!!";
   	require("structure/requires.php");
	require("structure/head.php");
}

// require_once("fonctions/FONCTION_FORMULAIRES_ANNEXES.php");
echo "<div id=\"frame\">\n";

	// nb. require suivant ouvre <nav> :
	require("structure/navigation.php"); // affichage entête et navigation
	// nb. require précédent referme </nav>

	echo "<div id=\"entete\">\n";
	
	require("structure/entete.php"); // affichage entête (candidat ici)
	
	echo "<div class=\"br\">&nbsp;</div>\n";
	
	echo "</div>\n";
// bloc verifier rapport =======================================================	
require("fonctions/verif_rapport_termine.php");
require_once("fonctions/droit_utilisateur_page.php");

$hide_bouton_enregister = "";

if(verif_rapport_termine($conn, $DETAILS[$_GET['cand']]['id_compte'],"") == 1 and $_SESSION['id_type_util'] == 4)
{
	$hide_bouton_enregister = 'style="display:none;"';
}

//=============================================================================
	echo "<div id=\"main\">";
	echo "<h1 id=\"titre\"></h1>";

//Déterminer si le rapport est clos
$rapportClos = 'non';

$sql_date_rapport = "
					SELECT date_cloture_rapport
					FROM [ELEC].[dbo].[rapport]
					where id_compte = ".$DETAILS[$_GET['cand']]['id_compte']
					;

$reqRapportClos = sqlsrv_query($conn, $sql_date_rapport);

if ($reqRapportClos === false)
{
	header("Content-Type: text/json");
	echo '{"success":false,"message":"Erreur de requête"}';	
	exit;
}

$dateClotureRapportArray = sqlsrv_fetch_array($reqRapportClos);
$dateClotureRapport = $dateClotureRapportArray[0];

if(isset($dateClotureRapport))
{
	$rapportClos = 'oui';
}

//$messageMandataireNonActif = ''; //Commentée par EA le 28 11 2018 car on n'a plus de date de fin de validité pour le mand actif

//========= Recuperation de tous les mandataires : l'actif et les antérieurs

$sql = "
	 	SELECT *
		FROM [ELEC].[dbo].[mandataire] AS MANDATAIRE
		INNER JOIN [ELEC].[dbo].[candidat_mandataire] AS MAPPING
		ON MANDATAIRE.id_mandataire = MAPPING.id_mandataire
		WHERE MAPPING.id_candidat = '".$DETAILS[$_GET['cand']]['id_compte']."'";

$req = sqlsrv_query($conn, $sql);

if ($req === false)
{
	header("Content-Type: text/json");
	echo '{"success":false,"message":"Erreur de requête"}';	
	exit;
}

$MANDATAIRES = array();

if(sqlsrv_has_rows($req))
{
	while($unMandataire = sqlsrv_fetch_array($req))
	{
		array_push($MANDATAIRES, $unMandataire);
	}
}

//========= Séparer le mandataire actif des mandataires anterieurs

//Initialisation de variables
$MANDATAIRE_ACTIF = array();
$MANDATAIRES_ANTERIEURS = array();
$i = 0;

//Détermination du mandataire actif et alimentation de l'array de mandataires antérieurs
for($i=0;$i<count($MANDATAIRES);$i++)
{
	if($MANDATAIRES[$i]['periodicite_mandataire'] == 'actif')
	{
		$MANDATAIRE_ACTIF = $MANDATAIRES[$i];
	}
	else
	{
		array_push($MANDATAIRES_ANTERIEURS, $MANDATAIRES[$i]);
	}
}

//Récupérer la date de fin de validité la plus récente de tous les mandataires antérieurs, et la stocker pour le code javascript
$sql_date_fin_validite_mandataires_anterieurs = "
												select top 1 date_fin_validite
												from candidat_mandataire
												where id_candidat = ".$DETAILS[$_GET['cand']]['id_compte']." 
												and periodicite_mandataire = 'anterieur'
												order by date_fin_validite desc
												";

$req_date_fin_validite_mandataires_anterieurs  = sqlsrv_query($conn, $sql_date_fin_validite_mandataires_anterieurs);

if($req_date_fin_validite_mandataires_anterieurs === false)
{
	die(print_r(sqlsrv_errors(), true));	
}

$dateFinValiditeLaPlusRecenteArray = [];
$dateFinValiditeLaPlusRecenteString = '';
	
if(sqlsrv_has_rows($req_date_fin_validite_mandataires_anterieurs))
{
	$dateFinValiditeLaPlusRecenteArray = sqlsrv_fetch_array($req_date_fin_validite_mandataires_anterieurs);
	$dateFinValiditeLaPlusRecenteString = $dateFinValiditeLaPlusRecenteArray[0]->format('Ymd'); //Recupération de la date
}

////Récupérer les dates de debut et fin de validite de tous les mandataires antérieurs, et les stocker dans un array pour le code javascript

//Récupérer les dates de début de validité et de fin de validité de tous les mandataires antérieurs du candidat (et nom prenom du mandataire pour le nommer si conflit)
$sql_dates_validite = "
						select mapping.date_debut_validite, mapping.date_fin_validite, mand.prenom_mf, mand.nom_mf
						from candidat_mandataire as mapping
						inner join mandataire as mand on mand.id_mandataire = mapping.id_mandataire
						where mapping.periodicite_mandataire = 'anterieur'
						and mapping.id_candidat = ".$DETAILS[$_GET['cand']]['id_compte']
					  ;

$req_dates_validite = sqlsrv_query($conn, $sql_dates_validite);
		
if($req_dates_validite === false)
{
	die(print_r(sqlsrv_errors(), true));
}

$arrayDatesValiditeTousMandataires = [];
		
if(sqlsrv_has_rows($req_dates_validite))
{			
	while($datesDeValiditeMandataireEnCours = sqlsrv_fetch_array($req_dates_validite, SQLSRV_FETCH_ASSOC))
	{
		//Convertir date debut validite de Date en String
		$datesDeValiditeMandataireEnCours['date_debut_validite'] = $datesDeValiditeMandataireEnCours['date_debut_validite']->format('Ymd');
		$datesDeValiditeMandataireEnCours['date_fin_validite'] = $datesDeValiditeMandataireEnCours['date_fin_validite']->format('Ymd');
		array_push($arrayDatesValiditeTousMandataires, $datesDeValiditeMandataireEnCours);
	}
}

//Encoder en JASON le tableau PHP pour que le code JS puisse l'exploiter
$arrayDatesValiditeTousMandataires_json = json_encode($arrayDatesValiditeTousMandataires);

//Recupérer la date de dépôt du compte pour le code JS
$dateDepotComptePlusSixMois_string = '';

if(isset($DETAILS[$_GET['cand']]['date_depot_cpte']))
{
	$dateDepotCompte_date = new DateTime(substr($DETAILS[$_GET['cand']]['date_depot_cpte']['date'],0,10));
	$dateDepotComptePlusSixMois_string = $dateDepotCompte_date->modify('+6 month')->format('Ymd');
}

//Initialisation de variables pour qu'il n'y ai pas d'erreur de compilation du fichier si zero mandataire
$datePremierTourElecMoinsSixMois_string = '';
$date_pericles_existe = 0;
$date_1t_compare = new DateTime();
$date_declaration_mf_min = new DateTime();
$date_declaration_mf_min_slash__date = new DateTime();
$date_declaration_mf_min_slash = $date_declaration_mf_min_slash__date->format('Ymd');

$presenceMandataireActif = false;

////Afficher le mandataire actif (s'il y en a un)
if(count($MANDATAIRE_ACTIF) > 1)
{
	$presenceMandataireActif = true;
?>

<form id="formMandActif" class="formulaireMandataire formulaire" name="r2_2" action="r2_2_ajax.php" method="post">

	<input type="hidden" name="type_mandataire_actif" value="actif" />
    <input type="hidden" name="annee_actif" value="<?php echo $DETAILS[0]['annee']; ?>" />
    <input type="hidden" name="id_mandataire_actif" value="<?php echo $MANDATAIRE_ACTIF['id_mandataire']; ?>" />
    <input type="hidden" name="id_scrutin_actif" value="<?php echo $DETAILS[0]['id_scrutin']; ?>" />
    <input type="hidden" name="id_candidat_actif" value="<?php echo $DETAILS[$_GET['cand']]['id_candidat']; ?>" />
    <input type="hidden" name="id_suppleant_actif" value="" />
    <input type="hidden" name="id_expert_actif" value="<?php echo adapte_fkey_null($DETAILS[$_GET['cand']]['id_expert']); ?>" />
    
<!-- checkbox MF ou AF -->

	<?php 

	$checked_mf = "";
	$checked_af = "";

	//Préparation de variables pour mandataire financier ou non
	if ($MANDATAIRE_ACTIF['qualite'] == 'MF')
	{
	 $checked_mf = " checked=\"checked\"";
	}
	else
	{
		$checked_af = " checked=\"checked\"";
	}

	//Préparation de variables pour présence mandataire, irrégularité mandataire, commentaire controle mandataire
	$presence_mandataire_chk1 = "";
	$presence_mandataire_chk0 = "";
	$chk_irregularite1 = "";
	$chk_irregularite0 = "";
	$commentaire_irregularite = "none";
	$requis = false;

	if($DETAILS[$_GET['cand']]['chk_presence_mandataire'] == 1)
	{
		$presence_mandataire_chk1 = "checked";
	}
	elseif($DETAILS[$_GET['cand']]['chk_presence_mandataire'] == 0)
	{
		$presence_mandataire_chk0 = "checked";
	}

	if($MANDATAIRE_ACTIF['chk_irregularite_mandataire'] == 1)
	{
		$chk_irregularite1 = "checked";
		$commentaire_irregularite = "block";
		$requis = true;
	}
	elseif($MANDATAIRE_ACTIF['chk_irregularite_mandataire'] == 0)
	{
		$chk_irregularite0 = "checked";
	}

	$txtArea_iregularite = $MANDATAIRE_ACTIF['commentaire_controle_mandataire'];

	//Règle de gestion "Alerter l'utilisateur lorsque la présence mandataire (du mandataire actif) est oui mais que la période de fin validité (du mandataire actif toujours) se termine avant la date où l'élection a été acquise"
	if ($presence_mandataire_chk1 == "checked")
	{
		//Recuperation des dates des 1er et 2eme tout du scrutin de l'élection
		$scrutin = $DETAILS[0]['id_scrutin'];
	}

	if (is_a($DETAILS[0]['date_1t'], "DateTime")) {
	
		$date_1t_compare = $DETAILS[0]['date_1t'];
	
	} else {

		$date_1t_compare = new DateTime(substr($DETAILS[0]['date_1t']['date'],0,10));
	}

	$date_actuelle = new DateTime(date('Y-m-d'));

	$date_pericles_existe = 1;

	$date_declaration_mf_min = clone $date_1t_compare;
	$date_declaration_mf_min->sub(new DateInterval('P12M'));

	if (is_a($DETAILS[$_GET['cand']]['date_declaration_mf'], "DateTime")) {
	
		$date_declaration_mf = $DETAILS[$_GET['cand']]['date_declaration_mf'];
	
	}
	else
	{
			if ($DETAILS[$_GET['cand']]['date_declaration_mf']['date'] == "") {
		
				$date_pericles_existe = 0;
		
			}
		
			$date_declaration_mf = new DateTime(substr($DETAILS[$_GET['cand']]['date_declaration_mf']['date'],0,10));
	}

	$date_declaration_mf_slash = affiche_date_fr($DETAILS[$_GET['cand']]['date_declaration_mf']);

	$bad_date_declaration_mf = 0;

	if ($date_declaration_mf < $date_declaration_mf_min OR $date_declaration_mf > $date_1t_compare) {

		$bad_date_declaration_mf = 1;
	
	}

	$date_declaration_mf_min_slash = affiche_date_fr($date_declaration_mf_min);

	if (isset($DETAILS[0]['date_2t'])) {

		if (is_a($DETAILS[0]['date_2t'], "DateTime")) {
	
			$date_2t_compare = $DETAILS[0]['date_2t'];
	
		} else {

			$date_2t_compare = new DateTime(substr($DETAILS[0]['date_1t']['date'],0,10));
		}
	
		$dernier_tour = $date_2t_compare;

	}
	else
	{
		$dernier_tour = $date_1t_compare;
	}

	//Commentée par EA le 28 11 2018 car on n'a plus de date de fin de validité pour le mand actif
	// $dernierTourPlusTroisMois = $dernier_tour->modify('+3 month');
	// $_SESSION['date_dernier_tour_plus_trois_mois'] = $dernierTourPlusTroisMois->format('Ymd'); //Servira pour le code JS
	
	// if($MANDATAIRE_ACTIF['date_fin_validite'] < $dernierTourPlusTroisMois)
	// {
	// 	$messageMandataireNonActif = 'Attention : la présence du mandataire est cochée, pourtant la date de fin de validité du mandataire actif est inférieure à la date du dernier tour de l\'election plus 3 mois';
	// }

	//Récupération de la date acquisition de l'election + 6 mois et la date du jour
	//$dateAcquisitionElection = $dernier_tour->modify('+3 month');
	//$dateAcquisitionElectionPlusSixMois = $dateAcquisitionElection->modify('+6 month')->format('Ymd'); //Servira pour le code JS

	//Pour code JS pour RG sur date de debut de validite
	$dateTempo = clone $date_1t_compare;
	$datePremierTourElecMoinsSixMois_string = $dateTempo->modify('-6 month')->format('Ymd');
?>

<div>
	<fieldset>
		<legend>Activité</legend>
		<p>
			<label for="periodicite_mandataire_actif"> Statut du mandataire : </label>
			<input type="text" required="required" name="periodicite_mandataire_actif" size='10' maxlength='10' value='actif' readonly='readonly'>
		 </p>
		<p>
			<!--<label for="date_debut_validite_actif"> Date de début de validité : </label>-->
			<input type="hidden" id='date_dbt_validite_actif' type="text" required="required" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size="15" maxlength="15" autocomplete="off" name="date_debut_validite_actif" value="<?php echo (isset($MANDATAIRE_ACTIF['date_debut_validite'])) ? $MANDATAIRE_ACTIF['date_debut_validite']->format('d/m/Y') : ''; ?>"/>
			<!--<span id='msgDatesValiditeMandataireActif' class='msgDateValidite' style="color:red" title=""></span>-->
		</p>
	</fieldset>

	<fieldset>
		<legend>Déclaration</legend>
	
		<p><label for="date_declaration_mf_actif"> Date de déclaration en préfecture : </label> <input type="text" id="date_declaration_mf_actif" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size="15" maxlength="15" autocomplete="off" name="date_declaration_mf_actif" value="<?php 
		
		echo affiche_date_fr($MANDATAIRE_ACTIF['date_declaration_mf']);
		
		?>" /> <span class="changement_expert" id="date_declaration_mf_span_actif" title="La date doit être au format jj/mm/aaaa"></span></p>
					
	</fieldset>

	<fieldset>
        <legend>Contrôles mandataire</legend> 
		
		
		<p><span class="label">Présence d'un mandataire :</span>
        <label for="chk_presence_mandataire1_actif" class="oui_non">Oui</label> 
        <input type="radio" id="chk_presence_mandataire1_actif" name="chk_presence_mandataire_actif" value="1" <?php echo $presence_mandataire_chk1; ?> />
        <label for="chk_presence_mandataire0_actif" class="oui_non">Non</label>
        <input type="radio" id="chk_presence_mandataire0_actif" name="chk_presence_mandataire_actif" value="0" <?php echo $presence_mandataire_chk0; ?>/>
        <span class="messagePresenceMandataire" style="color:red;" title="Réponse attendue !"></span>
        <?php
			//// Commenté par EA le 28 11 2018 car plus de date de fin de validité pour mand actif
			// if(isset($messageMandataireNonActif))
			// {
			// 	echo '<span style="color:red;">'.$messageMandataireNonActif.'</span>';
			// }
		?>
        
        </p> 
		
		<p id="saisie_irregularite_controle_actif"><span class="label">Irrégularité :</span>
        <label for="chk_irregularite1_actif" class="oui_non">Oui</label> 
        <input type="radio" class="chk_irregularite1" name="chk_irregularite_mandataire_actif" value="1" <?php echo $chk_irregularite1; ?> />
        <label for="chk_irregularite0_actif" class="oui_non">Non</label>
        <input type="radio" class="chk_irregularite0" name="chk_irregularite_mandataire_actif" value="0" <?php echo $chk_irregularite0; ?>/>
        <span title="Réponse attendue !"></span>
        </p> 		
	     <p class="saisie_commentaire_controle" style="display:<?php echo $commentaire_irregularite; ?>">
			<label for="commentaire_controle_mandataire_actif">Commentaires sur l'irrégularité : </label> 
		
		<?php 
			if($requis)
			{
		?>
			<textarea required class="commentaire_controle_mandataire" name="commentaire_controle_mandataire_actif" rows="3" cols="50" autocomplete="off"><?php echo $txtArea_iregularite; ?></textarea>
			</p>
		<?php	
			}
			else
			{
		?>
			<textarea  class="commentaire_controle_mandataire"  name="commentaire_controle_mandataire_actif"  rows="3" cols="50" autocomplete="off"><?php echo $txtArea_iregularite; ?></textarea>
			</p>
		<?php
			}		
		?>
		<p style="color:red;" class="msgIrregulNonPrecise"></p>
	</fieldset>

	<fieldset>
        <legend>Qualité</legend>    
	    	<p>
	    		<label for="qualite1_actif"> Mandataire financier</label>
				<input type="radio" name="qualite_actif" class="qualitemf" value="MF" <?php echo $checked_mf; ?>>
			</p>
	    	<p>
	    		<label for="qualite1_actif"> Association de financement</label>
				<input type="radio" name="qualite_actif" class="qualiteaf" value="AF" <?php echo $checked_af; ?>>
			</p>
		</fieldset>
	    	<!-- Affichage ou non du RNA géré en jQuery -->
	    <fieldset class="rna">
	        <legend>RNA</legend>    
	    	<p>
	    		<label for="rna_actif"> RNA : </label>
				<input type="text" name="rna_af_actif" size='10' maxlength='10' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['rna_af']; ?>"/>
				<span title="Le RNA est composé d'un W suivi de neuf chiffres"></span>
			</p>
	</fieldset>
</div>
  
<!-- Adapter les intitulés selon MF ou AF coché -->    
   
<div class="etatcivil">
    <fieldset>
        <legend>Identité<em class="libelle_pdt_af"> du président de l'association</em></legend>
        <p><label for="id_civ_mf_actif"> Civilité :</label> <?php echo combo_dynamique('civilite','id_civ_mf_actif','id_civilite','libelle_civ', $MANDATAIRE_ACTIF['id_civ_mf'],'','','','required',''); ?>
        </p>
        <p><label for="nom_mf_actif"> Nom :</label>
	        <input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" id="nom_mf_actif" name="nom_mf_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['nom_mf']; ?>" />
        <span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
        </p>
        <p><label for="prenom_mf_actif"> Prénom :</label>
	        <input type="text" required="required"  pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" id="prenom_mf_actif" name="prenom_mf_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['prenom_mf']; ?>" />
        <span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
        </p>
    </fieldset>
</div>

<?php /*
    $mandataireEstColistier = false; //Valeur par défault

    //On regarde si le mandataire actif est aussi colistier
    $sql = "select nom_colistier, prenom_colistier
            from colistier
            inner join liste on colistier.id_liste = liste.id_liste
            inner join candidat on candidat.id_candidat = liste.id_candidat
            where candidat.id_candidat = ".$DETAILS[$_GET['cand']]['id_candidat']."
            and nom_colistier = '".escape_quote($MANDATAIRE_ACTIF['nom_mf'])."'
            and prenom_colistier = '".escape_quote($MANDATAIRE_ACTIF['prenom_mf'])."'
            ";
 
    $req = sqlsrv_query($conn, $sql);
    
    if ($req === false)
    {
        header("Content-Type: text/json");
        echo $sql;
        echo '{"success":false,"message":"Erreur sur la requête qui vérifie si le mandataire actif est aussi colistier"}';
        exit;
    }
    
    if(sqlsrv_has_rows($req)) //Le mandataire actif est colistier, sauf si c'est en fait un homonyme du colistier, ce qu'on va vérifier
    {
        $sql_homonyme = "select homonyme_colistier
                         from mandataire
                         where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];
        
        $req_homonyme = sqlsrv_query($conn, $sql_homonyme);
        
        if ($req_homonyme === false)
        {
            header("Content-Type: text/json");
            echo '{"success":false,"message":"Erreur sur la requête qui vérifie si le mandataire actif est un homonyme d\'un colistier de la liste"}';
            exit;
        }
        
        $id_candidat_array = sqlsrv_fetch_array($req_homonyme);
        
        //Si le numcand stocké en base est le même que le numcand associé au mandataire qu'on consulte, alors le mandataire est un homonyme d'un colistier. Donc ce n'est pas un colistier
        //Dans le cas contraire (cas du if ci-dessous), le mandataire est colistier
        if (intval($id_candidat_array[0]) !== $DETAILS[$_GET['cand']]['id_candidat'])
        {
            $mandataireEstColistier = true;
        }
    }*/
?>

<span id="messageMandataireColistier" style="color:red">
	<?php /*if ($mandataireEstColistier == true)
	      {
	          echo "ATTENTION, ce mandataire est également colistier d'une liste du scrutin...";
	          
	          $sql_voir_si_alerte = "  select alerte_mandataire_colistier
                                       from mandataire
                                       where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];
	          
	          $req_voir_si_alerte = sqlsrv_query($conn, $sql_voir_si_alerte);
	          
	          if ($req_voir_si_alerte === false)
	          {
	              header("Content-Type: text/json");
	              echo '{"success":false,"message":"Erreur sur la requête qui vérifie si l\'alerte mandataire = colistier est déjà présente"}';
	              exit;
	          }
	          
	          $voir_si_alerte_array = sqlsrv_fetch_array($req_voir_si_alerte);
	          
	          $alerte_deja_presente = false; //Initialisation
	          
	          if($voir_si_alerte_array[0] == 1)
	          {
	              $alerte_deja_presente = true;
	          }
	          
	          if($alerte_deja_presente == false) //L'alerte n'est pas renseignée, il faut aller renseigner l'irrégularité en base
	          {
	              //Récupérer le commentaire de l'irrégularité
	              $sql_get_comment = "select commentaire_controle_mandataire
                                      from mandataire
                                      where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];
	              
	              $req_get_comment = sqlsrv_query($conn, $sql_get_comment);
	              
	              if ($req_get_comment === false)
	              {
	                  header("Content-Type: text/json");
	                  echo '{"success":false,"message":"Erreur sur la requête qui récupère le commentaire de l\'irregularite"}';
	                  exit;
	              }
	              
	              $commentaire_array = sqlsrv_fetch_array($req_get_comment);
	              
	              //Création du nouveau commentaire
	              $new_comment = ''; //Initialisation
	              
	              if($commentaire_array[0] == '')
	              {
	                  $new_comment = "Ce mandataire est également colistier d une liste du scrutin.";
	              }
	              else
	              {
	                  $new_comment = $commentaire_array[0]." Ce mandataire est également colistier d une liste du scrutin.";
	              }
	              
	              //Requête de maj des champs liés à l'irrégularité
	              $sql_update = "
                                    update mandataire
                                    set commentaire_controle_mandataire = '".$new_comment."', chk_irregularite_mandataire = 1, alerte_mandataire_colistier = 1
                                    where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];

	              $req_update = sqlsrv_query($conn, $sql_update);
	              
	              if ($req_update === false)
	              {
	                  header("Content-Type: text/json");
	                  echo '{"success":false,"message":"Erreur sur la requête qui met à jour les informations d\'irrégularité en déclarant que MF = colistier"}';
	                  exit;
	              }
	              
	              ?>
	             		<script>
	             				window.location.reload();
	             		</script>
	             <?php
	              
	          }
	      }
	      else //Vérifier s'il y a un reliquat d'irrégularité de type "colistier = MF" à retirer
	      {
	          //Récupérer les infos d'irrégularité
	          $sql_get_irregul = "select chk_irregularite_mandataire, commentaire_controle_mandataire
                                  from mandataire
                                  where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];
	          
	          $req_get_irregul = sqlsrv_query($conn, $sql_get_irregul);
	          
	          if ($req_get_irregul === false)
	          {
	              header("Content-Type: text/json");
	              echo '{"success":false,"message":"Erreur sur la requête qui récupère les infos sur l\'irregularite"}';
	              exit;
	          }
	          
	          $irregul_array = sqlsrv_fetch_array($req_get_irregul);
	          
	          if ($irregul_array[0] == 1) //il y a potentiellement une irregul de type "colistier = MF" à retirer
	          {
	              //S'il y a une irregularité de type "colistier = MF"
	              if(strpos($irregul_array[1],"Ce mandataire est également colistier d une liste du scrutin.") !== false)
	              {
	                  if($irregul_array[1] == "Ce mandataire est également colistier d une liste du scrutin.")
	                  {
	                      $sql_retirer_irregul = "update mandataire
                                  set chk_irregularite_mandataire = 0, commentaire_controle_mandataire = '', alerte_mandataire_colistier = 0
                                  where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];
	                      
	                      $req_retirer_irregul = sqlsrv_query($conn, $sql_retirer_irregul);

	                      if ($req_retirer_irregul === false)
	                      {
	                          header("Content-Type: text/json");
	                          echo '{"success":false,"message":"Erreur sur la requête qui retire les informations d\'irrégularité"}';
	                          exit;
	                      }
	                  }
	                  else
	                  {
	                      $new_comment = str_replace("Ce mandataire est également colistier d une liste du scrutin.", "", $irregul_array[1]);
	                      
	                      $sql_modif_comment = "update mandataire
                                                set commentaire_controle_mandataire = '".$new_comment."' , alerte_mandataire_colistier = 0
                                                where id_mandataire = ".$MANDATAIRE_ACTIF['id_mandataire'];
	                      
	                      $req_modif_comment = sqlsrv_query($conn, $sql_modif_comment);
	                      
	                      if ($req_modif_comment === false)
	                      {
	                          header("Content-Type: text/json");
	                          echo '{"success":false,"message":"Erreur sur la requête qui retire allège le commentaire"}';
	                          exit;
	                      }
	                  }
	                  
	                  ?>
	                  
	                 	 	<script>
	                 	 		window.location.reload();
	                 	 	</script>
	                  
	                  <?php
	              }
	          }
	      }
*/	?>
</span>
<?php /* if ($mandataireEstColistier == true)
      {
          echo "<button id='retirerAlerte' type='button'>Retirer l'alerte</button>";
      }*/
?>
    
<div class="adresse">
    <fieldset>
        <legend>Coordonnées<em class="libelle_pdt_af"> du président de l'association</em></legend>
        <p><label for="adresse1_mf_actif"> Adresse :</label>
        <input type="text" id="adresse1_mf_actif" name="adresse1_mf_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse1_mf']; ?>" /></p>
        <p><label for="adresse2_mf_actif"> Adresse (suite) :</label>
        <input type="text" id="adresse2_mf_actif" name="adresse2_mf_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse2_mf']; ?>" />
        </p>

        <p><label for="adresse3_mf_actif">Adresse (suite 2) :</label>
		<input type="text" id="adresse3_mf_actif" name="adresse3_mf_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse3_mf']; ?>" /></p>

        <p><label for="adresse4_mf_actif">Adresse (suite 3) :</label>
		<input type="text" id="adresse4_mf_actif" name="adresse4_mf_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse4_mf']; ?>" /></p>

        <p><label for="cp_mf_actif">Code postal :</label>
		<input type="text" required="required" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_mf_actif" name="cp_mf_actif" size='12' maxlength='12' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['cp_mf']; ?>" />
        <span title="5 chiffres pour la France. Code libre pour l'étranger après avoir renseigné le pays."></span>
        </p>
        
        <p><label for="ville_mf_actif">Ville :</label>
		<input required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,60}" type="text" class="pattern_texte_2_80"  id="ville_mf_actif" name="ville_mf_actif" size='40' maxlength='80' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['ville_mf']; ?>" />
		<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>
		

        <p><label for="pays_mf_actif">Pays :</label>
		<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,80}" placeholder="" id="pays_mf_actif" name="pays_mf_actif" size='40' maxlength='80' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['pays_mf']; ?>" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>

        <p><label for="telephone1_mf_actif">Téléphone 1 :</label>
		<input type="text" placeholder="" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telephone1_mf_actif" name="telephone1_mf_actif" size='25' maxlength='25' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['telephone1_mf']; ?>" />
		<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

        <p><label for="telephone2_mf_actif">Téléphone 2 :</label>
		<input type="text" placeholder="" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telephone2_mf_actif" name="telephone2_mf_actif" size='25' maxlength='25' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['telephone2_mf']; ?>" />
		<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

        <p><label for="telecopie_mf_actif">Fax :</label>
		<input type="text" placeholder="" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telecopie_mf_actif" name="telecopie_mf_actif" size='25' maxlength='25' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['telecopie_mf']; ?>" />
		<span title="Numéro incorrect. Les chiffres, le signe +, le point ou les espaces sont acceptés."></span></p>

        <p><label for="mail_mf_actif">Courriel :</label>
		<input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_mf_actif" name="mail_mf_actif" size='50' maxlength='50' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['mail_mf']; ?>" />
		<span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>
    </fieldset>
</div>
<!-- </fieldset> -->
<!-- suite formulaire commun -->   

<div class="banque">
    <fieldset>
        <legend>Relevé d'identité bancaire</legend>

        <p><label for="libelle_compte_bq_actif"> Libellé du compte :</label>
		<input type="text" id="libelle_compte_bq_actif" name="libelle_compte_bq_actif" size='60' maxlength='120' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['libelle_compte_bq']; ?>" /></p>

        <p><label for="nom_banque_actif"> Nom de la banque :</label>
		<input type="text" id="nom_banque_actif" name="nom_banque_actif" size='50' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['nom_banque']; ?>" /></p>

        <p><label for="num_compte_actif"> Numéro de compte :</label>
		<input type="text" id="num_compte_actif" name="num_compte_actif" size='50' maxlength='50' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['num_compte']; ?>" /></p>
        
        <p><label for="nom_agence_actif">Nom de l'agence :</label>
		<input type="text" id="nom_agence_actif" name="nom_agence_actif" size='50' maxlength='70' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['nom_agence']; ?>" /></p>

        <p><label for="adresse1_agence_actif">Adresse :</label>
		<input type="text" id="adresse1_agence_actif" name="adresse1_agence_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse1_agence']; ?>" /></p>

        <p><label for="adresse2_agence_actif"> Adresse (suite) :</label>
		<input type="text" id="adresse2_agence_actif" name="adresse2_agence_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse2_agence']; ?>" /></p>

        <p><label for="adresse3_agence_actif">Adresse (suite 2) :</label>
		<input type="text" id="adresse3_agence_actif" name="adresse3_agence_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse3_agence']; ?>" /></p>

        <p><label for="adresse4_agence_actif">Adresse (suite 3) :</label>
		<input type="text" id="adresse4_agence_actif" name="adresse4_agence_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse4_agence']; ?>" /></p>

        <p><label for="cp_agence_actif">Code postal :</label>
		<input type="text" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_agence_actif" name="cp_agence_actif" size='12' maxlength='12' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['cp_agence']; ?>" />
        <span title="5 chiffres pour la France, code libre pour les autres pays"></span>
        </p>

        <p><label for="ville_agence_actif">Ville :</label>
		<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" id="ville_agence_actif" name="ville_agence_actif" size='40' maxlength='80' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['ville_agence']; ?>" />
        <span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
        </p>

        <p><label for="pays_agence_actif"> Pays :</label>
		<input type="text" id="pays_agence_actif" name="pays_agence_actif" size="40" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" maxlength="80" autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['pays_agence']; ?>" />
		<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
		</p>

    </fieldset>
</div>

<div class="adresse adresse_af" id="adresse_af_actif">
    <fieldset>

        <legend>Coordonnées de l'association de financement</legend>
        <p><label for="adresse1_af_actif"> Nom de l'association de financement :</label> <textarea id="nom_af_actif" name="nom_af_actif" rows="3" cols="50"><?php echo $MANDATAIRE_ACTIF['nom_af']; ?></textarea></p>
        
        <p><label for="adresse1_af_actif">Adresse :</label>
        <input type="text" id="adresse1_af_actif" name="adresse1_af_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse1_af']; ?>" /></p>
        <p><label for="adresse2_af_actif">Adresse (suite) :</label>
		<input type="text" id="adresse2_af_actif" name="adresse2_af_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse2_af']; ?>" /></p>

        <p><label for="adresse3_af_actif">Adresse (suite 2) :</label>
        <input type="text" id="adresse3_af_actif" name="adresse3_af_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse3_af']; ?>" /></p>

        <p><label for="adresse4_af_actif">Adresse (suite 3) :</label>
        <input type="text" id="adresse4_af_actif" name="adresse4_af_actif" size='40' maxlength='60' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['adresse4_af']; ?>" /></p>

        <p><label for="cp_af_actif">Code postal :</label>
        <input type="text" pattern="[A-Z0-9\- ]{2,15}" placeholder="" id="cp_af_actif" name="cp_af_actif" size='12' maxlength='15' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['cp_af']; ?>" />
        <span title="5 chiffres pour la France, code libre pour les autres pays"></span>
        </p>

        <p><label for="ville_af_actif">Ville :</label>
		<input type="text" id="ville_af_actif" name="ville_af_actif" size="40" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,80}" maxlength='80' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['ville_af']; ?>" /><span title="Lettres, tirets, apostrophes et espaces acceptés"></span></p>

        <p><label for="pays_af_actif">Pays :</label>
		<input type="text" id="pays_af_actif" name="pays_af_actif" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,80}" size="40" maxlength="80" autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['pays_af']; ?>" /><span title="Lettres, tirets, apostrophes et espaces acceptés"></span></p>

        <p><label for="telephone1_af_actif"> Téléphone 1 :</label>
		<input type="text" id="telephone1_af_actif" name="telephone1_af_actif" size='25' maxlength='25' autocomplete="off" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" value="<?php echo $MANDATAIRE_ACTIF['telephone1_af']; ?>" />
		<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

        <p><label for="telephone2_af_actif">Téléphone 2 :</label>
		<input type="text" id="telephone2_af_actif" name="telephone2_af_actif" size='25' maxlength='25' autocomplete="off" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" value="<?php echo $MANDATAIRE_ACTIF['telephone2_af']; ?>" />
		<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

        <p><label for="telecopie_af_actif">Fax :</label>
		<input type="text" id="telecopie_af_actif" name="telecopie_af_actif" size='25' maxlength='25' autocomplete="off" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" value="<?php echo $MANDATAIRE_ACTIF['telecopie_af']; ?>" />
		<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

        <p><label for="mail_af_actif">Courriel :</label>
		<input input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_af_actif" name="mail_af_actif" size='50' maxlength='50' autocomplete="off" value="<?php echo $MANDATAIRE_ACTIF['mail_af']; ?>" />
		<span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>
        
    </fieldset>
</div>

<p id="zoneModifSupprMandActif">
	<input id="boutonEnregistrerMandataireActif" type="submit" value="Enregistrer Mandataire" class="enregistrer" <?php echo $hide_bouton_enregister; ?>>
	<button id="boutonSupprimerMandataireActif" type="button" class="annuler" value="Supprimer Mandataire" onclick="supprimer_mandataire(<?php echo $MANDATAIRE_ACTIF['id_mandataire'] ?>, 'actif', <?php echo $DETAILS[$_GET['cand']]['id_candidat'] ?>)">Supprimer Mandataire</button>
</p>
 
 </form> <!-- Fin du form avec MF actif -->
 
    
<?php
}
else
{
	echo '<span style="color:red">Aucun mandataire actif</span>';
}
?>

<hr>

<h1 class='titreSecondaire'>Mandataires antérieurs : </h1>

<?php

if(count($MANDATAIRES_ANTERIEURS) == 0)
{
	echo 'Aucun mandataire antérieur';
}

for ($i=0;$i<count($MANDATAIRES_ANTERIEURS);$i++)
{
echo '
<p class="triggerToggleMandataireAnterieur"><u>'.$MANDATAIRES_ANTERIEURS[$i]['prenom_mf'].' '.$MANDATAIRES_ANTERIEURS[$i]['nom_mf'].'</u></p>
<div>
	<form class="formulaireMandataire formulaire" name="r2_2_mandataires_anterieurs_num_'.$i.'" action="r2_2_ajax.php" method="post">

    <input type="hidden" name="annee_'.$i.'" value="'.$DETAILS[0]['annee'].'" />
    <input type="hidden" name="id_mandataire_'.$i.'" value="'.$MANDATAIRES_ANTERIEURS[$i]['id_mandataire'].'" />
    <input type="hidden" name="id_scrutin_'.$i.'" value="'.$DETAILS[0]['id_scrutin'].'" />
    <input type="hidden" name="id_candidat_'.$i.'" value="'.$DETAILS[$_GET['cand']]['id_candidat'].'" />
    <input type="hidden" name="id_suppleant_'.$i.'" value="" />
    <input type="hidden" name="id_expert_'.$i.'" value="'.adapte_fkey_null($DETAILS[$_GET['cand']]['id_expert']).'" />
    ';

	$checked_mf = "";
	$checked_af = "";

	//Préparation de variables pour mandataire financier ou non
	if ($MANDATAIRES_ANTERIEURS[$i]['qualite'] == 'MF')
	{
	 	$checked_mf = " checked=\"checked\"";
	}
	else
	{
	 	$checked_af = " checked=\"checked\"";
	}

	//Préparation de variables pour présence mandataire, irrégularité mandataire, commentaire controle mandataire
	$chk_irregularite1 = "";
	$chk_irregularite0 = "";
	$commentaire_irregularite = "none";
	$requis = false;

	if($MANDATAIRES_ANTERIEURS[$i]['chk_irregularite_mandataire'] == 1)
	{
		$chk_irregularite1 = "checked";
		$commentaire_irregularite = "block";
		$requis = true;
	}
	else if($MANDATAIRES_ANTERIEURS[$i]['chk_irregularite_mandataire'] == 0)
	{
		$chk_irregularite0 = "checked";
	}

	$txtArea_iregularite = $MANDATAIRES_ANTERIEURS[$i]['commentaire_controle_mandataire'];

	if(isset($MANDATAIRES_ANTERIEURS[$i]['date_debut_validite']))
	{
		$dateDebutValidite = $MANDATAIRES_ANTERIEURS[$i]['date_debut_validite']->format('d/m/Y');
	}
	else
	{
		$dateDebutValidite = '';
	}

	if(isset($MANDATAIRES_ANTERIEURS[$i]['date_fin_validite']))
	{
		$dateFinValidite = $MANDATAIRES_ANTERIEURS[$i]['date_fin_validite']->format('d/m/Y');
	}
	else
	{
		$dateFinValidite = '';
	}

	echo '
	<div>
		<fieldset>
			<legend>Activité</legend>
			<p>
				<label for="periodicite_mandataire_'.$i.'"> Statut du mandataire : </label>
				<input type="text" required="required" name="periodicite_mandataire_'.$i.'" size="10" maxlength="10" value="anterieur" readonly="readonly">
			</p>
			<p>
				<label for="date_debut_validite_'.$i.'"> Date de début de validité : </label>
				<input id="date_dbt_validite_'.$i.'" type="text" required="required" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size="15" maxlength="15" autocomplete="off" name="date_debut_validite_'.$i.'" value="'.$dateDebutValidite.'" />
				<span style="color:red" title=""></span>
			</p>
			<p>
				<label for="date_fin_validite_'.$i.'"> Date de fin de validité : </label>
				<input id="date_fin_validite_'.$i.'" type="text" required="required" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size="15" maxlength="15" autocomplete="off" name="date_fin_validite_'.$i.'" value="'.$dateFinValidite.'" />
				<span style="color:red" title=""></span>
			</p>
		</fieldset>
		<fieldset>
			<legend>Déclaration</legend>
			<p>
				<label for="date_declaration_mf_'.$i.'"> Date de déclaration en préfecture : </label>
				<input type="text" id="date_declaration_mf_'.$i.'" name="date_declaration_mf_'.$i.'" size="15" maxlength="15" autocomplete="off" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" value="'.affiche_date_fr($MANDATAIRES_ANTERIEURS[$i]['date_declaration_mf']).'"/>
				<span class="changement_expert" id="date_declaration_mf_span_'.$i.'" title="La date doit être au format jj/mm/aaaa"></span>		
			</p>	
		</fieldset>
		<fieldset>
	        <legend>Contrôles Irrégularité Mandataire</legend>
			<p id="saisie_irregularite_controle_'.$i.'">
				<span class="label">Irrégularité :</span>
	       		<label for="chk_irregularite1_'.$i.'" class="oui_non">Oui</label> <input type="radio" class="chk_irregularite1" name="chk_irregularite_mandataire_'.$i.'" value="1"'.$chk_irregularite1.'/>
	        	<label for="chk_irregularite0_'.$i.'" class="oui_non">Non</label> <input type="radio" class="chk_irregularite0" name="chk_irregularite_mandataire_'.$i.'" value="0"'.$chk_irregularite0.'/>
	        	<span title="Réponse attendue !"></span>
	        </p>
	        <p class="saisie_commentaire_controle" style="display:'.$commentaire_irregularite.'">
			<label for="commentaire_controle_mandataire_'.$i.'">Commentaires sur l\'irrégularité: </label>';
			
		if($requis)
		{
			echo '<textarea required class="commentaire_controle_mandataire" name="commentaire_controle_mandataire_'.$i.'" rows="3" cols="50" autocomplete="off">'.$txtArea_iregularite.'</textarea>
			</p>';
		}
		else
		{
			echo '<textarea  class="commentaire_controle_mandataire"  name="commentaire_controle_mandataire_'.$i.'" rows="3" cols="50" autocomplete="off">'.$txtArea_iregularite.'</textarea>
			</p>';
		}
	echo '
			<p style="color:red;" class="msgIrregulNonPrecise"></p>	
		</fieldset>
		<fieldset>
	        <legend>Qualité</legend>    
	    	<p>
	    		<label for="qualite1_'.$i.'"> Mandataire financier</label>
				<input type="radio" name="qualite_'.$i.'" class="qualitemf" value="MF" '.$checked_mf.'>
			</p>
	    	<p>
	    		<label for="qualite1_'.$i.'"> Association de financement</label>
				<input type="radio" name="qualite_'.$i.'" class="qualiteaf" value="AF" '.$checked_af.'>
			</p>
		</fieldset>
		<fieldset class="rna">
	        <legend>RNA</legend>    
	    	<p>
	    		<label for="rna_'.$i.'"> RNA : </label>
				<input type="text" name="rna_af_'.$i.'" size="10" maxlength="10" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['rna_af'].'" />
				<span title="Le RNA est composé d\'un W suivi de neuf chiffres"></span>
			</p>
		</fieldset>
	</div>

	<div class="etatcivil jaunePale">
	    <fieldset>
	        <legend>Identité<em class="libelle_pdt_af"> du président de l\'association</em></legend>
	        <p><label for="id_civ_mf_'.$i.'"> Civilité :</label>'.combo_dynamique('civilite','id_civ_mf_'.$i.'','id_civilite','libelle_civ', $MANDATAIRES_ANTERIEURS[$i]['id_civ_mf'],'','','','required','').'
	        </p> 
	        <p><label for="nom_mf_'.$i.'"> Nom :</label>
	       	 	<input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id="nom_mf_'.$i.'" name="nom_mf_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['nom_mf'].'" />
	        	<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
	        </p>
	        <p><label for="prenom_mf_'.$i.'"> Prénom :</label>
		        <input type="text" required="required"  pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id="prenom_mf_'.$i.'" name="prenom_mf_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['prenom_mf'].'" />
		        <span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
	        </p>
	    </fieldset>
	</div>
	    
	<div class="adresse jaunePale">
	    <fieldset>
	        <legend>Coordonnées<em class="libelle_pdt_af"> du président de l\'association</em></legend>
	        <p>
	        	<label for="adresse1_mf_'.$i.'"> Adresse :</label>
	        	<input type="text" id="adresse1_mf_'.$i.'" name="adresse1_mf_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse1_mf'].'" />
	        </p>
	        <p>
	        	<label for="adresse2_mf_'.$i.'"> Adresse (suite) :</label>
	        	<input type="text" id="adresse2_mf_'.$i.'" name="adresse2_mf_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse2_mf'].'" />
	        </p>
	        <p>
	        	<label for="adresse3_mf_'.$i.'">Adresse (suite 2) :</label>
				<input type="text" id="adresse3_mf_'.$i.'" name="adresse3_mf_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse3_mf'].'" />
			</p>
	        <p>
	        	<label for="adresse4_mf_'.$i.'">Adresse (suite 3) :</label>
				<input type="text" id="adresse4_mf_'.$i.'" name="adresse4_mf_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse4_mf'].'" />
			</p>
	        <p>
	        	<label for="cp_mf_'.$i.'">Code postal :</label>
				<input type="text" required="required" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_mf_'.$i.'" name="cp_mf_'.$i.'" size="12" maxlength="12" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['cp_mf'].'" />
	        	<span title="5 chiffres pour la France, code libre pour les autres pays"></span>
	        </p>
	        <p>
	        	<label for="ville_mf_'.$i.'">Ville :</label>
				<input type="text" required="required" id="ville_mf_'.$i.'" name="ville_mf_'.$i.'" size="40" maxlength="80" autocomplete="off" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&\'’ ]{2,60}" class="pattern_texte_2_80" value="'.$MANDATAIRES_ANTERIEURS[$i]['ville_mf'].'" />
				<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>
	        <p>
	        	<label for="pays_mf_'.$i.'">Pays :</label>
				<input type="text" id="pays_mf_'.$i.'" name="pays_mf_'.$i.'" size="40" maxlength="80" autocomplete="off" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&\'’ ]{2,80}" placeholder="" value="'.$MANDATAIRES_ANTERIEURS[$i]['pays_mf'].'" />
				<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>
	        <p>
	        	<label for="telephone1_mf_'.$i.'">Téléphone 1 :</label>
				<input type="text" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telephone1_mf_'.$i.'" name="telephone1_mf_'.$i.'" size="25" maxlength="25" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['telephone1_mf'].'" />
				<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span>
			</p>
	        <p>
	        	<label for="telephone2_mf_'.$i.'">Téléphone 2 :</label>
				<input type="text" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telephone2_mf_'.$i.'" name="telephone2_mf_'.$i.'" size="25" maxlength="25" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['telephone2_mf'].'" />
				<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span>
			</p>
	        <p>
	        	<label for="telecopie_mf_'.$i.'">Fax :</label>
				<input type="text" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telecopie_mf_'.$i.'" name="telecopie_mf_'.$i.'" size="25" maxlength="25" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['telecopie_mf'].'" />
				<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span>
			</p>
	        <p>
	        	<label for="mail_mf_'.$i.'">Courriel :</label>
				<input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_mf_'.$i.'" name="mail_mf_'.$i.'" size="50" maxlength="50" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['mail_mf'].'" />
				<span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>
			</p>    
	    </fieldset>
	</div>

	<div class="banque">
	    <fieldset>
	        <legend>Relevé d\'identité bancaire</legend>
	        <p>
	        	<label for="libelle_compte_bq_'.$i.'"> Libellé du compte :</label>
				<input type="text" id="libelle_compte_bq_'.$i.'" name="libelle_compte_bq_'.$i.'" size="60" maxlength="120" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['libelle_compte_bq'].'" />
			</p>
	        <p>
	        	<label for="nom_banque_'.$i.'"> Nom de la banque :</label>
				<input type="text" id="nom_banque_'.$i.'" name="nom_banque_'.$i.'" size="50" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['nom_banque'].'" />
			</p>
	        <p>
	        	<label for="num_compte_'.$i.'"> Numéro de compte :</label>
				<input type="text" id="num_compte_'.$i.'" name="num_compte_'.$i.'" size="50" maxlength="50" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['num_compte'].'" />
			</p>
	        <p>
	        	<label for="nom_agence_'.$i.'"> Nom de l\'agence :</label>
				<input type="text" id="nom_agence_'.$i.'" name="nom_agence_'.$i.'" size="50" maxlength="70" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['nom_agence'].'" />
			</p>
	        <p>
	        	<label for="adresse1_agence_'.$i.'"> Adresse :</label>
				<input type="text" id="adresse1_agence_'.$i.'" name="adresse1_agence_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse1_agence'].'" />
			</p>
	        <p>
	        	<label for="adresse2_agence_'.$i.'"> Adresse (suite) :</label>
				<input type="text" id="adresse2_agence_'.$i.'" name="adresse2_agence_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse2_agence'].'" />
			</p>
	        <p>
	        	<label for="adresse3_agence_'.$i.'"> Adresse (suite 2) :</label>
				<input type="text" id="adresse3_agence_'.$i.'" name="adresse3_agence_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse3_agence'].'" />
			</p>
	        <p>
	        	<label for="adresse4_agence_'.$i.'"> Adresse (suite 3) :</label>
				<input type="text" id="adresse4_agence_'.$i.'" name="adresse4_agence_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse4_agence'].'" />
			</p>
	        <p>
	        	<label for="cp_agence_'.$i.'"> Code postal :</label>
				<input type="text" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_agence_'.$i.'" name="cp_agence_'.$i.'" size="12" maxlength="12" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['cp_agence'].'" />
	        	<span title="5 chiffres pour la France, code libre pour les autres pays"></span>
	        </p>
	        <p>
	        	<label for="ville_agence_'.$i.'"> Ville :</label>
				<input type="text" pattern="[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id="ville_agence_'.$i.'" name="ville_agence_'.$i.'" size="40" maxlength="80" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['ville_agence'].'" />
	       	 	<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
	        </p>
	        <p>
	        	<label for="pays_agence_'.$i.'"> Pays :</label>
				<input type="text" id="pays_agence_'.$i.'" name="pays_agence_'.$i.'" size="40" maxlength="80" autocomplete="off" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" value="'.$MANDATAIRES_ANTERIEURS[$i]['pays_agence'].'" />
				<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
			</p>
	    </fieldset>
	</div>

	<div class="adresse_af adresse jaunePale ">
	    <fieldset>

	        <legend>Coordonnées de l\'association de financement</legend>
	        <p><label for="adresse1_af_'.$i.'"> Nom de l\'association de financement :</label> <textarea id="nom_af_'.$i.'" name="nom_af_'.$i.'" rows="3" cols="50" autocomplete="off">'.$MANDATAIRES_ANTERIEURS[$i]['nom_af'].'</textarea></p>
	        
	        <p><label for="adresse1_af_'.$i.'">Adresse :</label>
	        <input type="text" id="adresse1_af_'.$i.'" name="adresse1_af_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse1_af'].'" /></p>
	        <p><label for="adresse2_af_'.$i.'">Adresse (suite) :</label>
			<input type="text" id="adresse2_af_'.$i.'" name="adresse2_af_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse2_af'].'" /></p>

	        <p><label for="adresse3_af_'.$i.'">Adresse (suite 2) :</label>
	        <input type="text" id="adresse3_af_'.$i.'" name="adresse3_af_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse3_af'].'" /></p>

	        <p><label for="adresse4_af_'.$i.'">Adresse (suite 3) :</label>
	        <input type="text" id="adresse4_af_'.$i.'" name="adresse4_af_'.$i.'" size="40" maxlength="60" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['adresse4_af'].'" /></p>

	        <p><label for="cp_af_'.$i.'">Code postal :</label>
	        <input type="text" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_af_'.$i.'" name="cp_af_'.$i.'" size="12" maxlength="12" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['cp_af'].'" />
	        <span title="5 chiffres pour la France, code libre pour les autres pays"></span>
	        </p>

	        <p><label for="ville_af_'.$i.'">Ville :</label>
			<input type="text" id="ville_af_'.$i.'" name="ville_af_'.$i.'" size="40" maxlength="80" autocomplete="off" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,80}" value="'.$MANDATAIRES_ANTERIEURS[$i]['ville_af'].'" />
			<span title="Lettres, tirets, apostrophes et espaces acceptés"></span></p>

	        <p><label for="pays_af_'.$i.'">Pays :</label>
			<input type="text" id="pays_af_'.$i.'" name="pays_af_'.$i.'" size="40" maxlength="80" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['pays_af'].'" /></p>

	        <p><label for="telephone1_af_'.$i.'"> Téléphone 1 :</label>
			<input type="text" id="telephone1_af_'.$i.'" name="telephone1_af_'.$i.'" size="25" maxlength="25" autocomplete="off" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" value="'.$MANDATAIRES_ANTERIEURS[$i]['telephone1_af'].'" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

	        <p><label for="telephone2_af_'.$i.'">Téléphone 2 :</label>
			<input type="text" id="telephone2_af_'.$i.'" name="telephone2_af_'.$i.'" size="25" maxlength="25" autocomplete="off" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" value="'.$MANDATAIRES_ANTERIEURS[$i]['telephone2_af'].'" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

	        <p><label for="telecopie_af_'.$i.'">Fax :</label>
			<input type="text" id="telecopie_af_'.$i.'" name="telecopie_af_'.$i.'" size="25" maxlength="25" autocomplete="off" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" value="'.$MANDATAIRES_ANTERIEURS[$i]['telecopie_af'].'" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

	        <p><label for="mail_af_'.$i.'">Courriel :</label>
			<input input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_af_'.$i.'" name="mail_af_'.$i.'" size="50" maxlength="50" autocomplete="off" value="'.$MANDATAIRES_ANTERIEURS[$i]['mail_af'].'" />
			<span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>
	        
	    </fieldset>
	</div>
	 
	<p class="zoneModifSupprMandAnterieur">
		<input class="boutonSubmitManAnter enregistrer" type="submit" value="Enregistrer Mandataire" '.$hide_bouton_enregister.'>
		<button type="button" class="annuler" value="Supprimer Mandataire" onclick="supprimer_mandataire('.$MANDATAIRES_ANTERIEURS[$i]['id_mandataire'].', \'anterieur\', '.$DETAILS[$_GET['cand']]['id_candidat'].')">Supprimer Mandataire</button>
	</p>
	 
	 </form>
</div>
	';
} //Fin boucle for
?>

<div id="zoneCreationMandataire">
	<hr>
	<h1 class='titreSecondaire'>Création d'un mandataire (antérieur ou actif) : </h1>
	<button id="boutonAfficherFormCreationMand" class="enregistrer" type="button" value="Creer Mandataire">Creer Mandataire</button>
</div>

<form id="formCreationMandataire" class="formulaire" name="r2_2_creation_mandataire" action="r2_2_ajax.php" method="post">

    <input type="hidden" name="annee_creation" value="<?php echo $DETAILS[0]['annee']; ?>" />
    <input type="hidden" name="id_mandataire_creation" value="" />
    <input type="hidden" name="id_scrutin_creation" value="<?php echo $DETAILS[0]['id_scrutin']; ?>" />
    <input type="hidden" name="id_candidat_creation" value="<?php echo $DETAILS[$_GET['cand']]['id_candidat']; ?>" />
    <input type="hidden" name="id_suppleant_creation" value="" />
    <input type="hidden" name="id_expert_creation" value="<?php echo adapte_fkey_null($DETAILS[$_GET['cand']]['id_expert']); ?>" />

	<div> 
		<fieldset>
			<legend>Activité</legend>
			<p>
				<label for="periodicite_mandataire_creation"> Statut du mandataire : </label>
				<select id="periodicite_dans_creation_mand" name="periodicite_mandataire_creation">
					<option value="actif" selected>Actif</option> 
  					<option value="anterieur">Anterieur</option>
				</select>
			</p>
			<p>
				<label for="date_debut_validite_creation"> Date de début de validité : </label>
				<input id='date_dbt_validite_creation' type="text" required="required" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size="15" maxlength="15" autocomplete="off" name="date_debut_validite_creation" value=""/>
				<span style="color:red" title="" class="msgDateValidite"></span>
			</p>
			<p id='finValiditeCreationMandataire'>
				<label for="date_fin_validite_creation"> Date de fin de validité : </label>
				<input id='date_fin_validite_creation' type="text" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" size="15" maxlength="15" autocomplete="off" name="date_fin_validite_creation" value=""/>
				<span style="color:red" title="" class="msgDateValidite"></span>
			</p>
		</fieldset>
		<fieldset>
			<legend>Déclaration</legend>
			<p>
				<label for="date_declaration_mf_creation"> Date de déclaration en préfecture : </label>
				<input type="text" id="date_declaration_mf_creation" name="date_declaration_mf_creation" size='15' maxlength='15' autocomplete="off" placeholder="Format jj/mm/aaaa" pattern="[0-3]{1}[0-9]{1}/[0-1]{1}[0-9]{1}/\d{4}" value="" required="required"/>
				<span class="changement_expert" id="date_declaration_mf_span_creation" title="La date doit être au format jj/mm/aaaa"></span>
			</p>	
		</fieldset>
		<fieldset>
	        <legend>Contrôles Mandataire</legend>     
			<p id="presenceMandataire_creation"> <!-- cachée si click sur 'Anterieur' dans la périodicité -->
				<span class="label">Présence d'un mandataire :</span>
		        <label for="chk_presence_mandataire1_creation" class="oui_non">Oui</label><input type="radio" id="chk_presence_mandataire1_creation" name="chk_presence_mandataire_creation" value="1"/>
		        <label for="chk_presence_mandataire0_creation" class="oui_non">Non</label><input type="radio" id="chk_presence_mandataire0_creation" name="chk_presence_mandataire_creation" value="0"/>
		        <span id="msgPresenceMandCreationMand" style="color:red">Merci d'indiquer la présence d'un mandataire</span>
	        </p>
			<p id="saisie_irregularite_controle_creation">
				<span class="label">Irrégularité :</span>
	       		<label for="chk_irregularite1_creation" class="oui_non">Oui</label> <input type="radio" class="chk_irregularite1" name="chk_irregularite_mandataire_creation" value="1" id="irregulCreation"/>
	        	<label for="chk_irregularite0_creation" class="oui_non">Non</label> <input type="radio" class="chk_irregularite0" name="chk_irregularite_mandataire_creation" value="0"/>
	        	<span id="msgIrregulMandCreationMand" style="color:red">Merci d'indiquer la présence d'une irrégularité</span>
	        </p>
	        <p class="saisie_commentaire_controle" style="display:none">
				<label for="commentaire_controle_mandataire_creation">Commentaires sur l'irrégularité : </label>
				<textarea  class="commentaire_controle_mandataire"  name="commentaire_controle_mandataire_creation"  rows="3" cols="50" autocomplete="off"></textarea>
				<p style="color:red;" class="msgIrregulNonPrecise"></p>
			</p>
		</fieldset>
		<fieldset>
	        <legend>Qualité</legend>    
	    	<p>
	    		<label for="qualite1_creation"> Mandataire financier</label>
				<input type="radio" name="qualite_creation" id="qualiteCreation" class="qualitemf" value="MF">
			</p>
	    	<p>
	    		<label for="qualite1_creation"> Association de financement</label>
				<input type="radio" name="qualite_creation" class="qualiteaf" value="AF">
			</p>
			<p id="msgQualiteCreationMand" style="color:red">
				Merci de cocher l'un des deux types de mandataire financier.
			</p>
		</fieldset>
		<!-- Affichage ou non du RNA géré en jQuery -->
		<fieldset class="rna">
		    <legend>RNA</legend>    
	    	<p>
	    		<label for="rna_creation"> RNA : </label>
				<input type="text" name="rna_af_creation" size='10' maxlength='10' autocomplete="off"/>
				<span title="Le RNA est composé d'un W suivi de neuf chiffres"></span>
			</p>
		</fieldset>
	</div>  
	<div class="etatcivil">
	    <fieldset>
	        <legend>Identité<em class="libelle_pdt_af"> du président de l'association</em></legend>
       		<p><label for="id_civ_mf_creation"> Civilité :</label> <?php echo combo_dynamique('civilite','id_civ_mf_creation','id_civilite','libelle_civ','','','','','required',''); ?>
        	</p>
	        <p><label for="nom_mf_creation"> Nom :</label>
	       	 	<input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\\'’ ]{2,60}" id="nom_mf_creation" name="nom_mf_creation" size='40' maxlength='60' autocomplete="off" value="" />
	        	<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
	        </p>
	        <p><label for="prenom_mf_creation"> Prénom :</label>
		        <input type="text" required="required"  pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\\'’ ]{2,60}" id="prenom_mf_creation" name="prenom_mf_creation" size='40' maxlength='60' autocomplete="off" value="" />
		        <span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
	        </p>
	    </fieldset>
	</div>    
	<div class="adresse">
	    <fieldset>
	        <legend>Coordonnées<em class="libelle_pdt_af"> du président de l'association</em></legend>
	        <p>
	        	<label for="adresse1_mf_creation"> Adresse :</label>
	        	<input type="text" id="adresse1_mf_creation" name="adresse1_mf_creation" size='40' maxlength='60' autocomplete="off" value="" />
	        </p>
	        <p>
	        	<label for="adresse2_mf_creation"> Adresse (suite) :</label>
	        	<input type="text" id="adresse2_mf_creation" name="adresse2_mf_creation" size='40' maxlength='60' autocomplete="off" value="" />
	        </p>
	        <p>
	        	<label for="adresse3_mf_creation">Adresse (suite 2) :</label>
				<input type="text" id="adresse3_mf_creation" name="adresse3_mf_creation" size='40' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="adresse4_mf_creation">Adresse (suite 3) :</label>
				<input type="text" id="adresse4_mf_creation" name="adresse4_mf_creation" size='40' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="cp_mf_creation">Code postal :</label>
				<input type="text" required="required" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_mf_creation" name="cp_mf_creation" size='12' maxlength='12' autocomplete="off" value="" />
	        	 <span title="5 chiffres pour la France. Code libre pour l'étranger après avoir renseigné le pays."></span>
	        </p>
	        <p>
	        	<label for="ville_mf_creation">Ville :</label>
				<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,60}" class="pattern_texte_2_80" required="required" id="ville_mf_creation" name="ville_mf_creation" size='40' maxlength='80' autocomplete="off" value="" />
				<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>
	        <p>
	        	<label for="pays_mf_creation">Pays :</label>
				<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&'’ ]{2,80}" id="pays_mf_creation" name="pays_mf_creation" size='40' maxlength='80' autocomplete="off" value="" />
				<span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span>
			</p>
	        <p>
	        	<label for="telephone1_mf_creation">Téléphone 1 :</label>
				<input type="text" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telephone1_mf_creation" name="telephone1_mf_creation" size='25' maxlength='25' autocomplete="off" value="" />
				<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span>
			</p>
	        <p>
	        	<label for="telephone2_mf_creation">Téléphone 2 :</label>
				<input type="text" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telephone2_mf_creation" name="telephone2_mf_creation" size='25' maxlength='25' autocomplete="off" value="" />
				<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span>
			</p>
	        <p>
	        	<label for="telecopie_mf_creation">Fax :</label>
				<input type="text" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" id="telecopie_mf_creation" name="telecopie_mf_creation" size='25' maxlength='25' autocomplete="off" value="" />
				<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span>
			</p>
	        <p>
	        	<label for="mail_mf_creation">Courriel :</label>
				<input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_mf_creation" name="mail_mf_creation" size='50' maxlength='50' autocomplete="off" value="" />
				<span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span>
			</p>    
	    </fieldset>
	</div>
	<div class="banque">
	    <fieldset>
	        <legend>Relevé d'identité bancaire</legend>
	        <p>
	        	<label for="libelle_compte_bq_creation"> Libellé du compte :</label>
				<input type="text" id="libelle_compte_bq_creation" name="libelle_compte_bq_creation" size='60' maxlength='120' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="nom_banque_creation"> Nom de la banque :</label>
				<input type="text" id="nom_banque_creation" name="nom_banque_creation" size='50' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="num_compte_creation"> Numéro de compte :</label>
				<input type="text" id="num_compte_creation" name="num_compte_creation" size='50' maxlength='50' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="nom_agence_creation"> Nom de l'agence :</label>
				<input type="text" id="nom_agence_creation" name="nom_agence_creation" size='50' maxlength='70' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="adresse1_agence_creation"> Adresse :</label>
				<input type="text" id="adresse1_agence_creation" name="adresse1_agence_creation" size='40' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="adresse2_agence_creation"> Adresse (suite) :</label>
				<input type="text" id="adresse2_agence_creation" name="adresse2_agence_creation" size='40' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="adresse3_agence_creation"> Adresse (suite 2) :</label>
				<input type="text" id="adresse3_agence_creation" name="adresse3_agence_creation" size='40' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="adresse4_agence_creation"> Adresse (suite 3) :</label>
				<input type="text" id="adresse4_agence_creation" name="adresse4_agence_creation" size='40' maxlength='60' autocomplete="off" value="" />
			</p>
	        <p>
	        	<label for="cp_agence_creation"> Code postal :</label>
				<input type="text" pattern="[0-9]{5}" placeholder="5 chiffres" id="cp_agence_creation" name="cp_agence_creation" size='12' maxlength='12' autocomplete="off" value="" />
	        	<span title="5 chiffres pour la France, code libre pour les autres pays"></span>
	        </p>
	        <p>
	        	<label for="ville_agence_creation"> Ville :</label>
				<input type="text" pattern="[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id="ville_agence_creation" name="ville_agence_creation" size='40' maxlength='80' autocomplete="off" value="" />
	       	 	<span title="Lettres, chiffres, tirets, apostrophes et espaces acceptés"></span>
	        </p>
	        <p>
	        	<label for="pays_agence_creation"> Pays :</label>
				<input type="text" id="pays_agence_creation" name="pays_agence_creation" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,60}" size='40' maxlength='80' autocomplete="off" value="" />
				<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
			</p>
	    </fieldset>
	</div>

	<!-- Affiché que si association de financement coché dans bloc 'Qualité -->
	<div class="adresse_af adresse">
	    <fieldset>

	        <legend>Coordonnées de l'association de financement</legend>
	        <p><label for="adresse1_af_creation"> Nom de l'association de financement :</label> <textarea id="nom_af_creation" name="nom_af_creation" rows="3" cols="50" autocomplete="off"></textarea></p>
	        
	        <p><label for="adresse1_af_creation">Adresse :</label>
	        <input type="text" id="adresse1_af_creation" name="adresse1_af_creation" size='40' maxlength='60' autocomplete="off" value="" /></p>
	        <p><label for="adresse2_af_creation">Adresse (suite) :</label>
			<input type="text" id="adresse2_af_creation" name="adresse2_af_creation" size='40' maxlength='60' autocomplete="off" value="" /></p>

	        <p><label for="adresse3_af_creation">Adresse (suite 2) :</label>
	        <input type="text" id="adresse3_af_creation" name="adresse3_af_creation" size='40' maxlength='60' autocomplete="off" value="" /></p>

	        <p><label for="adresse4_af_creation">Adresse (suite 3) :</label>
	        <input type="text" id="adresse4_af_creation" name="adresse4_af_creation" size='40' maxlength='60' autocomplete="off" value="" /></p>

	        <p><label for="cp_af_creation">Code postal :</label>
	        <input type="text" pattern="[A-Z0-9\- ]{2,15}" id="cp_af_creation" name="cp_af_creation" size='12' maxlength='12' autocomplete="off" value="" />
	        <span title="5 chiffres pour la France, code libre pour les autres pays"></span>
	        </p>

	        <p><label for="ville_af_creation">Ville :</label>
			<input type="text" id="ville_af_creation" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,80}" name="ville_af_creation" size='40' maxlength='80' autocomplete="off" value="" />
			<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>
			</p>

	        <p><label for="pays_af_creation">Pays :</label>
			<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&'’ ]{2,80}" id="pays_af_creation" name="pays_af_creation" size='40' maxlength='80' autocomplete="off" value="" />
			<span title="Lettres, tirets, apostrophes et espaces acceptés"></span></p>

	        <p><label for="telephone1_af_creation"> Téléphone 1 :</label>
			<input type="text" id="telephone1_af_creation" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" name="telephone1_af_creation" size='25' maxlength='25' autocomplete="off" value="" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

	        <p><label for="telephone2_af_creation">Téléphone 2 :</label>
			<input type="text" id="telephone2_af_creation" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" name="telephone2_af_creation" size='25' maxlength='25' autocomplete="off" value="" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

	        <p><label for="telecopie_af_creation">Fax :</label>
			<input type="text" id="telecopie_af_creation" pattern="^(?:0|\(?\+33\)?\s?|0033\s?)[1-79](?:[\.\-\s]?\d\d){4}$" name="telecopie_af_creation" size='25' maxlength='25' autocomplete="off" value="" />
			<span title="Numéro incorrect. Les chiffres, le signe +, le point et les espaces sont acceptés."></span></p>

	        <p><label for="mail_af_creation">Courriel :</label>
			<input input type="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" id="mail_af_creation" name="mail_af_creation" size='50' maxlength='50' autocomplete="off" value="" />
			<span title="Adresse de messagerie incorrecte. Exemple de format attendu : abc@exemple.fr (point et @ indispensables)"></span></p>
	        
	    </fieldset>
	</div>

	<p>
		<input id="boutonEnregistrerNouveauMandataire" type="submit" value="Valider Creation Mandataire" class="enregistrer">
		<input id="boutonAnnulerNouveauMandataire" type="button" value="Annuler Creation Mandataire">
	</p>
 
 </form> <!-- Fin du form de creation d'un mandataire -->    


<!--   ************************************************************** PARTIE MANDATAIRES DELEGUES **************************************************************   -->

<hr>
<h1 class='titreSecondaire'>Délégués du mandataire actif :</h1>
 
<?php 

$cand_francais_etranger = $DETAILS[$_GET['cand']]['chk_francais_etranger']+0;
$id_mandataire = $DETAILS[$_GET['cand']]['id_mandataire']+0;

if(count($MANDATAIRE_ACTIF) > 1) //S'il y a un mandataire actif, on affiche ses délégués
{
	$id_mandataire = $MANDATAIRE_ACTIF['id_mandataire'];
 	
 	if($cand_francais_etranger === 1){   // valeur pour test - luc 10/05/2017
 
 	 
 		$nombre_max_delegue = 3;
 
		 $sql = "SELECT
			id_delegue1
			, id_delegue2
			, id_delegue3 
			FROM dbo.mandataire 
			WHERE id_mandataire = ".$id_mandataire."";
	
		 $req = sqlsrv_query($conn,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false) {
			header("Content-Type: text/json");
			echo '{"success":false,"message":"Erreur de requête"}';	
			exit;
		}
	
		$delegue = array();

		$rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC);
	
		$id_delegue1 = $rs['id_delegue1']+0;
		$id_delegue2 = $rs['id_delegue2']+0;
		$id_delegue3 = $rs['id_delegue3']+0;
	
		$nombre_delegue = 0;
	
		$T_delegue = array();
	
		if ($id_delegue1 !== 0) {
	
			$T_delegue[] = $id_delegue1;
			$nombre_delegue++;
	
		}
	
		if ($id_delegue2 !== 0) {
	
			$T_delegue[] = $id_delegue2;
			$nombre_delegue++;
	
		}
	
		if ($id_delegue3 !== 0) {
	
			$T_delegue[] = $id_delegue3;
			$nombre_delegue++;
	
		}
		
		if(count($T_delegue) == 0)
		{
			echo 'Aucun délégué';
		}
	
 ?> 
 
 <form id="formulaire_delegue_1"  name="r2_2" action="false;" method="post">
 
<input type="hidden" name="id_mandataire" value="<?php echo $id_mandataire; ?>" />
<input type="hidden" name="id_delegue1" value="<?php echo $id_delegue1; ?>" />
<input type="hidden" name="id_delegue2" value="<?php echo $id_delegue2; ?>" />
<input type="hidden" name="id_delegue3" value="<?php echo $id_delegue3; ?>" />

<?php 
 	echo '<div class=\'DelegueDFE\'>';
	echo '<fieldset>';
	echo '<legend class="legend_fieldset_delegue">Délégué DFE/SFE</legend>'; 
	
	$j = 1;
	
	for($i=0;$i<count($T_delegue);$i++) {
	
		
		$nom_champ = "champs_formulaire_delegue".$j;
		
		echo '<fieldset id='.$nom_champ.'>';
		echo "<legend class=\"legend_fieldset_delegue\">Délégué DFE/SFE ".$j."</legend>";
	
		// Recherche des elements des délégués enregistrés
		$sql = "select * from dbo.mandataire_delegue where id_delegue='".$T_delegue[$i]."'";	

		$req = sqlsrv_query($conn,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false) {
			header("Content-Type: text/json");
			echo '{"success":false,"message":"Erreur requête"}';	
			exit;
		}
		
		$nb = sqlsrv_num_rows($req);

		$rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC);
		
	
		affiche_formulaire_delegue($j,$rs);
		echo '<input type=\'button\' id=\'modifier_delegue'.$j.'\' '.$hide_bouton_enregister.' class="btn_form_delegue"  value=\'Modifier délégué '.$j.'\' />';
		echo '<input type=\'button\' id=\'supprimer_delegue'.$j.'\' '.$hide_bouton_enregister.' class="btn_form_delegue"  value=\'Supprimer délégué '.$j.'\' />';
		echo '</fieldset>';
		
		$j++;		
	}
	
	if($nombre_delegue < $nombre_max_delegue){
	
	// $numero_autre_delegue = $nombre_delegue+1;
		$nom_champ = "champs_formulaire_delegue".$j;
	
		echo '<fieldset id='.$nom_champ.'>';
		echo "<legend class=\"legend_fieldset_delegue\">Délégué DFE/SFE ".$j."</legend>";
		
	
		echo '<input type="button" id="ajouter_delegue'.$j.'" '.$hide_bouton_enregister.' class="btn_form_delegue"  value="Ajouter un délégué" />';
		echo '</fieldset>';
		
	} else {
	
		echo '<p> '.$nombre_max_delegue.' délégués maximum </p>';
	}
		
	echo '</div>';
	
	}//FIN DU if($cand_francais_etranger === 1)
	else {
		echo 'Aucun délégué';
	}
	
} else {
	
	echo 'Aucun délégué';
}	

function affiche_formulaire_delegue($num_delegue,$delegue){
	
	// print_r($delegue);
	// exit;	
	echo '<div class="etatcivil">';
	echo '<fieldset class="formulaire" id="champs_formulaire_delegue"'.$num_delegue.' >';
	echo '<legend>Identité</legend>';

// Identité	
	$civ = $delegue['id_civ_dlg'];
	
	$index = 'id_civ_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.' > Civilité :</label>';
	echo '<select id='.$index.' >';	
	if($civ==2){
		echo '<option value="2">Madame</option>';
		echo '<option value="1">Monsieur</option>';
	}else{
		echo '<option value="1">Monsieur</option>';
		echo '<option value="2">Madame</option>';
	}
	echo  '</select></p>';	
		
	$index = 'nom_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.' > Nom :</label>';
	echo '<input type="text" required="required" class="nom_mf_delegue" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['nom_dlg'].'" />';
	echo '<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>';
	echo '</p>';
        
	$index = "prenom_mf_delegue".$num_delegue;
    echo '<p><label for='.$index.' > Prénom :</label>'; 
    echo '<input type="text" required="required"  class="prenom_mf_delegue" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['prenom_dlg'].'" />';
    echo '<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>';
	echo '</p>';   
    echo "</fieldset>"; 
	echo '</div>';

// Coordonnées

	echo '<div class="adresse">';
	echo '<fieldset class="formulaire" >';
	echo '<legend>Coordonnées</legend>';
	
	// $index='particule_dlg_delegue'.$num_delegue;
	// echo '<p><label for='.$index.'> Particule :</label>';
	// echo '<input type="text" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['particule_dlg'].'" /></p>';
	
	$index='adresse1_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Adresse :</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['adresse1_dlg'].'" /></p>';
	
	$index='adresse2_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Adresse (suite):</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['adresse2_dlg'].'" /></p>';
	
	$index='adresse3_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Adresse (suite 2):</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['adresse3_dlg'].'" /></p>';
	
	$index='adresse4_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Adresse (suite 3):</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="40" maxlength="60" value="'.$delegue['adresse4_dlg'].'" /><span title="Majuscules (sans accents), chiffres, tirets et espaces acceptés"></span></p>';
	
	$index='cp_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Code postal :</label>';
	echo '<input type="text" required="required" class="cp_mf_delegue" pattern="[A-Z0-9 ]{2,20}" id='.$index.' name='.$index.' size="23" maxlength="20" value="'.$delegue['cp_dlg'].'" /><span title="Majuscules, chiffres et espaces acceptés."></span></p>';
	
    $index ='ville_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.' > Ville :</label>';
	echo '<input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,80}"  class="ville_mf_delegue" id='.$index.' name='.$index.' size="50" maxlength="80" value="'.$delegue['ville_dlg'].'" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>';
	    
     
    $index='pays_mf_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Pays:</label>';
	echo '<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,80}" id='.$index.' name='.$index.'  class="pays_mf_delegue" size="50" maxlength="80" value="'.$delegue['pays_dlg'].'" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>';
	
	echo '</fieldset>';
	echo '</div>';
	
// Coordonnées bancaire	
// [libelle_compte_bq_dlg],[nom_banque_dlg],[num_compte_dlg]
	echo '<div class="banque">';
	echo '<fieldset class="formulaire" >';
	echo '<legend>Coordonnées bancaires</legend>';
	
	$index='libelle_compte_bq_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Libellé du compte :</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="50" maxlength="50" value="'.$delegue['libelle_compte_bq_dlg'].'" /></p>';
	
	$index='nom_banque_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Nom de la banque :</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="50" maxlength="60" value="'.$delegue['nom_banque_dlg'].'" /></p>';
	
	$index='num_compte_delegue'.$num_delegue;
	echo '<p><label for='.$index.'> Numéro de compte du délégué :</label>';
	echo '<input type="text" id='.$index.' name='.$index.' size="50" maxlength="50" value="'.$delegue['num_compte_dlg'].'" /></p>';	
	echo '</fieldset>';	
	echo '</div>';
}
echo '</fieldset>';
echo '</form>';

?>


<!-- ******************************************** CSS / JAVASCRIPT / JQUERY ******************************************** -->
<style>
	.titreSecondaire
	{
		font-size: 1.4em;
	    letter-spacing: 1px;
	    font-style: normal;
	    font-weight: lighter;
	    margin-bottom: 19px;
	}
	.triggerToggleMandataireAnterieur>u:hover
	{
		color:blue;
	}
	form[name^="r2_2_mandataires_anterieurs_num_"] fieldset
	{
		background:#FFF9C4; /* jaune pale */
	}
	.jaunePale fieldset /* Pour mettre certaines informations des mandataires anterieurs en jaune pâle */
	{
		background:#FFF9C4; /* jaune pâle */
	}
	#boutonAnnulerNouveauMandataire
	{
		margin: 11px 7px 11px;
	    -moz-border-radius: 9px;
	    -webkit-border-radius: 9px;
	    -ie-border-radius: 9px;
	    -o-border-radius: 9px;
	    border-radius: 9px;
	    border: 3px solid orange;
	    font-size: 1.4rem;
	    font-weight: bold;
	    padding: 5px 11px 5px 11px;
	    background-color: #FFF;
	}
	#boutonAnnulerNouveauMandataire:hover
	{
		background-color: red;
	}
	input[readonly='readonly']
	{
		background:rgb(235, 235, 228); /*Gris clair : le même que quand on est en disabled */
	}
</style>
	
	<script src="js/jquery.min.js"></script>
	<script src="js/jquery-ui.min.js"></script>

	<script src="js/fonction_getParam.js"></script>
	
	<script src="js/verif_formulaire.js" type="text/javascript"></script>
	<script src="js/date_validation.js" type="text/javascript"></script>
	<script src="js/sorttable.js" type="text/javascript"></script>
	<script src="js/jquery.ui.datepicker-fr.js"></script>
	<script src="js/droit_utilisateur_page.js"></script>
	<script>

	$('#retirerAlerte').on('click', function()
	{
		$.ajax({
			url:'r2_21_ajax.php', 
			type: "get",
			data : {'id_mandataire': $(this).closest('form').find('input[name="id_mandataire_actif"]').val(),
				    'id_candidat': "<?php echo $DETAILS[$_GET['cand']]['id_compte']; ?>"
				    },
			success:function(result)
			{	
				//Rafraichir page
				window.location.reload(true);
			}
		});
	});

	document.title='Mandataire - informations';
	$('h1#titre').text("Mandataire | Informations");

	// ----- input et textarea avec fond jaune si "required" :
	$("#formulaire input:text[required],#formulaire textarea[required]").css('background-color','yellow');

	
	// ----- Code postal à 5 chiffres pour la France ou si pays vide (sinon libre) + numéros de téléphones français (commençant par +33, 0033 ou 0... avec ou sans espaces entre les séries de chiffres) sinon format plutôt libre
	
	$('input[id^="pays_af"]', 'input[id^="pays_mf"]', 'input[id^="pays_agence"]').on('change', function()
	{
		var pays=$(this).val().trim();
		var cp='#'+(($(this).attr('id')).replace('pays','cp'));
	
		if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france")
		{
			$(cp).attr('pattern','[0-9]{5}');
			$(cp).attr('placeholder','5 chiffres');
			
			if ($(this).attr('id').indexOf("pays_af") !== -1)
			{
				//$('#telephone1_af,#telephone2_af,#telecopie_af').attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
				$('input[id^="telephone1_af"]', 'input[id^="telephone2_af"]', 'input[id^="telecopie_af"]').attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
			} 
			else if ($(this).attr('id').indexOf("pays_mf") !== -1)
			{
				$('input[id^="telephone1_mf"]', 'input[id^="telephone2_mf"]', 'input[id^="telecopie_mf"]').attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
			}
		}
		else
		{
			$(cp).attr('pattern','[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ& ]{2,20}');
			$(cp).attr('placeholder','code libre');
			
			if ($(this).attr('id').indexOf("pays_af") !== -1)
			{
				$('input[id^="telephone1_af"]', 'input[id^="telephone2_af"]', 'input[id^="telecopie_af"]').attr('pattern','[0-9 \+\-\.]{8,20}');
			}
			else if ($(this).attr('id').indexOf("pays_mf") !== -1)
			{
				$('input[id^="telephone1_mf"]', 'input[id^="telephone2_mf"]', 'input[id^="telecopie_mf"]').attr('pattern','[0-9 \+\-\.]{8,20}');
			}
		}
	});
	
	
	$('input[id^="pays_af"]', 'input[id^="pays_mf"]', 'input[id^="pays_agence"]').each(function()
	{
		var pays=$(this).val().trim();
		
		if (window.console && console.log)
		{
			console.log('pays : '+pays);
		}
		
		var cp='#'+(($(this).attr('id')).replace('pays','cp'));

		if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france")
		{
			$(cp).attr('pattern','[0-9]{5}');
			$(cp).attr('placeholder','5 chiffres');
			
			if ($(this).attr('id').indexOf("pays_af") !== -1)
			{
				$('input[id^="telephone1_af"]', 'input[id^="telephone2_af"]', 'input[id^="telecopie_af"]').attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
			}
			else if ($(this).attr('id').indexOf("pays_mf") !== -1)
			{
				$('input[id^="telephone1_mf"]', 'input[id^="telephone2_mf"]', 'input[id^="telecopie_mf"]').attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8})|(?:[\\.\\-\\s]?\\d){8})$');
			}
		}
		else
		{
			$(cp).attr('pattern','[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ& ]{2,20}');
			$(cp).attr('placeholder','code libre');
			
			if ($(this).attr('id').indexOf("pays_af") !== -1)
			{
				$('input[id^="telephone1_af"]', 'input[id^="telephone2_af"]', 'input[id^="telecopie_af"]').attr('pattern','[0-9 \\+\\-\\.]{8,20}');
			}
			else if ($(this).attr('id').indexOf("pays_mf") !== -1)
			{
				$('input[id^="telephone1_mf"]', 'input[id^="telephone2_mf"]', 'input[id^="telecopie_mf"]').attr('pattern','[0-9 \\+\\-\\.]{8,20}');
			}
		}
	});
	
	$(".triggerToggleMandataireAnterieur").css('cursor', 'pointer');
	$(".triggerToggleMandataireAnterieur").css('font-size', '1.2em');


	//Au chargement de la page, cacher tout ce qui concerne l'AF (pour mandataire antérieur et actif et creation mandataire) si on est en MF, et tout afficher si on est en AF
	for ($i=0;$i<$(".qualiteaf").length;$i++)
	{
		if($(".qualiteaf").eq($i).prop("checked") == false) //Si Association de financement NON coché
		{
			$(".qualiteaf").eq($i).closest('form').find('.adresse_af').hide(); //cacher l'adresse de l'AF associé
			$(".qualiteaf").eq($i).closest('form').find('.libelle_pdt_af').hide(); //cacher la dénomination supplémentaire evoquant une AF
			$(".qualiteaf").eq($i).closest('form').find('.rna').hide(); //Cacher la zone code RNA
		}
		else //Si Association de financement coché
		{
			$(".qualiteaf").eq($i).closest('form').find('.adresse_af').show();
			$(".qualiteaf").eq($i).closest('form').find('.libelle_pdt_af').show();
			$(".qualiteaf").eq($i).closest('form').find('.rna').show();
			$(".qualiteaf").eq($i).closest('form').find('.rna').find('input').attr('pattern','^W[0-9]{9}$'); //Mettre le pattern sur le rna si AF
		}
	}

	//Réagir au clic sur le radio bouton 'Mandataire financier' du bloc Qualité (pour mandataire antérieur et actif et creation mandataire)
	$(".qualitemf").on('click',function()
	{
		$(this).closest('form').find('.adresse_af').hide();
		$(this).closest('form').find('.adresse_af').find('input').val(''); //vider tous les champs input de l'adresse de l'asso
		$(this).closest('form').find('.adresse_af').find('textarea').val(''); //vider tous les champs input de l'adresse de l'asso
		$(this).closest('form').find('.libelle_pdt_af').hide();
		$(this).closest('form').find('.rna').find('input').val(''); //On vide la valeur du RNA (au cas où elle contiendrait une valeur) puisqu'on vient de choisir MF (pour que ce soit NULL en BDD si soumission form)
		$(this).closest('form').find('.rna').find('input').removeAttr('pattern'); //Retirer le pattern (si besoin) pour que la validation du formulaire ne plante pas si on est en MF
		$(this).closest('form').find('.rna').hide(); //Cacher la zone code RNA
	});

	//Réagir au clic sur le radio bouton 'Association de financement' du bloc Qualité (pour mandataire antérieur et actif et creation mandataire)
	$(".qualiteaf").on('click',function()
	{
		$(this).closest('form').find('.adresse_af').show();
		$(this).closest('form').find('.libelle_pdt_af').show();
		$(this).closest('form').find('.rna').show();
		$(this).closest('form').find('.rna').find('input').attr('pattern','^W[0-9]{9}$'); //Mettre le pattern sur le rna si AF
	});
	// ----- noms de banques + noms de villes


	//// [Suite à modification] CONTROLES DU FORMATAGE DES DATES DE VALIDITE

	//Datepicker- Ajout par EA le 06 11 2018 pour ano 650 pour qu'il y est le calendrier dynamique
	$('input[id^="date_dbt_validite"]').datepicker($.datepicker.regional["fr"]);
	$('input[id^="date_fin_validite"]').datepicker($.datepicker.regional["fr"]);

	$('input[id^="date_dbt_validite"], input[id^="date_fin_validite"]').on("change paste", function()
	{
		//Vider les zones de message à côté des dates de validité
		$(this).closest('form').find('input[id^="date_dbt_validite"]').next('span').text('');
		$(this).closest('form').find('input[id^="date_dbt_validite"]').next('span').attr('title','');
		$(this).closest('form').find('input[id^="date_fin_validite"]').next('span').text('');
		$(this).closest('form').find('input[id^="date_fin_validite"]').next('span').attr('title','');

		//Controle du format de la date de DEBUT de validite
		var idElementDateDbtValidite = $(this).closest('form').find('input[id^="date_dbt_validite"]').attr('id');
		var elementDateDbtValidite = document.getElementById(idElementDateDbtValidite);
		var selecteurJquerySurElementDateDebutValidite = '#'+idElementDateDbtValidite;

		if(elementDateDbtValidite.validity.typeMismatch)
		{	
			$(selecteurJquerySurElementDateDebutValidite).next('span').attr('title','Une telle date n\'existe pas. ');
			elementDateDbtValidite.setCustomValidity("Une telle date n'existe pas.");
		}
		else
		{		
			elementDateDbtValidite.setCustomValidity("");
			$(selecteurJquerySurElementDateDebutValidite).css('border-color','none');
			$(selecteurJquerySurElementDateDebutValidite).next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');

			if ($(selecteurJquerySurElementDateDebutValidite).val().length == 10)
			{
				verification = date_validation($(selecteurJquerySurElementDateDebutValidite).val());

				if (!verification)
				{
					$(selecteurJquerySurElementDateDebutValidite).next("span").attr('title','Une telle date n\'existe pas. ');	
					elementDateDbtValidite.setCustomValidity("La date est invalide.");
				}
			}
			else if ($(selecteurJquerySurElementDateDebutValidite).val().length == 0)
			{
				$(selecteurJquerySurElementDateDebutValidite).next("span").attr('title','Veuillez renseigner cette date. ');	
				elementDateDbtValidite.setCustomValidity("La date est vide.");
			}
  		}

		//Controle du format de la date de FIN de validite si besoin
		var periodicite = $(this).closest('form').find('input[name^="periodicite"], select[name^="periodicite"]').val();

		if(periodicite == 'anterieur')
		{
			var idElementDateFinValidite = $(this).closest('form').find('input[id^="date_fin_validite"]').attr('id');
			var elementDateFinValidite = document.getElementById(idElementDateFinValidite);
			var selecteurJquerySurElementDateFinValidite = '#'+idElementDateFinValidite;

			if(elementDateFinValidite.validity.typeMismatch)
			{	
				$(selecteurJquerySurElementDateFinValidite).next('span').attr('title','Une telle date n\'existe pas. ');
				elementDateFinValidite.setCustomValidity("Une telle date n'existe pas.");
			}
			else
			{		
				elementDateFinValidite.setCustomValidity("");
				$(selecteurJquerySurElementDateFinValidite).css('border-color','none');
				$(selecteurJquerySurElementDateFinValidite).next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');

				if ($(selecteurJquerySurElementDateFinValidite).val().length == 10)
				{
					verification = date_validation($(selecteurJquerySurElementDateFinValidite).val());

					if (!verification)
					{
						$(selecteurJquerySurElementDateFinValidite).next("span").attr('title','Une telle date n\'existe pas. ');	
						elementDateFinValidite.setCustomValidity("La date est invalide.");
					}
				}
				else if ($(selecteurJquerySurElementDateFinValidite).val().length == 0)
				{
					$(selecteurJquerySurElementDateFinValidite).next("span").attr('title','Veuillez renseigner cette date. ');	
					elementDateFinValidite.setCustomValidity("La date est vide.");
				}
	  		}			
		}
	});

	//// [Suite à modification] CONTROLES SUR LA PERTINENCE DES DATES DE VALIDITE

	// [Suite à modification] Contrôles de la date de debut de validité sur le formulaire du MANDATAIRES ACTIF dès que la date de debut de validité a bougé
	$('#date_dbt_validite_actif').on("change paste", function()
	{
		var elementClique = document.getElementById('date_dbt_validite_actif');

		if(elementClique.checkValidity() == true) //Si aucun problème de format détécté plus haut, alors on peut tester la pertinence de la date de debut de validite
		{
			//Creation de variables pour les RG de pertinence des dates de validite
			var dateDebutValiditeString = $(this).val();
			var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2); //"Bon pattern" veut dire "bon pattern pour comparer des dates au format String"

			//Règle - La date de debut de validité du MF actif doit être supérieure à toutes les dates de fin de validité des MF antérieurs du candidat
			var dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern = "<?php echo $dateFinValiditeLaPlusRecenteString; ?>";

			if(dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern !== '') //Si on a au moins un mandataire antérieur
			{
				var dateFinValiditeDuMandataireAnterieurLePlusRecent = dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(6,8) + '/' + dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(4,6) + '/' + dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(0,4);

				if(dateDebutValiditeBonPattern <= dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern)
				{
					$('#msgDatesValiditeMandataireActif').text('La date de debut de validité du MF actif doit être supérieure à toutes les dates de fin de validité des autres MF antérieurs du candidat. Or un mandataire antérieur a une date de fin de validité qui est : '+dateFinValiditeDuMandataireAnterieurLePlusRecent+'. ');
					elementClique.setCustomValidity('Erreur');
					$(this).next('span').attr('title','');
					return;
				}
			}

			//Regle - La date de début de validité ne peut être supérieure à date dépôt du compte + 6 mois, car la date de fin de validité ne peut être supérieure à date dépôt du compte + 6 mois
			var resultat = controleDateDepotPlusSixMois("date_dbt_validite_actif");

			if (resultat == 'KO')
			{
				return;
			}

			//Règle métier : La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois"
			var id = $(this).attr('id');
			controleDatePremierTourMoinsSixMois(id);
		}
	});

	// [Suite à modification] Contrôles des dates de validité sur les formulaires des MANDATAIRES ANTERIEURS d'un mandataire dès que la date de debut de validité ou celle de fin de validité a bougé
	$('form[name^="r2_2_mandataires_anterieurs_num"] input[id*="validite"]').on("change paste", function()
	{
		//Tester si problème de format sur les deux dates de validite du form
		var id = $(this).attr('id');
		var elementClique = document.getElementById(id);

		var id_jumele = '';

		if(id.indexOf('dbt') !== -1)
		{
			id_jumele = id.substring(0,5) + 'fin' + id.substring(8,19);
		}
		else
		{
			id_jumele = id.substring(0,5) + 'dbt' + id.substring(8,19);
		}

		var elementJumele = document.getElementById(id_jumele);

		if(elementClique.checkValidity() == true && elementJumele.checkValidity() == true) //Si aucun problème de format détécté plus haut sur les deux dates, alors on peut tester la pertinence des deux dates de validite	
		{
			//Règle - La date de fin de validité doit être supérieure à la date de debut de validité
			var dateDebutValiditeString = $(this).closest('fieldset').find('input[id*="validite"]').eq(0).val();
			var dateFinValiditeString =  $(this).closest('fieldset').find('input[id*="validite"]').eq(1).val();

			var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2);
			var dateFinValiditeBonPattern = dateFinValiditeString.substring(6,10) + dateFinValiditeString.substring(3,5) + dateFinValiditeString.substring(0,2);

			if(dateDebutValiditeBonPattern > dateFinValiditeBonPattern)
			{
				$(this).closest('fieldset').find('input[id^="date_dbt_validite_"]').next('span').text('La date de début de validité doit être inférieure à la date de fin de validité. ');
				$(this).closest('fieldset').find('input[id^="date_fin_validite_"]').next('span').text('La date de fin de validité doit être supérieure à la date de debut de validité. ');
				elementClique.setCustomValidity("erreur");
				elementJumele.setCustomValidity("erreur");
				$(this).next('span').attr('title','');
				$('#'+id_jumele).next('span').attr('title','');
				return;
			}

			//Règle - Si l'une (ou les deux) date de validité du mandataire antérieur est conflictuelle avec d'autres MF antérieurs, un msg expliquera que la création n’est pas possible en précisant les MF en conflit (prenom, nom, et dates de validite)
			var idDateDebutValidite = $(this).closest('fieldset').find('input[id^="date_dbt_validite"]').attr('id');		
			var resultat1 = verifierConflitsDatesValiditeSurTouteLaPeriodicite(idDateDebutValidite);	

			if (resultat1 == 'KO')
			{
				return;
			}

			//Règle - Si la période de validité du mandataire antérieur est conflictuelle avec le mandataire actif, un msg l'expliquera
			var idDateFinValidite = $(this).closest('fieldset').find('input[id^="date_fin_validite"]').attr('id');
			var resultat3 = controleConflitAvecMandActif(idDateFinValidite);
			var resultat4 = controleConflitAvecMandActif(idDateDebutValidite);

			if (resultat3 == 'KO' || resultat4 == 'KO')
			{
				return;
			}

			//Règle métier : La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois"
			var resultat5 = controleDatePremierTourMoinsSixMois(idDateDebutValidite);
			var resultat6 = controleDatePremierTourMoinsSixMois(idDateFinValidite);

			if (resultat5 == 'KO' || resultat6 == 'KO')
			{
				return;
			}

			//Règle métier : La date de de fin de validité ne peut être supérieure à "date dépôt du compte + 6 mois"
			controleDateDepotPlusSixMois(idDateFinValidite);
			controleDateDepotPlusSixMois(idDateDebutValidite);
		}
		else if(elementClique.checkValidity() == true && elementJumele.checkValidity() == false)
		{
			var resultat7 = controleConflitAvecMandAnterieurs(id);

			if (resultat7 == 'KO')
			{
				return;
			}

			var resultat8 = controleConflitAvecMandActif(id);

			if (resultat8 == 'KO')
			{
				return;
			}

			var resultat9 = controleDatePremierTourMoinsSixMois(id);

			if (resultat9 == 'KO')
			{
				return;
			}

			controleDateDepotPlusSixMois(id);
		}
		else if(elementClique.checkValidity() == false && elementJumele.checkValidity() == true)
		{
			var resultat10 = controleConflitAvecMandAnterieurs(id_jumele);

			if (resultat10 == 'KO')
			{
				return;
			}

			var resultat11 = controleConflitAvecMandActif(id_jumele);

			if (resultat11 == 'KO')
			{
				return;
			}

			var resultat12 = controleDatePremierTourMoinsSixMois(id_jumele);

			if (resultat12 == 'KO')
			{
				return;
			}

			controleDateDepotPlusSixMois(id_jumele);		
		}
	});

	// [Suite à modification] Contrôles des dates de validité sur le formulaire de CREATION D'UN MANDATAIRE dès que la date de debut de validité ou celle de fin de validité a bougé
	$('#formCreationMandataire input[id$="_validite_creation"]').on("change paste", function()
	{
		//Préparer des variables pour les RG sur les règles de validité
		var periodiciteMandataireCreation = $('#periodicite_dans_creation_mand').val();
		var dateDebutValiditeString = $('#formCreationMandataire input[id="date_dbt_validite_creation"]').val(); //format : dd/mm/YYYY
		var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2); //format : YYYYmmdd
		var id = $(this).attr('id');
		var elementClique = document.getElementById(id);

		if(periodiciteMandataireCreation == 'anterieur')
		{
			var id_jumele = '';

			if(id.indexOf('dbt') !== -1)
			{
				id_jumele = id.substring(0,5) + 'fin_validite_creation';
			}
			else
			{
				id_jumele = id.substring(0,5) + 'dbt_validite_creation';
			}

			var elementJumele = document.getElementById(id_jumele);

			if(elementClique.checkValidity() == true && elementJumele.checkValidity() == true) //Si aucun problème de format détécté plus haut sur les deux dates, alors on peut tester la pertinence des deux dates de validite	
			{
				//Règle - La date de fin de validité doit être supérieure à la date de debut de validité
				var dateFinValiditeString = $('#formCreationMandataire input[id="date_fin_validite_creation"]').val();
				var dateFinValiditeBonPattern = dateFinValiditeString.substring(6,10) + dateFinValiditeString.substring(3,5) + dateFinValiditeString.substring(0,2);

				if(dateDebutValiditeBonPattern > dateFinValiditeBonPattern)
				{
					$('#date_dbt_validite_creation').next('span').text('La date de début de validité doit être inférieure à la date de fin de validité. ');
					$('#date_fin_validite_creation').next('span').text('La date de fin de validité doit être supérieure à la date de début de validité. ');				
					elementClique.setCustomValidity("erreur");
					elementJumele.setCustomValidity("erreur");
					$('#date_dbt_validite_creation').next('span').attr('title','');
					$('#date_fin_validite_creation').next('span').attr('title','');
					return;
				}

				//Règle - Si l'une (ou les deux) date de validité du mandataire antérieur est conflictuelle avec d'autres MF antérieurs, un msg expliquera que la création n’est pas possible en précisant les MF en conflit (prenom, nom, et dates de validite)
				var idDateDebutValidite = $('#date_dbt_validite_creation').attr('id');			
				var resultat1 = verifierConflitsDatesValiditeSurTouteLaPeriodicite(idDateDebutValidite);

				if (resultat1 == 'KO')
				{
					return;
				}

				//Règle - Si la période de validité du mandataire antérieur est conflictuelle avec le mandataire actif, un msg l'expliquera
				var idDateFinValidite = $('#date_fin_validite_creation').attr('id');					
				var resultat3 = controleConflitAvecMandActif(idDateFinValidite);
				var resultat4 = controleConflitAvecMandActif(idDateDebutValidite);

				if (resultat3 == 'KO' || resultat4 == 'KO')
				{
					return;
				}				

				//Règle métier : La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois", et donc même chose pour la date de fin de validite
				var resultat5 = controleDatePremierTourMoinsSixMois(idDateDebutValidite);
				var resultat6 = controleDatePremierTourMoinsSixMois(idDateFinValidite);

				if (resultat5 == 'KO' || resultat6 == 'KO')
				{
					return;
				}

				//Règle métier : La date de de fin de validité ne peut être supérieure à "date dépôt du compte + 6 mois", et donc même chose pour la date de début de validité
				controleDateDepotPlusSixMois(idDateFinValidite);
				controleDateDepotPlusSixMois(idDateDebutValidite);
			}
			else if(elementClique.checkValidity() == true && elementJumele.checkValidity() == false)
			{
				var resultat7 = controleConflitAvecMandAnterieurs(id);

				if (resultat7 == 'KO')
				{
					return;
				}

				var resultat8 = controleConflitAvecMandActif(id);

				if (resultat8 == 'KO')
				{
					return;
				}

				var resultat9 = controleDatePremierTourMoinsSixMois(id);

				if (resultat9 == 'KO')
				{
					return;
				}

				controleDateDepotPlusSixMois(id);
			}
			else if(elementClique.checkValidity() == false && elementJumele.checkValidity() == true)
			{
				var resultat10 = controleConflitAvecMandAnterieurs(id_jumele);

				if (resultat10 == 'KO')
				{
					return;
				}

				var resultat11 = controleConflitAvecMandActif(id_jumele);

				if (resultat11 == 'KO')
				{
					return;
				}

				var resultat12 = controleDatePremierTourMoinsSixMois(id_jumele);

				if (resultat12 == 'KO')
				{
					return;
				}

				controleDateDepotPlusSixMois(id_jumele);
			}
		}
		else //si mandataire ACTIF
		{
			if(elementClique.checkValidity() == true) //Si aucun problème de format détécté plus haut, alors on peut tester la pertinence de la date de debut de validite
			{			
				//Règle - La date de debut de validité du futur nouveau MF actif doit être supérieure à toutes les dates de fin de validité des MF antérieurs du candidat
				var dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern = "<?php echo $dateFinValiditeLaPlusRecenteString; ?>";

				if(dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern !== '') //Si on a au moins un mandataire antérieur
				{
					var dateFinValiditeDuMandataireAnterieurLePlusRecent = dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(6,8) + '/' + dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(4,6) + '/' + dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(0,4);

					if(dateDebutValiditeBonPattern <= dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern)
					{	
						var msg = 'La date de debut de validité du futur nouveau MF actif doit être supérieure à toutes les dates de fin de validité des autres MF antérieurs du candidat. Or un mandataire antérieur a une date de fin de validité qui est : '+dateFinValiditeDuMandataireAnterieurLePlusRecent+'. ';
						$('#date_dbt_validite_creation').next('span').text(msg);
						elementClique.setCustomValidity("erreur");
						$(this).next('span').attr('title','');
						return;
					}
				}

				//Règle - La date de debut de validité du futur nouveau MF actif doit être supérieure à la date de début de validité du MF actif actuel
				var dateDebutValiditeActuelMandActif = $('#date_dbt_validite_actif').val();
				var dateDebutValiditeActuelMandActifBonPattern = dateDebutValiditeActuelMandActif.substring(6,10) + dateDebutValiditeActuelMandActif.substring(3,5) + dateDebutValiditeActuelMandActif.substring(0,2);

				if(dateDebutValiditeBonPattern <= dateDebutValiditeActuelMandActifBonPattern)
				{
					var messageActuel = $('#date_dbt_validite_creation').next('span').text();
					var messageFinal = messageActuel + 'La date de debut de validité du futur nouveau MF actif doit être supérieure à la date de début de validité du MF actif actuel qui est '+dateDebutValiditeActuelMandActif+'. ';
					$('#date_dbt_validite_creation').next('span').text(messageFinal);
					elementClique.setCustomValidity("erreur");
					$(this).next('span').attr('title','');
					return;
				}

				//Regle - La date de début de validité ne peut être supérieure à date dépôt du compte + 6 mois, car la date de fin de validité ne peut l'être
				var idDateDebutValidite = $('#date_dbt_validite_creation').attr('id');
				var resultat14 = controleDateDepotPlusSixMois(idDateDebutValidite);

				if (resultat14 == 'KO')
				{
					return;
				}

				//Règle métier : La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois"
				controleDatePremierTourMoinsSixMois(idDateDebutValidite);
			}
		}
	});

	function controleConflitAvecMandActif(idElementDateValidite) //Peut prendre la date de debut de validite mais aussi la date de fin de validité
	{
		var selecteurJquerySurElementDateValidite = "#"+idElementDateValidite;
		var elementDateValidite = document.getElementById(idElementDateValidite);
		var dateValidite = $(selecteurJquerySurElementDateValidite).val();
		var dateValiditeBonPattern = dateValidite.substring(6,10) + dateValidite.substring(3,5) + dateValidite.substring(0,2);
		var typeDeDate = ''; //Prendra 'début' ou 'fin'
		var resultat = 'ok';

		if (idElementDateValidite.indexOf('dbt') !== -1)
		{
			typeDeDate = 'début';
		}
		else
		{
			typeDeDate = 'fin';
		}

		//Règle - Si la période de validité du mandataire antérieur est conflictuelle avec le mandataire actif, un msg l'expliquera
		var dateDebutValiditeMandActif = $('#date_dbt_validite_actif').val();

		if(dateDebutValiditeMandActif !== '')
		{
			var dateDebutValiditeMandActifBonPattern = dateDebutValiditeMandActif.substring(6,10) + dateDebutValiditeMandActif.substring(3,5) + dateDebutValiditeMandActif.substring(0,2);

			if(dateValiditeBonPattern >= dateDebutValiditeMandActifBonPattern)
			{
				var messageActuel = $(selecteurJquerySurElementDateValidite).next('span').text();
				var nouveauMessage = messageActuel + "La date de "+typeDeDate+" de validité doit être inférieure à la date de debut de validité du mandataire actif ("+dateDebutValiditeMandActif+"). ";
				$(selecteurJquerySurElementDateValidite).next('span').text(nouveauMessage);
				elementDateValidite.setCustomValidity("erreur");
				$(selecteurJquerySurElementDateValidite).next('span').attr('title','');
				resultat = 'KO';
			}
		}

		return resultat;
	}//Fin fonction

	function controleDateDepotPlusSixMois(idElementDateValidite) //Peut prendre la date de debut de validite mais aussi la date de fin de validité
	{
		var selecteurJquerySurElementDateValidite = "#"+idElementDateValidite;
		var elementDateValidite = document.getElementById(idElementDateValidite);
		var typeDeDate = ''; //Prendra 'début' ou 'fin'
		var resultat = 'ok';

		if (idElementDateValidite.indexOf('dbt') !== -1)
		{
			typeDeDate = 'début';
		}
		else
		{
			typeDeDate = 'fin';
		}

		//Règle métier : La date de de fin de validité ne peut être supérieure à "date dépôt du compte + 6 mois", et donc même chose pour la date de début de validité
		var dateValiditeSaisie_string = $(selecteurJquerySurElementDateValidite).val();
		var dateValiditeSaisie_string_bonPattern = dateValiditeSaisie_string.substring(6,10) + dateValiditeSaisie_string.substring(3,5) + dateValiditeSaisie_string.substring(0,2);
		var dateDepotComptePlusSixMois_string_bonPattern = '<?php echo $dateDepotComptePlusSixMois_string; ?>';

		if(dateDepotComptePlusSixMois_string_bonPattern !== '') //Si la date de depot du compte est renseignée
		{		
			if(dateValiditeSaisie_string_bonPattern > dateDepotComptePlusSixMois_string_bonPattern)
			{
				var messageActuel = $(selecteurJquerySurElementDateValidite).next('span').text();
				var nouveauMessage = messageActuel + "La date de "+typeDeDate+" de validité ne peut être supérieure à \"date dépôt du compte + 6 mois\". ";
				$(selecteurJquerySurElementDateValidite).next('span').text(nouveauMessage);
				elementDateValidite.setCustomValidity("erreur");
				$(selecteurJquerySurElementDateValidite).next('span').attr('title','');
				resultat = 'KO';
			}
		}

		return resultat;
	}//Fin fonction

	function controleDatePremierTourMoinsSixMois(idElementDateValidite) //Peut prendre la date de debut de validite mais aussi la date de fin de validité
	{
		var selecteurJquerySurElementDateValidite = "#"+idElementDateValidite;
		var elementDateValidite = document.getElementById(idElementDateValidite);
		var typeDeDate = ''; //Prendra 'début' ou 'fin'
		var resultat = 'ok';

		if (idElementDateValidite.indexOf('dbt') !== -1)
		{
			typeDeDate = 'début';
		}
		else
		{
			typeDeDate = 'fin';
		}

		//Règle métier : La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois", et donc même chose pour la date de fin de validite
		var dateValiditeSaisie_string = $(selecteurJquerySurElementDateValidite).val();
		var dateValiditeSaisie_string_bonPattern = dateValiditeSaisie_string.substring(6,10) + dateValiditeSaisie_string.substring(3,5) + dateValiditeSaisie_string.substring(0,2);
		var datePremierTourElecMoinsSixMois_string = '<?php echo $datePremierTourElecMoinsSixMois_string; ?>';

		// Modif ELM20190908 : Prendre le 1er jour du mois pour les 6 mois (mail TJ 20190808)
		datePremierTourElecMoinsSixMois_string = datePremierTourElecMoinsSixMois_string.substring(0,6)+'01';

		if(dateValiditeSaisie_string_bonPattern < datePremierTourElecMoinsSixMois_string)
		{
			var messageActuel = $(selecteurJquerySurElementDateValidite).next('span').text();
			var nouveauMessage = messageActuel + "La date de "+typeDeDate+" de validité d'un mandataire ne peut être inférieure à \"premier tour de l'election moins 6 mois\". ";
			$(selecteurJquerySurElementDateValidite).next('span').text(nouveauMessage);
			elementDateValidite.setCustomValidity("erreur");
			$(selecteurJquerySurElementDateValidite).next('span').attr('title','');
			resultat = 'KO';
		}

		return resultat;
	}//Fin fonction

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////// COPIE BCK /////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	// Cette fonction vérifie les conflits de dates de validité, entre un mandataire antérieur (du formulaire "mandataire antérieur" OU du formulaire "creation mandataire antérieur") et les autres mandataires antérieurs
	function verifierConflitsDatesValidite_OLDDDDDDDDDDDDDD(idElementClique)
	{
		var selecteurJquerySurElementclique = "#"+idElementClique; //Refaire un selecteur jQuery sur l'element clique
		var elementClique = document.getElementById(idElementClique);
		var nomMandataire = $(selecteurJquerySurElementclique).closest('form').find('input[id^="nom_mf"]').val();
		var prenomMandataire = $(selecteurJquerySurElementclique).closest('form').find('input[id^="prenom_mf"]').val();
		var datesValiditeTousMandataires_array = <?php echo $arrayDatesValiditeTousMandataires_json; ?>; //L'array ne contient que les dates des mandataires ANTERIEURS
		var tableauConflits = new Array(); //Sera alimenté dans le forEach

		var dateDebutValiditeString = $(selecteurJquerySurElementclique).closest('fieldset').find('input[id*="validite"]').eq(0).val();
		var dateFinValiditeString = $(selecteurJquerySurElementclique).closest('fieldset').find('input[id*="validite"]').eq(1).val();
		var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2);
		var dateFinValiditeBonPattern = dateFinValiditeString.substring(6,10) + dateFinValiditeString.substring(3,5) + dateFinValiditeString.substring(0,2);

		var resultat = 'ok';

		datesValiditeTousMandataires_array.forEach(function(currentCoupleDeDateDeValidite)
		{	
		  	var borneInf = currentCoupleDeDateDeValidite.date_debut_validite;
			var borneSup = currentCoupleDeDateDeValidite.date_fin_validite;
			var prenomMf = currentCoupleDeDateDeValidite.prenom_mf;
			var nomMf = currentCoupleDeDateDeValidite.nom_mf;

			//Tester si on se compare à soi même
			if ((prenomMandataire == prenomMf) && (nomMandataire == nomMf))
			{
				//on ne fait rien, puisqu'on se compare à soi-même
			}
			else
			{		
				if(!((dateDebutValiditeBonPattern<borneInf && dateFinValiditeBonPattern<borneInf) || (dateDebutValiditeBonPattern>borneSup && dateFinValiditeBonPattern>borneSup)))
				{
					//Mettre en forme les dates à afficher
					var borneInf_newPattern = borneInf.substring(6,8) + '/' + borneInf.substring(4,6) + '/' + borneInf.substring(0,4);
					var borneSup_newPattern = borneSup.substring(6,8) + '/' + borneSup.substring(4,6) + '/' + borneSup.substring(0,4);

					//Alimenter le tableau des conflits
					var tableauCurrentConflit = new Array(prenomMf, nomMf, borneInf_newPattern, borneSup_newPattern);
					tableauConflits.push(tableauCurrentConflit);
				}
			}
		}); //Fin foreach

		//Préparer des informations sur la date "jumelée" à celle passée en argument de la fonction
		var creation = false; //Permet de savoir si on est dans le formulaire de creation

		if(idElementClique.indexOf('creation') !== -1)
		{
			creation = true;
		}

		var id_jumele = '';

		if(idElementClique.indexOf('dbt') !== -1)
		{
			if(creation == false)
			{
				id_jumele = idElementClique.substring(0,5) + 'fin' + idElementClique.substring(8,19);
			}
			else
			{
				id_jumele = idElementClique.substring(0,5) + 'fin' + idElementClique.substring(8,26);
			}
		}
		else
		{
			if(creation == false)
			{
				id_jumele = idElementClique.substring(0,5) + 'dbt' + idElementClique.substring(8,19);		
			}
			else
			{
				id_jumele = idElementClique.substring(0,5) + 'dbt' + idElementClique.substring(8,26);				
			}
		}

		var elementJumele = document.getElementById(id_jumele);
		var selecteurJquerySurElementJumele= "#"+id_jumele;

		if(tableauConflits.length == 1)
		{
			var messageErreurAvecUnConflit = 'La période de validité du mandataire est conflictuelle avec un autre MF du candidat : '+tableauConflits[0][0]+' '+tableauConflits[0][1]+' en fonction du '+tableauConflits[0][2]+' au '+tableauConflits[0][3]+'. ';
			$(selecteurJquerySurElementclique).next('span').text(messageErreurAvecUnConflit);
			elementClique.setCustomValidity('erreur');
			$(selecteurJquerySurElementclique).next('span').attr('title','');
			elementJumele.setCustomValidity('erreur');
			$(selecteurJquerySurElementJumele).next('span').attr('title','');
			resultat = 'KO';
		}
		else
		{
			if(tableauConflits.length > 1) //On évite le cas où le tableau est vide
			{
				//On va raccourcir un peu le message d'erreur si plusieurs conflits de date
				var messageErreurAvecUnConflit = 'La période de validité du mandataire est conflictuelle avec '+tableauConflits.length+' autres MF du candidat : '; //début du message d'erreur

				for(var i=0; i<tableauConflits.length; i++)
				{
					if (i > 0)
					{
						messageErreurAvecUnConflit = messageErreurAvecUnConflit + ', ';
					}

					messageErreurAvecUnConflit = messageErreurAvecUnConflit + tableauConflits[i][0] + ' ' + tableauConflits[i][1] + ' (' + tableauConflits[i][2] + '-' + tableauConflits[i][3] + ')';

					if (i == tableauConflits.length-1) //Le dernier
					{
						messageErreurAvecUnConflit = messageErreurAvecUnConflit + '. ' //Permet d'espacer si un autre message d'erreur qu'un conflit sur dates doit être placé après
					}
				}

				$(selecteurJquerySurElementclique).next('span').text(messageErreurAvecUnConflit); //Ecrire le message d'erreur
				elementClique.setCustomValidity('erreur');
				$(selecteurJquerySurElementclique).next('span').attr('title','');
				elementJumele.setCustomValidity('erreur');
				$(selecteurJquerySurElementJumele).next('span').attr('title','');
				resultat = 'KO';
			}
		}

		return resultat;
	}//Fin fonction



	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////// EN CHANTIER /////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
	// Cette fonction vérifie les conflits de dates de validité, entre un mandataire antérieur (du formulaire "mandataire antérieur" OU du formulaire "creation mandataire antérieur") et les autres mandataires antérieurs
	function verifierConflitsDatesValiditeSurTouteLaPeriodicite(idDuneDesDeuxDateDeValidite)
	{
		var selecteurJquerySurElementclique = "#"+idDuneDesDeuxDateDeValidite; //Refaire un selecteur jQuery sur l'element clique
		var elementClique = document.getElementById(idDuneDesDeuxDateDeValidite);
		var nomMandataire = $(selecteurJquerySurElementclique).closest('form').find('input[id^="nom_mf"]').val();
		var prenomMandataire = $(selecteurJquerySurElementclique).closest('form').find('input[id^="prenom_mf"]').val();
		var datesValiditeTousMandataires_array = <?php echo $arrayDatesValiditeTousMandataires_json; ?>; //L'array ne contient que les dates des mandataires ANTERIEURS
		var tableauConflits = new Array(); //Sera alimenté dans le forEach

		var dateDebutValiditeString = $(selecteurJquerySurElementclique).closest('fieldset').find('input[id*="validite"]').eq(0).val();
		var dateFinValiditeString = $(selecteurJquerySurElementclique).closest('fieldset').find('input[id*="validite"]').eq(1).val();
		var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2);
		var dateFinValiditeBonPattern = dateFinValiditeString.substring(6,10) + dateFinValiditeString.substring(3,5) + dateFinValiditeString.substring(0,2);

		var resultat = 'ok';

		datesValiditeTousMandataires_array.forEach(function(currentCoupleDeDateDeValidite)
		{	
		  	var borneInf = currentCoupleDeDateDeValidite.date_debut_validite;
			var borneSup = currentCoupleDeDateDeValidite.date_fin_validite;
			var prenomMf = currentCoupleDeDateDeValidite.prenom_mf;
			var nomMf = currentCoupleDeDateDeValidite.nom_mf;

			//Tester si on se compare à soi même
			if ((prenomMandataire == prenomMf) && (nomMandataire == nomMf))
			{
				//on ne fait rien, puisqu'on se compare à soi-même
			}
			else
			{		
				if(!((dateDebutValiditeBonPattern<borneInf && dateFinValiditeBonPattern<borneInf) || (dateDebutValiditeBonPattern>borneSup && dateFinValiditeBonPattern>borneSup)))
				{
					//Mettre en forme les dates à afficher
					var borneInf_newPattern = borneInf.substring(6,8) + '/' + borneInf.substring(4,6) + '/' + borneInf.substring(0,4);
					var borneSup_newPattern = borneSup.substring(6,8) + '/' + borneSup.substring(4,6) + '/' + borneSup.substring(0,4);

					//Alimenter le tableau des conflits
					var tableauCurrentConflit = new Array(prenomMf, nomMf, borneInf_newPattern, borneSup_newPattern);
					tableauConflits.push(tableauCurrentConflit);
				}
			}
		}); //Fin foreach

		//Préparer des informations sur la date "jumelée" à celle passée en argument de la fonction
		var creation = false; //Permet de savoir si on est dans le formulaire de creation

		if(idDuneDesDeuxDateDeValidite.indexOf('creation') !== -1)
		{
			creation = true;
		}

		var id_jumele = '';

		if(idDuneDesDeuxDateDeValidite.indexOf('dbt') !== -1)
		{
			if(creation == false)
			{
				id_jumele = idDuneDesDeuxDateDeValidite.substring(0,5) + 'fin' + idDuneDesDeuxDateDeValidite.substring(8,19);
			}
			else
			{
				id_jumele = idDuneDesDeuxDateDeValidite.substring(0,5) + 'fin' + idDuneDesDeuxDateDeValidite.substring(8,26);
			}
		}
		else
		{
			if(creation == false)
			{
				id_jumele = idDuneDesDeuxDateDeValidite.substring(0,5) + 'dbt' + idDuneDesDeuxDateDeValidite.substring(8,19);		
			}
			else
			{
				id_jumele = idDuneDesDeuxDateDeValidite.substring(0,5) + 'dbt' + idDuneDesDeuxDateDeValidite.substring(8,26);				
			}
		}

		var elementJumele = document.getElementById(id_jumele);
		var selecteurJquerySurElementJumele= "#"+id_jumele;

		if(tableauConflits.length == 1)
		{
			var messageErreurAvecUnConflit = 'La période de validité du mandataire est conflictuelle avec un autre MF du candidat : '+tableauConflits[0][0]+' '+tableauConflits[0][1]+' en fonction du '+tableauConflits[0][2]+' au '+tableauConflits[0][3]+'. ';
			$(selecteurJquerySurElementclique).next('span').text(messageErreurAvecUnConflit);
			elementClique.setCustomValidity('erreur');
			$(selecteurJquerySurElementclique).next('span').attr('title','');
			$(selecteurJquerySurElementJumele).next('span').text(messageErreurAvecUnConflit);
			elementJumele.setCustomValidity('erreur');
			$(selecteurJquerySurElementJumele).next('span').attr('title','');
			resultat = 'KO';
		}
		else
		{
			if(tableauConflits.length > 1) //On évite le cas où le tableau est vide
			{
				//On va raccourcir un peu le message d'erreur si plusieurs conflits de date
				var messageErreurAvecUnConflit = 'La période de validité du mandataire est conflictuelle avec '+tableauConflits.length+' autres MF du candidat : '; //début du message d'erreur

				for(var i=0; i<tableauConflits.length; i++)
				{
					if (i > 0)
					{
						messageErreurAvecUnConflit = messageErreurAvecUnConflit + ', ';
					}

					messageErreurAvecUnConflit = messageErreurAvecUnConflit + tableauConflits[i][0] + ' ' + tableauConflits[i][1] + ' (' + tableauConflits[i][2] + '-' + tableauConflits[i][3] + ')';

					if (i == tableauConflits.length-1) //Le dernier
					{
						messageErreurAvecUnConflit = messageErreurAvecUnConflit + '. ' //Permet d'espacer si un autre message d'erreur qu'un conflit sur dates doit être placé après
					}
				}

				$(selecteurJquerySurElementclique).next('span').text(messageErreurAvecUnConflit); //Ecrire le message d'erreur
				elementClique.setCustomValidity('erreur');
				$(selecteurJquerySurElementclique).next('span').attr('title','');
				$(selecteurJquerySurElementJumele).next('span').text(messageErreurAvecUnConflit);				
				elementJumele.setCustomValidity('erreur');
				$(selecteurJquerySurElementJumele).next('span').attr('title','');
				resultat = 'KO';
			}
		}

		return resultat;
	}//Fin fonction


	/* 
		Cette fonction vérifie les conflits entre une date de validité (dont l'id est passée en argument) d'un mandataire antérieur (du formulaire "mandataire antérieur" OU du formulaire "creation mandataire antérieur") 
		 et les autres mandataires antérieurs.
		La fonction gère les conflits sur plusieurs MF. Mais cela n'arrivera plus, car la fonction a été revue pour gérer unitairement les deux dates de validité, et non plus la période définie entre les deux dates de validité.
		Donc algo trop compliquée pour le besoin, mais je laisse en l'état.
	*/
	function controleConflitAvecMandAnterieurs(idElementClique)
	{
		var selecteurJquerySurElementclique = "#"+idElementClique; //Refaire un selecteur jQuery sur l'element clique
		var elementClique = document.getElementById(idElementClique);
		var nomMandataire = $(selecteurJquerySurElementclique).closest('form').find('input[id^="nom_mf"]').val();
		var prenomMandataire = $(selecteurJquerySurElementclique).closest('form').find('input[id^="prenom_mf"]').val();
		var datesValiditeTousMandataires_array = <?php echo $arrayDatesValiditeTousMandataires_json; ?>; //L'array ne contient que les dates de validité des mandataires ANTERIEURS
		var tableauConflits = new Array(); //Sera alimenté dans le forEach
		var dateValiditeCliqueeString = $(selecteurJquerySurElementclique).val();
		var dateValiditeCliqueeStringBonPattern = dateValiditeCliqueeString.substring(6,10) + dateValiditeCliqueeString.substring(3,5) + dateValiditeCliqueeString.substring(0,2);
		var resultat = 'ok';

		datesValiditeTousMandataires_array.forEach(function(currentCoupleDeDateDeValidite)
		{	
		  	var borneInf = currentCoupleDeDateDeValidite.date_debut_validite;
			var borneSup = currentCoupleDeDateDeValidite.date_fin_validite;
			var prenomMf = currentCoupleDeDateDeValidite.prenom_mf;
			var nomMf = currentCoupleDeDateDeValidite.nom_mf;

			//Tester si on se compare à soi même
			if ((prenomMandataire == prenomMf) && (nomMandataire == nomMf))
			{
				//on ne fait rien, puisqu'on se compare à soi-même
			}
			else
			{
				//Mettre en forme les dates à afficher en cas de conflit
				var borneInf_newPattern = borneInf.substring(6,8) + '/' + borneInf.substring(4,6) + '/' + borneInf.substring(0,4);
				var borneSup_newPattern = borneSup.substring(6,8) + '/' + borneSup.substring(4,6) + '/' + borneSup.substring(0,4);

				if((borneInf <= dateValiditeCliqueeStringBonPattern) && (dateValiditeCliqueeStringBonPattern <= borneSup))
				{
					//Alimenter le tableau des conflits spécifique à la date de début de validité
					var tableauCurrentConflit = new Array(prenomMf, nomMf, borneInf_newPattern, borneSup_newPattern);
					tableauConflits.push(tableauCurrentConflit);
				}
			}
		}); //Fin foreach

		if(tableauConflits.length == 1)
		{
			var messageErreurAvecUnConflit = 'La date est conflictuelle avec un autre MF du candidat : '+tableauConflits[0][0]+' '+tableauConflits[0][1]+' en fonction du '+tableauConflits[0][2]+' au '+tableauConflits[0][3]+'. ';
			$(selecteurJquerySurElementclique).next('span').text(messageErreurAvecUnConflit);
			elementClique.setCustomValidity('erreur');
			$(selecteurJquerySurElementclique).next('span').attr('title','');
			resultat = 'KO';
		}
		else
		{
			if(tableauConflits.length > 1) //On évite le cas où le tableau est vide
			{
				//On va raccourcir un peu le message d'erreur si plusieurs conflits de date
				var messageErreurAvecUnConflit = 'La date est conflictuelle avec '+tableauConflits.length+' autres MF du candidat : '; //début du message d'erreur

				for(var i=0; i<tableauConflits.length; i++)
				{
					if (i > 0)
					{
						messageErreurAvecUnConflit = messageErreurAvecUnConflit + ', ';
					}

					messageErreurAvecUnConflit = messageErreurAvecUnConflit + tableauConflits[i][0] + ' ' + tableauConflits[i][1] + ' (' + tableauConflits[i][2] + '-' + tableauConflits[i][3] + ')';

					if (i == tableauConflits.length-1) //Le dernier
					{
						messageErreurAvecUnConflit = messageErreurAvecUnConflit + '. ' //Permet d'espacer si un autre message d'erreur qu'un conflit sur dates doit être placé après
					}
				}

				$(selecteurJquerySurElementclique).next('span').text(messageErreurAvecUnConflit); //Ecrire le message d'erreur
				elementClique.setCustomValidity('erreur');
				$(selecteurJquerySurElementclique).next('span').attr('title','');
				resultat = 'KO';
			}
		}

		return resultat;
	}//Fin fonction

	function verif_date_declaration_mf(idElementClic) //Modifie par EA pour ajouter un argument afin de rendre cette fonction utilisable dans tous les formulaires mandataires (actif, anterieur(s), creation)
	{
		var date_declaration_mf3 = document.getElementById(idElementClic);
	
		//var date_pericles_existe=<?php //echo $date_pericles_existe; ?>; // if cette date existe : impossible de la remplacer par une date vide - fiche 811
	
		var verification = true;

		var selecteurJquerySurElementclique = "#"+idElementClic;
	
		if ($(selecteurJquerySurElementclique).val().length == 10)
		{
			verification = date_validation($(selecteurJquerySurElementclique).val());
		}

		if (verification)
		{
			if ($(selecteurJquerySurElementclique).val().length > 0)
			{
				$(selecteurJquerySurElementclique).next('span').attr('title','Format de date incorrect : jj/mm/aaaa demandé.');	
		
				var aucune_date = 0; // 20171102 complément fiche anomalie 127 - nouvelle variable - quand la date de dépôt est absente, l'anomalie n'est pas surlignée en rouge alors qu'elle l'est quand la date_depot > date_limite_depot
	
				var mauvaise_date = 0; // 20171102 la date est considérée comme mauvaise si elle est antérieure à la date du 1er tour ou postérieure à la date actuelle (celle de la consultation de la page)
	
				var mauvais_format_date = 0; // 20171107 nouveau - initialisation 
	
				var date_1t=<?php echo substr(date_sql_objet_ou_array_vers_int($date_1t_compare),0,8); ?>;
	
				var date_declaration_mf_min_int=<?php echo substr(date_sql_objet_ou_array_vers_int($date_declaration_mf_min),0,8); ?>;
	
				var date_declaration_mf_min_slash="<?php echo $date_declaration_mf_min_slash; ?>";

				if ($(selecteurJquerySurElementclique).val() == '')
				{  // 20171102
					aucune_date = 1;
					var date_declaration_mf_int = 0;
				}
				else if ($(selecteurJquerySurElementclique).val().length == 10)
				{
					var date_declaration_mf_int = $(selecteurJquerySurElementclique).val().substr(6,4)+''+$(selecteurJquerySurElementclique).val().substr(3,2)+''+$(selecteurJquerySurElementclique).val().substr(0,2);
				}

<?php // Ce test est réalisé pour les MG2020. Pouvoir enregistrer un mandataire s'il n'y en avait pas déjà existant
// TODO Revoir création mandataire !!!
	if(count($MANDATAIRE_ACTIF) > 1) { ?>
				if (date_declaration_mf_min_int > date_declaration_mf_int || date_declaration_mf_int > date_1t)
				{
					mauvaise_date = 1;
		
					if (date_declaration_mf_min_int > date_declaration_mf_int)
					{	
						$(selecteurJquerySurElementclique).next('span').attr('title','ANOMALIE : la date de déclaration en préfecture devrait être située entre le '+date_declaration_mf_min_slash+' et la date du 1er tour.');
						date_declaration_mf3.setCustomValidity('ANOMALIE : la date de déclaration en préfecture devrait être située entre le '+date_declaration_mf_min_slash+' et la date du 1er tour.');
					}
					else
					{
						$(selecteurJquerySurElementclique).next('span').attr('title','ANOMALIE : la date de déclaration ne peut pas être postérieure à la date du 1er tour.');
						date_declaration_mf3.setCustomValidity("ANOMALIE : la date de déclaration ne peut pas être postérieure à la date du 1er tour.");
					}
				}
				else
				{
					date_declaration_mf3.setCustomValidity("");
					$(selecteurJquerySurElementclique).css('border-color','none');
				}
<?php } ?>
					
				var mauvais_format_date = 0;
	
				if($(selecteurJquerySurElementclique).val().length!=10 && $(selecteurJquerySurElementclique).val().length>0)
				{
					mauvais_format_date = 1;
				}
			}
			else if ($(selecteurJquerySurElementclique).val().length == 0) //Modifié par EA le 21 01 2019 pour ne pas regarder si la date est dans Pericles, car complexité avec n mandataires (vu avec DH)
			{
				$(selecteurJquerySurElementclique).next("span").attr('title','Vous devez renseigner la date de déclaration du mandataire.');
				date_declaration_mf3.setCustomValidity("Vous devez renseigner la date de déclaration du mandataire.");
			}
		}
		else
		{	
			$(selecteurJquerySurElementclique).next("span").attr('title','Une telle date n\'existe pas. ');	
			date_declaration_mf3.setCustomValidity("La date est invalide.");
		}
	
		if ($(selecteurJquerySurElementclique).is(":invalid"))
		{
			$(selecteurJquerySurElementclique).css("border-color","#FF434A");
		}
		else
		{
			$(selecteurJquerySurElementclique).css("border-color","unset");
		}
	} //Fin fonction


	//[Suite à modification] CONTROLES SUR FORMATAGE ET PERTINENCE DE LA DATE DE DECLARATION EN PREFECTURE
	
	$('input[id^="date_declaration_mf"]').datepicker($.datepicker.regional["fr"]);
	
	$('input[id^="date_declaration_mf"]').on("change paste focusout", function()
	{	
		var idElementClique = $(this).attr('id');
		var elementClique = document.getElementById(idElementClique);

		if(elementClique.validity.typeMismatch)
		{
			$(this).next('span').attr('title','Une telle date n\'existe pas. ');
			elementClique.setCustomValidity("Une telle date n'existe pas.");
		} 
		else
		{	
			elementClique.setCustomValidity("");
			var selecteurSurElementClique = '#'+idElementClique;
			$(selecteurSurElementClique).css('border-color','none');
			$(this).next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');
			verif_date_declaration_mf(idElementClique);
  		}
	});

	//[Sur modif] Mettre un encadré rouge autour des 'legend' si erreur dans le fieldset
	$('.formulaire fieldset').on('change', function()
	{
		nb_erreurs_dans_fieldset = $(this).find(":invalid").length;

		if (nb_erreurs_dans_fieldset == 0)
		{
			$(this).find('legend').css("border","none");
		}
		else
		{
			$(this).find('legend').css("border","1px solid red");
		}
	});



$(document).ready(function()
{
	//Par défaut, tous les mandataires antérieurs sont "repliés"
	$(".triggerToggleMandataireAnterieur").next().hide();


	//////// [Au chargement] CONTROLES SUR PERTINENCE DE LA DATE DE DECLARATION EN PREFECTURE

	////Contrôle de la date de déclaration en prefecture sur le formulaire du MANDATAIRES ACTIF
	var idDateDeclarationPrefDuMandActif = $('#date_declaration_mf_actif').attr('id');
//	verif_date_declaration_mf(idDateDeclarationPrefDuMandActif); //Ne fonctionne pas, donc je rajoute une ligne dessous
	$('#date_declaration_mf_actif').change(); //On simule un changement

	////Contrôle de la date de déclaration en prefecture sur les formulaires des MANDATAIRES ANTERIEURS
	var nbMandAnterieur = '<?php echo count($MANDATAIRES_ANTERIEURS); ?>';

	for(var i=0; i<nbMandAnterieur; i++)
	{
		var idDateDeclarationPrefDuMandAnterieur_current = 'date_declaration_mf_'+i;
		verif_date_declaration_mf(idDateDeclarationPrefDuMandAnterieur_current);
	}

    //[Au chargement] Mettre un encadré rouge autour des 'legend' si erreur dans le fieldset (form creation seulement) - Radio bouton seulement
    var elementQualiteCreation = document.getElementById('qualiteCreation');
	elementQualiteCreation.setCustomValidity("Erreur : qualite non renseignée.");
    var elementIrregulCreation = document.getElementById('irregulCreation');
	elementIrregulCreation.setCustomValidity("Erreur : irregularite ou présence non renseignée.");

    //[Au chargement] Mettre un encadré rouge autour des 'legend' si erreur dans le fieldset (pour tous formulaires) - hors radio seulement
	$('.formulaire fieldset').each(function()
	{	
		var nb_erreurs_dans_fieldset = $(this).find(":invalid").length;

		if (nb_erreurs_dans_fieldset == 0)
		{
			$(this).find('legend').css("border","none");
		}
		else
		{
			//console.log($(this).find('legend').eq(0).text());	
			$(this).find('legend').css("border","1px solid red");
		}
	});

	//Gérer l'affichage des mandataires antérieurs suite à un clic (un seul déplié à la fois + toggle possible), avec impossibilité d'ouvrir un nouveau mandataire (ou même de replier celui ouvert) si des modifications sont non sauvées sur celui ouvert
	$(".triggerToggleMandataireAnterieur").click(function()
	{
		//////// Vérifier qu pas d'erreur dans le form du mand actif
		nbErreursFormMandActif = $('#formMandActif').find(":invalid").length;

		if (nbErreursFormMandActif !== 0)
		{
			alert('Il y a une ou des erreurs sur le formulaire du mandataire actif. Merci de corriger avant de visualiser un autre mandataire.');
			return false; //On sort de la fonction
		}

		//////// Gestion de l'affichage des mandataires antérieurs

		//// Prise d'informations

		//Récupérer le display initial du mandataire cliqué
		var displayLorsDuClic = $(this).next().css('display');

		//Récupérer état global de l'affichage des mandataires antérieurs
		var auMoinsUnMandAntDeplie = false;

		$(".triggerToggleMandataireAnterieur").each(function()
		{
			if($(this).next().css('display') !== 'none')
			{
				auMoinsUnMandAntDeplie = true;
			}
		});

		////Vérifier si des modifications non enregistrées vont être perdues

		if(displayLorsDuClic == 'none') //On a cliqué sur un mandataire antérieur qui est non-déplié
		{
			if(auMoinsUnMandAntDeplie == true) //L'utilisateur veut voir un nouveau mandataire antérieur, il faut vérifier que celui qu'il quitte ne comporte pas des modifications non sauvées
			{
		    	if(desFormulairesOntEteModifies == true)
		    	{
			    	alert('Avant de visualiser un autre mandataire, vous devez enregistrer votre formulaire.');
			    	return false; //On sort de la fonction
				}
			}
			else //L'utilisateur veut voir un premier mandataire antérieur, il faut vérifier s'il y a des modifications non enregistrée sur le mandataire actif
			{
				if(desFormulairesOntEteModifies == true)
		    	{
		    		if($('#formMandActif').css('display') !== 'none') //Si le mandataire actif est visible, la modification non sauvé vient de là
		    		{
			    		alert('Avant de visualiser un mandataire antérieur, vous devez enregistrer vos modifications sur le mandataire actif.');
				    	return false; //On sort de la fonction
		    		}
		    		else //Sinon elle vient du formulaire de création
		    		{
		    			alert('Vous ne pouvez pas visualiser un mandataire antérieur pendant la création d\'un mandataire.');
				    	return false; //On sort de la fonction
		    		}
		    	}
			}
		}
		else  //On a cliqué sur un mandataire antérieur qui est déplié
		{
			if(desFormulairesOntEteModifies == true) //On a fait des modifications non sauvées sur le form déplié
	    	{
	    		alert('Merci de sauver vos modifications avant de replier ce mandataire.');
	    		return false; //On sort de la fonction
			}
		}

		////Gérer l'affichage des mandataires antérieurs, et du mandataire actif aussi

		//Cacher tous les mandataires antérieurs (pour l'instant)
		$(".triggerToggleMandataireAnterieur").each(function()
		{
			$(this).next().hide();
		});

		if(displayLorsDuClic == 'none')
		{
			$('#formMandActif').hide();
			$(this).next().show(); //Déplier celui qui a été cliqué (s'il etait caché)
			$('html,body').animate({scrollTop: $(this).eq(0).offset().top}, 'slow'); //Placer le début du mandataire antérieur choisi en haut de l'écran
		}
		else //aucun mandataire antérieur déplié
		{
			$('#formMandActif').show();
			$('html,body').animate({scrollTop: $(".triggerToggleMandataireAnterieur").eq(0).offset().top}, 'slow'); //Placer le début des mandataires antérieurs en haut de l'écran
		}
	});

	//Initialiser des variables pour détecter des changement dans les formulaires (actif + anterieurs) quand click sur creation (d'un mandataire)
	var desFormulairesOntEteModifies = false;
	var tableauDesFormulairesDeMandatairesModifies = new Array();

	//Détecter les formulaires qui ont été modifiés
	$('form').on('change', function()
	{
		desFormulairesOntEteModifies = true;
		var prenomMandataire = $(this).find('input[name^="prenom_mf_"]').val();
		var nomMandataire = $(this).find('input[name^="nom_mf_"]').val();
		var nomPrenomMandataire = prenomMandataire+" "+nomMandataire;

		if (tableauDesFormulairesDeMandatairesModifies.indexOf(nomPrenomMandataire) == -1)
		{
			tableauDesFormulairesDeMandatairesModifies.push(nomPrenomMandataire);
		}

		// for(var i=0;i<tableauDesFormulairesDeMandatairesModifies.length;i++)
		// {
		// 	console.log(tableauDesFormulairesDeMandatairesModifies[i]);
		// }	
	});


	//Intialiser un message même s'il est caché par défault
	$('#date_dbt_validite_creation').next('span').attr('title',' Format de date incorrect : jj/mm/aaaa demandé. ');


	//////// [Au chargement] CONTROLES SUR LA PERTINENCE DES DATES DE VALIDITE

	//// [Au chargement] Contrôle de la date de debut de validité sur le formulaire du MANDATAIRES ACTIF

	//Creation de variables pour les RG de pertinence des dates de validite
	var elementDateDbtValidite = document.getElementById('date_dbt_validite_actif');
	var dateDebutValiditeString = $('#date_dbt_validite_actif').val();

	if (dateDebutValiditeString != undefined){

		if (dateDebutValiditeString == '')
		{
			$('#date_dbt_validite_actif').next('span').attr('title','Veuillez renseigner cette date');
			elementDateDbtValidite.setCustomValidity('Erreur');
		}
		else
		{
			var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2); //"Bon pattern" veut dire "bon pattern pour comparer des dates au format String"
	
			//Règle - La date de debut de validité du MF actif doit être supérieure à toutes les dates de fin de validité des MF antérieurs du candidat
			var dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern = "<?php echo $dateFinValiditeLaPlusRecenteString; ?>";
	
			if(dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern !== '') //Si on a au moins un mandataire antérieur
			{
				var dateFinValiditeDuMandataireAnterieurLePlusRecent = dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(6,8) + '/' + dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(4,6) + '/' + dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern.substring(0,4);
	
				if(dateDebutValiditeBonPattern <= dateFinValiditeDuMandataireAnterieurLePlusRecent_bonPattern)
				{
					$('#msgDatesValiditeMandataireActif').text('La date de debut de validité du MF actif doit être supérieure à toutes les dates de fin de validité des autres MF antérieurs du candidat. Or un mandataire antérieur a une date de fin de validité qui est : '+dateFinValiditeDuMandataireAnterieurLePlusRecent+'. ');
					elementDateDbtValidite.setCustomValidity('Erreur');
					$('#date_dbt_validite_actif').next('span').attr('title','');
					return; //une seule erreur affichée (sinon message trop long)
				}
			}
	
			//Regle - La date de début de validité ne peut être supérieure à date dépôt du compte + 6 mois, car la date de fin de validité ne peut être supérieure à date dépôt du compte + 6 mois
			var resultat = controleDateDepotPlusSixMois("date_dbt_validite_actif");
	
			if (resultat == 'KO')
			{
				return;
			}		
	
			//Règle - La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois"
			var id = $('#date_dbt_validite_actif').attr('id');
			controleDatePremierTourMoinsSixMois(id);
		}

	}

	//// [Au chargement] Contrôle des dates de validité sur les formulaires des MANDATAIRES ANTERIEURS

	//Récupération du nombre de mandataires antérieurs
	var nbMandAnt = '<?php echo count($MANDATAIRES_ANTERIEURS); ?>';

	for(var i=0; i<nbMandAnt; i++)
	{
		var idDateDebutValidite = 'date_dbt_validite_'+i;
		var idDateFinValidite = 'date_fin_validite_'+i;
		var nomDuNameDansFormulaire = 'r2_2_mandataires_anterieurs_num_'+i;
		var elementDateFinValidite = document.getElementById(idDateFinValidite);

		//Règle - La date de fin de validité doit être supérieure à la date de debut de validité
		var dateDebutValiditeString = $('#'+idDateDebutValidite).val();
		var dateFinValiditeString = $('#'+idDateFinValidite).val();

		var dateDebutValiditeBonPattern = dateDebutValiditeString.substring(6,10) + dateDebutValiditeString.substring(3,5) + dateDebutValiditeString.substring(0,2);
		var dateFinValiditeBonPattern = dateFinValiditeString.substring(6,10) + dateFinValiditeString.substring(3,5) + dateFinValiditeString.substring(0,2);

		if(dateDebutValiditeBonPattern > dateFinValiditeBonPattern)
		{
			//Mettre champs date début validite en erreur
			$('form[name='+nomDuNameDansFormulaire+']').find('input[id^="date_dbt_validite_"]').next('span').text('La date de début de validité doit être inférieure à la date de fin de validité. ');
			var elementDateDbtValidite = document.getElementById(idDateDebutValidite);
			elementDateDbtValidite.setCustomValidity("erreur");
			$('#'+idDateDebutValidite).next('span').attr('title','');

			//Mettre champs date fin validite en erreur
			$('form[name='+nomDuNameDansFormulaire+']').find('input[id^="date_fin_validite_"]').next('span').text('La date de fin de validité doit être supérieure à la date de debut de validité. ');
			elementDateFinValidite.setCustomValidity("erreur");
			$('#'+idDateFinValidite).next('span').attr('title','');

			//On ne teste pas les autres règles si celle là apparait => Fin du if
		}
		else
		{
			//Règle - Si l'une (ou les deux) date de validité du mandataire antérieur est conflictuelle avec d'autres MF antérieurs, un msg expliquera que la création n’est pas possible en précisant les MF en conflit (prenom, nom, et dates de validite)
			var resultat1 = verifierConflitsDatesValiditeSurTouteLaPeriodicite(idDateDebutValidite);
			
			if (resultat1 == 'KO')
			{
				continue; //On passe à l'itération suivante du "for"
			}

			//Règle - Si la période de validité du mandataire antérieur est conflictuelle avec le mandataire actif, un msg l'expliquera
			var resultat3 = controleConflitAvecMandActif(idDateDebutValidite);
			var resultat4 = controleConflitAvecMandActif(idDateFinValidite);

			if (resultat3 == 'KO' || resultat4 == 'KO')
			{
				continue; //On passe à l'itération suivante du "for"
			}

			//Règle métier : La date de début de validité ne peut être inférieure à "premier tour election moins 6 mois", et donc même chose sur date de fin de validite
			var resultat5 = controleDatePremierTourMoinsSixMois(idDateDebutValidite);
			var resultat6 = controleDatePremierTourMoinsSixMois(idDateFinValidite);

			if (resultat5 == 'KO' || resultat6 == 'KO')
			{
				continue;
			}

			//Règle métier : La date de de fin de validité ne peut être supérieure à "date dépôt du compte + 6 mois", et donc même chose sur date de début de validite
			controleDateDepotPlusSixMois(idDateDebutValidite);
			controleDateDepotPlusSixMois(idDateFinValidite);
		}
	}


	$('input[id^="pays_mf"], input[id^="pays_agence"]').on('change', function()
	{	
		var pays=$(this).val().trim();
		var cp='#'+(($(this).attr('id')).replace('pays','cp'));
		var idPaysClique = $(this).attr('id');

		if(idPaysClique.indexOf('pays_mf') !== -1)
		{
			var tel1='#'+(($(this).attr('id')).replace('pays','telephone1'));
			var tel2='#'+(($(this).attr('id')).replace('pays','telephone2'));
			var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));
		}

		if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france")
		{
			$(cp).attr('pattern','[0-9]{5}');
			$(cp).attr('placeholder','5 chiffres');

			if(idPaysClique.indexOf('pays_mf') !== -1)
			{
				$(tel1+','+tel2+','+telc).attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8}))$');
			}
		}
		else
		{	
			$(cp).attr('pattern','[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏ& ]{2,20}');
			$(cp).attr('placeholder','code libre');

			if(idPaysClique.indexOf('pays_mf') !== -1)
			{
				$(tel1+','+tel2+','+telc).attr('pattern','[0-9 \\+\\-\\.]{8,20}');
				$(tel1+','+tel2+','+telc).attr('placeholder','numéro libre');
			}			
		}
	});
	
	$('input[id^="pays_mf"], input[id^="pays_agence"]').each(function()
	{		
		var pays=$(this).val().trim();
		var cp='#'+(($(this).attr('id')).replace('pays','cp'));
		var idPaysClique = $(this).attr('id');

		if(idPaysClique.indexOf('pays_mf') !== -1)
		{
			var tel1='#'+(($(this).attr('id')).replace('pays','telephone1'));
			var tel2='#'+(($(this).attr('id')).replace('pays','telephone2'));
			var telc='#'+(($(this).attr('id')).replace('pays','telecopie'));
		}

		if (pays == "" || pays == "FRANCE" || pays == "France" || pays == "france")
		{
			$(cp).attr('pattern','[0-9]{5}');
			$(cp).attr('placeholder','5 chiffres');

			if(idPaysClique.indexOf('pays_mf') !== -1)
			{
				$(tel1+','+tel2+','+telc).attr('pattern','^((00\\s?|\\+\\d{2,3}\\s?)(?:[\\.\\-\\s]?\\d){8,14}|(0([1-7]{1}|9)\\s?(?:[\\.\\-\\s]?\\d){6,8}))$');
			}
		}
		else
		{	
			$(cp).attr("pattern","[a-zA-Z0-9àâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏ& ]{2,20}");
			$(cp).attr('placeholder','code libre');

			if(idPaysClique.indexOf('pays_mf') !== -1)
			{
				$(tel1+','+tel2+','+telc).attr('pattern','[0-9 \\+\\-\\.]{8,20}');
				$(tel1+','+tel2+','+telc).attr('placeholder','numéro libre');
			}
		}
	});


	// modifie le filtre d'autocomplete par défaut : n'affiche que les termes qui COMMENCENT par l'expression recherchée :
	 
	droit_type_util_page(<?php echo $page_droit_util_values_json; ?>);
	$.ui.autocomplete.filter = function (array, term) {
		var matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex(term), "i");
		return $.grep(array, function (value) {
			return matcher.test(value.label || value.value || value);
		});
	};
	
	
	// 20180725 ajout pour fiche 809

	var presenceMandataireActif = <?php echo ($presenceMandataireActif ? 'true' : 'false') ?>;

	if (presenceMandataireActif == true)
	{
		var date_visa_compte2 = document.getElementById("date_declaration_mf_actif");
		
		date_visa_compte2.addEventListener("keyup", function (event) {
	  			
			if(date_visa_compte2.validity.typeMismatch) {
			
				date_visa_compte2.setCustomValidity("Une telle date n'existe pas.");
				
			} else {
	   				
				date_visa_compte2.setCustomValidity("");
	  			}
		});
	}

		var url_banques = 'ref/banques_2014_short.php';
		var url_villes = 'ref/communes_insee_2014_short.php';
		
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
		var normalize = function( term )
		{
		  var ret = "";
		  for ( var i = 0; i < term.length; i++ )
		  {
			ret += accentMap[ term.charAt(i) ] || term.charAt(i);
		  }
		  return ret;
		};
		
		var dataArr = []; // contiendra le nom de la banque
		var dataArrCode = []; // contiendra le code banque
		
		//Afficher les boutons submit qui sont cachés par un code JS lointain
		$('#boutonEnregistrerMandataireActif').css('display', 'initial');
		$('#boutonSupprimerMandataireActif').css('display', 'initial');
		$('.boutonSubmitManAnter').css('display', 'initial');

		//Ajouté par EA le 10 10 2018
		$('#boutonSupprimerMandataireActif').hide(); //Jusqu'à nouvel ordre, plus de bouton pour supprimer le mandataire actif, car on ne peut plus laisser 0 mandataire actif à un instant t

		//Cacher le formulaire de creation d'un mandataire
		$('#formCreationMandataire').hide();

		////Cacher les zones de creation/modification/suppression d'un mandataire pour les utilisateurs non autorisés
		var idTypeUtil = <?php echo $_SESSION['id_type_util']; ?>;
		var rapportClos = '<?php echo $rapportClos; ?>';

		//Seuls les rap et cm peuvent ajouter/modifier/supprimer des mandataires
		if ((!(idTypeUtil == 2 || idTypeUtil == 3 || idTypeUtil == 4)) || (idTypeUtil == 4 && rapportClos == 'oui'))
		{
			$('#zoneCreationMandataire').hide();
			$('#zoneModifSupprMandActif').hide();
			$('.zoneModifSupprMandAnterieur').hide();
			$('#ajouter_delegue1').hide();
		}
		
		$.getJSON(url_banques,function(data){
 
            $.each(data,function(i, item){
                dataArr[i]=item.nom;
                dataArrCode[i]=item.code;
            });
 
            $("#nom_banque").autocomplete({
            	minLength: 2,
            	maxRows: 12,
                source: dataArr,
                select: function(event,ui)
                {
        			console.log( ui.item ? "Code choisi " +ui.item.value+" et label "+ui.item.label:"Nothing selected, input was " + this.value);
      			}
                
            });
 
        });
        
        $.getJSON(url_villes, function(data_villes) { 
         //autocomplete           
         $( "#ville_af,#ville_mf,#ville_agence" ).autocomplete({
            minLength: 2,
            source: data_villes
          })
    });
	
	//Modifiée par EA le 17 10 2018 pour gérer non plus seulement le formulaire mandataire actif, mais aussi les formulaires mandataires antérieurs et le formulaire creation mandataire
	$("input[name^='chk_irregularite_mandataire']").on('change', function()
	{
		//Je recode tout le 22 01 2019 (EA)	
		if($(this).val() == 1)
		{			
			$(this).closest('fieldset').find('.saisie_commentaire_controle').show();
			$(this).closest('fieldset').find('.commentaire_controle_mandataire').attr('required','required').css('border-color', 'red');
			$(this).closest('fieldset').find('.msgIrregulNonPrecise').text("Veuillez préciser l'irrégularité.");
		}
		else if($(this).val() == 0)
		{
			$(this).closest('fieldset').find('.commentaire_controle_mandataire').removeAttr('required');
			$(this).closest('fieldset').find('.commentaire_controle_mandataire').css('border-color', '');
			$(this).closest('fieldset').find('.commentaire_controle_mandataire').val('');
			$(this).closest('fieldset').find('.msgIrregulNonPrecise').text('');
			$(this).closest('fieldset').find('.saisie_commentaire_controle').hide();
		}
	});

	$("textarea[name^='commentaire_controle_mandataire']").on('change', function()
	{
		if ($(this).val() !== '') //L'utilisateur vient de de remplir le commentaire
		{
			$(this).closest('fieldset').find('.commentaire_controle_mandataire').css('border-color', '');
			$(this).closest('fieldset').find('.msgIrregulNonPrecise').text('');
		}
		else //L'utilisateur vient de retirer son commentaire
		{
			$(this).closest('fieldset').find('.commentaire_controle_mandataire').css('border-color', 'red');
			$(this).closest('fieldset').find('.msgIrregulNonPrecise').text("Veuillez préciser l'irrégularité.");
		}
	});
	
	// ---------------- SUBMIT - envoi du formulaire d'un mandataire antérieur ou du mandataire actif pour MODIFICATION ---------------- //
	$(".formulaireMandataire input[type=submit]").on('click', function(e)
	{
		e.preventDefault; //ne pas aller sur page r2_2_ajax.php

		//Contrôler qu'il n'y a pas de message d'erreur concernant les dates de validite

		// var messageErreurDatesValidite = $(this).closest(".formulaireMandataire").find('.msgDateValidite').text();
		// messageErreurDatesValidite = $.trim(messageErreurDatesValidite);

		// if(messageErreurDatesValidite !== '')
		// {
		// 	alert('Modification impossible.\nLe formulaire contient des erreurs.\nMerci de bien vouloir les corriger.');
 	// 		return false;
		// }


		var nb_erreurs = 0;
     	nb_erreurs = $(this).closest(".formulaireMandataire").find(":invalid").length;
     	
     	if (nb_erreurs == 0)
     	{
     		$.ajax(
     		{
	            url: $(this).closest(".formulaireMandataire").attr('action'),
	            data: $(this).closest(".formulaireMandataire").serialize(),
	            type: 'POST',
	            success: function(resp)
	            {
	            	//if (resp.includes('réussie'))
	            	//{
	            		//setTimeout(window.location.reload(true), 3000);
	            		window.location.reload(true);
	            	//}
            	},
            	error: function()
            	{
            		alert('erreur lors de la modification du mandataire!');
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


  	// ---------------- SUBMIT - envoi du formulaire de CREATION D'UN MANDATAIRE ---------------- //
	$("#formCreationMandataire input[type=submit]").on('click', function(e)
	{
		e.preventDefault; //ne pas aller sur page r2_2_ajax.php

		var msgErreurQualiteMandataireNonRenseigne = $("#msgQualiteCreationMand").text();
		msgErreurQualiteMandataireNonRenseigne = $.trim(msgErreurQualiteMandataireNonRenseigne);
		var msgErreurPresenceMandataireNonRenseigne = $("#msgPresenceMandCreationMand").text();
		msgErreurPresenceMandataireNonRenseigne = $.trim(msgErreurPresenceMandataireNonRenseigne);
		var msgErreurIrregulariteMandataireNonRenseigne = $("#msgIrregulMandCreationMand").text();
		msgErreurIrregulariteMandataireNonRenseigne = $.trim(msgErreurIrregulariteMandataireNonRenseigne);
		var msgErreurDatesValidite = $('#msgDatesValiditeCreationMandataire').text();
		msgErreurDatesValidite = $.trim(msgErreurDatesValidite);

		if(msgErreurQualiteMandataireNonRenseigne !== '' || msgErreurPresenceMandataireNonRenseigne !== '' || msgErreurIrregulariteMandataireNonRenseigne !== '' || msgErreurDatesValidite !== '')
		{
     		alert('Creation impossible.\nLe formulaire contient des erreurs.\nMerci de bien vouloir les corriger.');
     		return false;
		}

		var nb_erreurs = 0;
     	nb_erreurs = $(this).closest("#formCreationMandataire").find(":invalid").length;
     	
     	if (nb_erreurs == 0)
     	{
     		$.ajax(
     		{
	            url: $(this).closest("#formCreationMandataire").attr('action'),
	            data: $(this).closest("#formCreationMandataire").serialize(),
	            type: 'POST',
	            success: function(resp)
	            {
					if (resp.indexOf('réussie') !== -1)
	            	{
	            		window.location.reload(true);
	            	}
            	}
            });
            
            return false;
     	}
     	else
     	{
     		alert('Creation impossible.\nLe formulaire contient des erreurs.\nMerci de bien vouloir les corriger.');
     		return false;
     	}
    });

    //Annulation de la creation de mandataire
    $("#boutonAnnulerNouveauMandataire").on('click', function(e)
    {
    	window.location.reload(true);
    });

	//Afficher le formulaire de creation d'un mandataire
    $("#boutonAfficherFormCreationMand").on('click', function()
    {
console.log("-->1");
        
    	//////// Vérifier qu pas d'erreur dans le form du mand actif
		nbErreursFormMandActif = $('#formMandActif').find(":invalid").length;

		if (nbErreursFormMandActif !== 0)
		{
			alert('Il y a une ou des erreurs sur le formulaire du mandataire actif. Merci de corriger avant de creer un mandataire.');
			return false; //On sort de la fonction
		}

		//////// Afficher (ou non) le formulaire de creation d'un mandataire
    	if(desFormulairesOntEteModifies == false)
    	{
	    	$("#formCreationMandataire").show();
	    	$("#finValiditeCreationMandataire").hide(); //Cacher par defaut la date de fin de validite dans la partie "creation mandataire" car on est sur mandataire actif par defaut
	    	$("#boutonAfficherFormCreationMand").hide();
	    	$('#formMandActif').hide(); //Cacher le formulaire du mandataire actif
	    	$('html,body').animate({scrollTop: $("#zoneCreationMandataire").offset().top}, 'slow'); //Placer le début du formulaire de creation de mandataire en haut de l'écran
	    	$('#zoneCreationMandataire h1').css('color', 'red'); //Mettre le titre en rouge
	    }
	    else
	    {
	    	var listeFormMandatairesModifies = '';

			for(var i=0 ; i<tableauDesFormulairesDeMandatairesModifies.length ; i++)
			{
				if(i == 0)
				{
					listeFormMandatairesModifies = tableauDesFormulairesDeMandatairesModifies[i];
				}
				else if(i == tableauDesFormulairesDeMandatairesModifies.length - 1)
				{
					listeFormMandatairesModifies = listeFormMandatairesModifies + ', ' + tableauDesFormulairesDeMandatairesModifies[i] + '.';				
				}
				else
				{
					listeFormMandatairesModifies = listeFormMandatairesModifies + ', ' + tableauDesFormulairesDeMandatairesModifies[i];
				}
			}

			var msg = 'Avant de creer un mandataire, vous devez enregistrer tous les formulaires modifiés : '+listeFormMandatairesModifies;
	    	alert(msg);
	    }
    });

    //Réagir au changement de périodicité dans la partie "creation mandataire"
	$('#periodicite_dans_creation_mand').on("change", function()
	{
		//Afficher ou cacher la date de fin de validité
		var periodicite = $(this).val();

		if(periodicite == 'actif')
		{
			$('#finValiditeCreationMandataire').hide();
			$('#date_fin_validite_creation').removeAttr('required');
			$('#date_fin_validite_creation').val(''); //vider valeur
			$('#presenceMandataire_creation').show();
			$('#date_dbt_validite_creation').val('');
		}
		else
		{
			$('#finValiditeCreationMandataire').show();
			$('#date_fin_validite_creation').attr('required', 'required');
			$('#msgPresenceMandCreationMand').text(''); //Vider la zone de message d'erreur puisqu'elle est cachée pour un mandataire antérieur
			$('#presenceMandataire_creation').hide();
			$('#date_dbt_validite_creation').val('');
		}

		//Refaire les contrôles sur les date de validité du formulaire suite au changement de la périodicité
		$('#date_dbt_validite_creation').change(); //On simule un changement de la date de début de validité
		
		if(periodicite == 'anterieur')
		{
			$('#date_fin_validite_creation').change(); //On simule un changement de la date de fin de validité
		}
	});
	
	
	//Réagir au cochage sur le champs "Présence d'un mandataire" dans le form MAND ACTIF
	$("#formMandActif #chk_presence_mandataire1_actif").on('click', function()
	{
		$(this).closest("form").find('.messagePresenceMandataire').text(''); //Vider le message en rouge s'il y en avait un
	});

	//Réagir sur cochage de la PRESENCE d'un mandataire dans le formulaire de CREATION DE MANDATAIRE
	$("#formCreationMandataire input[name='chk_presence_mandataire_creation']").on('click', function()
	{
		$('#msgPresenceMandCreationMand').text(''); //Vider la zone de message d'erreur puisque le choix est fait

		//Rendre le case irregularité valide (et en fait tout le fieldset) si irregularité mandataire déjà renseignée"
		var elementIrregulCreation = document.getElementById('irregulCreation');

		if($('#msgIrregulMandCreationMand').text() == '')
		{
			elementIrregulCreation.setCustomValidity("");
		}
	});

	//Réagir sur cochage de l'IRREGULARITE du mandataire dans le formulaire de CREATION DE MANDATAIRE (seul endroit où il peut être non coché)
	$("#formCreationMandataire input[name='chk_irregularite_mandataire_creation']").on('click', function()
	{
		$('#msgIrregulMandCreationMand').text(''); //Vider la zone de message d'erreur puisque le choix est fait

		//Rendre le case valide si "creation mandataire antérieur" OU si ("creation mandataire actif ET présence mandataire déjà renseignée")
		var elementIrregulCreation = document.getElementById('irregulCreation');

		if($('#periodicite_dans_creation_mand').val() == 'anterieur')
		{
			elementIrregulCreation.setCustomValidity("");
		}
		else if($('#chk_presence_mandataire1_creation').prop('checked') == true || $('#chk_presence_mandataire0_creation').prop('checked') == true)
		{
			elementIrregulCreation.setCustomValidity("");
		}
	});

	//Réagir sur cochage QUALITE mandataire dans le formulaire de CREATION DE MANDATAIRE (seul endroit où il peut être non coché)
	$("#formCreationMandataire input[name='qualite_creation']").on('click', function()
	{
		$('#msgQualiteCreationMand').text(''); //Vider la zone de message d'erreur puisque le choix est fait

		//Rendre le case valide
		var elementQualiteCreation = document.getElementById('qualiteCreation');
		elementQualiteCreation.setCustomValidity("");
	});

	
	$('.commentaire_controle_mandataire').on('keypress paste', function(e) { // 20170208 contrôle de la longueur du texte
		var tval = $(this).val(),
			tlength = tval.length,
			max = 248,
			remain = parseInt(max - tlength);
		// $('p').text(remain);
		if (remain <= 0 && e.which !== 0 && e.charCode !== 0) {
			alert('Texte limité à 250 caractères');
		
			$(this).val((tval).substring(0, tlength - 1))
		}
	});
	
	$("#ajouter_delegue1").click(function(e) {
		ajouter_formulaire(1);		
	
	});
	$("#ajouter_delegue2").click(function(e) {
		ajouter_formulaire(2);
		
	});
	$("#ajouter_delegue3").click(function(e) {
		ajouter_formulaire(3);		
	});
	
	$("#modifier_delegue1").click(function(e) {
		modifier_formulaire(1);		
	
	});
	$("#modifier_delegue2").click(function(e) {
		modifier_formulaire(2);		
	
	});
	$("#modifier_delegue3").click(function(e) {
		modifier_formulaire(3);		
	});
	
	$("#supprimer_delegue1").click(function(e) {
		supprimer_formulaire(1);		
	
	});
	$("#supprimer_delegue2").click(function(e) {
		supprimer_formulaire(2);		
	
	});
	$("#supprimer_delegue3").click(function(e) {
		supprimer_formulaire(3);		
	});
});


function supprimer_mandataire($id_mandataire, $periodicite_mandataire, $id_candidat)
{
	$.ajax({
				url:'r2_2_ajax.php', 
				type: "get",
				data : {'action':'obtenir_nombre_mandataire','id_candidat': $id_candidat},
				success:function(result)
				{ 
					var nbMandataire = result;

					if($periodicite_mandataire == 'actif') ////Règle - Si le mandataire supprimé est celui qui est actif, on doit le signaler par warning
					{
						if(nbMandataire == 1) //Règle - Si le mandataire supprimé est le dernier (actif et antérieurs confondus), on doit le signaler par warning
											  /*==> Règle changé par EA le 10 10 2018 car désormais on ne peut plus laisser 0 mandataire actif à un instant (plantage PHP dans "FONCTIONS_navigation")
											  	===> Nouvelle règle = interdiction de supprimer la mandataire actif
											  	====> Je modifie le code JS par sécurité, mais surtout je cache le bouton 'Supprimer' en bas du formulaire du mandataire actif (je ne le supprime pas si on s'en ressert un jour)
											  */
						{
							//$("#dialog").text("Voulez-vous vraiment supprimer définitivement ce mandataire, d'autant plus que c'est le mandataire actif, et que c'est le dernier mandataire du candidat ?"); //Commenté par EA le 10 10 2018
							alert('Interdiction de supprimer le mandataire actif ! Vous pouvez creer un nouveau mandataire actif, qui fera passer le dernier mandataire actif à antérieur.');
							return;
						}
						else
						{
							//$("#dialog").text("Voulez-vous vraiment supprimer définitivement ce mandataire, d'autant plus que c'est le mandataire actif ?"); //Commenté par EA le 10 10 2018
							alert('Interdiction de supprimer le mandataire actif ! Vous pouvez creer un nouveau mandataire actif, qui fera passer le dernier mandataire actif à antérieur.');
							return;
						}
					}
					else
					{
						if(nbMandataire == 1) //Règle - Si le mandataire supprimé est le dernier (actif et antérieurs confondus), on doit le signaler par warning
						{
							$("#dialog").text("Voulez-vous vraiment supprimer définitivement ce mandataire antérieur, d'autant plus que c'est le dernier mandataire du candidat ?");
						}
						else
						{
							$("#dialog").text("Voulez-vous vraiment supprimer définitivement ce mandataire antérieur ?");
						}
					}

					$("#dialog").dialog({
											title : 'Confirmation',
											buttons :
											{
												'Confirmer' : function()
												{
													$.ajax({
																url:'r2_2_ajax.php', 
																type: "post",
																data : {'action':'suppression','id_mandataire': $id_mandataire, 'id_candidat' : $id_candidat},
																success:function(result)
																{ 
																	window.location.reload(true);
																	$(this).dialog("close");
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

				} //Fin sucess premier appel ajax
			}); //Fin premier appel ajax
}


function modifier_formulaire(d){

	// alert('debug modifier form '+d);
	recupere_donnees(d,"modifier");
}

function supprimer_formulaire(d){
	
	texte_alerte = "Voulez-vous vraiment supprimer les renseignements de ce délégué ?";
	texte_bouton_ok = "Débloquer";
	$("#dialog").text(texte_alerte);
	$("#dialog").dialog({
		title : 'Confirmation',
		buttons : {
			'Confirmer' : function() {
				page = 'r2_2_delegue.php';
				nouvelle_url = page;
				$.ajax({
					url:nouvelle_url, 
					type: "post",
					data : {'action':'suppression','d':d,'id_mandataire':"<?php echo $id_mandataire; ?>"},
					success:function(result){ 
						window.location.reload(true);
						// location.reload(true);
						// alert(result.message);
						$(this).dialog("close");
					}
				});
				$(this).dialog("close");
			},
			"Annuler" : function() {
				$(this).dialog("close");
			}
		}
	});
	// $("#dialog").dialog("open"); 
	
	// alert('supprimer'+d.toString());	
}


function ajouter_formulaire(d){

	nb_delegue = $(".legend_fieldset_delegue").length;
	
	if(d==1)
		ajouter_formulaire_final(1);
	else{
		if(d==2)
			champs='id_delegue1';
		if(d==3)
			champs='id_delegue2';
		$.ajax({
			url: 'r2_2_delegue.php',
			data : {'action':'verifie','champs':champs,'id_mandataire':"<?php echo $id_mandataire; ?>"},
			type: 'POST',
			success: function(resp) {
					if(resp.success){
						ajouter_formulaire_final(d);
					}else{
						alert(resp.message);
					}								
				}
		});
	}

	// if(nb_delegue==1)
// 		ajouter_formulaire_final(1);
// 	else{
// 		if(d==2)
// 			champs='id_delegue1'; // pour vérifier si l'enregistrement du précédent a bien été effectué
// 		if(d==3)
// 			champs='id_delegue2';
// 		$.ajax({
// 			url: 'r2_2_delegue.php',
// 			data : {'action':'verifie','champs':champs,'id_mandataire':"<?php echo $id_mandataire; ?>"},
// 			type: 'POST',
// 			success: function(resp) {
// 					if(resp.success){
// 						ajouter_formulaire_final(d);
// 					}else{
// 						alert(resp.message);
// 					}								
// 				}
// 		});
// 	}
}		
function ajouter_formulaire_final(d){	
	var delegue = "delegue"+d.toString();		
	$("#ajouter_"+delegue).hide();	
	var index = '';
	var html = '';	
	html += '<div class="etatcivil">';
	html +='<fieldset class="formulaire" id="champs_formulaire"'+delegue+' >';
	html +="<legend>Identité</legend>";

//Identité	
	index = 'id_civ_mf_'+delegue;
	html += '<p><label for='+index+' > Civilité :</label>';
	html += '<select id='+index+' >';	
	html += '<option value="2">Madame</option>';
	html += '<option value="1">Monsieur</option>';
	html += '</select>';	
		
	index = 'nom_mf_'+delegue;
	html += '<p><label for='+index+' > Nom :</label>';
	html += '<input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id='+index+' name='+index+' size="40" maxlength="60" value="" />';
	html += '<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>';
	html += '</p>';
        
	index = "prenom_mf_"+delegue;	
    html += '<p><label for='+index+' > Prénom :</label>'; 
    html += '<input type="text" required="required"  pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,60}" id='+index+' name='+index+' size="40" maxlength="60" value="" />';
    html += '<span title="Lettres, tirets, apostrophes et espaces acceptés"></span>';
	html += '</p>';   
    html +="</fieldset>"; 
	html += '</div>';
// Coordonnées
	html += '<div class="adresse">';
	html +='<fieldset class="formulaire">';
	html +='<legend>Coordonnées</legend>';
	
	// index='particule_dlg_'+delegue;
	// html +='<p><label for='+index+'> Particule :</label>';
	// html +='<input type="text" id='+index+' name='+index+' size="40" maxlength="60" value="" /></p>';
	
	index='adresse1_mf_'+delegue;
	html +='<p><label for='+index+'> Adresse :</label>';
	html +='<input type="text" id='+index+' name='+index+' size="40" maxlength="60" value="" /></p>';
	
	index='adresse2_mf_'+delegue;
	html +='<p><label for='+index+'> Adresse (suite):</label>';
	html +='<input type="text" id='+index+' name='+index+' size="40" maxlength="60" value="" /></p>';
	
	index='adresse3_mf_'+delegue;
	html +='<p><label for='+index+'> Adresse (suite 2):</label>';
	html +='<input type="text" id='+index+' name='+index+' size="40" maxlength="60" value="" /></p>';
	
	index='adresse4_mf_'+delegue;
	html +='<p><label for='+index+'> Adresse (suite 3):</label>';
	html +='<input type="text" id='+index+' name='+index+' size="40" maxlength="60" value="" /></p>';
	
	index='cp_mf_'+delegue;
	html +='<p><label for='+index+'> Code postal :</label>';
	html +='<input required="required" type="text" id='+index+' name='+index+' size="23" pattern="[A-Z0-9 ]{2,20}" maxlength="20" value="" /><span title="Majuscules, chiffres et espaces acceptés."></span></p></p>';
	
    index ='ville_mf_'+delegue;
	html +='<p><label for='+index+' > Ville :</label>';
	html +='<input type="text" required="required" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,80}" id='+index+' name='+index+' size="50" maxlength="80" value="" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>';
	    
     
    index='pays_mf_'+delegue;
	html +='<p><label for='+index+'> Pays:</label>';
	html +='<input type="text" pattern="[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{2,80}" id='+index+' name='+index+' size="50" maxlength="80" value="" /><span title="2 caractères minimum. Lettres, tirets, apostrophes et espaces acceptés."></span></p>';
	
	html +="</fieldset>";
	html +='</div>';
// Coordonnées bancaire	
	// [libelle_compte_bq_dlg],[nom_banque_dlg],[num_compte_dlg]
	
	html += '<div class="banque">';
	html +='<fieldset class="formulaire">';
	html +='<legend>Coordonnées bancaires</legend>';
	
	index='libelle_compte_bq_'+delegue;
	html +='<p><label for='+index+'> Libellé du compte :</label>';
	html +='<input type="text" id='+index+' name='+index+' size="50" maxlength="120" value="" /></p>';
	
	index='nom_banque_'+delegue;
	html +='<p><label for='+index+'> Nom de la banque :</label>';
	html +='<input type="text" id='+index+' name='+index+' size="50" maxlength="60" value="" /></p>';
	
	index='num_compte_'+delegue;
	html +='<p><label for='+index+'> Numéro de compte du délégué :</label>';
	html +='<input type="text" id='+index+' name='+index+' size="50" maxlength="50" value="" /></p>';	
	
	html +='</fieldset>';
	html +='</div>';
// Ajout des boutons
	html += '<p><input type=\'button\' class="btn_form_delegue" id=\'enregistrer_'+delegue+'\'  value=\'Enregistrer délégué '+d.toString()+'\' />';
	html += '<input type=\'button\' class="btn_form_delegue" id=\'supprimer_'+delegue+'\'  value=\'Supprimer délégué '+d.toString()+'\'  /></p>';	
	$("#champs_formulaire_"+delegue).append(html);	
	
	$("#enregistrer_"+delegue).click(function(e){		
		recupere_donnees(d,"ajouter");				
			
	});
	$("#supprimer_"+delegue).click(function(e){
		supprimer_formulaire(d);			
	});	
	// d=d+1;
	var d2 = d+1;
	
		var suivant = "delegue"+d2.toString();
		var html_suivant = "</fieldset>";
		html_suivant += "<fieldset id=champs_formulaire_"+suivant+" >";
		html_suivant += '<legend class="legend_fieldset_delegue" >Délégué DFE/SFE '+d2.toString()+'</legend>';
		
		if(d2==4){
		
			var html_suivant = "<p> 3 délégués maximum </p>";
			
			// $("#champs_formulaire_"+delegue).after(html_suivant);
		
		}else{
			html_suivant += "<input type=\'button\' class='btn_form_delegue' id=ajouter_"+suivant+" value=\'Ajouter un délégué\' />";
		}
		html_suivant += "</fieldset>";		
		$("#champs_formulaire_"+delegue).after(html_suivant);
		$("#ajouter_"+suivant).on('click',function(e){
			ajouter_formulaire(d2);
		});
		
		$("input:text[required],#formulaire textarea[required]").css('background-color','yellow');
}

function recupere_donnees(d,action){
	
	// alert('debug récupérer données formulaire '+d);
	
	// d2 = d.toString();
	d2 = d;
// 	var id_civ_mf_delegue = $("#id_civ_mf_delegue"+d2);
// 	var nom_mf_delegue = $("#nom_mf_delegue"+d2);
// 	var prenom_mf_delegue = $("#prenom_mf_delegue"+d2);
// 	var particule_dlg_delegue = $("#particule_dlg_delegue"+d2);
// 	var adresse1_mf_delegue = $("#adresse1_mf_delegue"+d2);
// 	var adresse2_mf_delegue = $("#adresse2_mf_delegue"+d2);
// 	var adresse3_mf_delegue = $("#adresse3_mf_delegue"+d2);
// 	var adresse4_mf_delegue = $("#adresse4_mf_delegue"+d2);
// 	var cp_mf_delegue = $("#cp_mf_delegue"+d2);
// 	var ville_mf_delegue = $("#ville_mf_delegue"+d2);
// 	var pays_mf_delegue = $("#pays_mf_delegue"+d2);
// 	var libelle_compte_bq_delegue = $("#libelle_compte_bq_delegue"+d2);
// 	var nom_banque_delegue = $("#nom_banque_delegue"+d2);
// 	var num_compte_delegue = $("#num_compte_delegue"+d2);
	
	// var json={
// 			d:d,
// 			action:action,
// 			id_mandataire:"<?php echo $id_mandataire; ?>",
// 			civilite_mf_delegue:id_civ_mf_delegue.val(),
// 			nom_mf_delegue:nom_mf_delegue.val(),	
// 			prenom_mf_delegue:prenom_mf_delegue.val(),
// 			particule_dlg_delegue:particule_dlg_delegue.val(),
// 			adresse1_mf_delegue:adresse1_mf_delegue.val(),
// 			adresse2_mf_delegue:adresse2_mf_delegue.val(),
// 			adresse3_mf_delegue:adresse3_mf_delegue.val(),
// 			adresse4_mf_delegue:adresse4_mf_delegue.val(),
// 			cp_mf_delegue:cp_mf_delegue.val(),
// 			ville_mf_delegue:ville_mf_delegue.val(),
// 			pays_mf_delegue:pays_mf_delegue.val(),
// 			libelle_compte_bq_delegue:libelle_compte_bq_delegue.val(),
// 			nom_banque_delegue:nom_banque_delegue.val(),
// 			num_compte_delegue:num_compte_delegue.val()		
// 	};
	
	var json={
			d:d,
			action:action,
			id_mandataire:"<?php echo $id_mandataire; ?>",
			civilite_mf_delegue:$("#id_civ_mf_delegue"+d2).val(),
			nom_mf_delegue:$("#nom_mf_delegue"+d2).val(),	
			prenom_mf_delegue:$("#prenom_mf_delegue"+d2).val(),
			particule_dlg_delegue:$("#particule_dlg_delegue"+d2).val(),
			adresse1_mf_delegue:$("#adresse1_mf_delegue"+d2).val(),
			adresse2_mf_delegue:$("#adresse2_mf_delegue"+d2).val(),
			adresse3_mf_delegue:$("#adresse3_mf_delegue"+d2).val(),
			adresse4_mf_delegue:$("#adresse4_mf_delegue"+d2).val(),
			cp_mf_delegue:$("#cp_mf_delegue"+d2).val(),
			ville_mf_delegue:$("#ville_mf_delegue"+d2).val(),
			pays_mf_delegue:$("#pays_mf_delegue"+d2).val(),
			libelle_compte_bq_delegue:$("#libelle_compte_bq_delegue"+d2).val(),
			nom_banque_delegue:$("#nom_banque_delegue"+d2).val(),
			num_compte_delegue:$("#num_compte_delegue"+d2).val()		
	};
	
	// 20180808 : utilisation de la validation html5 du formulaire, ou plutôt du fiedset du délégué (au lieu d'un formulaire par délégué, j'ai réalisé qu'il y avait un fieldset par délégué, dans un seul formulaire nommé 'formulaire_delegue_1' probablement parce qu'il était prévu de créer un 'formulaire_delegue_2', ce qui aurait été plus logique (les données envoyées pour enregistrement ne correspondant qu'à un délégué, il eut été plus naturel de faire un formulaire par délégué et non un fieldset).
	
	// var nom_form = "formulaire_delegue_"+d2;
	
	var nom_fieldset = "champs_formulaire_delegue"+d2; // 20180808 id du fieldset dont les champs doivent être contrôlés avant enregistrement 
	
	valide = false;
	
	// alert('fieldset : '+nom_fieldset+' d : '+d);
	
	
	valide = testJason(nom_fieldset,json,d); // 20180808 ajout du nouveau paramètre 'nom_fieldset' et de 'd'
	
	
	// alert('valide json '+valide+' - nom délégué '+json.nom_mf_delegue+' bis '+json['nom_mf_delegue']);
}

function testJason(nom_fieldset,json,d){

/* 
20180808 modification complète -> suppression de ce qui suit pour utilisation de la validation de formulaires html5 :

// var alpha = /^[a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙÏÜÖÄ&\'’ ]{1,60}$/;
// 	var alpha_num = /^[0-9a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙ&\'’ ]{0,60}$/;
// 	
// 	var testOk = true;
// 	var message = "";
// 	if(!alpha.test(json['nom_mf_delegue'])){
// 		message += "Le nom ne doit comporter que des caractères alphabétiques et être renseigné.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha.test(json['prénom_mf_delegue'])){
// 		message += "Le prénom ne doit comporter que des caractères alphabétiques et être renseigné.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha.test(json['ville_mf_delegue'])){
// 		message += "La ville ne doit comporter que des caractères alphabétiques et être renseignée.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha.test(json['pays_mf_delegue'])){
// 		message += "Le pays ne doit comporter que des caractères alphabétiques et être renseigné.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha_num.test(json['adresse1_mf_delegue'])){
// 		message += "L\'adresse 1 comporte un ou plusieurs caractères interdits.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha_num.test(json['adresse2_mf_delegue'])){
// 		message += "L\'adresse 2 comporte un ou plusieurs caractères interdits.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha_num.test(json['adresse3_mf_delegue'])){
// 		message += "L\'adresse 3 comporte un ou plusieurs caractères interdits.\n\n";
// 		testOk = false;
// 	}
// 	if(!alpha_num.test(json['adresse4_mf_delegue'])){
// 		message += "L\'adresse 4 comporte un ou plusieurs caractères interdits.\n\n";
// 		testOk = false;
// 	}
// 	if(!/^[0-9a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙ&\'’ ]{0,15}$/.test(json['cp_mf_delegue'])){
// 		message += "Le code postal doit contenir entre 0 et 15 caractères.\n\n";
// 		testOk = false;
// 	}
// 	
// 	if(!/^[0-9a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÏÜÖÄÙ&\'’ ]{0,50}$/.test(json['libelle_compte_bq_delegue'])){
// 		message += "Le libellé doit contenir entre 0 et 50 caractères.\n\n";
// 		testOk = false;
// 	}
// 	if(!/^[0-9a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÏÜÖÄÙ&\'’ ]{0,60}$/.test(json['nom_banque_delegue'])){
// 		message += "Le nom de la banque doit contenir entre 0 et 60 caractères .\n\n";
// 		testOk = false;
// 	}
// 	if(!/^[0-9a-zA-Zàâäùüûñîïôöûüêéêëèíóú\-çÇÉÈEËÀÔÛÎÙ&\'’ ]{0,50}$/.test(json['num_compte_delegue'])){
// 		message += "Le numéro du compte doit contenir entre 0 et 50 caractères .\n\n";
// 		testOk = false;
// 	}
	
	// alert('test : '+testOk);
	
*/

	/* 20180808 utilisation de la validation html5 : */
	
	var fieldset = document.getElementById(nom_fieldset); // 20180808 récupération du fieldset
	
	var InvalidInputs = fieldset.querySelectorAll('input:invalid');
    var InvalidTextarea = fieldset.querySelectorAll('textarea:invalid');
     	
 	console.log('InvalidInputs', InvalidInputs);
 		
 	// console.log('nombre : '+InvalidInputs.length);
 		
 	nb_erreurs = InvalidInputs.length+InvalidTextarea.length;
 		
	if (nb_erreurs > 0) {
		
		liste_champs = 'Le formulaire ne peut être enregistré car des anomalies ont été trouvées dans les champs suivants :\n';
	
		$(InvalidInputs).each(function() {
	
		// alert($(this).attr('id'));
			var input_id = $(this).attr('id');
			var label_texte = $("label[for='" + input_id + "']").text().trim();
			// label_texte = label_texte.trim();
			
			label_texte = label_texte.substring(0, label_texte.length-2);
		
			liste_champs = liste_champs+'- '+label_texte+'\n';
		
		});
		
		$(InvalidTextarea).each(function() {
	
		// alert($(this).attr('id'));
			var input_id = $(this).attr('id');
			var label_texte = $("label[for='" + input_id + "']").text().trim();
			// label_texte = label_texte.trim();
			
			label_texte = label_texte.substring(0, label_texte.length-2);
		
			liste_champs = liste_champs+'- '+label_texte+'\n';
		
		});
		
		encadre_erreurs();

		alert(liste_champs);
		return false;
		
	} else {
    
    	envoiModifieDelegue(json,d);
 	}

/* BL 20180808 - supprimé là encore	:

if(testOk==true){
		
		envoiModifieDelegue(json);
	}
	else{
		alert(message);
	}
	
	envoiModifieDelegue(json);
}
*/
}

function envoiModifieDelegue(json,d){
	$.ajax({
		url: 'r2_2_delegue.php',
		data : json,
		type: 'POST',
		success: function(resp) {
			if(resp.success){
				alert(resp.message);
				
/* 20180808 j'ai d'abord corrigé de nombreuses erreurs de code sur cete page... mais pour réaliser très vite que tout ce qui suit est du grand n'importe quoi finalement... avec pour finir la création d'une boucle !!! -->>> supression de ce qui suit :

				// echo '<input type=\'button\' id=\'supprimer_delegue2\' style=\'width:200px;\' value=\'Supprimer délégué 2\' />';
				//var delegue = "delegue"+d;
				//var html = "<input type=\'button\' class=\'btn_form_delegue\' id=\'modifier_"+delegue+"\' value=\'Modifier délégué "+d+d+"\' />";
				//$("#enregistrer_"+delegue).after(html);
				//$("#enregistrer_"+delegue).hide();		
//				$("#modifier_"+delegue).on('click',function(e){
// 					modifier_formulaire(d);
// 				});
*/
			}else{
			
				alert(resp.message);
			}								
		}
	});
}


	</script>
	
<?php

require("inclusion/redirection_AR_rapporteur.php");

?>


<!-- https://github.com/browserstate/history.js -->
	<!-- <script src="js/jquery.history.js"></script> -->

	<!-- extrait de code_deroulant/index_bl3.html : -->
	<script src="js/jquery_NAVIGATION.js"></script>
	<script src="js/menus/jquery.dropdownPlain.js"></script>
	
	
	<!-- <link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" /> 
	-->
	<script src="js/jquery_ENTETE.js"></script>
	
	<script src="code_popup_div/code_popup_div21.js"></script>
	
	<script src="js/jquery.saisie.php.js"></script>
	
	<script src="js/verif_formulaire.js" type="text/javascript"></script>
	<script src="js/sorttable.js" type="text/javascript"></script>
	<script src="js/jquery_enregistre_formulaire.js"></script>
	
	<script src="js/disable_champs.js"></script>

<script src="js/jquery.affichage_fieldset_h5.js"></script> 

<script>

	//Dans le collapse des fieldset, casser la concurence d'un code dans le fichier 'jquery.affichage_fieldset_h5.js' qui pose problème dans mes formulaires
	$('.formulaire legend').on('click', function()
	{
		var displayModele = $(this).parent().find('p[id^="saisie_irregularite_controle"]').css('display');

		if(displayModele == 'none') //on vient de collapser
		{
			$(this).parent().find('.saisie_commentaire_controle').hide();
		}
		else //on vient de déplier
		{
			if($(this).parent().find('.chk_irregularite1').prop("checked") == true)
			{
				$(this).parent().find('.saisie_commentaire_controle').show();
			}
			else
			{
				$(this).parent().find('.saisie_commentaire_controle').hide();	
			}
		}
	});

    //[Sur modif] Gérer l'encadré rouge autour des 'legend' si erreur dans le fieldset (form creation seulement) - Radio bouton seulement //Le code va être bizarre car il faut prendre la main sur un code étrange qui gère les 3 radios boutons comme un lot
    // $('#chk_presence_mandataire1_creation', '#chk_presence_mandataire0_creation', 'input[name="chk_irregularite_mandataire_creation"]', 'input[name="qualite_creation"]').on('click', function()
    // {
    // 	//Fieldset 'controle mandataires'
    // 	if($('#msgPresenceMandCreationMand').text() == '' && $('#msgIrregulMandCreationMand').text() == '') //pour mand anterieur
    // 	{
    // 		$('#msgIrregulMandCreationMand').closest('fieldset').find('legend').css("border","none");
    // 	}
    // 	else
    // 	{
    // 		$('#msgIrregulMandCreationMand').closest('fieldset').find('legend').css("border","1px solid red");
    // 	}

    //    	//Fieldset 'qualité'
    //    	if($('#msgQualiteCreationMand').text() == '')
    //    	{
    //    	    $('#msgQualiteCreationMand').closest('fieldset').find('legend').css("border","none");	
    //    	}
    //    	else
    //    	{
    //    		$('#msgQualiteCreationMand').closest('fieldset').find('legend').css("border","1px solid red");
    //    	}
    // });

</script>

<?php

	// NB. Le précédent script sert à l'envoi du formulaire avec vérification 

	echo "</div>"; // fin du main
	
echo "</div>"; // fin du frame


require("structure/pied.php");

?>