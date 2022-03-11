<?php
session_start();
require("../../inclusion/CONNEXION.php");
require_once("../../fonctions/chaines.php");
require_once("../../fonctions/nombres.php");
require_once("../../fonctions/dates.php"); 
require_once("../../fonctions/combo_dynamique.php");
require("../../fonctions/FONCTIONS_bandeau.php");
require_once("../../fonctions/anti_injection_sql.php"); // indispensable pour éviter les injections, notamment lors de la vérification de login via  login_verif.php et lors de tous les GET ou POST reçus ici
require_once("../../auth/login_verif.php");  // vérification utilisateur
require_once("../../fonctions/verif_rapport_termine.php");
require_once("../../fonctions/FONCTIONS_FORMULAIRES_contentieux.php");

//Verification que la référence est bien unique
if (isset($_POST['ref_unique_hidden']))
{
	header("content-type: application/javascript");
	echo "alert('ATTENTION : la référence du CTX est déjà utilisée.');";
}
else
{
	$arrylist = array();
	$execlure_champ = array('elec','departement','departement_id','grief','analyse_grief','chk_grief_financier','ref_unique_hidden', 'id_defendeur', 'defendeur');
	$champs_grief = array('grief','analyse_grief');
	$modif = false;
	
	if (isset($_GET['id']) && $_GET['id'] != '')
	{
		$modif = true;
	}
	else
	{
		$_POST['date_creation_rqt'] = date("Y-m-d");	
	}
	
	if (!isset($_POST['chk_joindre_dossier_instruction']))
	{ // 20180516 ajout pour ano évolution 732
		$_POST['chk_joindre_dossier_instruction'] = 0;
	}
	
	$_POST['chk_requerant_prefet'] = '0';
	$_POST['chk_defendeur_cnccfp'] = '0';
	$_POST['chk_requerant_cnccfp'] = '0';

	if ($_POST['id_requerant'] == "Tiers")
	{
		$_POST['id_requerant'] = NULL;
	}
	elseif ($_POST['id_requerant'] == "Prefet")
	{
		$_POST['id_requerant'] = NULL;
		$_POST['requerant'] = NULL;
		$_POST['chk_requerant_prefet'] = true;
	}
	elseif ($_POST['id_requerant'] == "CNCCFP")
	{
		$_POST['id_requerant'] = NULL;
		$_POST['requerant'] = NULL;
		$_POST['chk_requerant_cnccfp'] = true;	
	}
	else
	{
		$_POST['requerant'] = NULL;
	}

	if ($_POST['id_defendeur'] == "Autre")
	{
		$_POST['id_defendeur'] = NULL;	
	}
	elseif ($_POST['id_defendeur'] == "CNCCFP")
	{
		$_POST['id_defendeur'] = NULL;
		$_POST['defendeur'] = NULL;
		$_POST['chk_defendeur_cnccfp'] = true;
	}
	else
	{
		$_POST['defendeur'] = NULL;
	}

	$LISTE= '';
	
	foreach($_POST AS $k=>$val)
	{
	    if(!in_array($k, $execlure_champ) && (substr($k, 0, 21) !== 'nom_prenom_defendeur_') && (substr($k, 0, 13) !== 'id_defendeur_'))
		{
			if (strpos($k,'date') !== false)
			{				
				if(validation_date($val))
				{	
					$LISTE=$LISTE."$k='".$_POST[$k]."',";
					array_push($arrylist,"$k=".$_POST[$k]."");
				} 
				else
				{	
					if ($_POST[$k] == '')
					{	
						$_POST[$k] == 0;
					}
					
					if(escape_quote(anti_injection_sql($_POST[$k]))=='NULL')
					{	
						array_push($arrylist,"$k=null");
						$LISTE=$LISTE."$k=null,";
					}
					else
					{	
						array_push($arrylist,"$k=".escape_quote(anti_injection_sql($_POST[$k]))."");
						$LISTE=$LISTE."$k='".escape_quote(anti_injection_sql($_POST[$k]))."',";
					}
				}
			}
			else
			{
				if(escape_quote(anti_injection_sql($_POST[$k]))=='NULL' || escape_quote(anti_injection_sql($_POST[$k]))=='')
				{
					array_push($arrylist,"$k=null");
					$LISTE=$LISTE."$k=null,";
				}
				else
				{
					array_push($arrylist,"$k=".escape_quote(anti_injection_sql($_POST[$k]))."");
					$LISTE=$LISTE."$k='".escape_quote(anti_injection_sql($_POST[$k]))."',";
				}
			}
		}
	} //Fin du foreach

	//Pour les données grief
	$arrylist_grief = array();
	$LISTE_grief = '';
	
	foreach($_POST AS $k=>$val)
	{
		if(in_array($k, $champs_grief))
		{
			if (strpos($k,'date') !== false)
			{				
				if(validation_date($val))
				{	
					$LISTE_grief=$LISTE_grief."$k='".$_POST[$k]."',";
					array_push($arrylist_grief,"$k=".$_POST[$k]."");
				}
				else
				{	
					if ($_POST[$k] == '')
					{	
						$_POST[$k] == 0;
					}
					
					if(escape_quote(anti_injection_sql($_POST[$k]))=='NULL')
					{	
						array_push($arrylist_grief,"$k=null");
						$LISTE_grief=$LISTE_grief."$k=null,";
					}
					else
					{	
						array_push($arrylist_grief,"$k=".escape_quote(anti_injection_sql($_POST[$k]))."");
						$LISTE_grief=$LISTE_grief."$k='".escape_quote(anti_injection_sql($_POST[$k]))."',";
					}
				}
			}
			else
			{
					if(escape_quote(anti_injection_sql($_POST[$k]))=='NULL' || escape_quote(anti_injection_sql($_POST[$k]))=='')
					{
						array_push($arrylist_grief,"$k=null");
						$LISTE_grief=$LISTE_grief."$k=null,";
					}
					else
					{
						array_push($arrylist_grief,"$k=".escape_quote(anti_injection_sql($_POST[$k]))."");
						$LISTE_grief=$LISTE_grief."$k='".escape_quote(anti_injection_sql($_POST[$k]))."',";
					}
			}
		}
	} //Fin du foreach
	
	if(isset($_POST['chk_grief_financier']))
	{
		array_push($arrylist_grief,"chk_grief_financier = 1");
		$LISTE_grief=$LISTE_grief."chk_grief_financier = 1".",";
	}
	else
	{
		array_push($arrylist_grief,"chk_grief_financier = 0");
		$LISTE_grief=$LISTE_grief."chk_grief_financier = 0".",";
	}

	//Pour les griefs : en creation et modif
	$fields_name_grief = array();
	$fields_value_grief = array();
	
	for($i = 0; $i < count($arrylist_grief); $i++)
	{
		$arrval_grief = [];
		$arrval_grief = explode('=', $arrylist_grief[$i] );
		$fieldName_grief = $arrval_grief[0];
		$fieldvalue_grief = $arrval_grief[1];
		array_push($fields_name_grief,$fieldName_grief);
		array_push($fields_value_grief,'\''.$fieldvalue_grief.'\'');
	}
	
	$fields_name_grief = implode(",",$fields_name_grief);
	$fields_value_grief = implode(",",$fields_value_grief);	

	//Mode creation
	if(!$modif)
	{
		$fields_name = array();
		$fields_value = array();
		
		for($i = 0; $i < count($arrylist); $i++)
		{
			$arrval = [];
			$arrval = explode('=', $arrylist[$i] );
			$fieldName = $arrval[0];
			$fieldvalue = $arrval[1];
			
			array_push($fields_name,$fieldName);
			array_push($fields_value,'\''.$fieldvalue.'\'');
		}
		
		$fields_name = implode(",",$fields_name);
		$fields_value = implode(",",$fields_value);	
		
		$sql_update = "INSERT INTO requete_ctx (".$fields_name.") 
					   VALUES (".$fields_value.");SELECT SCOPE_IDENTITY() AS IDENTITY_COLUMN_NAME;";	
		$sql_update = str_replace( "'null'","null",$sql_update);				
		$req = sqlsrv_query($conn,$sql_update);
		
		if( $req === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}
		
		$id_r = last_Insert_Id($req);
		
		
		////////////// Défendeur(s) - On passe de 1 à n défendeurs pour les MG2020 //////////////
		
		$dernier_id_mapping = 0; //Initialisation

//echo '<pre class="debug">';print_r($_POST);echo '</pre>';die();
		
		foreach($_POST AS $k=>$val)
		{
		    if(substr($k,0,21) == 'nom_prenom_defendeur_')
		    {
		        $sql_insert_defendeurs = "insert into requete_ctx_defendeur (id_requete_ctx, nom_prenom_defendeur)
                      values (".$id_r.", '".$val."')";
		        
		        $req_insert_defendeurs = sqlsrv_query($conn, $sql_insert_defendeurs);
		        
		        if($req_insert_defendeurs === false)
		        {
		            die(print_r(sqlsrv_errors(), true));
		        }
		        
		        $sql_get_last_id = "select top 1 id_mapping
                                    from requete_ctx_defendeur
                                    order by id_mapping desc";
		        
		        $req_get_last_id = sqlsrv_query($conn, $sql_get_last_id);  
		        $last_insert_id__array = sqlsrv_fetch_array($req_get_last_id);
		        $last_insert_id = $last_insert_id__array[0];
		        $dernier_id_mapping = $last_insert_id;
		    }
		    
		    if(substr($k,0,13) == 'id_defendeur_')
		    {
		        $sql_update_defendeur = "update requete_ctx_defendeur
                                         set id_defendeur = ".$val
                                         ." where id_mapping = ".$dernier_id_mapping;

                $req_update_defendeur = sqlsrv_query($conn, $sql_update_defendeur);
                
                if($req_update_defendeur === false)
                {
                    die(print_r(sqlsrv_errors(), true));
                }
		    }
		    
		    //Si on coche 'Autre' pour le défendeur, Alors il n'y aura pas en plus des défendeurs sélectionnés
		    if($k == 'defendeur' && isset($val) && $val !== '')
		    {
		       $sql_insert_defendeur = "insert into requete_ctx_defendeur (id_requete_ctx, defendeur)
                      values (".$id_r.", '".$val."')";
                                         
		       $req_insert_defendeur = sqlsrv_query($conn, $sql_insert_defendeur);
             
		       if($req_insert_defendeur === false)
               {
                     die(print_r(sqlsrv_errors(), true));
               }
		    }
		}
		
		//GRIEF
		$sql_update_grief = "INSERT INTO requete_ctx_grief (id_requete,".$fields_name_grief.") 
					         VALUES (".$id_r.", ".$fields_value_grief.");";	
		$sql_update_grief = str_replace( "'null'","null",$sql_update_grief);				
		$req_grief = sqlsrv_query($conn,$sql_update_grief);
		
		if( $req_grief === false)
		{
			die(print_r(sqlsrv_errors(), true));	
		}

		scrutin_chk_ctx($conn,$_POST['id_scrutin']);	
		header("content-type: application/javascript");
		echo "alert('Enregistrement réussi');";
		echo 'document.location.replace(document.location.href+"?id='.$id_r.'");';
	}
	else //Mode modification	
	{
		$id_scrutin_stocke = get_id_scrutin_from_requete_ctx($conn ,$_GET['id']);

		if($id_scrutin_stocke != $_POST["id_scrutin"])
		{	    
		    if(!empty($_POST["id_scrutin"]))
		    {	        
		      mettre_a_ajour_scrutin($conn, $_POST["id_scrutin"]); //Remodifié par EA le 04 09 2020
		    }
		}	

		$LISTE = mb_substr($LISTE,0,-1); // suppression du dernier caractère (virgule finale)

		$where = " WHERE id_requete = '".$_GET['id']."'";
		$sql_update = "UPDATE requete_ctx SET ".$LISTE."".$where;

		$req = sqlsrv_query($conn,$sql_update);
	
		if( $req === false)
		{
			die(print_r(sqlsrv_errors(), true));
		}

		// ------------------------------------------------------------------------------------------------------------------------
		// ------------------------------------------------- Maj des défendeurs ---------------------------------------------------
		
		/* Je vais appeler A l'ensemble des défendeurs de type 'candidat' en base, c'est à dire avant la modification de l'utilisateur
		 * Et B sera l'ensemble des défendeurs de type 'candidat' dans le formulaire, c'est à dire après la modification de l'utilisateur
		 * */
		
		//Récupérer les id_cand des défendeurs en base
		$sql_defendeurs_A = "  select id_defendeur
                               from requete_ctx_defendeur
                               where id_requete_ctx = ".$_GET['id'];
		
		$req_defendeurs_A = sqlsrv_query($conn, $sql_defendeurs_A);
		
		$id_defendeurs_A_array = array();
		
		while($un_id_defendeur = sqlsrv_fetch_array($req_defendeurs_A, SQLSRV_FETCH_NUMERIC))
		{
		    if(isset($un_id_defendeur[0]))
		    {
		      array_push($id_defendeurs_A_array, $un_id_defendeur[0]);
		    }
		}
		
//print_r($id_defendeurs_A_array);

		//Récupérer les id_cand et les noms des défendeurs selectionnés dans le formulaire
		$id_defendeurs_B_array = array();
		$nom_prenom_defendeurs_B_array = array();
		
//print_r($_POST);

		foreach($_POST AS $k=>$val)
		{
		    if(substr($k,0,13) == 'id_defendeur_')
		    {
		        array_push($id_defendeurs_B_array, $val);
		    }
		    
		    if(substr($k,0,21) == 'nom_prenom_defendeur_')
		    {
		        array_push($nom_prenom_defendeurs_B_array, $val);
		    }
		}
		
//print_r($id_defendeurs_B_array);
//print_r($nom_prenom_defendeurs_B_array);

		//Déterminer les défendeurs à supprimer en base
		$id_defendeurs_suppression_array = array();
		
		for($i=0;$i<count($id_defendeurs_A_array);$i++)
		{
		    if(!in_array($id_defendeurs_A_array[$i], $id_defendeurs_B_array))
		    {
		        array_push($id_defendeurs_suppression_array, $id_defendeurs_A_array[$i]);
		    }
		}
		
//print_r($id_defendeurs_suppression_array);

		//Déterminer les défendeurs à insérer en base
		$id_defendeurs_insertion_array = array();
		$nom_prenom_defendeurs_insertion_array = array();
		
		for($j=0;$j<count($id_defendeurs_B_array);$j++)
		{
		    if(!in_array($id_defendeurs_B_array[$j], $id_defendeurs_A_array))
		    {
		        array_push($id_defendeurs_insertion_array, $id_defendeurs_B_array[$j]);
		        array_push($nom_prenom_defendeurs_insertion_array, $nom_prenom_defendeurs_B_array[$j]);
		    }
		}

//print_r($id_defendeurs_insertion_array);
//print_r($nom_prenom_defendeurs_insertion_array);

		//Supprimer les défendeurs à supprimer
		if(count($id_defendeurs_suppression_array) > 0)
		{
		    $id_defendeur_liste = '';
		    
		    for($k=0;$k<count($id_defendeurs_suppression_array);$k++)
        	{
        	    $id_defendeur_liste = $id_defendeur_liste.$id_defendeurs_suppression_array[$k].",";
        	}
        	
        	//Retirer la dernière virgule
        	$id_defendeur_liste = substr($id_defendeur_liste, 0, strlen($id_defendeur_liste) - 1);
 	  
		    $sql_delete = "delete from requete_ctx_defendeur
                           where id_defendeur in (".$id_defendeur_liste.")";
	    
		    $req_delete = sqlsrv_query($conn, $sql_delete);
		    
		    if($req_delete === false)
		    {
		        die(print_r(sqlsrv_errors(), true));
		    }
		}
	
		//Insérer en base les défendeurs à ajouter
		for($l=0;$l<count($id_defendeurs_insertion_array);$l++)
		{
		    $sql_insert = "insert into requete_ctx_defendeur (id_requete_ctx, id_defendeur, nom_prenom_defendeur)
                           values (".$_GET['id'].",".$id_defendeurs_insertion_array[$l].",'".$nom_prenom_defendeurs_insertion_array[$l]."')";

		    $req_insert = sqlsrv_query($conn, $sql_insert);
		    
		    if($req_insert === false)
		    {
		        die(print_r(sqlsrv_errors(), true));
		    }
		}
	
		//Insertion/Modification/Suppresion éventuelle d'un défendeur de type 'Autre'
		// -----
		$sql_check_defendeur = "select top 1 defendeur
                                FROM requete_ctx_defendeur
                                where defendeur is not null
                                and id_requete_ctx = ".$_GET['id'];
		
		$req_check_defendeur = sqlsrv_query($conn, $sql_check_defendeur);
		
		if($req_check_defendeur === false)
		{
		    die(print_r(sqlsrv_errors(), true));
		}
		
		$check_defendeur_array = sqlsrv_fetch_array($req_check_defendeur);
		$check_defendeur_string = $check_defendeur_array[0];
		
		//Si le champs 'defendeur' est renseigné dans le formulaire, ET que le champ 'défendeur' n'est pas renseigné en base, Alors il faut INSERER le défendeur en base
		if(isset($_POST['defendeur']) && !isset($check_defendeur_string))
		{
//print_r('la');		    
		    $sql_insert_autre = "insert into requete_ctx_defendeur (id_requete_ctx, defendeur)
                                 values (".$_GET['id'].",'".$_POST['defendeur']."')";
		    
		    $req_insert_autre = sqlsrv_query($conn, $sql_insert_autre);
		    
		    if($req_insert_autre === false)
		    {
		        die(print_r(sqlsrv_errors(), true));
		    }
		}
		//Si le champs 'defendeur' est renseigné dans le formulaire, ET que le champ 'défendeur' est renseigné en base, Alors il faut le METTRE A JOUR en base
		else if(isset($_POST['defendeur']) && isset($check_defendeur_string))
		{
//print_r('ici');		    
		    $sql_update_autre = "update requete_ctx_defendeur
                                 set defendeur = '".$_POST['defendeur']."'
                                 where id_requete_ctx = ".$_GET['id'];
		    
		    $req_update_autre = sqlsrv_query($conn, $sql_update_autre);
		    
		    if($req_update_autre === false)
		    {
		        die(print_r(sqlsrv_errors(), true));
		    }
		}
		//Si le champs 'defendeur' n'est pas renseigné dans le formulaire, ET que le champ 'défendeur' est renseigné en base, Alors il faut le SUPPRIMER en base
		else if(!isset($_POST['defendeur']) && isset($check_defendeur_string))
		{
//print_r('plop');		    
		    $sql_delete_autre = "delete from requete_ctx_defendeur
                                 where id_requete_ctx = ".$_GET['id']."
                                 and defendeur = '".$check_defendeur_string."'";
		    
		    $req_delete_autre = sqlsrv_query($conn, $sql_delete_autre);
		    
		    if($req_delete_autre === false)
		    {
		        die(print_r(sqlsrv_errors(), true));
		    }
		}
		// -----

		// -------------------------------------------------- Fin maj des defendeurs ----------------------------------------------
		// ------------------------------------------------------------------------------------------------------------------------
		
		//GRIEF
		$LISTE_grief = mb_substr($LISTE_grief,0,-1); // suppression du dernier caractère (virgule finale)
		$where_grief = " WHERE id_requete = '".$_GET['id']."'";
		$sql_update_grief = "
			                 IF EXISTS(SELECT id_grief FROM dbo.requete_ctx_grief".$where_grief.")
				                UPDATE requete_ctx_grief SET ".$LISTE_grief."".$where_grief."
			                 ELSE 
                				INSERT INTO requete_ctx_grief (id_requete,".$fields_name_grief.") 
                				VALUES (".$_GET['id'].", ".$fields_value_grief.");";		

		$req_grief = sqlsrv_query($conn,$sql_update_grief);
	
		if( $req_grief === false)
		{
			die(print_r(sqlsrv_errors(), true));
		}
		
		//Mise à jour du chk contentieux du scrutin
        if(!empty($_POST["id_scrutin"]))
        {
		  scrutin_chk_ctx($conn,$_POST['id_scrutin']);
        }
		header("content-type: application/javascript");
		echo "alert('Modification réussie');";
	}
}

