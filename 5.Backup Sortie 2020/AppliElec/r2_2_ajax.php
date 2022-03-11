<?php

//EA le 02/05/2018 : Beaucoup de choses ont été rajoutées pour l'ano 650, donc il n'y aura pas de références précises sur chaque ligne où j'ai fait des ajouts/modifications

session_start();

require("inclusion/CONNEXION.php");
require_once("fonctions/chaines.php");
require_once("fonctions/nombres.php");
require_once("fonctions/dates.php");
require_once("fonctions/combo_dynamique.php");
require("fonctions/FONCTIONS_bandeau.php");

require_once("fonctions/anti_injection_sql.php"); // indispensable pour éviter les injections, notamment lors de la vérification de login via  login_verif.php et lors de tous les GET ou POST reçus ici

require_once("fonctions/chaines.php");
require_once("auth/login_verif.php");  // vérification utilisateur
require_once("fonctions/dates.php");
require_once("fonctions/verif_rapport_termine.php");

//Vérification que l'utilisateur a le droit de modifier/creer/supprimer un mandataire
if (!($_SESSION['id_type_util'] == 2 || $_SESSION['id_type_util'] == 3 || $_SESSION['id_type_util'] == 4))
{
	header("content-type: application/javascript");
	echo "alert('Vous n avez pas les droits de creation/modification/suppression')";
	die();
}

//Initialisation de tableaux pour insérer les key (nom du champ) / value (valeur du champ) des champs du formulaire source
$LISTE_POUR_TABLE_MANDATAIRE = ""; //contient des 'key = value' séparés par des virgules
$nom_champs_pour_table_mandataire = []; //contient les key
$valeur_champs_pour_table_mandataire = []; //contient les value

$LISTE_POUR_TABLE_MAPPING = "";
$nom_champs_pour_table_mapping = [];
$valeur_champs_pour_table_mapping = [];

$chk_presence_mandataire = '';

//Initialisation d'una variable qui servira à différents endroits de la page
$messageIncoherenceDatesMandataires = ", mais attention il y a au moins une période où aucun mandataire n est défini";

