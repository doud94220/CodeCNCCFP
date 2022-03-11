<?php

/* TEST 20180515 ---- A SUIVRE ------


- réf. requête : 10042230938
- Législative générale 
- Paris 


*/

$TITRE_HEAD = "Requête CTX Initial";

if (session_status() == PHP_SESSION_NONE) {
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
$path_plus = "";
// bloc verifier rapport =======================================================	
$hide_bouton_enregister = "";

if($_SESSION['id_type_util']!=2 && $_SESSION['id_type_util']!=3 && $_SESSION['id_type_util']!=8 && $_SESSION['id_type_util']!=9 && $_SESSION['id_type_util']!=18)
{
	$hide_bouton_enregister = 'style="display:none;"';
}	

echo "<div id=\"main\">";
echo "<h1 id=\"titre\"></h1>";
// echo "<h1>".$DEBUG_alerte."</h1>"; // renvoit alerte type "ce n'est pas de l'ajax"
require("fonctions/FONCTIONS_FORMULAIRES_contentieux.php");
require("fonctions/nombres.php");
require("fonctions/FONCTIONS_ged.php");

?>

<link rel="stylesheet" href="styles/ged.css" type="text/css" media="screen, projection" />
<script>

	//Si on a upload=reussi dans l'url, alors mettre une popup de confirmation que l'upload est reussi
	var url = window.location.href;

	if(url.includes("upload=reussi"))
	{
		alert("L'upload des fichiers s'est bien passé.");
		url = url.replace("&upload=reussi","");
		window.location.href = url;
	}

	function supprimer_requete(id, ref,id_scrutin){
		$( "#supprimer_ctx" ).text("Voulez-vous supprimer la requête "+ref+"?");
		$( "#supprimer_ctx" ).dialog({
			title:"Avertissement",
			modal: true,
			buttons: {
				"Oui":function(){
					$.ajax({
							url: 'ajax/requete_ctx/ctx_ajax_supprimer.php?id='+id+'&id_scrutin='+id_scrutin,
							method: 'GET',
							success: function(data){
										$( "#supprimer_ctx_ok" ).text("La requête a été correctement supprimée.");
										$( "#supprimer_ctx_ok" ).dialog({	
											title:"Information",
											modal: true,
											buttons: {
												"Ok":function(){
													window.location.href = "index.php?#tabs-4_1";
													$(this).dialog("close");
												}
											}				
										});	
							}
					});
					$(this).dialog("close");	
				},
				"Annuler":function(){
					$(this).dialog("close");
				}
			}
		});
	}	
</script>
<?php

// #####################################################
// ################ début du formulaire ################

?>
<?php
//Fonction a supprimer
$array_dpt = array();
$sql_dpt = "select id_dpt, nom_dpt from departement";
$rslt_rqt = sqlsrv_query($conn,$sql_dpt, array(), array("Scrollable"=>"buffered"));	
$nb_ligne = sqlsrv_num_rows($rslt_rqt);
$array_dpt_tmp = array();
$array_dpt_tmp["value"] =  "";
$array_dpt_tmp["label"] =  "";
array_push($array_dpt,$array_dpt_tmp);
if ($nb_ligne > 0) {
	while($rs = sqlsrv_fetch_array($rslt_rqt, SQLSRV_FETCH_ASSOC)) {
		$array_dpt_tmp = array();
		$array_dpt_tmp["value"] =  $rs['id_dpt'];
		$array_dpt_tmp["label"] =  $rs['nom_dpt'];
		array_push($array_dpt,$array_dpt_tmp);
	}
}

//Récupération du formulaire si on est en modification
$modif = false;
$info_ctx = array();

if(isset($_GET['id']) && $_GET['id'] != '')
{
	$modif = true;
	$info_ctx = recup_ctx($conn,$_GET['id']);

	if(empty($info_ctx))
	{
		echo "Aucune requête trouvée : vérifier que le scrutin de la requête appartient bien à votre périmètre.";
		exit;
	}
	else
	{	
		$info_ctx = $info_ctx[0];
	}
}
else
{
    unset($_SESSION['fichiers_uplodes']);
}

$checked = " checked=\"checked\""; // 20180516 cf. ci-dessous


//checkbox grief financier

$checked_grief = "";		

 if ($modif && $info_ctx['chk_grief_financier']== 1) {
	$checked_grief = $checked;
}
// echo $info_ctx['ref_ctx'];
// echo $modif;exit;

// 20180516 nouvelle variable / fiche ano 732 - http://ccfp.cnccfp.local/sites/si/nouvAppliElec/_layouts/15/FormServer.aspx?XmlLocation=/sites/si/nouvAppliElec/Fiches%20anomalie/fiche_ano_732_de_DH :

$checked_joindre_dossier_instruction = "";

if ($modif && $info_ctx['chk_joindre_dossier_instruction'] == 1) {
	$checked_joindre_dossier_instruction = $checked;
}


?>
<form id="formulaire_requete_ctx" class="formulaire" name="ctx" action="ajax/requete_ctx/ctx_ajax.php<?php if($modif){echo "?id=".$_GET['id'];}?>" method="post">
	<div id="supprimer_ctx"></div>
	<div id="supprimer_ctx_ok"></div>
    <div class="contentieux">
       <fieldset>
            <legend class="" ><?php if($modif){echo "Modification d'une requête contentieuse";}else{ echo "Création d'une requête contentieuse";}?></legend>
			<p>
				<label for="id_requete_type"> Type de requête : </label>
				<input type="radio" name="id_requete_type" value="1" <?php if(!$modif){echo "checked=\"checked\"";}else{if($info_ctx['id_requete_type']=='1'){echo "checked=\"checked\"";}} ?>> Requête CTX initiale
				<input type="radio" name="id_requete_type" value="2" <?php if($modif && $info_ctx['id_requete_type']=='2'){echo "checked=\"checked\"";} ?>> Requête CTX Remboursement
				<input type="radio" name="id_requete_type" value="3" <?php if($modif && $info_ctx['id_requete_type']=='3'){echo "checked=\"checked\"";} ?>> Requête CTX R, HD, AD	
			</p>		
			<p>
				<label for="ref_ctx"> Saisir la référence (numéro) du CTX : </label>
				<input type="text" id="ref_ctx" name="ref_ctx" size='40' maxlength='60'  required="required" placeholder="Référence du ctx" <?php if($modif){echo "value=\"".$info_ctx['ref_ctx']."\"";} else {echo "value=\"\"";}?>/>
				<input type="button" id="infos_ged" value="Récupérer informations GED">
			</p>
			<p>
				<label for="date_arrivee_rqt"> Date d'arrivée de la requête à la Commission : </label>
				<input type="text" id="date_arrivee_rqt" name="date_arrivee_rqt" size='40' maxlength='60' required="required"  placeholder="Date d'arrivée" <?php if($modif){echo "value=\"".affiche_date_fr($info_ctx['date_arrivee_rqt'])."\"";} else {echo "value=\"\"";}?>/>
			</p>			
			<p>
				<label for="elec"> Sélectionner l’élection : </label>
				<select id="elec" name="elec" style="width:335px" required="required">
					<?php 
					$list_elec = elections_en_cours($conn);
					echo "<option value=\"\"></option>";
					foreach ($list_elec as $elec){
							echo "<option value=\"".$elec[0]."\">".$elec[1]." (".$elec[2].")</option>"; //Modification de la ligne par EA le 20/11/2019 pour résoudre ano 924
					}?>
				</select>
			</p>
			
			<p>
				<label for="departement"> Saisir le département de référence : </label> 
				<input type="text" id="departement" name="departement" size='40' maxlength='60' required="required" placeholder="Département de référence" <?php if($modif){echo "value=\"".$info_ctx['nom_dpt']."\"";} else {echo "value=\"\"";}?>/>
				<input type="hidden" id="departement_id" name="departement_id" <?php if($modif){echo "value=\"".$info_ctx['id_dpt']."\"";}?>/>
			</p>
			<p>
				<label for="id_scrutin"> Sélectionner le scrutin : </label> 
					<select id="id_scrutin" name="id_scrutin" style="width:335px"  required="required" >
						<option value=""></option>
					
					</select>
			</p>	
			<p>
				<label for="juge"> Juge de l’élection concerné : </label> <input type="text" id="juge" name="juge" size='40' maxlength='60' value="" disabled="disabled" />  
				
				<? php // 20180516 ajout pour ano évolution 732 : ?>
				
				<label for="chk_joindre_dossier_instruction">Dossier d'instruction à joindre : </label>
				<input type="checkbox" value="1" id="chk_joindre_dossier_instruction" name="chk_joindre_dossier_instruction" <?php echo $checked_joindre_dossier_instruction;?>/>
			</p>
			<p>
				<label for="ged"> Saisir le numéro GED : </label>
				<input type="text" id="ged" name="ged" size='40' maxlength='60' <?php if($modif){echo "value=\"".$info_ctx['numero_ged']."\"";} else {echo "value=\"\"";}?> disabled readonly/>
			</p>
			<p>
				<label for="id_requerant"> Sélectionner le requérant : </label>
				<select id="id_requerant" name="id_requerant" style="width:335px" required="required">
					<option value=""></option>
				</select>	
			</p>
			<p>
				<label for="requerant"> Tiers : </label> <input type="text" id="requerant" name="requerant" size='40' maxlength='60' value="<?php if($modif){echo $info_ctx['requerant'];}?>" />
			</p>
			<p>
				<label for="id_defendeur"> Sélectionner le(s) défendeur(s) : </label>
				<select id="id_defendeur" name="id_defendeur" style="width:335px" required="required">
					<option value=""></option>
				</select>
				<button id="ajout_defendeur" type="button">Ajouter</button>
			</p>
			<div id="candidats_selectionnes" align="center" <?php if($modif == false){echo 'style="display:none"';} ?>>
				<p>Les défendeurs sélectionnés : </p>
				
				<?php
				    //Initialisation
				    $defendeurs_json = "default_value";

    				//Afficher les défendeurs qui sont en base
    				if ($modif == true)
    				{
				         $sql_recherche_defendeur = "select id_defendeur, nom_prenom_defendeur
                                                     from requete_ctx_defendeur
                                                     where id_requete_ctx = ".$_GET['id']."
				                                     and id_defendeur is not null
				                                     and nom_prenom_defendeur is not null"; 
				                                    //les deux dernières lignes servent à empêcher l'affichage d'un defendeur fantôme si le défendeur est 'Autre'
				         
				         $req_recherche_defendeur = sqlsrv_query($conn, $sql_recherche_defendeur);
				         
				         if($req_recherche_defendeur === false)
				         {
				             die(print_r(sqlsrv_errors(), true));
				         }

				         $defendeurs = array();
				         
				         if(sqlsrv_has_rows($req_recherche_defendeur))
				         {
				             while($unDefendeur = sqlsrv_fetch_array($req_recherche_defendeur))
				             {
				                 array_push($defendeurs, $unDefendeur);
				             }
				         }
				         else
				         {
				             ?>
				             	<script>
				             			$('#candidats_selectionnes').hide();
				             	</script>
				             <?php
				         }

				         for($i=0;$i<count($defendeurs);$i++)
				         {
				             $j = $i+1;
				             
				             echo '<div class="candidat_selectionne">
                                        <input id_cand = "'.$defendeurs[$i]['id_defendeur'].'" name="nom_prenom_defendeur_'.$j.'" value="'.$defendeurs[$i]['nom_prenom_defendeur'].'" disabled>
                                        <input type="hidden" name="nom_prenom_defendeur_'.$j.'" value = "'.$defendeurs[$i]['nom_prenom_defendeur'].'">
                                        <input type="hidden" name="id_defendeur_'.$j.'" value = "'.$defendeurs[$i]['id_defendeur'].'">
                                        <button class="retirer_candidat" type="button">Retirer</button>
                                   </div>';
				         }
				         
				         //Il faut désormais retirer les défendeurs qui sont en base de la liste déroulante dans la page
				         $defendeurs_json = json_encode($defendeurs); //encodage en json pour pouvoir exploiter le tableau en javascript
    				}
				?>
				
			</div>

			<p>
				<label for="defendeur"> autre : </label> <input type="text" id="defendeur" name="defendeur" size='40' maxlength='60' value="<?php if($modif){echo $info_ctx['defendeur'];}?>" />
			</p>	
			<p>
				<label for="grief"> Grief : </label>
				<textarea id="grief" name="grief" rows="3" cols="45"><?php if($modif){echo $info_ctx['grief'];}?></textarea>
			</p>	
			<p>
				<label for="analyse_grief"> Analyse Grief : </label>
				<textarea id="analyse_grief" name="analyse_grief" rows="3" cols="45"><?php if($modif){echo $info_ctx['analyse_grief'];}?></textarea>
			</p>		
			<p>	
				<label for="chk_grief_financier">Est un grief financier : </label>
				<input type="checkbox" id="chk_grief_financier" name="chk_grief_financier" <?php echo $checked_grief;?>/>
			</p>
			<p>
				<label for="date_notif_juge"> Date notification juge : </label>
				<input type="text" id="date_notif_juge" name="date_notif_juge" size='40' maxlength='60'  placeholder="Date notification du juge" <?php if($modif){echo "value=\"".affiche_date_fr($info_ctx['date_notif_juge'])."\"";} else {echo "value=\"\"";}?>/>
			</p>			
        </fieldset>
   </div> 
   
 <?php
	//$id_candidat=0, $id_lettre=0, $version='base', $nomcand='',$reference_ctx=''
	
	if(isset($info_ctx['ref_ctx'])){
	
		$TABLEAU_SCAN = liste_lettres_ged($id_candidat=0, $id_lettre=0, $version='base', $nomcand='',$reference_ctx=$info_ctx['ref_ctx']);
		
		echo "<fieldset>\n";
		echo "<legend>Courriers numérisés</legend>\n"; 
		echo "<div id=\"liste\">\n";
		echo $TABLEAU_SCAN;
		echo " <div style=\'clear:both\"> </div>\n";
		echo "</div>\n";
		echo "</fieldset>\n";
	}
	
	if ($hide_bouton_enregister == "") { // 20180516 simplification + sécurité +  meilleure lisibilité (code php only - non imbriqué dans html) - les boutons d'enregistrements n'existent que s'ils ont lieu d'exister. Pas besoin de les charger pour les masquer ensuite sinon

		echo "<p><input type=\"submit\" id=\"enregistrer_requete_ctx\" value=\"Enregistrer\" />\n";

	  	if ($modif) {
	
			echo "   <input type=\"button\" value=\"Supprimer\"  id=\"supprimer_requete_ctx\" type=\"button\" onclick=\"supprimer_requete('".$info_ctx['id_requete']."','".$info_ctx['ref_ctx']."','".$info_ctx['id_scrutin']."');return false;\" />";
		}
		
		echo "</p>";
		
	}
	
	//$repertoire = "requete/ville/num-requete"; //TEMPORAIREMENT, on met tout dans le même dossier
?>
	
</form>

<fieldset>
	<legend>Fiche d'analyse du CM : Intégrer un dossier avec tous ses sous-dossiers</legend>
	<form action="r2_18_upload_directory.php?id=<?php if(!(empty($_GET['id']))) {echo $_GET['id'];} ?>" method="post" enctype="multipart/form-data" >
			<input type="file" id="files" name="files[]" multiple="" webkitdirectory="" />​
		
			<input type="hidden" id="repertoire" name="repertoire" value="" />
			<input type="hidden" id="paths" name="paths" />
		
			<div id="output"></div>

			<input type="submit" value="Envoyer le dossier" id="upload_directory" />
			<div> <?php
                        //$tableauFichiers = scandir();
                        echo $_SESSION['id_scrutin'];
                    ?>
			</div>
	</form>
</fieldset>
    
<!-- affichage masquage du formulaire au click sur <legend> correspondant, en fonction de l'état de la vérification : -->

<script>

var output = document.getElementById('output');

// Detect when the value of the files input changes.
document.getElementById('files').onchange = function(e)
{
	// Retrieve the file list from the input element
	uploadFiles(e.target.files);
	
	// Outputs file names to div id "output"
    output.innerText = "";
    
	for (var i in e.target.files)
		output.innerText  = output.innerText + e.target.files[i].webkitRelativePath+"\n";
}

function uploadFiles(files)
{	
	// Create a new HTTP requests, Form data item (data we will send to the server) and an empty string for the file paths.
	xhr = new XMLHttpRequest();
	data = new FormData();
	paths = "";
	
	// Set how to handle the response text from the server
	xhr.onreadystatechange = function(ev)
	{
		console.debug(xhr.responseText);
	};

	var nb=0;
	
	// Loop through the file list
	for (var i in files)
	{
		if (files[i].webkitRelativePath != undefined)
		{
			// Append the current file path to the paths variable (delimited by tripple hash signs - ###)
			paths += files[i].webkitRelativePath+"###";
			// Append current file to our FormData with the index of i
			data.append(i, files[i]);

			nb++;

	//		console.log(files[i]);
		}
	};

	$("#paths").val(paths);

	if (nb><?php echo ini_get('max_file_uploads') ;?>) alert ("Attention, le nombre maximum de fichiers (<?php echo ini_get('max_file_uploads') ;?>) est dépassé");
}

</script>

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
<script>

	//EA le 12 03 2020
	//La fonction ci-dessous sert pour la creation de req ctx mais aussi pour modification de req ctx, mais uniquement dans le cas où le défendeur a été ajouté dynamiquement à la page
	/*A noter : Si on est en modification d'une req ctx, et qu'on clique sur 'Retirer' sur un défendeur présent dans les défendeurs sélectionnés au chargement de la page,
	            ca déclenchera un message d'erreur dans la console. En effet la fonction ci-dessous sert à gérer le cas ou le défendeur à retirer a été ajouté après le chargement de 
	            la page. Cette erreur n'est pas grave, car le travail sera fait dans la fonction suivante cad dans $('.candidat_selectionne > button').on('click', function().
	            */
	/* On créer un event listener sur le "container" (cad le parent) des candidats sélectionnés (si on créer un event listener sur les candidats sélectionnés directement, ca ne fonctionne pas,
	 car ils ont été ajoutés au DOM dynamiquement) */
	 $('#candidats_selectionnes').on('click', function(e)
	 {	
		 if(e.target && e.target.nodeName == "BUTTON")
		 {
			//On remet le défendeur dans la liste déroulante initiale
			var id_cand = e.target.previousSibling.getAttribute("id_cand");
			var nom_prenom_cand = e.target.previousSibling.previousSibling.value;		
			$('select#id_defendeur').append("<option value ="+id_cand+">"+nom_prenom_cand+"</option>");
			
			//On retire le défendeur de notre selection
			e.target.parentNode.remove();

			if($('.candidat_selectionne').length == 0) //Si on retire le dernier défendeur sélectionné
			{
				$('#candidats_selectionnes').hide();
			}

			//Renommer proprement l'attribut 'name' pour chaque défendeur sélectionné
			var i=1;
			
			$('#candidats_selectionnes').find('.candidat_selectionne').each(function()
			{
				$(this).find('input').eq(0).attr('name','nom_prenom_defendeur_'+i);
				$(this).find('input').eq(1).attr('name','nom_prenom_defendeur_'+i);
				$(this).find('input').eq(2).attr('name','id_defendeur_'+i);
				i++;
			});
		 }
	 });

	 //EA le 09 06 2020
	 //La fonction ci-dessous sert pour la modification de la req ctx, mais uniquement dans le cas où le défendeur est présent au chargement de la page
	 /*A noter : Si on est en modification d'une req ctx, et qu'on clique sur 'Retirer' sur un défendeur ajouté dynamiquent au DOM, alors la fonction ci-dessous ne fonctionnera pas
	             et ne jetera pas d'erreur. Mais c'est pas grave car la fonction au dessus cad $('#candidats_selectionnes').on('click', function(e) fera le travail. */
	 $('.candidat_selectionne > button').on('click', function()
	 {
		 //On remet le défendeur dans la liste déroulante initiale
		 var id_cand = $(this).prev().val();
		 var nom_prenom_cand = $(this).prev().prev().val();
		 $('select#id_defendeur').append("<option value ="+id_cand+">"+nom_prenom_cand+"</option>");

		//On retire le défendeur de notre selection
		$(this).parent().remove();

		if($('.candidat_selectionne').length == 0) //Si on retire le dernier défendeur sélectionné
		{
			$('#candidats_selectionnes').hide();
		}

		//Renommer proprement l'attribut 'name' pour chaque défendeur sélectionné
		var i=1;
		
		$('#candidats_selectionnes').find('.candidat_selectionne').each(function()
		{
			$(this).find('input').eq(0).attr('name','nom_prenom_defendeur_'+i);
			$(this).find('input').eq(1).attr('name','nom_prenom_defendeur_'+i);
			$(this).find('input').eq(2).attr('name','id_defendeur_'+i);
			i++;
		});
	 });
	 
	//EA le 11 03 2020
	$('#ajout_defendeur').on('click', function()
	{
		var nom_prenom_defendeur = $("#id_defendeur option:selected").html();
		var id_cand = $(this).prev().find('option:selected').val();

		if(nom_prenom_defendeur == '')
		{
			alert ("Vous n'avez pas sélectionné de candidat");
		}
		else
		{
			//Afficher la zone des défendeurs sélectionnés
			$('#candidats_selectionnes').show();
			
			//Déterminer nombre de défendeurs avant ajout à la sélection
			var nb_defendeur = $('#candidats_selectionnes').find('div.candidat_selectionne').length;
			var num_defendeur_a_ajouter = nb_defendeur + 1;
			
			//On ajoute un defendeur (on met 3 input dans le append : 1 pour l'affichage et 2 pour le submit)
			$('#candidats_selectionnes').append('<div class="candidat_selectionne"><input name="nom_prenom_defendeur_'+num_defendeur_a_ajouter+'" value="'+nom_prenom_defendeur+'" disabled><input type="hidden" name="nom_prenom_defendeur_'+num_defendeur_a_ajouter+'" value = "'+nom_prenom_defendeur+'"><input type="hidden" name="id_defendeur_'+num_defendeur_a_ajouter+'" value = '+id_cand+'><button class="retirer_candidat" type="button">Retirer</button></div>');

			//On retire le defendeur de la liste déroulante
			$('#id_defendeur').find('option:selected').remove();
		}
	});

	//EA 06 03 2020
	$('#upload').on('submit', function(e)
	{
				$.ajax(
			     		{
				            url: $(this).attr('action'),
				            data: $(this).serialize(),
				            type: 'POST',
				            success: function(resp)
				            {
				            	//window.location.reload(true);
			            	},
			            	error: function(XMLHttpRequest, textStatus, errorThrown)
			            	{
			            		//alert('erreur lors de l upload du fichier');
			            		alert("Status: " + textStatus); alert("Erreur: " + errorThrown);
			            	}
			            });
	});

	//EA 09 03 2020
	$('#suppr_upload').on('click', function(e)
	{
				$.ajax(
			     		{
				            url: 'r2_16_ajax.php',
				            data: 'cheminFichier=' + $(this).prev('a').attr('href'),
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

	//EA le 23 07 2020
	$('#ref_ctx').on('change', function()
	{
		$('#repertoire').val($(this).val());
	});

	//EA le 23 07 2020
	$('#id_scrutin').on('change', function()
	{
		var chemin_tempo = $('#repertoire').val();
		var ville = $(this).text;

        var element = document.getElementById("id_scrutin");
        var ville_et_scrutin = element.options[element.selectedIndex].text;
        var tabs = ville_et_scrutin.split(' ');
        var ville = tabs[0];

		var chemin_final = 'requete/'+ville+'/'+chemin_tempo;

		$('#repertoire').val(chemin_final);
	});

$("#formulaire_requete_ctx input:text[required],#formulaire_requete_ctx textarea[required]").css('background-color','yellow');

document.title='Candidat - informations';	
$('h1#titre').text("Requête CTX");

</script>


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
	
	<script src="js/disable_champs.js"></script>

<script src="js/jquery.affichage_fieldset_h5.js"></script> 
<script src="js/jquery.ui.datepicker-fr.js"></script>
	
<script>



$(document).ready(function()
{
	recuperer_repertoire_upload();
	
	//Rajouté par EA le 13 03 2020 pour le rendre spécifique //EA le 24 04 2020 : je ne comprends pas pkoi j'ai mis cette ligne ?
	
	$("#formulaire_requete_ctx input[type=submit]").on('click', function(e)
	{
		e.preventDefault;

		var nb_erreurs = 0;
		var liste_champs = '';
     	var InvalidInputs = document.querySelectorAll('input:invalid');
     	var InvalidTextarea = document.querySelectorAll('textarea:invalid');
     	
 		nb_erreurs = InvalidInputs.length+InvalidTextarea.length;
 		
 		if (nb_erreurs > 0)
 	 	{
 			liste_champs = 'Le formulaire ne peut être enregistré car des anomalies ont été trouvées dans les champs suivants :\n';
 		
 			$(InvalidInputs).each(function()
 		 	{
 				var input_id = $(this).attr('id');
 				var label_texte = $("label[for='" + input_id + "']").text().trim();
 				
 				label_texte = label_texte.substring(0, label_texte.length-2);
 				liste_champs = liste_champs+'- '+label_texte+'\n';
 			});
 			
 			$(InvalidTextarea).each(function()
 		 	{
 				var input_id = $(this).attr('id');
 				var label_texte = $("label[for='" + input_id + "']").text().trim();
 				
 				label_texte = label_texte.substring(0, label_texte.length-2);
 				liste_champs = liste_champs+'- '+label_texte+'\n';
 			});

 			encadre_erreurs();
     		alert(liste_champs);
     	}
     	else
        {
     		$.ajax({
            url: $('#formulaire_requete_ctx').attr('action'),
            data: $('#formulaire_requete_ctx').serialize(),
            type: 'POST',
            success: function(resp)
            	{
            		//alert(resp.trim());
            		window.location.reload(true);
            	}
            });
            
            return false;
     	}
    }); //Fin du on-click
	
	
	$( "#date_arrivee_rqt" ).datepicker($.datepicker.regional[ "fr" ] );
	$( "#date_notif_juge" ).datepicker($.datepicker.regional[ "fr" ] );
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
	
    $( "#departement" ).autocomplete({
      source: <?php echo json_encode($array_dpt); ?>,
	  select: function (event, ui) {
		  		//	console.log(ui);
		$(this).val(ui.item.label);
		$('#departement_id').val(ui.item.value);
		ajax_scrutin();
		return false;
	  }
    });
    
	
	$("#departement").on('keyup blur select', function() {
		$( "#id_requerant" ).val("");
		$( "#id_defendeur" ).val("");
		$( "#juge" ).val("");
		$( "#ged" ).val("");
		ajax_scrutin();
	});
	
	$("#elec").on('change', function() {
		$( "#id_scrutin" ).val("");
		reset_autocomplete("departement_id");
		$( "#id_requerant" ).val("");
		$( "#id_defendeur" ).val("");
		$( "#juge" ).val("");
		//$( "#ged" ).val(""); //Commenté par EA le 18 12 2019 pour ano 662
		ajax_scrutin();
	});
	function reset_autocomplete(id_combo){
		//clear each option if its selected
		$('#'+id_combo+' option').each(function() { 
			$(this).removeAttr('selected')
		});

		//set the first option as selected
		$('#'+id_combo+' option:first').attr('selected', 'selected');

		//set the text of the input field to the text of the first option
		$('input.ui-autocomplete-input').val($('#'+id_combo+' option:first').text());
	}
	$("#id_scrutin").on('change', function() {
		ajax_juge($(this).val());
	});
	
	$("#id_scrutin").on('change', function()
	{
		ajax_candidat($(this).val());
	});
	
	$("#ref_ctx").on('change keyup', function() {
		<?php if($modif)echo "var id_req='&id_req=".$_GET['id']."';\n";
		else echo "var id_req='';\n";
		?>
		$.ajax({
				url: 'ajax/requete_ctx/ctx_ajax_ref_unique.php?ref_ctx='+$(this).val()+id_req,
				method: 'GET',
				success: function(data){
					if(data == true){
						$("#ref_unique").remove();
						$("#ref_unique_hidden").remove();
						//Les 2 lignes en dessous commentées par EA le 16 07 2020 car ce contrôle est toxique, car il peut y avoir 2 numéros de req ctx identiques mais sur des scrutins différents
						//$("#ref_ctx").parent().after('<span id=\'ref_unique\' style="margin-left:260px; color: red; font-weight: bold;">Cette référence est déjà utilisée.</span>');
						//$("#ref_ctx").parent().after('<input type="hidden" id=\'ref_unique_hidden\' name=\'ref_unique_hidden\' value="true">');
					} else{
						$("#ref_unique").remove();
						$("#ref_unique_hidden").remove();
					}
				}
		});
	});
	
	function ajax_scrutin()
	{
		if($("#departement") !='' && $("#elec").val() != '')
		{		
			$.ajax({
					url: 'ajax/requete_ctx/ctx_ajax_scrutin.php?departement='+$("#departement_id").val()+"&elec="+$("#elec").val(),
					method: 'GET',
					success: function(data)
					{					
						$( "#id_scrutin" ).html(data);

						//---------------------- Rajouté par EA le 08 06 2020 afin de récupérer les numcand des candidats sélectionnés (plus bas) pour les retirer de la liste déroulante
						var candidats_selectionnes = ''; //initialisation
						
						$('#candidats_selectionnes').find('.candidat_selectionne').each(function()
						{
							var id_cand = $(this).find('input').eq(0).attr('id_cand');
							candidats_selectionnes = candidats_selectionnes + id_cand +",";
						});

						if(candidats_selectionnes !== '')
						{
							//Retirer le dernier caractère ,
							var longueur = candidats_selectionnes.length;
							candidats_selectionnes = candidats_selectionnes.substring(0, longueur - 1);
						}
						//---------------------- Fin ajout
						
							<?php if($modif){
								echo "$(\"#id_scrutin option[value='".$info_ctx['id_scrutin']."']\").prop('selected', true);\n";
								echo "ajax_juge(\"".$info_ctx['id_scrutin']."\");\n";
								echo "ajax_candidat(\"".$info_ctx['id_scrutin']."\", candidats_selectionnes);\n"; //Ajout du 2ème paramètre par EA le 08 06 2020							
							} ?>

						recuperer_repertoire_upload(); //Par EA le 31 07 2020 (fonction déclarée tout en bas du fichier)
					}
			});
		} /*
		else { // debug 20180515
		
			alert('département ('+$("#departement")+') ou elec ('+$("#elec").val()+' manquent'); 
		}*/ //Bloc commenté par EA le 18 12 2019 pour corriger ano 977
	}
	
	function ajax_juge(val) {
		
		$.ajax({
				url: 'ajax/requete_ctx/ctx_ajax_juge.php?id_scrutin='+val,
				method: 'GET',
				success: function(data){
					$( "#juge" ).val(data);
				}
		});
	}
		
	function ajax_candidat(val, candidats_selectionnes = '') //Ajout du 2ème paramètre par EA le 08 06 2020
	{		
		$.ajax({
				url: 'ajax/requete_ctx/ctx_ajax_candidats.php?id_scrutin='+val+'&candidats_selectionnes='+candidats_selectionnes,
				method: 'GET',
				success: function(data){
					$( "#id_requerant" ).html(data);
					$( "#id_requerant" ).prepend("<option disabled>------------------------------------------------------------</option>");	
					$( "#id_requerant" ).prepend("<option value=\"Prefet\"><b>Le préfet du département</b></option>");
					$( "#id_requerant" ).prepend("<option value=\"CNCCFP\"><b>CNCCFP</b></option>");					
					$( "#id_requerant" ).prepend("<option value=\"Tiers\"><b>Tiers</b></option>");			
					$( "#id_requerant" ).prepend("<option></option>");						
					$( "#id_defendeur" ).html(data);
					//$( "#id_defendeur" ).prepend("<option disabled>------------------------------------------------------------</option>");		
					$( "#id_defendeur" ).prepend("<option value=\"CNCCFP\">CNCCFP</option>");		
					$( "#id_defendeur" ).prepend("<option value=\"Autre\">Autre</option>");		
					$( "#id_defendeur" ).prepend("<option></option>");	
					<?php
					if($modif){
						if($info_ctx['chk_requerant_prefet'] == true){
							echo "$(\"#requerant\").parent().hide();\n";
							echo "$(\"#id_requerant option[value='Prefet']\").prop('selected', true);\n";
						}else if($info_ctx['chk_requerant_cnccfp'] == true){
							echo "$(\"#requerant\").parent().hide();\n";
							echo "$(\"#id_requerant option[value='CNCCFP']\").prop('selected', true);\n";
						}else if($info_ctx['id_requerant'] == null){
							echo "$(\"#id_requerant option[value='Tiers']\").prop('selected', true);\n";
							echo "$(\"#requerant\").val('".$info_ctx['requerant']."');\n";
						}else {
							echo "$(\"#requerant\").parent().hide();\n";
							echo "$(\"#id_requerant option[value='".$info_ctx['id_requerant']."']\").prop('selected', true);\n";
						}	

						if($info_ctx['chk_defendeur_cnccfp'] == true){
							echo "$(\"#defendeur\").parent().hide();\n";							
							echo "$(\"#id_defendeur option[value='CNCCFP']\").prop('selected', true);\n";
						} else if($info_ctx['id_defendeur'] == null){
							echo "$(\"#id_defendeur option[value='Autre']\").prop('selected', true);\n";
							echo "$(\"#defendeur\").val('".$info_ctx['defendeur']."');\n";
						}else {
							echo "$(\"#defendeur\").parent().hide();\n";
							echo "$(\"#id_defendeur option[value='".$info_ctx['id_defendeur']."']\").prop('selected', true);\n";
						}	
					} ?>								
				}
		});
	}
	

	
    $( "#id_requerant" ).on('change', function()
    {
		if($("#id_requerant option:selected").val() == "Tiers")
		{
			$("#requerant").parent().slideDown(); //afficher
		}
		else
		{
			$("#requerant").val(''); //vider valeur
			$("#requerant").parent().slideUp(); //cacher
		}
	});

    $( "#id_defendeur" ).on('change', function()
    {
		if($("#id_defendeur option:selected").val() == "Autre")
		{
			$("#defendeur").parent().slideDown(); //afficher
			$("button#ajout_defendeur").hide();
			$('.candidat_selectionne').each(function()
    		{
	    		$(this).find('button').click();
    		});
			$("#candidats_selectionnes").hide();
			$("#defendeur").attr("required", "true");
		}
		else
		{
			$("#defendeur").parent().slideUp(); //cacher
			$("#defendeur").val(''); //on vide la case
			$("button#ajout_defendeur").show();
			
			if($('.candidat_selectionne').length >= 1) //Si y'a déja au moins un defendeur (de type candidat) selectionne
			{
				$("#candidats_selectionnes").show();
			}
		}
	});
	
	<?php if($modif){
		echo "$(\"#elec option[value='".$info_ctx['id_election']."']\").prop('selected', true);\n";
		echo "ajax_scrutin();\n";
	} else {
		echo "$(\"#requerant\").parent().hide();\n";
		echo "$(\"#defendeur\").parent().hide();\n";
	} ?>
	

	 

	
	$( "#infos_ged").on('click', function(){
		 $.ajax({
			url: 'ajax/requete_ctx/ctx_ajax_ged.php?ref_ctx='+$( "#ref_ctx").val(),
				method: 'GET',
				success: function(data){
					//console.log(data);
					//$json = {\"num_chron_prive\" : \"\", \"data_arrive\" : \"\" , \"reference_ctx\" : \"\"}";
					if(data.reference_ctx!=""){
						$("#ref_ctx").val(data.reference_ctx);
						$("#date_arrivee_rqt").val(data.data_arrive);
						$("#ged").val(data.num_chron_prive);
						$("#liste").html(data.TABLEAU_SCAN);
					}else{
						alert("Référence GED non trouvée !")
						$("#date_arrivee_rqt").val("");
						$("#ged").val("");
						$("#liste").html("<p class=\"introuvable\">Lettre introuvable en GED</p>");
					}
				}
		});		 
	 });

	 var modification_bool = '<?php
    	                           if($modif == true)
    	                           {
    	                               echo $modif;
    	                           }
    	                           else
    	                           {
    	                               echo false;
    	                           }
	                         ?>';
					
	 if (modification_bool == true)
	 {
    	 var defendeurs_array = <?php echo $defendeurs_json; ?>;

    	 /* On va retirer chaque défendeur en base (et donc affiché dans "Les défendeurs sélectionnés") du menu déroulant 
    	 "Sélectionner le(s) défendeur(s)" */
     	 defendeurs_array.forEach(function(currentDefendeur)
    	 {	
    			var idDefendeur = currentDefendeur.id_defendeur;
    			$('#id_defendeur > option[value='+idDefendeur+']').remove();
    	 });
	 }

	 //Par EA le 30 07 2020 pour ajouter le nom du repertoire dans le champ dont l'id est repertoire
	 function recuperer_repertoire_upload()
	 {
    	 var ref_ctx = $('#ref_ctx').val();
    	 	
    	 var element = document.getElementById("id_scrutin");
    	 var ville_et_scrutin = element.options[element.selectedIndex].text;
    	 var tabs = ville_et_scrutin.split(' ');
    	 var ville = tabs[0];
    	 var chemin_req_ctx = 'requete/'+ville+'/'+ref_ctx;

    	 $('#repertoire').val(chemin_req_ctx);
	 }
});


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