function get_id_scrutin_from_requete_ctx($conn ,$id_requete)
{
	$id_scrutin = "";
	
	$sql = "SELECT id_scrutin from  requete_ctx where id_requete = '".$id_requete."'";
	$rs = sqlsrv_query($conn,$sql,array(), array("Scrollable"=>"buffered"));

	if( $rs === false)
	{
		die(print_r(sqlsrv_errors(), true));
	}
	
	$nb = sqlsrv_num_rows($rs);
	
	if($nb >0)
	{
		while($row = sqlsrv_fetch_array($rs, SQLSRV_FETCH_ASSOC))
		{
			$id_scrutin = $row['id_scrutin'];
		}
	}

	return $id_scrutin ;
}

function mettre_a_ajour_scrutin($conn,$id_scrutin)
{
	$sql_ctx =  "UPDATE scrutin set chk_ctx = '0' where id_scrutin=".$id_scrutin;

	$req_ctx = sqlsrv_query($conn,$sql_ctx);
	
	if( $req_ctx === false)
	{
		die(print_r(sqlsrv_errors(), true));		
	}	
}

function last_Insert_Id($queryID)
{
	sqlsrv_next_result($queryID);
	sqlsrv_fetch($queryID);
	return sqlsrv_get_field($queryID, 0);
}

?>