if(isset($_POST['action']))
{	
	if($_POST['action'] == 'suppression') //CAS DE LA SUPPRESSION D'UN MANDATAIRE
	{
		//Suppression dans la table de mapping candidats <--> mandataires
		$sql_delete = "DELETE FROM candidat_mandataire WHERE id_mandataire = ".$_POST['id_mandataire'];
		$req = sqlsrv_query($conn, $sql_delete);

		if( $req === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}

		//Suppression dans la table mandataire
		$sql_delete2 = "DELETE FROM mandataire WHERE id_mandataire = ".$_POST['id_mandataire'];
		$req2 = sqlsrv_query($conn, $sql_delete2);

		if( $req2 === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}

		//Règle : Un warning doit être affiché si on a une période de « creux » dans l’historique des mandataires
		$presenceIncoherenceDatesMandataires = verifier_continuite_mandataires($conn, $_POST['id_candidat']);

		if($presenceIncoherenceDatesMandataires)
		{
			header("content-type: application/javascript");
			echo "alert('Suppression réussie, la page va se rafraîchir".$messageIncoherenceDatesMandataires."')"; //Popup de confirmation de la suppression du mandataire, avec en plus un message mentionnant un creux dans les périodes de validite des mandataires
		}
		else
		{
			header("content-type: application/javascript");
			echo "alert('Suppression réussie, la page va se rafraîchir')"; //Popup de confirmation de la suppression du mandataire
		}
	}
}
elseif(isset($_GET['action']) && $_GET['action'] == 'obtenir_nombre_mandataire') //CAS DE L'INTERROGATION DU NOMBRE DE MANDATAIRES RESTANT
{
	$nbMandataire = 0;
		
	$sql = "select *
			from mandataire
			where id_candidat = ".$_GET['id_candidat'];
			
	$req = sqlsrv_query($conn, $sql, array(), array("Scrollable"=>"buffered"));
			
	if($req === false)
	{
		die(print_r(sqlsrv_errors(), true));		
	}
	else
	{
		$nb = sqlsrv_num_rows($req);
		$nbMandataire = $nb;
	}
				
	echo $nbMandataire;
}
else //CAS DE LA MODIFICATION OU CREATION D'UN MANDATAIRE
{
	////Retirer la fin des key de $_POST (cad retirer la partie rajouté dans r2_2.php dans le HTML pour rendre unique les attributs name)

	//Initialiser le $_POST "temporaire"
	$tempoPost = [];

	//Alimenter le $_POST "temporaire"
	foreach($_POST AS $k=>$val)
	{
		if ($k !== 'action' && $k !== 'id_candidat' && $k !== 'id_mandataire') //on ne traite que les $k qui contiennent des attributs name (qui ont été allongés dans le HTML pour unicité)
		{
			//Chercher le dernier underscore et retirer ce underscore et ce qui est après
			$arrayTempo = explode('_', $k);
			$indiceDerniereLigneTableau = sizeof($arrayTempo) - 1;
			unset($arrayTempo[$indiceDerniereLigneTableau]);
			$newKey = implode ('_' , $arrayTempo);

			//Alimenter le $_POST "temporaire"
			$tempoPost[$newKey] = $val;
		}
	}

	//Ecraser $_POST avec le $_POST "temporaire"
	$_POST = $tempoPost;

	//Récupérer les key et value de $_POST, et les ventiler dans les 7 variables initialisés après les require
	foreach($_POST AS $k=>$val)
	{
		if ($k !== 'annee' and $k !=='id_candidat' and $k !=='id_mandataire' and $k !=='id_scrutin' and $k !=='id_suppleant' and $k !=='id_expert' and $k !=='type_mandataire') //On ignore les champs de type hidden du formulaire source
		{
			if($k == 'chk_presence_mandataire') //champ de la table candidat
			{
				if(escape_quote(anti_injection_sql($_POST[$k])) == 'NULL')
				{
					$chk_presence_mandataire = '';
				}
				else
				{
					$chk_presence_mandataire = escape_quote(anti_injection_sql($_POST[$k]));
				}
			}
			elseif ($k == 'date_debut_validite' or $k == 'date_fin_validite' or $k == 'periodicite_mandataire') //champs de la table mapping
			{
				array_push($nom_champs_pour_table_mapping, $k);
				$valeur = "'".$val."'";
				array_push($valeur_champs_pour_table_mapping, $valeur);
			
				if ((strpos($k,'date') !== false) && (validation_date($val)))
				{	
					$LISTE_POUR_TABLE_MAPPING = $LISTE_POUR_TABLE_MAPPING."$k='".$_POST[$k]."',";
				}
				else
				{
					if(escape_quote(anti_injection_sql($_POST[$k])) == 'NULL')
					{
						$LISTE_POUR_TABLE_MAPPING = $LISTE_POUR_TABLE_MAPPING."$k=null,";
				}
					else
					{
						$LISTE_POUR_TABLE_MAPPING = $LISTE_POUR_TABLE_MAPPING."$k='".escape_quote(anti_injection_sql($_POST[$k]))."',";
					}
				}
			}
			else //champs de la table mandataire
			{		
				array_push($nom_champs_pour_table_mandataire, $k);
				$valeur = "'".$val."'";
				array_push($valeur_champs_pour_table_mandataire, $valeur);
		
				if ((strpos($k,'date') !== false) && (validation_date($val)))
				{	
					$LISTE_POUR_TABLE_MANDATAIRE = $LISTE_POUR_TABLE_MANDATAIRE."$k='".$_POST[$k]."',";
				}
				else
				{
					if(escape_quote(anti_injection_sql($_POST[$k])) == 'NULL')
					{
						$LISTE_POUR_TABLE_MANDATAIRE = $LISTE_POUR_TABLE_MANDATAIRE."$k=null,";
					}
					else
					{
						$LISTE_POUR_TABLE_MANDATAIRE = $LISTE_POUR_TABLE_MANDATAIRE."$k='".escape_quote(anti_injection_sql($_POST[$k]))."',";
					}
				}
			}
		}
	}

	$LISTE_POUR_TABLE_MANDATAIRE = mb_substr($LISTE_POUR_TABLE_MANDATAIRE,0,-1); // suppression du dernier caractère (virgule finale)
	$LISTE_POUR_TABLE_MAPPING = mb_substr($LISTE_POUR_TABLE_MAPPING,0,-1); //idem pour la 2eme liste
		
	////Initilisation de variables pour les règles de gestion
	$dateDebutValidite = NULL;
	$dateFinValidite = NULL;
	$dateDuJour = new DateTime();
	$dateDuJour = date_time_set($dateDuJour, 0, 0, 0, 0);

	$dateDebutValidite = DateTime::createFromFormat('d/m/Y', $_POST['date_debut_validite']);
	$dateDebutValidite = date_time_set($dateDebutValidite, 0, 0, 0, 0); //Retirer heure minutes secondes millisecondes
	

	////REGLES DE GESTION SUR LES DATES DE VALIDITE => !!!!! Elles sont dorénavant toutes en FRONT en bloquantes !!!!!

	// if($_POST['periodicite_mandataire'] == 'actif') //Cas du mandataire actif seulement
	// {
	// 	////Règle - La date de debut de validité du MF actif doit être supérieure à toutes les dates de fin de validité des autres MF (donc les mandataires antérieurs) du candidat	
	// 	$sql_date_fin_validite_mandataires = "
	// 											select top 1 date_fin_validite
	// 											from candidat_mandataire
	// 											where id_candidat = ".$_POST['id_candidat']." 
	// 											and periodicite_mandataire = 'anterieur'
	// 											order by date_fin_validite desc
	// 										";//Récupérer la date de fin de vadilité la plus récente de tous les mandataires du candidat

	// 	//En cas de modification (pas creation), il faut retirer du résultat de la requete sql la date de fin de validité du mandataire concerné, sinon il y aura un conflit "sur lui même" si la date est inchangée dans le formulaire par exemple
	// 	//EA : plus d'actualité puisque la date de fin de validitite du mand actif est nulle désormais, mais je laisse la règle en place pour retirer cette valeur nulle qui peut poser des problèmes
	// 	if(anti_injection_sql($_POST['id_mandataire']) !== '')
	// 	{
	// 		$sql_date_fin_validite_mandataires = "
	// 												select top 1 date_fin_validite
	// 												from candidat_mandataire
	// 												where id_candidat = ".$_POST['id_candidat']." 
	// 												and id_mandataire != ".$_POST['id_mandataire']." 
	// 												and periodicite_mandataire = 'anterieur'
	// 												order by date_fin_validite desc
	// 											";
	// 	}

	// 	$req_date_fin_validite_mandataire = sqlsrv_query($conn, $sql_date_fin_validite_mandataires);

	// 	if($req_date_fin_validite_mandataire === false)
	// 	{
	// 		die(print_r(sqlsrv_errors(), true));	
	// 	}

	// 	$dateFinValiditeLaPlusRecenteDesAutresMandatairesArray = [];
	
	// 	if(sqlsrv_has_rows($req_date_fin_validite_mandataire))
	// 	{
	// 		$dateFinValiditeLaPlusRecenteDesAutresMandatairesArray = sqlsrv_fetch_array($req_date_fin_validite_mandataire);
	// 		$dateFinValiditeLaPlusRecenteDesAutresMandatairesDate = $dateFinValiditeLaPlusRecenteDesAutresMandatairesArray[0]; //Recupération de la date au format Date

	// 		if($dateDebutValidite <= $dateFinValiditeLaPlusRecenteDesAutresMandatairesDate)
	// 		{
	// 			$dateDebutValiditeString = $dateDebutValidite->format('d/m/Y');
	// 			$dateFinValiditeLaPlusRecenteDesAutresMandatairesString = $dateFinValiditeLaPlusRecenteDesAutresMandatairesDate->format('d/m/Y');

	// 			header("content-type: application/javascript");
	// 			echo "alert('La date de debut de validité du MF actif (".$dateDebutValiditeString.") doit être supérieure à toutes les dates de fin de validité des autres MF antérieurs du candidat. Or un mandataire antérieur a une date de fin de validité qui est ".$dateFinValiditeLaPlusRecenteDesAutresMandatairesString.".')";
	// 			die();
	// 		}
	// 	}

	// 	////Règle - La date de debut de validité du futur nouveau MF actif doit être supérieure à la date de début de validité du MF actif actuel
	// 	if(anti_injection_sql($_POST['id_mandataire']) == '') //Si on est en train de creer un mandataire actif
	// 	{
	// 		$sql_recup_date_validite_mand_actif = "
	// 												select date_debut_validite
	// 												from candidat_mandataire
	// 												where periodicite_mandataire = 'actif'
	// 												and id_candidat = ".$_POST['id_candidat'];
												   
	// 		$req_recup_date_validite_mand_actif = sqlsrv_query($conn, $sql_recup_date_validite_mand_actif);

	// 		if($req_recup_date_validite_mand_actif === false)
	// 		{
	// 			die(print_r(sqlsrv_errors(), true));	
	// 		}

	// 		$dateDbtValiditeActuelMandActif_array = sqlsrv_fetch_array($req_recup_date_validite_mand_actif);
	// 		$dateDbtValiditeActuelMandActif = $dateDbtValiditeActuelMandActif_array[0];

	// 		$dateDebutValiditeSaisiePourNouveauMandActif_string = $_POST['date_debut_validite'];
	// 		$dateDebutValiditeSaisiePourNouveauMandActif_date = DateTime::createFromFormat('d/m/Y', $dateDebutValiditeSaisiePourNouveauMandActif_string);

	// 		//Retirer Hms et millisecondes
	// 		$dateDebutValiditeSaisiePourNouveauMandActif_date = date_time_set($dateDebutValiditeSaisiePourNouveauMandActif_date, 0, 0, 0, 0);
	// 		$dateDbtValiditeActuelMandActif = date_time_set($dateDbtValiditeActuelMandActif, 0, 0, 0, 0);

	// 		if($dateDebutValiditeSaisiePourNouveauMandActif_date <= $dateDbtValiditeActuelMandActif)
	// 		{
	// 			$dateDbtValiditeActuelMandActif_string = $dateDbtValiditeActuelMandActif->format('d/m/Y');
	// 			$dateDebutValiditeSaisiePourNouveauMandActif_string = $dateDebutValiditeSaisiePourNouveauMandActif_date->format('d/m/Y');

	// 			header("content-type: application/javascript");
	// 			echo "alert('La date de debut de validité du futur nouveau MF actif doit être supérieure à la date de début de validité du MF actif actuel qui est ".$dateDbtValiditeActuelMandActif_string.". Or vous avez saisi cette date de début de validité : ".$dateDebutValiditeSaisiePourNouveauMandActif_string."')";
	// 			die();
	// 		}
	// 	}
	// }
	// else //cas du mandataire antérieur seulement
	// {
	// 	////Règle - La date de fin de validité doit être supérieure à la date de debut de validité
	// 	$dateFinValidite = DateTime::createFromFormat('d/m/Y', $_POST['date_fin_validite']);
	// 	$dateFinValidite = date_time_set($dateFinValidite, 0, 0, 0, 0);

	// 	if($dateDebutValidite > $dateFinValidite)
	// 	{
	// 		header("content-type: application/javascript");
	// 		echo "alert('La date de fin de validité doit être supérieure à la date de debut de validité')";
	// 		die();
	// 	}

	// 	////Règle - Si la période de validité du mandataire antérieur est conflictuelle avec un autre MF, une popup viendra expliquer que la création n’est pas possible

	// 	//Récupérer les dates de début de validité et de fin de validité de tous les mandataires du candidat (et nom prenom du mandataire pour le nommer si conflit)
	// 	$sql = "
	// 				select mapping.date_debut_validite, mapping.date_fin_validite, mand.prenom_mf, mand.nom_mf
	// 				from candidat_mandataire as mapping
	// 				inner join mandataire as mand on mand.id_mandataire = mapping.id_mandataire
	// 				where mapping.id_candidat = ".$_POST['id_candidat']
	// 			;

	// 	//En cas de modification (pas creation), il faut retirer du résultat de la requete sql les dates de validité du mandataire concerné, sinon il y aura un conflit "sur lui même" si les dates sont inchangées dans le formulaire par exemple
	// 	if(anti_injection_sql($_POST['id_mandataire']) !== '')
	// 	{
	// 		$sql .= "
	// 					EXCEPT
	// 					(
	// 						select mapping2.date_debut_validite, mapping2.date_fin_validite, mand2.prenom_mf, mand2.nom_mf
	// 						from candidat_mandataire as mapping2
	// 						inner join mandataire as mand2 on mand2.id_mandataire = mapping2.id_mandataire
	// 						where mapping2.id_candidat = ".$_POST['id_candidat']."
	// 						and mapping2.id_mandataire = ".$_POST['id_mandataire']."
	// 					)
	// 				";
	// 	}

	// 	$req = sqlsrv_query($conn, $sql);

	// 	if($req === false)
	// 	{
	// 		die(print_r(sqlsrv_errors(), true));	
	// 	}

	// 	if(sqlsrv_has_rows($req))
	// 	{
	// 		while($unCoupleDeDatesDunMandataire = sqlsrv_fetch_array($req))
	// 		{
	// 			$borneInf = $unCoupleDeDatesDunMandataire[0];
	// 			$borneSup = $unCoupleDeDatesDunMandataire[1];
	// 			$prenomMf = $unCoupleDeDatesDunMandataire[2];
	// 			$nomMf = $unCoupleDeDatesDunMandataire[3];

	// 			if(!isset($borneSup)) //Cas du mandataire actif, cad date fin validité = NULL
	// 			{
	// 				$borneSup = DateTime::createFromFormat('d/m/Y', '01/01/2099'); //On met en borne supérieur une date très lointaine
	// 			}

	// 			if(!(($dateDebutValidite<$borneInf && $dateFinValidite<$borneInf) || ($dateDebutValidite>$borneSup && $dateFinValidite>$borneSup)))
	// 			{
	// 				header("content-type: application/javascript");
	// 				echo "alert('La période de validité du mandataire est conflictuelle avec un autre MF du candidat : ".$prenomMf." ".$nomMf."')";
	// 				die();
	// 			}
	// 		}
	// 	}
	// }

	////REQUETES EN BDD SI LES REGLES FONCTIONNELLES CI-DESSUS ONT ETE RESPECTEES
	if(anti_injection_sql($_POST['id_mandataire']) == '') //Cas de la CREATION d'un mandataire
	{
		//// ----------- INSERTION DU MANDATAIRE EN BDD  -----------
			
		////Règle - Si on est sur le point d'insérer en BDD un mandataire actif et qu'il y en avait déjà un, l'ancien mandataire actif devient antérieur
		if($_POST['periodicite_mandataire'] == 'actif')
		{
			mettre_mandataire_actif_a_anterieur($conn, $_POST['date_debut_validite']);
		}

		//Insérer une ligne dans la table mandataire
		$sql_insert = "INSERT INTO mandataire (id_candidat,".implode(",", $nom_champs_pour_table_mandataire).") 
					   VALUES (".$_POST['id_candidat'].",". implode(",", $valeur_champs_pour_table_mandataire). ")";

		$req_insert = sqlsrv_query($conn, $sql_insert);

		if($req_insert === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}

		//Insérer une ligne dans la table de mapping
		$id_mandataire = trouver_id_mandataire($conn, $_POST['id_candidat']); //Récupérer id_mandataire qu'on vient juste de créer

		$sql_insert2 = "INSERT INTO candidat_mandataire (id_candidat, id_mandataire, ".implode(",", $nom_champs_pour_table_mapping).") 
						VALUES (".$_POST['id_candidat'].",".$id_mandataire.",".implode(",", $valeur_champs_pour_table_mapping). ")";
		
		$req_insert2 = sqlsrv_query($conn, $sql_insert2);
	
		if($req_insert2 === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}

		//Si c'est un mandataire actif, remplacer en BDD la date de fin de validité qui etait vide dans le form (et donc qui a été remplacée en BDD par 1900-01-01) par NULL
		if($_POST['periodicite_mandataire'] == 'actif')
		{
			$sql_update_date_fin_validite = "UPDATE candidat_mandataire
								   SET date_fin_validite = NULL
								   WHERE id_mandataire = ".$id_mandataire;
								  ;

			$req_update_date_fin_validite = sqlsrv_query($conn, $sql_update_date_fin_validite);

			if($req_update_date_fin_validite === false)
			{
				die(print_r(sqlsrv_errors(), true));	
			}
		}	
				
		//Rajouté par EA le 16 10 2018 pour résoudre bug de la creation du mand actif qui remplace l'actif en cours (notamment)
		if($_POST['periodicite_mandataire'] == 'actif')
		{
			$sql_update_candidat = "
									UPDATE candidat
									SET id_mandataire = ".$id_mandataire."
									WHERE id_candidat = ".$_POST['id_candidat']
									;
			$req_update_candidat = sqlsrv_query($conn, $sql_update_candidat);

			if($req_update_candidat === false)
			{
				die(print_r(sqlsrv_errors(), true));	
			}
		}

		//Modifier éventuellement le champ chk_presence_mandataire dans la table candidat
		if($_POST['periodicite_mandataire'] == 'actif')
		{
			$sql_update = "
							UPDATE candidat
							SET chk_presence_mandataire = ".$chk_presence_mandataire."
							WHERE id_candidat = ".$_POST['id_candidat']
							;
			$req_update = sqlsrv_query($conn, $sql_update);

			if($req_update === false)
			{
				die(print_r(sqlsrv_errors(), true));	
			}
		}

		//Règle : Un warning doit être affiché si on a une période de « creux » dans l’historique des mandataires
		$presenceIncoherenceDatesMandataires = verifier_continuite_mandataires($conn, $_POST['id_candidat']);
		
		if($presenceIncoherenceDatesMandataires)
		{
			header("content-type: application/javascript");
			update_entete('candidat', $_POST['id_candidat'], $_POST['annee'], $_SESSION['id_type_util'], 60, $conn);
			echo "alert('Création réussie, la page va se rafraîchir".$messageIncoherenceDatesMandataires."')"; //Popup de confirmation de la création du mandataire, avec en plus un message mentionnant un creux dans les périodes de validite des mandataires
		}
		else
		{
			header("content-type: application/javascript");
			update_entete('candidat', $_POST['id_candidat'], $_POST['annee'], $_SESSION['id_type_util'], 60, $conn);			
			echo "alert('Création réussie, la page va se rafraîchir')"; //Popup de confirmation de la creation du mandataire
		}
	}
	else //Cas de la MODIFICATION d'un mandataire
	{
		//// ----------- MODIFICATION DU MANDATAIRE EN BDD  -----------

		//// Je commente le bloc dessous car plus utilisé
		// $mandataireEstIlAnterieur = verifier_si_mandataire_anterieur($conn);
		// ////Règle - Si on est sur le point de modifier en BDD un mandataire antérieur en mandataire actif, et qu'il y avait déjà un mandataire actif, l'ancien mandataire actif devient antérieur
		// if($_POST['periodicite_mandataire'] == 'actif')
		// {
		// 	//On vérifie que le mandataire modifié dans le form est antérieur, ce qui voudrait dire qu'on l'a passé à actif dans le form
		// 	if($mandataireEstIlAnterieur)
		// 	{
		// 		mettre_mandataire_actif_a_anterieur($conn, $_POST['date_debut_validite']);
		// 	}
		// }

		//Table mandataire
		$where_mandataire = " WHERE id_mandataire='".$_POST['id_mandataire']."'";
		$sql_update_mandataire = "UPDATE mandataire SET ".$LISTE_POUR_TABLE_MANDATAIRE."".$where_mandataire;

		$req_update_mandataire = sqlsrv_query($conn, $sql_update_mandataire);

		if($req_update_mandataire === false)
		{
			die(print_r(sqlsrv_errors(), true));
		}

		//Table mapping
		$where_mapping = " WHERE id_mandataire='".$_POST['id_mandataire']."'";
		$sql_update_mapping = "UPDATE candidat_mandataire SET ".$LISTE_POUR_TABLE_MAPPING."".$where_mapping;

		$req_update_mapping = sqlsrv_query($conn, $sql_update_mapping);

		if($req_update_mapping === false)
		{
			die(print_r(sqlsrv_errors(), true));
		}

		//Table candidat
		if($_POST['periodicite_mandataire'] == 'actif') //Modifier éventuellement le champ chk_presence_mandataire dans la table candidat si on vient de modifier le mandataire actif
		{
			$sql_update_2 = "
							UPDATE candidat
							SET chk_presence_mandataire = ".$chk_presence_mandataire." WHERE id_candidat = ".$_POST['id_candidat']
							;
			
			$req_update_2 = sqlsrv_query($conn, $sql_update_2);

			if($req_update_2 === false)
			{
				die(print_r(sqlsrv_errors(), true));	
			}
		}
		
		//Règle : Un warning doit être affiché si on a une période de « creux » dans l’historique des mandataires
		$presenceIncoherenceDatesMandataires = verifier_continuite_mandataires($conn, $_POST['id_candidat']);
	 
		if($presenceIncoherenceDatesMandataires)
		{
			header("content-type: application/javascript");
			update_entete('candidat', $_POST['id_candidat'], $_POST['annee'], $_SESSION['id_type_util'], 60, $conn);	
			echo "alert('Modification réussie, la page va se rafraîchir".$messageIncoherenceDatesMandataires."')"; //Popup de confirmation de la modification du mandataire, avec en plus un message mentionnant un creux dans les périodes de validite des mandataires
		}
		else
		{
			header("content-type: application/javascript");
			update_entete('candidat', $_POST['id_candidat'], $_POST['annee'], $_SESSION['id_type_util'], 60, $conn);				
			echo "alert('Modification réussie, la page va se rafraichir')"; //Popup de confirmation de la modification du mandataire
		}
	}
}

	
function trouver_id_mandataire($conn ,$id_candidat)
{
	$id_mandataire = "";
	$sql = "select top 1 id_mandataire from dbo.mandataire where id_candidat = ".$id_candidat." order by id_mandataire desc";
	$req = sqlsrv_query($conn,$sql, array(), array("Scrollable"=>"buffered"));
	
	if($req === false)
	{
		die(print_r(sqlsrv_errors(), true));		
	}
	else
	{
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while($rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				$id_mandataire = $rs['id_mandataire'];
				break;
			}
		}
	}

	return $id_mandataire;
}
	
	
function mettre_mandataire_actif_a_anterieur($conn, $dateDebutValiditeNouveauMandataireActif)
{
	$sql_compter_mand_actif = "
								select count(*)
								from candidat_mandataire
								where id_candidat = ".$_POST['id_candidat']."
								and periodicite_mandataire = 'actif'
				  			   ";
	$req_compter_mand_actif = sqlsrv_query($conn, $sql_compter_mand_actif);

	if($req_compter_mand_actif === false)
	{
		die(print_r(sqlsrv_errors(), true));	
	}	

	$nbMandActif = [];

	if(sqlsrv_has_rows($req_compter_mand_actif))
	{
		$nbMandActif = sqlsrv_fetch_array($req_compter_mand_actif);
	}

	if($nbMandActif[0] == 1) //S'il y avait déjà un mand actif, on le passe à anterieur, et on lui renseigne sa date de fin de validite
	{
		$dateDebutValiditeNouveauMandataireActifFormatDate = DateTime::createFromFormat('d/m/Y', $dateDebutValiditeNouveauMandataireActif);
		$dateFinValiditeAncienMandataireActif = $dateDebutValiditeNouveauMandataireActifFormatDate->modify('-1 day')->format('Ymd');

		$sql_update_ancien_mand_actif = "
											UPDATE candidat_mandataire
											SET periodicite_mandataire = 'anterieur',
												date_fin_validite = '".$dateFinValiditeAncienMandataireActif."'
											WHERE id_mandataire = (
																		select id_mandataire
																		from candidat_mandataire
																		where id_candidat = ".$_POST['id_candidat']." 
																		and periodicite_mandataire = 'actif'
																	)
				  			   			";

		$req_update_ancien_mand_actif = sqlsrv_query($conn, $sql_update_ancien_mand_actif);

		if($req_update_ancien_mand_actif === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}	
	}
}

//Plus utilisée
// function verifier_si_mandataire_anterieur($conn)
// {
// 	$reponse = false;

// 	$sql = "
// 				select periodicite_mandataire
// 				from [ELEC].[dbo].[candidat_mandataire]
// 				where id_mandataire = ".$_POST['id_mandataire']
// 	   		;
// 	$req = sqlsrv_query($conn, $sql);
			
// 	if($req === false)
// 	{
// 			die(print_r(sqlsrv_errors(), true));
// 	}
			
// 	$resp = sqlsrv_fetch_array($req);
		
// 	if($resp[0] == 'anterieur')
// 	{
// 		$reponse = true;
// 	}

// 	return $reponse;
// }


function verifier_continuite_mandataires($conn, $id_candidat)
{
	$sql = "select date_debut_validite, date_fin_validite
			from candidat_mandataire
			where id_candidat = ".$id_candidat." 
			order by date_debut_validite asc";
		
	$req = sqlsrv_query($conn, $sql);

	if($req === false)
	{
		die(print_r(sqlsrv_errors(), true));		
	}

	//Mettre les dates dans un tableau
	$dateDeValiditeDesMandataires = [];

	if(sqlsrv_has_rows($req))
	{			
		while($dateDeValiditeMandataireEnCours = sqlsrv_fetch_array($req))
		{
			array_push($dateDeValiditeDesMandataires, $dateDeValiditeMandataireEnCours);
		}
	}

	//Analyser les dates
	$incoherenceDatesValiditeMandataires = false;

	for ($i=0;$i<count($dateDeValiditeDesMandataires);$i++)
	{
		if($i < (count($dateDeValiditeDesMandataires) - 1)) //si ce n'est pas le dernier element du tableau
		{
			if (!isset($dateDeValiditeDesMandataires[$i]['date_fin_validite'])) //Si c'est le mandataire actif
			{
				$dateDeValiditeDesMandataires[$i]['date_fin_validite'] = '20990101'; //Forcer la date de fin validité du mand actif à une date très lointaine
			}

			$lendemainDeDateDebutValiditeString = $dateDeValiditeDesMandataires[$i]['date_fin_validite']->modify('+1 day')->format('Ymd'); //Conversion en String sinon comparaison date ne fonctionne pas
			$next = $i+1;
			$dateDebutValiditeMandataireSuivantString = $dateDeValiditeDesMandataires[$next]['date_debut_validite']->format('Ymd');

			if($lendemainDeDateDebutValiditeString !== $dateDebutValiditeMandataireSuivantString)
			{
				$incoherenceDatesValiditeMandataires = true;
				break;
			}
		}
	}

	return $incoherenceDatesValiditeMandataires;
}

?>