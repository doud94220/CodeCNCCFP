<?php

/*****************************************************************************************************************************************************
Le précédent fichier est celui là : "FONCTIONS_decision_texte_depot_Bck21092018.php"
******************************************************************************************************************************************************
Dans cette nouvelle version du fichier principal de l'export, ont été faites les modifications/corrections suivantes :
- Correctif pour que "la Polynésie s'exporte"
- Modifications des commentaires aux différents endroits où l'on créer le PDF TEXTE
- Mise en commentaire du code qui recupère une copie du PDF TEXTE dans .../zz_decisions_pdf/...pour l'ancienne application (année < 2015)
  ET remise en route de la creation de PDF TEXTE pour l'ancienne application
- Prise en compte du fait que Imagick fonctionne en LOCAL sur mon PC => Donc la creation des pdf image sera déclénchée sur tous les environnements
- Pour les pdf image : on ne prend désormais que les 50 premières pages (evolution suite à plantage en prod parce qu'une requete faisait 1576 pages)
- Correction du nettoyage des fichiers JPEG juste après la creation du pdf image d'un candidat (les JPEG n'etaient pas effacées)
- Modification au niveau du log en base : 
  - désormais on ne logue sur S8APP1 que en PROD, pour la PREPROD on logue sur DEVTJ, pour la RECETTE et en LOCAL c'est sur DEVEA
  - et du coup j'ai créé le fichier "ENV_PourPrgmExport.txt" qui contient l'environnement d'éxécution
- Modification du code pour gérer un quatrieme environnement : la PREPROD
*****************************************************************************************************************************************************/

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Paris');

class Outil_consultation_sp
{
	///////////// ATTRIBUTS et CONSTANTES /////////////

	public $conn_traitement;
	public $conn_ged;
	public $conn_ged_utf8;
	public $conn_nouvelle_appli;
	public $conn_ancien_appli;
	public $date_debut_traitement;
	public $id_depot_traitement = "";
	public $document_param = null;
	public $type_traitement;

    private $COMPTEUR_TOTAL_GED_LUS = 0;
    private $COMPTEUR_TOTAL_FONC_ERROR = 0;
    private $COMPTEUR_TOTAL_TECH_ERROR = 0;
    private $COMPTEUR_TOTAL_WARNING = 0;
	private $COMPTEUR_TOTAL_DECISION_CREE = 0;
	private $COMPTEUR_TOTAL_RECOURS_CREE = 0;
	private $COMPTEUR_TOTAL_MODIFICATIVE_CREE = 0;
    private $COMPTEUR_ECART_DECISIONS = 0;
	private $COMPTEUR_TOTAL_REQUETE_CREE = 0;
	private $COMPTEUR_TOTAL_MEMOIRE_CREE = 0;
	private $COMPTEUR_TOTAL_JUGEMENT_CREE = 0;
	private $COMPTEUR_TOTAL_DECISION_COPIE_POUR_ANONYMISER = 0;
	private $COMPTEUR_TOTAL_RECOURS_COPIE_POUR_ANONYMISER = 0;
	private $COMPTEUR_TOTAL_MODIFICATIVE_COPIE_POUR_ANONYMISER = 0;
	
	private $METADONNEE_DECISION = [];
	private $METADONNEE_RECOURS = [];
	private $METADONNEE_MODIFICATIVE = [];
	private $METADONNEE_REQUETE = [];
	private $METADONNEE_MEMOIRE = [];
	private $METADONNEE_JUGEMENT = [];
	
	//// Serveur et base GED ////
    public $serverGed = '';
    public $password = '';

	//// Serveur et base ELEC nouvelle version pour les LOGS ////
    public $serverTraitement = '';
	public $serverTraitementDb = '';
	const TABLE_TRAITEMENT_PARAM = "z_depot_traitement_param"; //Edouard : Utilise par le traitement qui lit un fichier CSV je crois, mais non utilise dans ce fichier...
	const TABLE_TRAITEMENT = "z_depot_traitement";
	const TABLE_TRAITEMENT_MESSAGE = "z_depot_traitement_message";
	const TABLE_PARAMETRAGES = "z_depot_parametrages";
	
	//// Serveur et base ELEC nouvelle version pour les REQUETES SELECT ////
    public $serverProdName = '';
    public $serverProdDb = '';

    //// Serveur et base ELEC ancienne version pour les REQUETES SELECT ////
	const SERVER_ANCIEN_NAME = "192.168.6.14,1433"; //Même IP pour DEV et PROD
	const PASSWORD_ANCIEN = "pho"; //Même mdp pour DEV et PROD
	const SERVER_ANCIEN_DB_2010 = "BD_ELEC_2010";
	const SERVER_ANCIEN_DB_2011 = "BD_ELEC_2011";
	const SERVER_ANCIEN_DB_2012 = "BD_ELEC_2012";
	const SERVER_ANCIEN_DB_2013 = "BD_ELEC_2013";
	const SERVER_ANCIEN_DB_2014 = "BD_ELEC_2014";

    public $repertoireDepot = ''; //Sera renseigné après une requête dans la table de paramétrage, dans le constructeur
	const REPERTOIRE_DECISION = "Decisions_initiales";
	const REPERTOIRE_RECOURS = "Decisions_rec_gr";
	const REPERTOIRE_MODIFICATIVE = "Decisions_rectif";
	const REPERTOIRE_REQUETE = "Requetes";
	const REPERTOIRE_MEMOIRE = "Memoires";
	const REPERTOIRE_JUGEMENT = "Jugements";
	const REPERTOIRE_ANONYMISATION = "A_Anonymiser";
	
	const REPERTOIRE_ANCIEN_APP = "zz_decisions_pdf";
	
	const REPERTOIRE_MODELES_CSV = "z_modeles_csv";
	const FILE_DECISION_CSV = "Decision_metadonnees.csv";
	const FILE_RECOURS_CSV = "Decision_rec_gr_metadonnees.csv";
	const FILE_MODIFICATIVE_CSV = "Decision_rectif_metadonnees.csv";
	const FILE_REQUETE_CSV = "Requete_metadonnees.csv";
	const FILE_MEMOIRE_CSV = "Memoire_metadonnees.csv";
	const FILE_JUGEMENT_CSV = "Jugement_metadonnees.csv";
	
	const REPERTOIRE_MODELES_XML = "z_modeles_xml";
	const FILE_DECISION_XML = "Metadonnees.xml"; //J'ai voulu mettre un nom spécifique à chaque type de document mais le script d'import SP ne lit que 'Metadonnees.xml'
	const FILE_RECOURS_XML = "Metadonnees.xml";
	const FILE_MODIFICATIVE_XML = "Metadonnees.xml";
	const FILE_REQUETE_XML = "Metadonnees.xml";
	const FILE_MEMOIRE_XML = "Metadonnees.xml";
	const FILE_JUGEMENT_XML = "Metadonnees.xml";

    public $repertoireImportSp = ''; //Sera renseigné après une requête dans la table de paramétrage, dans le constructeur
    public $environnement = '';

	//PARAMETRES EN DUR POUR GERER LA CREATION OU NON DES PDF, DU XML, DU CSV
	const PROCESS_TYPE = ''; // Activation du traitement csv (en mettant 'csv') OU Désactivation du traitement csv (en mettant '')
	private $PDF_FILES = true; // à commenter pour ne pas créer de fichier PDFs


	///////////// METHODES /////////////
	
	//Constructeur
    public function __construct()
	{
        //RECUPERATION EN BDD DES DONNES DU PARAMETRAGES
        try
        {
			// Récupération de l'environnement d'éxécution de l'export à partir d'un fichier
			$ressourceFichierEnv = fopen('fonctions\ENV_PourPrgmExport.txt', 'r');
			$environnementExecution = fgets($ressourceFichierEnv);
			fclose($ressourceFichierEnv);

            // Initialisation de la connection à la base des traitements de l'export (avec des valeurs en dur, je ne peux pas faire autrement)
            if($environnementExecution == 'PROD')
            {
            	$this->conn_traitement = sqlsrv_connect("S8APP1", array("Database"=>"ELEC","UID"=>"sa", "PWD"=>"ykjb003340","CharacterSet" => "UTF-8"));
            }
            elseif($environnementExecution == 'PREPROD')
            {
            	$this->conn_traitement = sqlsrv_connect("S7DEV1\DEVTJ", array("Database"=>"ELEC","UID"=>"sa", "PWD"=>"ykjb003340","CharacterSet" => "UTF-8"));
            }
            elseif($environnementExecution == 'RECETTE')
            {
            	$this->conn_traitement = sqlsrv_connect("S7DEV1\DEVEA", array("Database"=>"ELEC","UID"=>"sa", "PWD"=>"ykjb003340","CharacterSet" => "UTF-8"));
            }
            elseif($environnementExecution == 'LOCAL')
            {
            	$this->conn_traitement = sqlsrv_connect("S7DEV1\DEVEA", array("Database"=>"ELEC","UID"=>"sa", "PWD"=>"ykjb003340","CharacterSet" => "UTF-8"));
            }
            
            // Récupérer toutes les informations de paramétrage, et les mettre dans les attributs de l'instance de la classe
            $this->recuperer_informations_de_parametrage();
        }
        catch(Exception $e)
        {
            $this->Stopper_traitement_decision("Erreur lors de la recuperation de données de paramétrage dans en base : ".$e->getMessage());
        }

	    // CREATION DES CONNECTIONS AUX BDD
	    try
        {
            // Initialisation des connections aux bases
            $this->conn_ged = sqlsrv_connect($this->serverGed,array("Database"=>"FD_C66DBCEA","UID"=>"sa", "PWD"=>$this->password));
            $this->conn_ged_utf8 = sqlsrv_connect($this->serverGed,array("Database"=>"FD_C66DBCEA", "UID"=>"sa", "PWD"=>$this->password, "CharacterSet" => "UTF-8")); //Rajoute par Edouard en decembre pour 2eme connexion à la GED (recuperation IMAGE)
            $this->conn_nouvelle_appli = sqlsrv_connect($this->serverProdName,array("Database"=>$this->serverProdDb,"UID"=>"sa", "PWD"=>$this->password,"CharacterSet" => "UTF-8"));
            $this->conn_ancien_appli = odbc_connect("Driver={SQL Server};Server=".self::SERVER_ANCIEN_NAME.";", "sa", self::PASSWORD_ANCIEN);

            // Paramétrages divers
            $this->date_debut_traitement = date('Ymd');
            $_SESSION['id_type_util'] = 2;

            // Analyse des réponses des BDD, et lever des erreurs (de type mdp invalide), si besoin
            if( $this->conn_traitement === false )
            {
                $this->Stopper_traitement_decision("Erreur d identifiant(s) lors de la connection à la base qui gère les logs et les paramétrages du traitement d export.");
            }
            if( $this->conn_ged === false )
            {
                $this->Stopper_traitement_decision("Erreur d identifiant(s) lors de la connection à la base de la GED.");
            }
            if( $this->conn_ged_utf8 === false )
            {
                $this->Stopper_traitement_decision("Erreur d identifiant(s) lors de la connection (gerant l UTF8) à la base de la GED.");
            }
            if( $this->conn_nouvelle_appli === false )
            {
                $this->Stopper_traitement_decision("Erreur d identifiant(s) lors de la connection à la base ELEC nouvelle appli.");
            }
            if  ($this->conn_ancien_appli === false)
            {
                $this->Stopper_traitement_decision("Erreur d identifiant(s) lors de la connection à la base ELEC ancienne appli.");
            }
        }
        catch(Exception $e)
        {
            $this->Stopper_traitement_decision("Erreur lors d une connection a l une des BDD GED ou ELEC ou TRAITEMENT). Voici l erreur interceptée : ".$e->getMessage());
        }
	}
	
	/*
        CETTE FONCTION EST UTILISEE POUR TRAITER EN MASSE L'EXPORT A PARTIR D'UN FICHIER CSV, MAIS PERSONNE NE S'EN EST SERVI DEPUIS LONGTEMPS
	*/
	public function Traitement_from_csv()
	{
		if($this->Initialiser_tous_fichier_DDRT())
		{
			$this->Upsert_log_traitement('process','');
			$this->document_param['id_lettre']="";
			$this->document_param['type_decision']="toutes";
			$this->document_param['annee']="";
			$this->document_param['DATE_SCAN_SQL']="";
			$this->document_param['DATE_SCAN_DEBUT_SQL']="";
			$this->document_param['DATE_SCAN_FIN_SQL']="";
			$this->document_param['type_process']="";
			$this->document_param['dossier']="";
			$this->document_param['chk_grief_financier']="";

			$row = 1;

			if (($handle = fopen(self::REPERTOIRE_DEPOT."/z_candidat_csv/"."candidats.csv", "r")) !== FALSE)
			{
				while (($data = fgetcsv($handle, 1000, ";")) !== FALSE)
				{
					if($row > 1)
					{
						$candidat = $data[0];
						// $annee = isset($data[1])?$data[1]:"";
						//$annee = "2011"; // // <<<<<< METTRE L'ANNEE EN DUR POUR L'ANCIENNE APPLICATION >>>>>>
						// $affaire2 = isset($data[2])?$data[2]:"";
						$this->document_param['id_candidat'] = $candidat;
						//$this->document_param['annee']=$annee;
						//$this->document_param['dossier']="";
						// $this->Process_decision_documents_ged(); // paramètres - commenter pour ne pas traiter les décisions.
						$this->Process_mutualises_documents_ged(); // requêtes, jugements... // paramètres
					}
					
					$row++;
				}
				
				fclose($handle);

				$this->Ecrire_metadonnes_xml();
				$this->Ecrire_metadonnes_csv();
				$this->Upsert_log_traitement('process','fin');
			}
		}
		else
		{
		    $this->Stopper_traitement_decision("Erreur d'initialisation des repertoires de DDRT");
		}
	}

	/*
	    CETTE FONCTION PERMET DE LANCER UN EXPORT DE MASSE avec ses nombreux filtres (cf. $this->document_param[....)
	 */
	public function Traitement_masse()
	{
	    //Initialisation du contenu du mail qui est envoyé à la fin
	    $messageMail = "";

        //Lancement du traitement d'export de masse
		if($this->Initialiser_tous_fichier_DDRT())
		{
			$this->Upsert_log_traitement('Export de masse des décisions','');
			$this->document_param['id_lettre']="";
			$this->document_param['type_decision']="";
			$this->document_param['annee']="2011";
			$this->document_param['DATE_SCAN_SQL']= "";
			$this->document_param['DATE_SCAN_DEBUT_SQL']="";
			$this->document_param['DATE_SCAN_FIN_SQL']="";
			$this->document_param['type_process']="masse";
			$this->document_param['dossier']="";
			$this->document_param['chk_grief_financier']="";
			$this->document_param['id_candidat'] = "";
			
			if ($this->document_param['annee'] == "" AND $this->document_param['DATE_SCAN_SQL'] != "")
			{
				$this->document_param['annee'] = substr($this->document_param['DATE_SCAN_SQL'],0,4);
			}
			
			$this->Process_decision_documents_ged(); //Export des DECISIONS INITIALES, DECISIONS DE RECOURS GRACIEUX, DECISIONS RECTIFICATIVES (= MODIFICATIVES)
            $this->comparer_nbre_decisions_lues_et_nbre_decisions_crees(); //Voir écart entre décisions lues et décisions créés
			$this->Process_mutualises_documents_ged(); //Export des JUGEMENTS, MEMOIRES, REQUETES
			$this->Ecrire_metadonnes_xml(); //CREATION D'UN FICHIER XML PAR TYPE DE DECISION
			$this->Ecrire_metadonnes_csv(); //CREATION D'UN FICHIER CSV PAR TYPE DE DECISION

            // Copier tous les fichiers (sauf ceux de l'anonymisation) dans le repertoire d'import de SP au bon endroit pour chaque type de document
            if($this->environnement !== 'ENVIRONNEMNT_INCONNU') // Si on n'est pas en local, on va faire la copie
            {
                $this->copier_fichiers_exportes_vers_dossier_import("decision");
                $this->copier_fichiers_exportes_vers_dossier_import("recours");
                $this->copier_fichiers_exportes_vers_dossier_import("modificative");
                $this->copier_fichiers_exportes_vers_dossier_import("jugement");
                $this->copier_fichiers_exportes_vers_dossier_import("memoire");
                $this->copier_fichiers_exportes_vers_dossier_import("requete");
            }

			$this->Upsert_log_traitement('Export de masse des décisions','fin');
            $this->Couper_connections_aux_bases();

            //Création et return du corps du mail
			$messageMail = $this->get_message_mail();
			return $messageMail;
		}
		else
		{
			$this->Stopper_traitement_decision("Erreur d'initialisation des repertoires de DDRT");
		}
	}

    /*
        CETTE FONCTION PERMET DE LANCER L'EXPORT QUOTIDIEN
     */
    public function Traitement_quotidien()
    {
        ///// Récupération de la dernière date de traitement en SUCCES /////

        $sql = "
                select top 1 LEFT(CONVERT(VARCHAR, date_debut_traitement, 120), 10)
                from [ELEC].[dbo].[z_depot_traitement]
                where etat_traitement = 'OK'
                order by date_debut_traitement desc                
                ";

        $req = sqlsrv_query($this->conn_traitement, $sql);

        if($req === false)
        {
            $this->Stopper_traitement_decision("Erreur lors d une requete sur la base ELEC : ".sqlsrv_errors());
        }

        // Recuperation et formatage de la date retournée par la requête
        $arrayResultatRequete = sqlsrv_fetch_array($req, SQLSRV_FETCH_NUMERIC);
        $dateDernierExportOk = $arrayResultatRequete[0]; //Format YYYY-MMM-DD (mais en String)
        $dateDernierExportOkFormatee = str_replace('-','', $dateDernierExportOk); //Format YYYYMMMDD (mais en String)

        ///// Determination des journées à exporter /////

        // Recuperation de la date d'hier
        $dateHier = new DateTime('yesterday');
        $dateHierFormatee = $dateHier->format('Ymd'); //Format YYYY-MMM-DD

        // Recuperation de la date du jour
        $dateDuJour = new DateTime();
        $dateDuJourFormatee = $dateDuJour->format('Ymd'); //Format YYYYMMMDD

        // Détermination des journées à exporter

        $dateSurLaquelleExporter = "";
        $dateBorneInferieure = "";
        $dateBorneSuperieure = "";

        if($dateDernierExportOkFormatee == $dateHierFormatee) //Cas classique => on exporte le jour J
        {
            $dateSurLaquelleExporter = $dateDuJourFormatee;
        }
        elseif($dateDernierExportOkFormatee == $dateDuJourFormatee) //Cas complètment anormal, le traitement a déjà tourné en succès ce jour => on arrête le traitement
        {
            die("Le traitement a déjà tourné en succès ce jour. Arrêt du traitement.");
        }
        else //Cas anormal, au moins une journée a échouée => on exporte tout depuis le dernier export en succès
        {
            $dateDernierExportOkFormateeFormatDate = new DateTime($dateDernierExportOkFormatee);
            $dateBorneInferieure = $dateDernierExportOkFormateeFormatDate->modify('+1 day')->format('Ymd');
            $dateBorneSuperieure = $dateDuJourFormatee;
        }

        ///// Initialisation du contenu du mail qui est envoyé à la fin /////
        $messageMail = "";

        ///// Lancement du traitement d'export quotidien /////
        if($this->Initialiser_tous_fichier_DDRT())
        {
            // Notifier le debut du traitement d'export quotidien en base
            $this->Upsert_log_traitement('Export quotidien des décisions','');

            //Initialiser les attributs de l'instance de la classe
            $this->document_param['id_lettre']="";
            $this->document_param['type_decision']="";
            $this->document_param['annee']="";
            $this->document_param['DATE_SCAN_SQL']= "";
            $this->document_param['DATE_SCAN_DEBUT_SQL']="";
            $this->document_param['DATE_SCAN_FIN_SQL']="";
            $this->document_param['type_process']="quotidien";
            $this->document_param['dossier']="";
            $this->document_param['chk_grief_financier']="";
            $this->document_param['id_candidat'] = "";

            // Filtrage sur la ou les journées à exporter
            if(!empty($dateSurLaquelleExporter))
            {
                // Filtrage sur la date du jour seulement
                $this->document_param['DATE_SCAN_SQL'] = $dateSurLaquelleExporter;

                // Renseigner l'année
                //$this->document_param['annee'] = substr($this->document_param['DATE_SCAN_SQL'],0,4);
                //$this->document_param['annee'] = '2017';
            }
            else
            {
                // Filtrage sur une période de plusieurs jours
                $this->document_param['DATE_SCAN_DEBUT_SQL'] = $dateBorneInferieure;
                $this->document_param['DATE_SCAN_FIN_SQL'] = $dateBorneSuperieure;

                // Renseigner l'année
                //$this->document_param['annee'] = substr($this->document_param['DATE_SCAN_DEBUT_SQL'],0,4);
                //$this->document_param['annee'] = '2017';
            }

            // Export
            $this->Process_decision_documents_ged(); //Export des DECISIONS INITIALES, DECISIONS DE RECOURS GRACIEUX, DECISIONS RECTIFICATIVES (ou MODIFICATIVES)
            $this->comparer_nbre_decisions_lues_et_nbre_decisions_crees(); //Voir écart entre décisions lues et décisions créés
            $this->Process_mutualises_documents_ged(); //Export des JUGEMENTS, MEMOIRES, REQUETES
            $this->Ecrire_metadonnes_xml(); //CREATION D'UN FICHIER XML PAR TYPE DE DECISION
            $this->Ecrire_metadonnes_csv(); //CREATION D'UN FICHIER CSV PAR TYPE DE DECISION

            // Copier tous les fichiers (sauf ceux de l'anonymisation) dans le repertoire d'import de SP au bon endroit pour chaque type de document
            if($this->environnement !== 'ENVIRONNEMNT_INCONNU') // Si on n'est pas en local, on va faire la copie
            {
                $this->copier_fichiers_exportes_vers_dossier_import("decision");
                $this->copier_fichiers_exportes_vers_dossier_import("recours");
                $this->copier_fichiers_exportes_vers_dossier_import("modificative");
                $this->copier_fichiers_exportes_vers_dossier_import("jugement");
                $this->copier_fichiers_exportes_vers_dossier_import("memoire");
                $this->copier_fichiers_exportes_vers_dossier_import("requete");
            }

            // Notifier la fin du traitement d'export quotidien en base
            $this->Upsert_log_traitement('Export quotidien des décisions','fin');

            // Couper les connections aux BDD
            $this->Couper_connections_aux_bases();

            // Création et return du corps du mail
            $messageMail = $this->get_message_mail();
            return $messageMail;
        }
        else
        {
            $this->Stopper_traitement_decision("Erreur d'initialisation des repertoires de DDRT");
        }
    }
    
    //COMPARAISON DECISIONS LUES ET DECISIONS CREES EN PDF
    public function comparer_nbre_decisions_lues_et_nbre_decisions_crees()
    {
        //Calculer l'écart
        $decisionsLues = $this->COMPTEUR_TOTAL_GED_LUS; //Au moment où cette focntion est appelée, on n'a lu que des decisions (pas de PM)
        $decisionsCreesEnPdf = $this->COMPTEUR_TOTAL_DECISION_CREE + $this->COMPTEUR_TOTAL_RECOURS_CREE + $this->COMPTEUR_TOTAL_MODIFICATIVE_CREE;
        $écartDécisions = $decisionsLues - $decisionsCreesEnPdf;
        
        //Mettre l'écart dans les attributs de l'instance
        $this->COMPTEUR_ECART_DECISIONS = $écartDécisions;
        
        if ($écartDécisions != '0') //Si écart, on loggue une erreur
        {  
            $pourMsgLoggueEnBase[0] = $écartDécisions;
            $this->Ecrire_log_traitement_message("fonctionnel","18", $pourMsgLoggueEnBase);
        }
    }

	//CREATION ET RETURN DU CONTENU DU MAIL
	public function get_message_mail()
	{
            $message = '
                    <br><br><br>
                    <table border="solid 1px black">
		                <tr>
		                    <th bgcolor="#BDBDBD">Quantité</th>
		                    <th bgcolor="#BDBDBD">Type de document exporté</th>
		                 </tr> 
		                 <tr>
		                    <td bgcolor="#E3F2FD" align="center">'.$this->COMPTEUR_TOTAL_DECISION_CREE.'</td>
		                    <td bgcolor="#E3F2FD" align="center">Decision(s) initiale(s)</td>  
		                </tr>             
		                <tr>
		                    <td bgcolor="#E3F2FD" align="center">'.$this->COMPTEUR_TOTAL_RECOURS_CREE.'</td>
		                    <td bgcolor="#E3F2FD" align="center">Decision(s) recours</td>  
		                </tr> 
		               <tr>
		                    <td bgcolor="#E3F2FD" align="center">'.$this->COMPTEUR_TOTAL_MODIFICATIVE_CREE.'</td>
		                    <td bgcolor="#E3F2FD" align="center">Decision(s) modificative(s)</td>  
		                </tr>
		                <tr>
                    		<td bgcolor="#E3F2FD" colspan="2">L\' écart entre les décisions lues en GED et les décisions crées est de '.$this->COMPTEUR_ECART_DECISIONS.' décision(s)</td>
                		</tr>
		               <tr>
		                    <td bgcolor="#E8EAF6" align="center">'.$this->COMPTEUR_TOTAL_JUGEMENT_CREE.'</td>
		                    <td bgcolor="#E8EAF6" align="center">Jugement(s)</td>  
		                </tr> 
		               <tr>
		                    <td bgcolor="#E8EAF6" align="center">'.$this->COMPTEUR_TOTAL_MEMOIRE_CREE.'</td>
		                    <td bgcolor="#E8EAF6" align="center">Mémoire(s)</td>  
		                </tr> 
		               <tr>
		                    <td bgcolor="#E8EAF6" align="center">'.$this->COMPTEUR_TOTAL_REQUETE_CREE.'</td>
		                    <td bgcolor="#E8EAF6" align="center">Requête(s)</td>  
		                </tr> 
        			</table>';

            return $message;
	}

    public function recuperer_informations_de_parametrage()
    {
        $this->repertoireDepot = $this->recuperer_une_information_de_parametrage("REPERTOIRE_DEPOT");
        $this->repertoireImportSp = $this->recuperer_une_information_de_parametrage("REPERTOIRE_IMPORT_SP");
        $this->serverGed = $this->recuperer_une_information_de_parametrage("SERVER_GED");
        $this->password = $this->recuperer_une_information_de_parametrage("PASSWORD");
        $this->serverTraitement = $this->recuperer_une_information_de_parametrage("SERVER_TRAITEMENT");
        $this->serverProdName = $this->recuperer_une_information_de_parametrage("SERVER_PROD_NAME");
        $this->serverTraitementDb = $this->recuperer_une_information_de_parametrage("SERVER_TRAITEMENT_DB");
        $this->serverProdDb = $this->recuperer_une_information_de_parametrage("SERVER_PROD_DB");
        $this->environnement = $this->recuperer_une_information_de_parametrage("ENVIRONNEMENT");
    }

    public function recuperer_une_information_de_parametrage($key)
    {
        $sql = "
                SELECT value
                FROM [ELEC].[dbo].[z_depot_parametrages]
                WHERE [key] = '".$key."'
                ";

        $req = sqlsrv_query($this->conn_traitement, $sql, array(), array("Scrollable"=>"buffered"));

        if($req === false)
        {
            $this->Stopper_traitement_decision("Erreur lors de la recuperation d une information de parametrage en base : ".sqlsrv_errors());
        }

        $nb = sqlsrv_num_rows($req);

        if ($nb == 0 or $nb > 1 ) //Anormal
        {
            $this->Stopper_traitement_decision("La requete pour recuperer la key ".$key." a ramené soit aucune soit plusieurs reponses. Merci de voir le probleme en base.");
        }
        else //Ok normal
        {
            $arrayResultatRequete = sqlsrv_fetch_array($req, SQLSRV_FETCH_NUMERIC);
            $value = $arrayResultatRequete[0];
        }

        return $value;
    }

    public function Couper_connections_aux_bases()
    {
        sqlsrv_close($this->conn_traitement);
        sqlsrv_close($this->conn_ged);
        sqlsrv_close($this->conn_ged_utf8);
        sqlsrv_close($this->conn_nouvelle_appli);
        odbc_close($this->conn_ancien_appli);
    }

    public function copier_fichiers_exportes_vers_dossier_import($typeDocument)
    {
        $dossierSource = "";
        $dossierCible = "";

        // Définir les dossiers source et cible
        switch($typeDocument)
        {
            case "decision" :
                $dossierSource = $this->repertoireDepot."/".self::REPERTOIRE_DECISION;
                $dossierCible = $this->repertoireImportSp."/".self::REPERTOIRE_DECISION;
                break;
            case "recours" :
                $dossierSource = $this->repertoireDepot."/".self::REPERTOIRE_RECOURS;
                $dossierCible = $this->repertoireImportSp."/".self::REPERTOIRE_RECOURS;
                break;
            case "modificative" :
                $dossierSource = $this->repertoireDepot."/".self::REPERTOIRE_MODIFICATIVE;
                $dossierCible = $this->repertoireImportSp."/".self::REPERTOIRE_MODIFICATIVE;
                break;
            case "jugement" :
                $dossierSource = $this->repertoireDepot."/".self::REPERTOIRE_JUGEMENT;
                $dossierCible = $this->repertoireImportSp."/".self::REPERTOIRE_JUGEMENT;
                break;
            case "memoire" :
                $dossierSource = $this->repertoireDepot."/".self::REPERTOIRE_MEMOIRE;
                $dossierCible = $this->repertoireImportSp."/".self::REPERTOIRE_MEMOIRE;
                break;
            case "requete" :
                $dossierSource = $this->repertoireDepot."/".self::REPERTOIRE_REQUETE;
                $dossierCible = $this->repertoireImportSp."/".self::REPERTOIRE_REQUETE;
                break;
        }

        //Copier tous les fichiers du dossier source dans le dossier cible
        try
        {
            $searchPattern = $dossierSource."/*.*";
            $tableauNomsCompletsFichiersSources = glob($searchPattern);
            foreach ($tableauNomsCompletsFichiersSources as $fichierSourceNomComplet)
            {
                $fichierCibleNomComplet = str_replace($dossierSource, $dossierCible, $fichierSourceNomComplet);
                copy($fichierSourceNomComplet, $fichierCibleNomComplet);
            }
        }
        catch(Exception $e)
        {
            $this->Stopper_traitement_decision("Erreur inattendue pendant la copie (vers le dossier d import) des fichiers du type de document ".$typeDocument.". Erreur catchée : ".$e->getMessage());
        }
    }

	//	Edouard : Fonction non utilisée pour l'instant car l'appel est commentée dans le fichier 'X_moulinette_outil_consultation_sharepoint_batch_quotidien.php'
	public function Traitements_mutualises()
	{
		if($this->Controller_pre_requi("mutualises"))
		{
			// $this->initialiser_METADONNEE_CSV();
		    $this->Commencer_traitement_mutualises();
			// $this->Ecrire_metadonnes_csv();
			$this->Ecrire_metadonnes_xml();
		}
    }
	
	/*
	Traitements_decisions : traitements des decisions initial et recours (gracieux,modificative)
	Controller_pre_requi : vérification des près requi pour le lancement (document parametre, date d'excution ..)
	Edouard : Fonction non utilisée pour l'instant car l'appel est commentée dans le fichier 'X_moulinette_outil_consultation_sharepoint_batch_quotidien.php'
	*/
    public function Traitements_decisions()
	{
		if($this->Controller_pre_requi("decisions"))
		{
	
			// $this->initialiser_METADONNEE_CSV();
		    $this->Commencer_traitement_decision();
			// $this->Ecrire_metadonnes_csv();
			$this->Ecrire_metadonnes_xml();
		}
    }
	
	/*
	Initialiser_tous_fichier_DDRT : Suppression de tous les fichier dans le répertoire de traitement
	Upsert_log_traitement : Enregistrement de log de lancement 
	Process_decision_documents_ged : Chercher les documents decision dans la ged 
	*/
	public function Commencer_traitement_decision()
	{
		if($this->Initialiser_tous_fichier_DDRT())
		{
			$this->Upsert_log_traitement('decisions','');
			$this->Process_decision_documents_ged();
		}
		else
		{
			$this->Stopper_traitement_decision("Erreur d'initialisation du repertoire DDRT"); 
		}
	}
	
	public function Commencer_traitement_mutualises()
	{
		if($this->Initialiser_tous_fichier_DDRT())
		{
			$this->Upsert_log_traitement('mutualisee','');
			$this->Process_mutualises_documents_ged();    
		}
		else
		{
			$this->Stopper_traitement_decision("Erreur d'initialisation du repertoire DDRT"); 
		}
	}
	
	public function Process_mutualises_documents_ged()
	{
		//Initialisation d'un tableau qui va contenir la requete SQL
		$w = array();

        if(!empty($this->document_param['id_candidat']))
		{
			$w[] = "FD_09A9C5FE = '".$this->document_param['id_candidat']."'";
		}

        if(!empty($this->document_param['dossier']))
		{
			$w[]= "FD_7EDEFFC4 like '%".$this->document_param['dossier']."%'";
		}

        if($this->document_param['type_process'] == 'masse')
        {
	       	if(!empty($this->document_param['annee']))
	        {
	            $w[]= "substring(CONVERT(VARCHAR,CAST(FD_Documents.CreatedOn AS DATETIME),112), 1, 4) = '".$this->document_param['annee']."'";
	        }
	        else
	        {
	            $this->Ecrire_log_traitement_message("warning","1","Il faut renseigner l'annee lors de l'execution du traitement d'export de masse");
	            die();
	        }
        }
        elseif($this->document_param['type_process'] == 'quotidien')
        {
        	if ($this->document_param['DATE_SCAN_SQL'] != "")
			{		
				$w[]= "FD_34CFCD24 = '".$this->document_param['DATE_SCAN_SQL']."'";	
			}
			else
			{
				if ($this->document_param['DATE_SCAN_DEBUT_SQL'] != "")
				{
					$w[] = "FD_34CFCD24 >= '".$this->document_param['DATE_SCAN_DEBUT_SQL']."'";
				}

				if ($this->document_param['DATE_SCAN_FIN_SQL'] != "")
				{
					$w[] = "FD_34CFCD24 <= '".$this->document_param['DATE_SCAN_FIN_SQL']."'";
				}
			}
        }

		if ($this->document_param['id_lettre'] > 0)
		{
			$w[]= "FD_3A5E7E76 = '".$this->document_param['id_lettre']."'";
		}
		
		if($this->document_param['type_decision']=="toutes" or $this->document_param['type_decision']=="")
		{
			$w[]= "
				((((FD_A45B18CB LIKE '".utf8_decode('%mémoire%')."' OR 
				FD_A45B18CB LIKE '".utf8_decode('%memoire%')."') OR (
				 FD_E8554E3E = '".utf8_decode('Mémoire en appel')."' OR 
				 FD_E8554E3E = '".utf8_decode('Mémoire en défense')."' OR
				 FD_E8554E3E = '".utf8_decode('Mémoire en défense CCFP')."'))
			AND FD_87C3BE08 is not null) OR
			FD_E8554E3E = '".utf8_decode('Recours gracieux')."' OR
			FD_E8554E3E = '".utf8_decode('Recours contentieux / Requête')."' OR
			FD_E8554E3E = '".utf8_decode('Jugements/avis/décisions')."')";
		}
		
		if($this->document_param['type_decision']=="memoire")
		{
			$w[]= "
                        (
                          (FD_A45B18CB LIKE '".utf8_decode('%mémoire%')."' OR FD_A45B18CB LIKE '".utf8_decode('%memoire%')."')
                            OR
                          (FD_E8554E3E = '".utf8_decode('Mémoire en appel')."' OR FD_E8554E3E = '".utf8_decode('Mémoire en défense')."' OR FD_E8554E3E = '".utf8_decode('Mémoire en défense CCFP')."')
                        )
                        AND FD_87C3BE08 is not NULL 
                    ";
		}
		
		if($this->document_param['type_decision']=="requete")
		{
			$w[]= "(FD_E8554E3E = '".utf8_decode('Recours gracieux')."' OR FD_E8554E3E = '".utf8_decode('Recours contentieux / Requête')."')"; 
		}
		
		if($this->document_param['type_decision']=="jugement")
		{
			$w[]= "FD_E8554E3E like '".utf8_decode('Jugements/avis/décisions')."'";
		}
		
		if (count($w) > 1)
		{
			$WHERE = implode (" AND ", $w); 
		}
		else
		{
			$WHERE = $w[0];
		}

        $sql = "
                    select  GUID, ActRevision, Deleted, FD_E7787B05 AS ANNEE, FD_09A9C5FE AS NUMCAND, LocationSubID,
                            FD_3A5E7E76 AS NOLETTRE,FD_CD52E931 AS CHRONO,
                            FD_7EDEFFC4 AS DOSSIER,FD_E8554E3E AS NATUREDOCUMENT,
                            FD_502503A7 AS ADMINISTRATION,
                            FD_87C3BE08 AS CHRONODEPART,
                            CONVERT(VARCHAR,CAST(FD_34CFCD24 AS DATETIME),110) AS DATE_CCFP,
                            CONVERT(VARCHAR,CAST(FD_34CFCD24 AS DATETIME),110) AS DATE_CCFPAFF,
                            CONVERT(VARCHAR,CAST(FD_7378028E AS DATETIME),110) AS DATE_DEPART_RETOUR,
                            CONVERT(VARCHAR,CAST(FD_Documents.CreatedOn AS DATETIME),112) AS DATE_CREATEDON,
                            CONVERT(VARCHAR,CAST(FD_Documents.CreatedOn AS DATETIME),110) AS DATE_CREATEDONAFF,
                            FD_C6004355 AS TYPE_LETTRE, CAST(FD_CC000D17 AS TEXT) AS NOM_EXPEDITEUR, PageNo AS PAGE, StorageRev AS DOSSIER_IMAGE, SourceFileName AS IMAGE, FD_A45B18CB AS OBJET_LIBELLE
                    from dbo.FD_Documents
                    LEFT JOIN dbo.FD_Images ON (GUID = DocGUID)
                    where " . $WHERE . " AND  ActRevision = RevNo and FD_5188A29D = 'Elections' and Deleted <> '1' and PageNo='1'
                    ORDER BY GUID, FD_3A5E7E76, FD_34CFCD24, PageNo desc
                    ";

        //Execution requete
		$req = sqlsrv_query($this->conn_ged,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base GED dans la fonction Process_mutualises_documents_ged");
		}

		$nb = sqlsrv_num_rows($req);
		$NUMCAND = "";
		$NOLETTRE = "";

		if ($nb > 0)
		{
            $tableauDossiersLus = []; //Tableau pour gestion des doublons de dossiers

		    while ($res = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
                // Retirer les apostrophes des numéros de dossier
                if (strstr($res['DOSSIER'], "'") != FALSE)
                {
                    $res['DOSSIER'] = str_replace("'", "", $res['DOSSIER']);
                }

                // Retirer les ° des numéros de dossier
                if (strstr(utf8_decode($res['DOSSIER']), "?") != FALSE)
                {
                    $res['DOSSIER'] = str_replace("?", "", utf8_decode($res['DOSSIER']));
                }

                // ------------- Gestion des doublons de dossiers : rajouté par Edouard sur demande de Dominique -------------
                //Concaténation du dossier et de "nature doc" et de "objet/libelle" avec des @ entre ces 3 valeurs
                $dossierEnCoursDeTraitement = trim($res['DOSSIER']) . '@' . trim($res['NATUREDOCUMENT']) . '@' . trim($res['OBJET_LIBELLE']);
                //Faire recherche dans tableau des dossiers lus (cf. variable $tableauDossiersLus)
                $dossierTrouve = array_search($dossierEnCoursDeTraitement, $tableauDossiersLus);

                //Si dossier pas trouvé, on traite le dossier (sinon doublon donc on ignore)
                if ($dossierTrouve == false)
                {
                    $annee_dossier_valide = 1;

                    if ($this->document_param['annee'] == 2012 AND $annee_dossier_valide === 0)
                    {
                        $T_requetes_2012 = array(4616, 4630, 4650, 4591, 4636, 4645, 4617, 4600, 4592, 4637, 4594, 4610, 4647, 4642, 4577, 4576, 4575, 4567, 4565, 4574, 4626, 4597, 4624, 4580, 4554, 4633, 4604, 4638, 4593, 4596, 4639, 4598, 4628, 4578, 4620, 4623, 4590, 4599, 4611, 4612, 4589, 4602, 4605, 4646, 4563, 4588, 4601, 4603, 4568, 4551, 4627, 4587, 4619, 4618, 4558); // liste des codes des requêtes spécifiques : 2012
                        $a_comparer_1 = $res['DOSSIER'];
                        $a_comparer_2 = substr($res['DOSSIER'], -4); // pour les codes de requêtes sous la forme '2012-xxxx' -> récupère la série des 4 derniers chiffres à comparer avec le contenu de l'array précédent.

                        if (in_array($a_comparer_1, $T_requetes_2012) OR in_array($a_comparer_2, $T_requetes_2012))
                        {
                            $annee_dossier_valide = 1;
                        }

                        if (substr($res['DOSSIER'], 0, 2) == 13 or strpos($res['DOSSIER'], '2013') === true)
                        {
                            $annee_dossier_valide = 1;
                        }
                    }

                    // Il faut remettre le numcand dans this->docparam à vide avant de lancer l'un des process dans les "case" en dessous, sinon on va avoir le même candidat tout le temps
                    $this->document_param['NUMCAND'] = "";
                    $this->document_param['id_candidat'] = "";

                    if ($annee_dossier_valide === 1) //Feu vert pour aller plus loin
                    {
                        switch ($res['NATUREDOCUMENT'])
                        {
                            case utf8_decode("Recours contentieux / Requête") :
                            case utf8_decode("Recours gracieux") :
                                $this->Process_mutualises($res, "requete");
                                $this->COMPTEUR_TOTAL_GED_LUS++;
                                break;
                            case utf8_decode("Jugements/avis/décisions"):
                                $this->Process_mutualises($res, "jugement");
                                $this->COMPTEUR_TOTAL_GED_LUS++;
                                break;
                            default : //default pour les mémoires
                                $this->Process_mutualises($res, "memoire");
                                $this->COMPTEUR_TOTAL_GED_LUS++;
                                break;
                        }

                        // ------------- Gestion des doublons de dossiers : rajouté par Edouard sur demande de Dominique -------------
                        //Ajout de dossier/natureDoc/ObjetLibelle à la liste des dossiers traités
                        array_push($tableauDossiersLus, $dossierEnCoursDeTraitement);
                    }
                }//Fin du traitement du nouveau dossier
                else
                {
                    //Logguer que c'est un doublon qui ne sera pas traité
                    $pourMsgLoggueEnBase[0] = $res['GUID'];
                    $this->Ecrire_log_traitement_message("fonctionnel","15", $pourMsgLoggueEnBase);
                }
			} //Fin du while
		} //Fin du if ($nb > 0)
	} //Fin de Process_mutualises_documents_ged()

	public function Get_num_candidat_mutualises($document_ged, $type_document)
	{
		$id_candidat = "";
		$DOSSIER = $document_ged["DOSSIER"];
		
		$ANNEE_demandee = "";
		
		if ($this->document_param['annee'] != "")
		{
			$ANNEE_demandee = $this->document_param['annee'];
		}
		
		if($this->document_param["id_candidat"] != "")
		{
			$id_candidat = $this->document_param["id_candidat"];
			return $id_candidat;
		}
		else
		{
			//On cherche le NUMCAND dans la nouvelle appli ELEC
		    $id_candidat = $this->Get_num_candidat_nouvelle_appli($document_ged,$type_document);

		    //Si on l'a trouvé, on le retourne
			if($id_candidat!="")
			{
				$this->document_param["id_candidat"] = $id_candidat;
				return $id_candidat;
			}
			else //Sinon on va chercher le NUMCAND dans l'ancienne appli ELEC
			{
				if ($this->document_param['annee'] != "")
				{
					if ($this->document_param['annee'] < 2016)
                    {
                        $id_candidat = $this->Get_num_candidat_ancien_appli_annee($document_ged,$type_document,$ANNEE_demandee);
                        return $id_candidat;
                    }
				} 
				else
				{
					$id_candidat = $this->Get_num_candidat_ancien_appli_annee("2014");
					
					if($id_candidat != "")
					{
						$this->document_param["id_candidat"] = $id_candidat;
						$this->document_param["annee"]= "2014";
						return $id_candidat;
					}
					
					$id_candidat = $this->Get_num_candidat_ancien_appli_annee("2013");
					
					if($id_candidat != "")
					{
						$this->document_param["id_candidat"] = $id_candidat;
						$this->document_param["annee"]= "2013";
						return $id_candidat;
					}
			
					$id_candidat = $this->Get_num_candidat_ancien_appli_annee("2012");
					
					if($id_candidat != "")
					{
						$this->document_param["id_candidat"] = $id_candidat;
						$this->document_param["annee"]= "2012";
						return $id_candidat;
					}
					
					$id_candidat = $this->Get_num_candidat_ancien_appli_annee("2011");
					
					if($id_candidat != "")
					{
						$this->document_param["id_candidat"] = $id_candidat;
						$this->document_param["annee"]= "2011";
						return $id_candidat;
					}
					
					$id_candidat = $this->Get_num_candidat_ancien_appli_annee("2010");
					
					if($id_candidat != "")
					{
						$this->document_param["id_candidat"] = $id_candidat;
						$this->document_param["annee"]= "2010";
						return $id_candidat;
					}
				}
			}
		}

		return $id_candidat; //Si on est là le NUMCAND sera vide car non trouvé
	}
	
	public function Get_num_candidat_nouvelle_appli($document_ged, $type_document)
	{
		$id_candidat = "";
		$DOSSIER = $document_ged["DOSSIER"];
		$sql = "SELECT REQ.id_defendeur FROM dbo.requete_ctx as REQ
				LEFT JOIN requete_ctx_grief as GRIEF ON (REQ.id_requete = GRIEF.id_requete)
				where REQ.ref_ctx = '".$DOSSIER."' AND GRIEF.chk_grief_financier = 1;";

		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_num_candidat_nouvelle_appli");
		}
		
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while ($res = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				$id_candidat = $res["id_defendeur"];
			}
		}
		
		return $id_candidat;
	}
	
	public function Get_num_candidat_ancien_appli_annee($document_ged,$type_document,$annee)
	{

		$id_candidat = "";
		$where_plus = "";
		$or_plus = "";
		
		$DOSSIER = $document_ged["DOSSIER"];

		$sql = "select REQ.CandidatDefendeur from BD_ELEC_".$annee.".dbo.Table_Requete as REQ
				where (REQ.CodeRequete='".$DOSSIER."'".$or_plus.") 
				".$where_plus.";";
		
		$req = odbc_exec($this->conn_ancien_appli, $sql);
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC ancienne version dans la fonction Get_num_candidat_ancien_appli_annee");
		}
		
		while(odbc_fetch_row($req))
		{
			$row = [];

			for($i=1; $i <= odbc_num_fields($req); $i++)
			{
				$fkey=odbc_field_name($req,$i);
				$value = utf8_encode(odbc_result($req,$i));
				$row[$fkey] = $value;
				if($fkey == "CandidatDefendeur")
				{
					$id_candidat = $value;
					break;
				}
			}
		}
		
		return $id_candidat;
	}
	
	public function Process_mutualises($document_ged,$type_document)
	{
		//Recherche dans ELEC avec le numrero de DOSSIER, Si trouvé on ramène l'id candidat
	    $id_candidat_defendeur = $this->Get_num_candidat_mutualises($document_ged,$type_document);
		
		if($id_candidat_defendeur != "") //Si le numéro de candidat a été trouvé par la fonction Get_num_candidat_mutualises()
		{
			$this->document_param["id_candidat"] = $id_candidat_defendeur;
			$document_ged['NUMCAND'] = $this->document_param["id_candidat"]; //On considère que le NUMCAND est trouvé ! //Cette ligne va servir pour le if à la fin de cette focntion

			if ($document_ged['ANNEE'] == "")
			{
				$document_ged['ANNEE'] = $this->document_param["annee"]; 
			}
		}
		else
		{
			if ($type_document == "memoire" && $this->document_param["annee"] >= '2015')
            // Si le type de document est un mémoire et si on est sur la nouvelle application ELEC, on va tenter une 2eme recherche avec le numéro du candidat. Je pars du principe que l'année est forcément dans $this->document_param["annee"]
            {
                //Definir la limite qu'il faut dépasser pour chercher le NUMNCAND dans la GED (limite trouvée dans S8APP1 > [ELEC].[dbo].[scrutin])
                $limiteNumCandDansTableScrutin = '';

                if ($this->document_param["annee"] == '2015')
                {
                    $limiteNumCandDansTableScrutin = '201502161';
                }
                elseif ($this->document_param["annee"] == '2016')
                {
                    $limiteNumCandDansTableScrutin = '201600030';
                }
                elseif ($this->document_param["annee"] == '2017')
                {
                    $limiteNumCandDansTableScrutin = '201700720';
                }

                //Chercher le NUMCAND dans la GED si NUMCAND est bien présent dans $document_ged ET si NUMCAND strictement supérieur à la "limite"
                if (isset($document_ged['NUMCAND']) && $document_ged['NUMCAND'] > $limiteNumCandDansTableScrutin)
                {
                    //Recherche du NUMCAND dans la GED
                    $sql=
                        "
                        select FD_09A9C5FE
                        from dbo.FD_Documents 
                        where FD_09A9C5FE = ".$document_ged['NUMCAND']
                        ;

                    $req = sqlsrv_query($this->conn_ged, $sql, array(), array("Scrollable"=>"buffered"));

                    if ($req === false)
                    {
                        $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base GED dans la fonction Process_mutualises");
                    }

                    $nb = sqlsrv_num_rows($req);

                    //Gestion du résultat de la requête
                    if ($nb > 0) //Si le NUMCAND est présent en GED
                    {
                        //On ne fait rien, et on rentrera dans le processus mutualisé en dessous
                    }
                    else
                    {
                        $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                        $this->Ecrire_log_traitement_message("fonctionnel","16", $pourMsgLoggueEnBase);
                        $document_ged['NUMCAND'] = ""; //On considère que le NUMCAND est introuvable //Cette ligne va servir pour le if suivant
                    }
                }
                else
                {
                    $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                    $this->Ecrire_log_traitement_message("fonctionnel","14", $pourMsgLoggueEnBase);
                    $document_ged['NUMCAND'] = ""; //On considère que le NUMCAND est introuvable //Cette ligne va servir pour le if suivant
                }
            }
            else
            {
                $pourMsgLoggueEnBase[0] = $type_document;
                $pourMsgLoggueEnBase[1] = $document_ged["DOSSIER"];
                $this->Ecrire_log_traitement_message("fonctionnel","3", $pourMsgLoggueEnBase);
                $document_ged['NUMCAND'] = ""; //On considère que le NUMCAND est introuvable //Cette ligne va servir pour le if suivant
            }
		}
		
		if($document_ged['NUMCAND'] != "") //Si on rentre dans cette condition, on peut lancer le process mutualisé
		{
			if(strlen($document_ged['NUMCAND']) < 9)
			{
				$this->Process_mutualises_ancien_appli($document_ged,$type_document);	
			} 
			elseif(strlen($document_ged['NUMCAND']) == 9)
			{
				$this->Process_mutualises_nouvelle_appli($document_ged,$type_document);
			}			
		}
	}

	public function Process_mutualises_ancien_appli($document_ged,$type_document)
	{
		$DETAILS = $this->get_DETAILS_ancien_application($document_ged, $type_document);
		
		if($DETAILS !=null )
		{
			$metadonnee = $this->Metadonnes_ancien_application($document_ged,$DETAILS,$type_document);
            $path_orginal = $this->Regles_nomage_fichier(true,$type_document,$metadonnee);

            //Creer le PDF IMAGE
            $document_existe = $this->Convertir_document_ged_image_to_pdf($document_ged,$type_document,$metadonnee,$path_orginal);

            if($document_existe)
            {
                $metadonnee["Path"] = $path_orginal;
                $metadonnee["N_candidat"] = $metadonnee["Annee_election"]."00000" + $DETAILS["NumCand"]; //Le  NUMNCAND doit être sur 9 digits même pour ancienne application, je fais la modif au dernier moment car c'est plus prudent pour éviter des régressions
                $this->Alimenter_metadonnee($type_document,$metadonnee);
                $this->Alimenter_le_compteur_du_process_mutualise($type_document);
            }
		}
		else
		{
            $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
            $pourMsgLoggueEnBase[1] = $document_ged["ANNEE"];
            $this->Ecrire_log_traitement_message("fonctionnel","1", $pourMsgLoggueEnBase);
        }
	}
	
	public function Process_decision_documents_ged()
	{
		//Initialisation du tableau qui va contenir la requete
		$w = array();
		
		if ($this->document_param['id_candidat'] > 0)
		{
			$w[]= "FD_09A9C5FE = '".$this->document_param['id_candidat']."'";
		}

		if ($this->document_param['id_lettre'] > 0)
		{
			$w[]= "FD_3A5E7E76 = '".$this->document_param['id_lettre']."'";
		}

		if($this->document_param['type_decision']=="toutes" or $this->document_param['type_decision']=="")
		{
			$w[]= "(FD_C6004355 = 'N' OR FD_C6004355= 'X')";
		}

		if($this->document_param['type_decision']=="decision")
		{
			$w[]= "FD_C6004355 = 'N'";
		}

		if($this->document_param['type_decision']=="recours")
		{
			$w[]= "FD_C6004355= 'X'";
		}

		if($this->document_param['annee']!="")
		{
			$w[]= "FD_E7787B05= '".$this->document_param['annee']."'";
		}

		if ($this->document_param['DATE_SCAN_SQL'] != "")
		{		
			$w[]= "FD_34CFCD24 = '".$this->document_param['DATE_SCAN_SQL']."'";	
		}
		else
		{
			if ($this->document_param['DATE_SCAN_DEBUT_SQL'] != "")
			{
				$w[] = "FD_34CFCD24 >= '".$this->document_param['DATE_SCAN_DEBUT_SQL']."'";
			}

			if ($this->document_param['DATE_SCAN_FIN_SQL'] != "")
			{
				$w[] = "FD_34CFCD24 <= '".$this->document_param['DATE_SCAN_FIN_SQL']."'";
			}
		}
		
		if (count($w) > 1)
		{
			$WHERE = implode (" AND ", $w); 
		}
		else
		{
			$WHERE = $w[0];
		}
		
		$sql = "select GUID, ActRevision, Deleted, FD_E7787B05 AS ANNEE, FD_09A9C5FE AS NUMCAND, LocationSubID, FD_3A5E7E76 AS NOLETTRE,FD_CD52E931 AS CHRONO,
					FD_7EDEFFC4 AS DOSSIER,FD_E8554E3E AS NATUREDOCUMENT,
					FD_502503A7 AS ADMINISTRATION,
					CONVERT(VARCHAR,CAST(FD_34CFCD24 AS DATETIME),110) AS DATE_CCFP, 
					CONVERT(VARCHAR,CAST(FD_34CFCD24 AS DATETIME),110) AS DATE_CCFPAFF, 
					CONVERT(VARCHAR,CAST(FD_7378028E AS DATETIME),110) AS DATE_DEPART_RETOUR,
					CONVERT(VARCHAR,CAST(FD_Documents.CreatedOn AS DATETIME),112) AS DATE_CREATEDON,
					CONVERT(VARCHAR,CAST(FD_Documents.CreatedOn AS DATETIME),110) AS DATE_CREATEDONAFF,
					FD_C6004355 AS TYPE_LETTRE, CAST(FD_CC000D17 AS TEXT) AS NOM_EXPEDITEUR, PageNo AS PAGE, StorageRev AS DOSSIER_IMAGE, SourceFileName AS IMAGE 
				from dbo.FD_Documents 
				LEFT JOIN dbo.FD_Images ON (GUID = DocGUID) 
				where ".$WHERE." AND  ActRevision = RevNo and Deleted <> '1' and PageNo=1 
				ORDER BY GUID, FD_3A5E7E76, FD_34CFCD24, PageNo;";

		$req = sqlsrv_query($this->conn_ged,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base GED dans la focntion Process_decision_documents_ged");
		}

		$nb = sqlsrv_num_rows($req);

		$NUMCAND = "";
		$NOLETTRE = "";
		$TYPELETTRE = "";
		$CHRONO = "";
		
		if ($nb > 0)
		{
			while ($res = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				if ( 
						   ($NUMCAND != $res["NUMCAND"] or $NOLETTRE != $res["NOLETTRE"])
						OR ($NUMCAND == $res["NUMCAND"] and $NOLETTRE == $res["NOLETTRE"] and $TYPELETTRE != $res['TYPE_LETTRE'])
						OR ($NUMCAND == $res["NUMCAND"] and $NOLETTRE == $res["NOLETTRE"] and $TYPELETTRE == $res['TYPE_LETTRE'] and $CHRONO != $res['CHRONO'])
				   )
				{ 
					switch($res['TYPE_LETTRE'])
					{
						case "N" :
							$this->Process_decision_initial($res);
							$this->COMPTEUR_TOTAL_GED_LUS++;
							break;
						case "X" :
							$this->Process_decision_recours($res);
                            $this->COMPTEUR_TOTAL_GED_LUS++;
							break;
                        default :
                            $pourMsgLoggueEnBase[0] = $res['TYPE_LETTRE'];
                            $this->Ecrire_log_traitement_message("fonctionnel","17", $pourMsgLoggueEnBase);
                            break;
					}
				}
				else
				{
                    $pourMsgLoggueEnBase[0] = $TYPELETTRE;
                    $pourMsgLoggueEnBase[1] = $NUMCAND;
                    $pourMsgLoggueEnBase[2] = $NOLETTRE;
                    $this->Ecrire_log_traitement_message("fonctionnel","4", $pourMsgLoggueEnBase);

					switch($res['TYPE_LETTRE'])
					{
						case "N" :
							$this->Process_decision_initial($res);
                            $this->COMPTEUR_TOTAL_GED_LUS++;
							break;
						case "X" :
							$this->Process_decision_recours($res);
                            $this->COMPTEUR_TOTAL_GED_LUS++;
							break;
                        default :
                            $pourMsgLoggueEnBase[0] = $res['TYPE_LETTRE'];
                            $this->Ecrire_log_traitement_message("fonctionnel","17", $pourMsgLoggueEnBase);
                            break;
					}
				}
				
				$NUMCAND=$res["NUMCAND"];
				$NOLETTRE=$res["NOLETTRE"];
				$TYPELETTRE =$res["TYPE_LETTRE"];
				$CHRONO =$res["CHRONO"];
			}//Fin du while
		}
		else
		{
            $this->Ecrire_log_traitement_message("fonctionnel","6", '');
		}
	}

	public function Process_mutualises_nouvelle_appli($document_ged,$type_document)
	{
		if($this->verifier_coherence_candidat_nouvelle_appli($document_ged))
		{
			$DETAILS = recupere_array_DETAILS("candidat", $document_ged['NUMCAND'], $this->conn_nouvelle_appli, "");
			$metadonnee = $this->Metadonnes_nouvelle_application($document_ged,$DETAILS,$type_document);
			$path_orginal = $this->Regles_nomage_fichier(true,$type_document,$metadonnee);

			//CREATION DU PDF IMAGE
			$document_existe = $this->Convertir_document_ged_image_to_pdf($document_ged,$type_document,$metadonnee,$path_orginal);

			if($document_existe)
			{
			    $metadonnee["Path"] = $path_orginal;
				$this->Alimenter_metadonnee($type_document,$metadonnee);
                $this->Alimenter_le_compteur_du_process_mutualise($type_document);
			}
		}
	}

	public function Process_decision_initial($document_ged)
	{	
		if(empty($document_ged['NUMCAND']))
        {
            if(empty($document_ged['ANNEE']))
            {
                if($this->document_param['annee'] < 2015 )
                {
                    $this->Process_decision_initial_ancien_appli($document_ged);
                }
                else
                {
                    $this->Process_decision_initial_nouvelle_appli($document_ged);
                }
            }
            else
            {
                if ($document_ged['ANNEE'] < 2015)
                {
                    $this->Process_decision_initial_ancien_appli($document_ged);
                }
                else
                {
                    $this->Process_decision_initial_nouvelle_appli($document_ged);
                }
            }
        }
        else
        {
            if (strlen($document_ged['NUMCAND']) == 9)
            {
                $this->Process_decision_initial_nouvelle_appli($document_ged);
            }
            elseif (strlen($document_ged['NUMCAND']) <= 5)
            {
                $this->Process_decision_initial_ancien_appli($document_ged);
            }
            else //Anormal => Logguer erreur
            {
                $pourMsgLoggueEnBase[0] = $document_ged['NUMCAND'];
                $this->Ecrire_log_traitement_message("fonctionnel","5", $pourMsgLoggueEnBase);
            }
        }
	}
	
	public function Process_decision_initial_ancien_appli($document_ged)
	{
		if($this->verifier_coherence_candidat_ancien_appli($document_ged))
		{	
			$DETAILS = $this->get_DETAILS_ancien_application($document_ged);

			if($DETAILS===null)
			{
                $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                $pourMsgLoggueEnBase[1] = $document_ged["ANNEE"];
                $this->Ecrire_log_traitement_message("fonctionnel","1", $pourMsgLoggueEnBase);
			} 
			else
			{
				$type_document = "decision";
				$metadonnee = $this->Metadonnes_ancien_application($document_ged,$DETAILS,$type_document);
				$path_orginal = $this->Regles_nomage_fichier(true,$type_document,$metadonnee);

				//CREATION DU PDF IMAGE
				$document_existe = $this->Convertir_document_ged_image_to_pdf($document_ged,$type_document,$metadonnee,$path_orginal);

				//Si le pdf a été créé
				if($document_existe)
				{
					$metadonnee["Path"] = $path_orginal;
					$metadonnee2 = $metadonnee;
                    $metadonnee["N_candidat"] = $metadonnee["Annee_election"]."00000" + $DETAILS["NumCand"]; // Le  NUMNCAND doit être sur 9 digits même pour ancienne application, je fais la modif au dernier moment car si je la fais dans la fonction metadonnes_ancien_application() on a des erreurs lors de la copie du pdf texte
					$this->Alimenter_metadonnee($type_document,$metadonnee);
                    $this->Alimenter_le_compteur_de_la_decision($type_document,$metadonnee);

					//La fonction ci-dessous créer le PDF TEXTE
					$path = $this->Convertir_pdf_text_ancien_appli($document_ged,$type_document,$metadonnee2,$DETAILS);

					if($path != "") //Si la création s'est bien passée
					{
						$metadonnee["Path"] = $path;
						$this->Alimenter_metadonnee($type_document,$metadonnee);

						if(self::PROCESS_TYPE == 'csv')
						{
							$this->Process_decision_mutualiser_ancien($metadonnee,$type_document);
						}
					}
					else
					{
                        $this->Ecrire_log_traitement_message("technique","", "Problème de récupération du PDF TEXTE pour une décision initiale pour le candidat ".$document_ged["NUMCAND"]." sur l année ".$document_ged["ANNEE"]);
					}
				}
			}
		}
	}
	
	public function Process_decision_mutualiser_ancien($metadonnee,$type_document)
	{
		$CodeRequete = [];
		$where_plus = "";

		if($type_document=="requete" or $type_document=="jugement")
		{
			$where_plus = " and LOWER(REQ.Observation) LIKE '%grief financier%' ";
		}
		
		$sql = "select REQ.CodeRequete from BD_ELEC_".$metadonnee["Annee_election"].".dbo.Table_Scrutin as SCRUTIN
				left join BD_ELEC_".$metadonnee["Annee_election"].".dbo.Table_Requete as REQ on (SCRUTIN.NoScrutin = REQ.NoScrutin)
				where SCRUTIN.NoScrutin='".$metadonnee["N_scrutin"]."' 
				".$where_plus."
				and REQ.CandidatDefendeur='".$metadonnee["N_candidat"]."';";

		$req = odbc_exec($this->conn_ancien_appli, $sql);
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Process_decision_mutualiser_ancien");
		}
		
		while(odbc_fetch_row($req))
		{
			$row = [];
			for($i=1; $i <= odbc_num_fields($req); $i++)
			{
				$fkey=odbc_field_name($req,$i);
				$value = utf8_encode(odbc_result($req,$i));
				$row[$fkey] = $value;
				if($fkey == "CodeRequete")
				{
					array_push($CodeRequete,$value);
				}
			}
		}
		
		if(count($CodeRequete)>0)
		{
			foreach ($CodeRequete as &$value)
			{
				$this->document_param['dossier']= $value;
				$this->Process_mutualises_documents_ged();
			}
		}
	}

    public function Creer_Fichiers_Anonymisation($type_document, $metadonnee)
    {
        //Recuperation du dossier contenant les 2 pdf (trois repertoires possibles)
        $folder = $this->Get_repertoire_type_document($type_document);

        try //copie du pdf version texte
        {
            $sourcePdfTexte = $folder . "/" . $metadonnee['Path'];
            $destinationPdfTexte = $this->repertoireDepot . "/" . self::REPERTOIRE_ANONYMISATION . "/" . $metadonnee['Path'];
            copy($sourcePdfTexte, $destinationPdfTexte);
        }
        catch(Exception $e1)
        {
            $this->Stopper_traitement_decision("Erreur inattendue pendant la copie pour anonymisation du fichier ".$metadonnee['Path']." : ".$e1->getMessage());
        }

        try //copie du pdf version image
        {
            $sourcePdfImage = $folder . "/[ORIG]" . $metadonnee['Path'];
            $destinationPdfImage = $this->repertoireDepot . "/" . self::REPERTOIRE_ANONYMISATION . "/[ORIG]" . $metadonnee['Path'];
            copy($sourcePdfImage, $destinationPdfImage);
        }
        catch(Exception $e2)
        {
            $this->Stopper_traitement_decision("Erreur inattendue pendant la copie pour anonymisation du fichier [ORIG]".$metadonnee['Path']." : ".$e2->getMessage());
        }

        try //creation d'un fichier de metadonnées xml pour le document à anonymiser
        {
            $tab_array_document_a_anonymiser[0] = end($this->METADONNEE_DECISION);
            $destinationXml = $this->repertoireDepot . "/" . self::REPERTOIRE_ANONYMISATION . "/" . $metadonnee['Path'] . '_' . self::FILE_DECISION_XML;
            $this->ecrire_file_xml($tab_array_document_a_anonymiser, $destinationXml);
        }
        catch(Exception $e3)
        {
            $this->Stopper_traitement_decision("Erreur inattendue pendant la creation du fichier xml de métadonnées associé au fichier suivant en cours d anonymisation ".$metadonnee['Path']." : ".$e3->getMessage());
        }

        try //creation d'un fichier de metadonnées csv pour le document à anonymiser
        {
            $destinationCsv = $this->repertoireDepot . "/" . self::REPERTOIRE_ANONYMISATION . "/" . $metadonnee['Path'] . '_' . self::FILE_DECISION_CSV;
            $this->ecrire_file_csv($tab_array_document_a_anonymiser, $destinationCsv);
        }
        catch(Exception $e4)
        {
            $this->Stopper_traitement_decision("Erreur inattendue pendant la creation du fichier csv de métadonnées associé au fichier suivant en cours d anonymisation ".$metadonnee['Path']." : ".$e4->getMessage());
        }
    }

	public function Alimenter_le_compteur_de_la_decision($type_document,$metadonnee)
	{
		switch($type_document)
		{
			case "decision":
				$this->COMPTEUR_TOTAL_DECISION_CREE++;
				if($metadonnee["Anonymisation"] == "Oui")
				{
					$this->COMPTEUR_TOTAL_DECISION_COPIE_POUR_ANONYMISER++;
                    $this->Creer_Fichiers_Anonymisation($type_document,$metadonnee); //Rajouté par Edouard : copie des deux pdf (texte et image) dans le dossier 'A_Anonymiser', et creation d'un xml et d'un csv avec les metadonnées associées
				}
			break;
			case "recours":
				$this->COMPTEUR_TOTAL_RECOURS_CREE++;
				if($metadonnee["Anonymisation"] == "Oui")
				{
					$this->COMPTEUR_TOTAL_RECOURS_COPIE_POUR_ANONYMISER++;
                    $this->Creer_Fichiers_Anonymisation($type_document,$metadonnee);
				}
			break;
			case "modificative":
				$this->COMPTEUR_TOTAL_MODIFICATIVE_CREE++;
				if($metadonnee["Anonymisation"] == "Oui")
				{
					$this->COMPTEUR_TOTAL_MODIFICATIVE_COPIE_POUR_ANONYMISER++;
                    $this->Creer_Fichiers_Anonymisation($type_document,$metadonnee);
				}
			break;
		}	
	}

    public function Alimenter_le_compteur_du_process_mutualise($type_document)
    {
        switch($type_document)
        {
            case "requete":
                $this->COMPTEUR_TOTAL_REQUETE_CREE++;
                break;
            case "jugement":
                $this->COMPTEUR_TOTAL_JUGEMENT_CREE++;
                break;
            case "memoire":
                $this->COMPTEUR_TOTAL_MEMOIRE_CREE++;
                break;
        }
    }

	public function Process_decision_initial_nouvelle_appli($document_ged)
	{
		if($this->verifier_coherence_candidat_nouvelle_appli($document_ged))
		{
			$id_decision = $this->verifier_coherence_decision_nouvelle_appli($document_ged);
			
			if($id_decision!="")
			{
				$DETAILS = recupere_array_DETAILS("candidat", $document_ged['NUMCAND'], $this->conn_nouvelle_appli, $id_decision);
				$type_document = "decision";
				$metadonnee = $this->Metadonnes_nouvelle_application($document_ged,$DETAILS,$type_document);
				$path_orginal = $this->Regles_nomage_fichier(true,$type_document,$metadonnee);

				//Creer le PDF IMAGE
				$document_existe = $this->Convertir_document_ged_image_to_pdf($document_ged,$type_document,$metadonnee,$path_orginal);

				if($document_existe) //Si le pdf a été créé
				{
					$metadonnee["Path"] = $path_orginal;
					$this->Alimenter_metadonnee($type_document,$metadonnee);
                    $this->Alimenter_le_compteur_de_la_decision($type_document,$metadonnee);

                    //La fonction ci-dessous créé le PDF TEXTE
					$path = $this->Convertir_pdf_text($document_ged,$type_document,$metadonnee,$DETAILS,$id_decision);
					
					if($path != "") //Si la création s'est bien passée
					{
						$metadonnee["Path"] = $path;
						$this->Alimenter_metadonnee($type_document,$metadonnee);

						// condition pour le traitement fichier csv
						if(self::PROCESS_TYPE == 'csv')
						{
							$this->Process_decision_mutualiser_nouvelle($metadonnee,$type_document,$document_ged);
						}
					}
					else
					{
                        $this->Ecrire_log_traitement_message("technique","", "Problème de récupération du PDF TEXTE pour une décision initiale pour le candidat ".$document_ged["NUMCAND"]." sur l année ".$document_ged["ANNEE"]);
                    }
				}
			}
		}
	}
	
	public function Process_decision_mutualiser_nouvelle($metadonnee, $type_document)
	{
		$sql = " SELECT REQ.ref_ctx,GRIEF.chk_grief_financier FROM dbo.requete_ctx as REQ
					LEFT JOIN requete_ctx_grief as GRIEF ON (REQ.id_requete = GRIEF.id_requete)
					where REQ.id_scrutin = '".$metadonnee["N_scrutin"]."' AND REQ.id_defendeur = '".$metadonnee["N_candidat"]."'
					AND GRIEF.chk_grief_financier =1;
				";

		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la focntion Process_decision_mutualiser_nouvelle");
		}
		
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while ($res = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				$this->document_param['dossier']= $res["ref_ctx"];
				$this->document_param['chk_grief_financier']= $res["chk_grief_financier"];
				$this->Process_mutualises_documents_ged();
			}
		}
		else
		{
			$this->document_param['dossier']="";
			$this->Process_mutualises_documents_ged();
		}
	}
	
	public function Process_decision_recours($document_ged)
	{
		if($document_ged['ANNEE'] <= 2014 or strlen($document_ged['NUMCAND']) < 9)
		{
			$this->Process_decision_recours_ancien_appli($document_ged);
		}
		elseif ($document_ged['ANNEE'] > 2014 or strlen($document_ged['NUMCAND']) == 9)
		{
			$this->Process_decision_recours_nouvelle_appli($document_ged);
		}
	}
	
	public function Get_type_recours_ancien_application($document_ged,$DETAILS)
	{
		$text = "recours";
		$NUMCAND = $document_ged["NUMCAND"];
		$annee_scrutin = $document_ged["ANNEE"];
		$dec_titre = "";
		
		if ($document_ged["ANNEE"] > 2011 or ($document_ged["ANNEE"] == 2011 and $document_ged["NUMCAND"] == 1880))
		{
			$CodeDecisionRecours = $DETAILS["CodeDecisionRecours"];
			$dec_titre = $DETAILS["dec_titre"];
			
			if($dec_titre=="Décision relative au recours gracieux")
			{
				$text = "recours";	
			}
			elseif ($dec_titre=="Décision modificative" or $dec_titre=="Décision rectificative" )
			{ 
				$text = "modificative";		
			}			
		}
		else
		{
			$sql = "select RO.ordre, RR.*, TR.LibelleTypeDecision, CIV.Abrege_Civilite from BD_ELEC_".$annee_scrutin.".dbo.considerants_Recours_ordre AS RO
					LEFT JOIN BD_ELEC_".$annee_scrutin.".dbo.Table_Rapport_Recours AS RR ON 
					(RO.Numcand = RR.NoCandidat) 
					LEFT JOIN WEB_CONST.dbo.Table_CTypeDecisiongrac AS TR ON 
					(RR.CodeDecisionRecours = TR.CodeTypeDecision) 
					LEFT JOIN BD_ELEC_".$annee_scrutin.".dbo.Table_Candidat_Rectifie AS C ON 
					(RO.Numcand = C.NumCand) 
					LEFT JOIN WEB_CONST.dbo.Table_Civilites AS CIV ON 
					(C.CivCand = CIV.Code_Civilite) 
					WHERE RO.NumCand = '".$NUMCAND."'";
		
			$req = odbc_exec($this->conn_ancien_appli, $sql);
			
			if ($req === false)
			{
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_type_recours_ancien_application");
			}

			$nb= 0;
			$arrrow = [];

			while(odbc_fetch_row($req))
			{	
				for($i=1; $i <= odbc_num_fields($req); $i++)
				{
					$fkey=odbc_field_name($req,$i);
					$value = utf8_encode(odbc_result($req,$i));
					$arrrow[$fkey] = $value;
				}
				break;
			}
			
			if(count($arrrow)>0)
			{
				$row = $arrrow;
				$CodeDecisionRecours = $row['CodeDecisionRecours'];
			}
			
			if ($CodeDecisionRecours == 0 or $CodeDecisionRecours == 2 or $CodeDecisionRecours == 3 or $CodeDecisionRecours == 4 or $CodeDecisionRecours == 6) // 20080201 nouveau changement F. Logerot // 20080703 ajout des cas 3 et 4 // 20081009 ajout cas 6 demande Anne-Laure pour F. Logerot // 20090514 ajout code 9 // 20091015 sortie du cas 9
			{
				$text = "modificative";
			}
			elseif ($CodeDecisionRecours === 1) // nouveau cas 20080201 F. Logerot - pou *rejet* uniquement
			{
				$text = "recours";
			}
			elseif ($CodeDecisionRecours == 9 or $CodeDecisionRecours == 10) // 20091015 nouveau cas : 10 associé au cas 9 déplacé ici
			{
				$text = "recours";
			}
			else 
			{
				$text = "modificative";
			}
		}

		return $text;
	}
	
	public function Process_decision_recours_ancien_appli($document_ged)
	{
		if($this->verifier_coherence_candidat_ancien_appli($document_ged))
		{
			$DETAILS = $this->get_DETAILS_ancien_application($document_ged);
			
			if($DETAILS != null)
			{
				$type_document = $this->Get_type_recours_ancien_application($document_ged,$DETAILS);

				if($type_document != "")
				{
					$metadonnee = $this->Metadonnes_ancien_application($document_ged,$DETAILS,$type_document);
					$path_orginal = $this->Regles_nomage_fichier(true,$type_document,$metadonnee);

					//CREATION DU PDF IMAGE
					$document_existe = $this->Convertir_document_ged_image_to_pdf($document_ged,$type_document,$metadonnee,$path_orginal);

					if($document_existe) //Si le pdf a été créé
					{
						$metadonnee["Path"] = $path_orginal;
                        $metadonnee2 = $metadonnee;
                        $metadonnee["N_candidat"] = $metadonnee["Annee_election"]."00000" + $DETAILS["NumCand"]; //Le  NUMNCAND doit être sur 9 digits même pour ancienne application, je fais la modif au dernier moment car si je la fais dans la fonction metadonnes_ancien_application() on a des erreurs lors de la copie du pdf texte
						$this->Alimenter_metadonnee($type_document,$metadonnee);
                        $this->Alimenter_le_compteur_de_la_decision($type_document,$metadonnee);

                        //La fonction ci-dessous créer le PDF TEXTE
						$path = $this->Convertir_pdf_text_ancien_appli($document_ged,$type_document,$metadonnee2,$DETAILS);

						if($path !="") //Si la création s'est bien passée
						{
							$metadonnee["Path"] = $path;
							$this->Alimenter_metadonnee($type_document,$metadonnee);
						}
						else
                        {
                            $this->Ecrire_log_traitement_message("technique","", "Problème de récupération du PDF TEXTE pour une décision recours pour le candidat ".$document_ged["NUMCAND"]." sur l année ".$document_ged["ANNEE"]);
                        }
					}
				}
				else
                {
                    $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                    $this->Ecrire_log_traitement_message("fonctionnel","9", $pourMsgLoggueEnBase);
                }
			}
			else
			{
                $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                $pourMsgLoggueEnBase[1] = $document_ged["ANNEE"];
                $this->Ecrire_log_traitement_message("fonctionnel","1", $pourMsgLoggueEnBase);
			}
		}
	}
	
	public function Process_decision_recours_nouvelle_appli($document_ged)
	{
		if($this->verifier_coherence_candidat_nouvelle_appli($document_ged))
		{
			$id_decision = $this->verifier_coherence_decision_nouvelle_appli($document_ged);

			if($id_decision != "")
			{
				$DETAILS = recupere_array_DETAILS("candidat", $document_ged['NUMCAND'], $this->conn_nouvelle_appli, $id_decision);
				$type_document = "";
				
				if($DETAILS[0]["id_type_recours"] == 1)
				{
					$type_document = "recours";
				}
				elseif ($DETAILS[0]["id_type_recours"] == 2)
				{ 
					$type_document = "modificative";
				}
				
				if($type_document != "")
				{
					$metadonnee = $this->Metadonnes_nouvelle_application($document_ged,$DETAILS,$type_document);
					$path_orginal = $this->Regles_nomage_fichier(true,$type_document,$metadonnee);

					//CREATION DU PDF IMAGE
					$document_existe = $this->Convertir_document_ged_image_to_pdf($document_ged,$type_document,$metadonnee,$path_orginal);

					if($document_existe)
					{
						$metadonnee["Path"] = $path_orginal;
						$this->Alimenter_metadonnee($type_document,$metadonnee);
                        $this->Alimenter_le_compteur_de_la_decision($type_document,$metadonnee);

						//Creation du PDF Texte
						$path = $this->Convertir_pdf_text($document_ged,$type_document,$metadonnee,$DETAILS,$id_decision);

						if($path != "") //Si la creation s'est bien passée
						{
							$metadonnee["Path"] = $path;
							$this->Alimenter_metadonnee($type_document,$metadonnee);
						}
						else
                        {
                            $this->Ecrire_log_traitement_message("technique","", "Problème de récupération du PDF TEXTE pour une décision recours pour le candidat ".$document_ged["NUMCAND"]." sur l année ".$document_ged["ANNEE"]);
                        }
					}
				}
				else
				{
                    $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                    $this->Ecrire_log_traitement_message("fonctionnel","9", $pourMsgLoggueEnBase);
				}
			}
		}
	}
	
	public function Alimenter_metadonnee($type_document,$metadonnee)
	{
		switch ($type_document)
		{
			case "decision" :
				array_push($this->METADONNEE_DECISION,$metadonnee);
				break;
			case "recours" :
				array_push($this->METADONNEE_RECOURS,$metadonnee);
				break;
			case "modificative" :
				array_push($this->METADONNEE_MODIFICATIVE,$metadonnee);
				break;
			case "requete" :
				array_push($this->METADONNEE_REQUETE,$metadonnee);
				break;
			case "memoire" :
				array_push($this->METADONNEE_MEMOIRE,$metadonnee);
				break;
			case "jugement" :
				array_push($this->METADONNEE_JUGEMENT,$metadonnee);
				break;
		}
	}
	
	public function verifier_coherence_decision_nouvelle_appli($document_ged)
	{
		$sql="SELECT notif.id_decision FROM dbo.notification as notif 
			  left join dbo.decision as dec on(dec.id_decision = notif.id_decision)
			  WHERE id_notif='".$document_ged["NOLETTRE"]."' and dec.id_compte='".$document_ged["NUMCAND"]."' ;";
		
		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction verifier_coherence_decision_nouvelle_appli");
		}
		
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while($rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				return $rs["id_decision"];
			}
		}
		else
		{
            $pourMsgLoggueEnBase[0] = $document_ged["NOLETTRE"];
            $pourMsgLoggueEnBase[1] = $document_ged["NUMCAND"];
            $this->Ecrire_log_traitement_message("fonctionnel","10", $pourMsgLoggueEnBase);

			return "";
		}
	}

	public function get_metadonnees_vide()
	{
		return $array = [
			"Type_de_contenu" => "",
			"N_candidat" => "",
			"Nom_candidat_1" => "",
			"Prenom_candidat_1" => "",
			"Nom_candidat_2" => "",
			"Prenom_candidat_2" => "",
			"N_scrutin" => "",
			"Nom_circonscription" => "",
			"Numero_affaire" => "",
			"N_INSEE_departement" => "",
			"N_INSEE_Region" => "",
			"Election" => "",
			"Type_election" => "",
			"Annee_election" => "",
			"Scrutin_contentieux" => "",
			"Rapporteur" => "",
			"N_rapporteur" => "",
			"Membre_commission" => "",
			"Suivi_par" => "",
			"N_lettre" => "",
			"Chrono_GED" => "",
			"Date_decision" => "",
			"Date_jour" => "",
			"Date_base_CCFP" => "",
			"Etiquette_politique" => "",
			"Nuance_politique" => "",
			"Parti_politique" => "",
			"N_parti" => "",
			"Type_mandataire" => "",
			"Nom_mandataire" => "",
			"N_association_financement" => "",
			"N_expert_comptable" => "",
			"Nom_cabinet_expertise_comptable" => "",
			"Elu" => "",
			"Sortant" => "",
			"pourcentage_voix_1er_tour" => "",
			"remboursement" => "",
			"Sens_decision" => "",
			"Decision_signalee" => "",
			"Doctrine" => "",
			"Ex_doctrine" => "",
			"Cas_espece" => "",
			"Jurisprudence" => "",
			"Ex_jurisprudence" => "",
			"Mots_cles_automatiques" => "",
			"Mot_cles_indexeurs" => "",
			"Commentaires" => "",
			"Decision_remise_cause" => "",
			"Anonymisation" => "",
			"A_reviser" => "",
			"Path" => "",
		];
	}
	
	public function recuper_array_DECISION_nouvelle_appli($id_decision)
	{
		$sql="SELECT DEC.*, CONVERT(VARCHAR,CAST(DEC.date_pass_com AS DATETIME),110) AS DATE_PASS_COM ,DS.nom_decision_sens,DS.nom_abrege_decision_sens FROM dbo.decision as DEC
				LEFT JOIN dbo.decision_sens as DS on(DEC.id_sens_decision=DS.id_sens_decision)
				WHERE id_decision='".$id_decision."';";

		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction recuper_array_DECISION_nouvelle_appli");
		}
		
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while($rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				return $rs;
			}
		}
	}

	public function get_mot_cles($id_decision)
	{
		$mot_cle_indexeur=[];
		$mot_cle_automatique=[];
		$sql = $this->sql_get_mots_cles_decision($id_decision);
		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if( $req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction get_mot_cles");
		}
		
		$nb = sqlsrv_num_rows($req);
		$mot_cle=[];
		$mot_cle["indexeur"]="";
		$mot_cle["automatique"]="";
		
		if ($nb > 0)
		{
			while($rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				if($rs["chk_nouveau"])
				{
					array_push($mot_cle_indexeur,$rs["nom_theme"]);
				}
				else
				{
					array_push($mot_cle_automatique,$rs["nom_theme"]);
				}
			}
			
			$mot_cle["indexeur"]=implode (";", $mot_cle_indexeur);
			$mot_cle["automatique"]=implode (";", $mot_cle_automatique);
		}
	
		return $mot_cle;	
	}

	public function sql_get_mots_cles_decision($id_decision)
	{
		return "SELECT DT.* , T.nom_theme FROM dbo.decision_theme as DT
				left join dbo.theme as T on (T.id_theme = DT.id_theme)
				where id_decision = '".$id_decision."';";
	}
	
	public function Get_type_contenu($type_document)
	{
		$text = "";
		
		switch ($type_document)
		{
			case "decision" :
				$text = "Décision initiale";
				break;
			case "recours" :
				$text = "Décision recours";
				break;
			case "modificative" :
				$text = "Rectification d'erreur matérielle";
				break;
			case "requete" :
				$text = "Requête";
				break;
			case "memoire" :
				$text = "Mémoires";
				break;
			case "jugement" :
				$text = "Jugement";
				break;
		}
		
		return $text;
	}

	public function Get_remboursement($Pct1T,$meta)
	{
		$flag = "False";
		
		if($Pct1T > 5)
		{
			$flag = "True";
		}
		elseif($Pct1T > 3 and $meta["Election"] == "Territoriales générales" and $meta["N_INSEE_departement"] == "987")
		{
			$flag = "True";
		}
		elseif($Pct1T > 3 and $meta["Election"] == "Européennes")
		{
			$flag = "True";
		}
		
		return $flag;
	}
	
	public function Get_Election_name($libelle_elec,$TypeElection="")
	{
		$text = "";
		
		if($TypeElection != "")
		{
			$elec = $libelle_elec ." ".$TypeElection;
		}
		else
		{
			$elec = $libelle_elec;
		}
		
		switch($elec)
		{
			case "municipale":
			case "municipale générale":
				$text = "Municipales générales";
				break;
			case "municipale partielle":
				$text = "Municipales partielles";
				break;
			case "sénatoriale générale":
			case "sénatoriale":
				$text = "Sénatoriales générales";
				break;
			case "sénatoriale partielle":
				$text = "Sénatoriales partielles";
				break;
			case "législative":
			case "législative générale":
				$text = "Législatives générales";
				break;
			case "législative partielle":
				$text = "Législatives partielles";
				break;
			case "départementale générale":
			case "départementale":
			case "cantonale":
			case "cantonale générale":
				$text="Départementales générales";
				break;
			case "départementale uninominale partielle":
				$text="Départementales uninominales";
				break;
			case "régionale":
			case "régionale générale":
			case "des conseillers à l'Assemblée de Corse":
			case "des conseillers de l'Assemblée de Martinique":
			case "des conseillers de l'Assemblée de Guyane":
			case "des membres de l'assemblée de la Polynésie française":
			case "des conseillers à l'Assemblée de Corse générale":
			case "des conseillers de l'Assemblée de Martinique générale":
			case "des conseillers de l'Assemblée de Guyane générale":
			//case "des membres de l'assemblée de la Polynésie française générale":
			case "des membres de l'Assemblée de la Polynésie Française générale": //Modif par EA le 14 08 2018 pour corriger problème import Polynésie
				$text="Régionales générales";
				break;
			case "régionale partielle":
			case "des conseillers à l'Assemblée de Corse partielle":
			case "des conseillers de l'Assemblée de Martinique partielle":
			case "des conseillers de l'Assemblée de Guyane partielle":
			case "des membres de l'assemblée de la Polynésie française partielle":
				$text="Régionales partielles";
				break;
			case "départementale partielle":
			case "cantonale partielle":
				$text="Départementales partielles";
				break;
			case "provinciale":
			case "provinciale générale":
				$text="Provinciales générales";
				break;
			case "provinciale partielle":
				$text="Provinciales partielles";
				break;
			case "territoriale":
			case "territoriale générale":
				$text="Territoriales générales";
				break;
			case "territoriale partielle":
				$text="Territoriales partielles";
				break;	
			case "référendum":
			case "référendum générale":
			case "référendum partielle":
				$text="Référendum";
				break;	
			case "des représentants au Parlement européen":
			case "des représentants au Parlement européen générales":
			case "des représentants au Parlement européen partielles":
				$text="Européennes";
				break;
            default : $text='false';
		}
		
		return $text;
	}

	public function Get_mandataire_type($Type_mandataire)
	{
		$text = "";
		
		switch($Type_mandataire)
		{
			case "MF":
				$text = "Personne physique";
				break;
			case "AF":
				$text = "Association de financement électoral";
				break;
		}
		
		return $text;
	}
	
	public function Get_suivi_par($suivi_par )
	{
		$text = $suivi_par;
		
		switch ($suivi_par)
		{
			case "Anne-Laure Vignal-Roussel":
			case "Anne Laure VIGNAL-ROUSSEL":
				$text = "Anne-Laure Vignal";
				break;
		}
		
		return $text;
	}
	
	public function Get_sens_decision_init_mutualise($id_candidat)
	{
		$sens_dec = "A";
		
		$sql="SELECT DS.nom_abrege_decision_sens FROM dbo.decision as DEC
				LEFT JOIN dbo.decision_sens as DS on(DEC.id_sens_decision=DS.id_sens_decision)
				WHERE DEC.id_type_decision='3' and DEC.id_compte='".$id_candidat."';";
				
		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_sens_decision_init_mutualise");
		}
		
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while($rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				$sens_dec = $rs["nom_abrege_decision_sens"];
			}
		}
		
		return $sens_dec;
	}
	
	public function Get_numero_dossier($document_ged)
	{
		$dossier = $document_ged['DOSSIER'];
		
		if($document_ged["ADMINISTRATION"]== "Tribunal Administratif")
		{
			$pos = strpos($dossier, "/");

			if ($pos === true)
			{
				$dossier = substr($dossier, 0, $pos);
			} 
			else 
			{
				$poss = strpos($dossier, "-");
				
				if ($poss === true)
				{
					$arr = explode("-",$dossier);
					$arr = array_pop($arr);
					$dossier = implode("",$arr);
				}
			}
		}
		
		$dossier = preg_replace('#[^0-9a-z]+#i', '', $dossier);
		
		return $dossier;
	}
	
	public function Metadonnes_nouvelle_application($document_ged,$DETAILS,$type_document)
	{
		$DETAIL = $DETAILS[0];
		$DETAILS_DECISION = [];
		$DETAILS_DECISION_MOT_CLE = [];
	
		if($type_document == "requete" or $type_document == "memoire" or $type_document == "jugement")
		{
			$DETAILS_DECISION_MOT_CLE["automatique"]="";
			$DETAILS_DECISION_MOT_CLE["indexeur"]="";
			$DETAILS_DECISION["DATE_PASS_COM"]="";
			$DETAILS_DECISION["nom_decision_sens"]="";
			$DETAILS_DECISION["chk_signalee"]="False";
			$DETAILS_DECISION["chk_doctrine"]="False";
			$DETAILS_DECISION["chk_cas_d_espece"]="False";
			$DETAILS_DECISION["commentaire_signalement"]="";
			$DETAILS_DECISION["chk_anonymiser"]="False";
			$DETAILS_DECISION["chk_completer_theme"]="False";
			$DETAILS_DECISION["id_decision"]="";
			$DETAILS_DECISION["nom_abrege_decision_sens"]= $this->Get_sens_decision_init_mutualise($DETAIL["id_candidat"]);
		}
		else
		{
			$DETAILS_DECISION = $this->recuper_array_DECISION_nouvelle_appli($DETAIL["id_decision"]);
			$DETAILS_DECISION_MOT_CLE = $this->get_mot_cles($DETAIL["id_decision"]);
			$DETAILS_DECISION["id_decision"]=$DETAIL["id_decision"];
			$DETAILS_DECISION["nom_abrege_decision_sens"]=$DETAIL["nom_abrege_decision_sens"];
		}

		$meta = $this->get_metadonnees_vide();
		$meta["Dossier"]= $this->Get_numero_dossier($document_ged);
		$meta["id_decision"]= $DETAILS_DECISION["id_decision"];
		$meta["type_document"]= $type_document;
		$meta["DATE_CREATEDON"] = $document_ged["DATE_CREATEDON"];
		$meta["Type_de_contenu"]= $this->Get_type_contenu($type_document);
		$meta["N_candidat"]=$DETAIL["id_candidat"];
		$meta["Nom_candidat_1"]=$DETAIL["nom_cand"];
		$meta["Prenom_candidat_1"]=$DETAIL["prenom_cand"];
		$meta["Nom_candidat_2"]=isset($DETAIL["nom_cand_associe"])?$DETAIL["nom_cand_associe"]:"";
		$meta["Prenom_candidat_2"]=isset($DETAIL["prenom_cand_associe"])?$DETAIL["prenom_cand_associe"]:"";
		$meta["N_scrutin"]=$DETAIL["id_scrutin"];
		$meta["Nom_circonscription"]=$DETAIL["nom_circons"];
		$meta["Numero_affaire"]=preg_replace('#[^0-9a-z]+#i', '', $document_ged['DOSSIER']);
		$meta["N_INSEE_departement"]=$DETAIL["id_dpt"]; // a vérifier
		$meta["N_INSEE_Region"]=$DETAIL["id_region"]; // a vérifier
		$meta["Election"]=$this->Get_Election_name($DETAIL["libelle_elec"],"");
		$meta["Type_election"] = $DETAIL["abrev_type_elec"]=="P"?"Partielle":"Générale"; 
		$meta["Annee_election"] = $DETAIL["ANNEE_ELECTION"];
		$meta["Scrutin_contentieux"] = $DETAIL["chk_ctx"]==1?"True":"False";
		$meta["Rapporteur"] = $DETAIL["RAP_prenom"]." ". $DETAIL["RAP_nom"];
		$meta["N_rapporteur"] = $DETAIL["id_groupe_rapporteur"];
		$meta["Membre_commission"] = $DETAIL["RG_prenom"]." ".$DETAIL["RG_nom"] ;
		$meta["Suivi_par"] = $this->Get_suivi_par($DETAIL["CM_prenom"]." ".$DETAIL["CM_nom"]) ;
		$meta["N_lettre"] = $document_ged['NOLETTRE'];
		$meta["Chrono_GED"] = $document_ged['CHRONO']; //FD_CD52E931 a vérifier
		$meta["Date_decision"] = $DETAILS_DECISION["DATE_PASS_COM"];
		$meta["Date_jour"] = ($document_ged['DATE_CCFPAFF']!="")?$document_ged['DATE_CCFPAFF']:$document_ged['DATE_CREATEDONAFF'];
		$meta["Date_base_CCFP"] = $document_ged['DATE_DEPART_RETOUR'];
		$meta["Etiquette_politique"] = $DETAIL["etiquette"];
		$meta["Nuance_politique"] = $DETAIL["nuance"];
		$meta["Parti_politique"] = $DETAIL["nom_parti"];
		$meta["N_parti"] = $DETAIL["id_parti"];
		$meta["Type_mandataire"] = $this->Get_mandataire_type($DETAIL["qualite"]);
		$meta["Nom_mandataire"] = str_replace("&", "et", $DETAIL["nom_mf"]);
		$meta["N_association_financement"] = "";   // a vérifier
		$meta["N_expert_comptable"] = $DETAIL["id_expert"];
		$meta["Nom_cabinet_expertise_comptable"] = str_replace("&", "et", $DETAIL["nom_cie_expert"]);
		$meta["Elu"] = $DETAIL["chk_elu"]==1?"True":"False";
		$meta["Sortant"] = $DETAIL["chk_sortant"]==1?"True":"False";
		$meta["pourcentage_voix_1er_tour"] = $DETAIL["pct_voix_1t"]/100;
		$meta["remboursement"] = $this->Get_remboursement($DETAIL["pct_voix_1t"],$meta);
		$meta["Sens_decision"] = $DETAILS_DECISION["nom_abrege_decision_sens"];
		$meta["Decision_signalee"] = $DETAILS_DECISION["chk_signalee"]==1?"True":"False";
		$meta["Doctrine"] = $DETAILS_DECISION["chk_doctrine"]==1?"True":"False";
		$meta["Ex_doctrine"] = "False";
		$meta["Cas_espece"] = $DETAILS_DECISION["chk_cas_d_espece"]==1?"True":"False";
		$meta["Jurisprudence"] = "";
		$meta["Ex_jurisprudence"] = "";
		$meta["Mots_cles_automatiques"] = $DETAILS_DECISION_MOT_CLE["automatique"];
		$meta["Mots_cles_automatiques"] = $this->Corriger_interet($meta["Mots_cles_automatiques"]);
		$meta["Mot_cles_indexeurs"] = $DETAILS_DECISION_MOT_CLE["indexeur"];
		$meta["Commentaires"] = $DETAILS_DECISION["commentaire_signalement"];
		$meta["Decision_remise_cause"] = ""; // a vérifier
		$meta["Anonymisation"] = $DETAILS_DECISION["chk_anonymiser"]==1?"Oui":"Non";
		$meta["A_reviser"] = $DETAILS_DECISION["chk_completer_theme"]==1?"True":"False";
		
		return $meta;	
	}

	public function Corriger_interet($chaineMotsClesAuto)
	{
		if ($this->environnement == 'PROD')
		{
			$pattern1 = "#intérêt;#";
			$replacement1 = "intérêts;";
			$chaineMotsClesAuto = preg_replace($pattern1, $replacement1, $chaineMotsClesAuto);
			$pattern2 = "#intérêt$#";
			$replacement2 = "intérêts";
			$chaineMotsClesAuto = preg_replace($pattern2, $replacement2, $chaineMotsClesAuto);
		}

		return $chaineMotsClesAuto;
	}
	
	public function Ecrire_metadonnes_xml()
	{
		if(count($this->METADONNEE_DECISION)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_DECISION."/".self::FILE_DECISION_XML;
			$this->ecrire_file_xml($this->METADONNEE_DECISION ,$file_path);
		}
		
		if(count($this->METADONNEE_RECOURS)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_RECOURS."/".self::FILE_RECOURS_XML;
			$this->ecrire_file_xml($this->METADONNEE_RECOURS ,$file_path);
		}
		
		if(count($this->METADONNEE_MODIFICATIVE)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_MODIFICATIVE."/".self::FILE_MODIFICATIVE_XML;
			$this->ecrire_file_xml($this->METADONNEE_MODIFICATIVE ,$file_path);
		}
		
		if(count($this->METADONNEE_REQUETE)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_REQUETE."/".self::FILE_REQUETE_XML;
			$this->ecrire_file_xml($this->METADONNEE_REQUETE ,$file_path);
		}
		
		if(count($this->METADONNEE_MEMOIRE)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_MEMOIRE."/".self::FILE_MEMOIRE_XML;
			$this->ecrire_file_xml($this->METADONNEE_MEMOIRE ,$file_path);
		}
		
		if(count($this->METADONNEE_JUGEMENT)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_JUGEMENT."/".self::FILE_JUGEMENT_XML;
			$this->ecrire_file_xml($this->METADONNEE_JUGEMENT ,$file_path);
		}
	}
	
	public function Ecrire_metadonnes_csv()
	{
		if(count($this->METADONNEE_DECISION)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_DECISION."/".self::FILE_DECISION_CSV;
			$this->ecrire_file_csv($this->METADONNEE_DECISION ,$file_path);
		}
		
		if(count($this->METADONNEE_RECOURS)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_RECOURS."/".self::FILE_RECOURS_CSV;
			$this->ecrire_file_csv($this->METADONNEE_RECOURS ,$file_path);
		}
		
		if(count($this->METADONNEE_MODIFICATIVE)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_MODIFICATIVE."/".self::FILE_MODIFICATIVE_CSV;
			$this->ecrire_file_csv($this->METADONNEE_MODIFICATIVE ,$file_path);
		}
		
		if(count($this->METADONNEE_REQUETE)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_REQUETE."/".self::FILE_REQUETE_CSV;
			$this->ecrire_file_csv($this->METADONNEE_REQUETE ,$file_path);
		}
		
		if(count($this->METADONNEE_MEMOIRE)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_MEMOIRE."/".self::FILE_MEMOIRE_CSV;
			$this->ecrire_file_csv($this->METADONNEE_MEMOIRE ,$file_path);
		}
		
		if(count($this->METADONNEE_JUGEMENT)>0)
		{
			$file_path = $this->repertoireDepot."/".self::REPERTOIRE_JUGEMENT."/".self::FILE_JUGEMENT_CSV;
			$this->ecrire_file_csv($this->METADONNEE_JUGEMENT ,$file_path);
		}
	}
	
	public function ecrire_file_xml($array ,$file_path)
	{
		$fp = fopen($file_path, 'w');
		$xml_debut = "<Webs>
					  <Web Url=\"/\">
						<List Url=\"\">";
		fwrite($fp,$xml_debut);
		foreach ($array as $meta)
		{
			$this->ecrire_document_xml($fp,$meta);
		}
		$xml_fin = "
					</List>
					</Web>
					</Webs>";
		fwrite($fp, $xml_fin);	
		fclose($fp);
	}
	
	public function Get_document_xml_decision($meta)
	{  
        $xml_document = "
		<File FilePath=\"".$meta["Path"]."\">
		  <Field Name=\"DCSCandidatId\">".$meta["N_candidat"]."</Field>
		  <Field Name=\"DCSCandidatNom1\">".$meta["Nom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatPrenom1\">".$meta["Prenom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatNom2\">".$meta["Nom_candidat_2"]."</Field>
		  <Field Name=\"DCSCandidatPrenom2\">".$meta["Prenom_candidat_2"]."</Field>
		  <Field Name=\"DCSScrutin\">".$meta["N_scrutin"]."</Field>
		  <Field Name=\"DCSCirconscription\">".$meta["Nom_circonscription"]."</Field>
		  <Field Name=\"DCSDepartement\">".$meta["N_INSEE_departement"]."</Field>
		  <Field Name=\"DCSRegion\">".$meta["N_INSEE_Region"]."</Field>
		  <Field Name=\"DCSElection\">".$meta["Election"]."</Field>
		  <Field Name=\"DCSElectionType\">".$meta["Type_election"]."</Field>
		  <Field Name=\"DCSElectionAnnee\">".$meta["Annee_election"]."</Field>
		  <Field Name=\"DCSScrutinContentieux\">".$meta["Scrutin_contentieux"]."</Field>
		  <Field Name=\"DCSRapporteur\">".$meta["Rapporteur"]."</Field>
		  <Field Name=\"DCSRapporteurId\">".$meta["N_rapporteur"]."</Field>
		  <Field Name=\"DCSMembreCommission\">".$meta["Membre_commission"]."</Field>
		  <Field Name=\"DCSSuiviPar\" UserType=\"True\">".$meta["Suivi_par"]."</Field>
		  <Field Name=\"DCSLettreId\">".$meta["N_lettre"]."</Field>
		  <Field Name=\"DCSGEDChrono\">".$meta["Chrono_GED"]."</Field>
		  <Field Name=\"DCSDate\" DateTime=\"True\">".$meta["Date_decision"]."</Field>
		  <Field Name=\"DCSDateJour\" DateTime=\"True\">".$meta["Date_jour"]."</Field>
		  <Field Name=\"DCSEtiquette\">".$meta["Etiquette_politique"]."</Field>
		  <Field Name=\"DCSNuance\">".$meta["Nuance_politique"]."</Field>
		  <Field Name=\"DCSParti\">".$meta["Parti_politique"]."</Field>
		  <Field Name=\"DCSPartiId\">".$meta["N_parti"]."</Field>
		  <Field Name=\"DCSMandataireType\">".$meta["Type_mandataire"]."</Field>
		  <Field Name=\"DCSMandataire\">".$meta["Nom_mandataire"]."</Field>
		  <Field Name=\"DCSAssociation\">".$meta["N_association_financement"]."</Field>
		  <Field Name=\"DCSExpertComptableId\">".$meta["N_expert_comptable"]."</Field>
		  <Field Name=\"DCSExpertComptable\">".$meta["Nom_cabinet_expertise_comptable"]."</Field>
		  <Field Name=\"DCSElu\">".$meta["Elu"]."</Field>
		  <Field Name=\"DCSSortant\">".$meta["Sortant"]."</Field>
                   "; 
                  
          if ($meta["Type_de_contenu"] !== 'Décision initiale' AND ($this->environnement == 'RECETTE' OR $this->environnement == 'PREPROD'))
          {
              $xml_document .= "<Field Name=\"Remboursable\">".$meta["remboursement"]."</Field>";
          }
          else
          {
              $xml_document .= "<Field Name=\"DCSRemboursable\">".$meta["remboursement"]."</Field>";
          }

          $xml_document .= "
          <Field Name=\"DCSSens\">".$meta["Sens_decision"]."</Field>
		  <Field Name=\"DCSSignalee\">".$meta["Decision_signalee"]."</Field>
		  <Field Name=\"DCSDoctrine\">".$meta["Doctrine"]."</Field>
		  <Field Name=\"DCSExDoctrine\">".$meta["Ex_doctrine"]."</Field>
		  <Field Name=\"DCSCasEspece\">".$meta["Cas_espece"]."</Field>
		  <Field Name=\"DCSMotsClesAuto\" IsTaxo=\"True\">".$meta["Mots_cles_automatiques"]."</Field>
		  <Field Name=\"DCSMotsClesIndex\" IsTaxo=\"True\">".$meta["Mot_cles_indexeurs"]."</Field>
		  <Field Name=\"DCSCommentaires\">".$meta["Commentaires"]."</Field>
		  <Field Name=\"DCSRemiseEnCause\">".$meta["Decision_remise_cause"]."</Field>
		  <Field Name=\"DCSAnonymisation\">".$meta["Anonymisation"]."</Field>
		  <Field Name=\"DCSAReviser\">".$meta["A_reviser"]."</Field>
		</File>";
		
		return $xml_document;
	}
	
	public function Get_document_xml_jugement($meta)
	{
		$xml_document = "
		<File FilePath=\"".$meta["Path"]."\">
		  <Field Name=\"DCSCandidatId\">".$meta["N_candidat"]."</Field>
		  <Field Name=\"DCSCandidatNom1\">".$meta["Nom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatPrenom1\">".$meta["Prenom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatNom2\">".$meta["Nom_candidat_2"]."</Field>
		  <Field Name=\"DCSCandidatPrenom2\">".$meta["Prenom_candidat_2"]."</Field>
		  <Field Name=\"DCSScrutin\">".$meta["N_scrutin"]."</Field>
		  <Field Name=\"DCSCirconscription\">".$meta["Nom_circonscription"]."</Field>
		  <Field Name=\"DCSAffaireId\">".$meta["Numero_affaire"]."</Field>
		  <Field Name=\"DCSDepartement\">".$meta["N_INSEE_departement"]."</Field>
		  <Field Name=\"DCSRegion\">".$meta["N_INSEE_Region"]."</Field>
		  <Field Name=\"DCSElection\">".$meta["Election"]."</Field>
		  <Field Name=\"DCSElectionType\">".$meta["Type_election"]."</Field>
		  <Field Name=\"DCSElectionAnnee\">".$meta["Annee_election"]."</Field>
		  <Field Name=\"DCSScrutinContentieux\">".$meta["Scrutin_contentieux"]."</Field>
		  <Field Name=\"DCSRapporteur\">".$meta["Rapporteur"]."</Field>
		  <Field Name=\"DCSRapporteurId\">".$meta["N_rapporteur"]."</Field>
		  <Field Name=\"DCSMembreCommission\">".$meta["Membre_commission"]."</Field>
		  <Field Name=\"DCSSuiviPar\" UserType=\"True\">".$meta["Suivi_par"]."</Field>
		  <Field Name=\"DCSGEDChrono\">".$meta["Chrono_GED"]."</Field>
		  <Field Name=\"DCSDateJour\" DateTime=\"True\">".$meta["Date_jour"]."</Field>
		  <Field Name=\"DCSEtiquette\">".$meta["Etiquette_politique"]."</Field>
		  <Field Name=\"DCSNuance\">".$meta["Nuance_politique"]."</Field>
		  <Field Name=\"DCSParti\">".$meta["Parti_politique"]."</Field>
		  <Field Name=\"DCSPartiId\">".$meta["N_parti"]."</Field>
		  <Field Name=\"DCSMandataireType\">".$meta["Type_mandataire"]."</Field>
		  <Field Name=\"DCSMandataire\">".$meta["Nom_mandataire"]."</Field>
		  <Field Name=\"DCSAssociation\">".$meta["N_association_financement"]."</Field>
		  <Field Name=\"DCSExpertComptableId\">".$meta["N_expert_comptable"]."</Field>
		  <Field Name=\"DCSExpertComptable\">".$meta["Nom_cabinet_expertise_comptable"]."</Field>
		  <Field Name=\"DCSElu\">".$meta["Elu"]."</Field>
		  <Field Name=\"DCSSortant\">".$meta["Sortant"]."</Field>
                   "; 
                  
          if ($this->environnement == 'RECETTE' OR $this->environnement == 'PREPROD')
          {
              $xml_document .= "<Field Name=\"Remboursable\">".$meta["remboursement"]."</Field>";
          }
          else
          {
              $xml_document .= "<Field Name=\"DCSRemboursable\">".$meta["remboursement"]."</Field>";
          }

          $xml_document .= "
		  <Field Name=\"DCSSens\">".$meta["Sens_decision"]."</Field>
		  <Field Name=\"DCSSignalee\">".$meta["Decision_signalee"]."</Field>
		  <Field Name=\"DCSJurisprudence\">False</Field>
		  <Field Name=\"DCSExJurisprudence\">False</Field>
		  <Field Name=\"DCSMotsClesIndex\" IsTaxo=\"True\">".$meta["Mot_cles_indexeurs"]."</Field>
		  <Field Name=\"DCSCommentaires\">".$meta["Commentaires"]."</Field>
		  <Field Name=\"DCSAReviser\">".$meta["A_reviser"]."</Field>
		</File>";
		
		return $xml_document;
	}
	
	public function Get_document_xml_memoire($meta)
	{
		$xml_document = "
		<File FilePath=\"".$meta["Path"]."\">
		  <Field Name=\"DCSCandidatId\">".$meta["N_candidat"]."</Field>
		  <Field Name=\"DCSCandidatNom1\">".$meta["Nom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatPrenom1\">".$meta["Prenom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatNom2\">".$meta["Nom_candidat_2"]."</Field>
		  <Field Name=\"DCSCandidatPrenom2\">".$meta["Prenom_candidat_2"]."</Field>
		  <Field Name=\"DCSScrutin\">".$meta["N_scrutin"]."</Field>
		  <Field Name=\"DCSCirconscription\">".$meta["Nom_circonscription"]."</Field>
		  <Field Name=\"DCSAffaireId\">".$meta["Numero_affaire"]."</Field>
		  <Field Name=\"DCSDepartement\">".$meta["N_INSEE_departement"]."</Field>
		  <Field Name=\"DCSRegion\">".$meta["N_INSEE_Region"]."</Field>
		  <Field Name=\"DCSElection\">".$meta["Election"]."</Field>
		  <Field Name=\"DCSElectionType\">".$meta["Type_election"]."</Field>
		  <Field Name=\"DCSElectionAnnee\">".$meta["Annee_election"]."</Field>
		  <Field Name=\"DCSScrutinContentieux\">".$meta["Scrutin_contentieux"]."</Field>
		  <Field Name=\"DCSRapporteur\">".$meta["Rapporteur"]."</Field>
		  <Field Name=\"DCSRapporteurId\">".$meta["N_rapporteur"]."</Field>
		  <Field Name=\"DCSMembreCommission\">".$meta["Membre_commission"]."</Field>
		  <Field Name=\"DCSSuiviPar\" UserType=\"True\">".$meta["Suivi_par"]."</Field>
		  <Field Name=\"DCSGEDChrono\">".$meta["Chrono_GED"]."</Field>
		  <Field Name=\"DCSDateJour\" DateTime=\"True\">".$meta["Date_jour"]."</Field>
		  <Field Name=\"DCSEtiquette\">".$meta["Etiquette_politique"]."</Field>
		  <Field Name=\"DCSNuance\">".$meta["Nuance_politique"]."</Field>
		  <Field Name=\"DCSParti\">".$meta["Parti_politique"]."</Field>
		  <Field Name=\"DCSPartiId\">".$meta["N_parti"]."</Field>
		  <Field Name=\"DCSMandataireType\">".$meta["Type_mandataire"]."</Field>
		  <Field Name=\"DCSMandataire\">".$meta["Nom_mandataire"]."</Field>
		  <Field Name=\"DCSAssociation\">".$meta["N_association_financement"]."</Field>
		  <Field Name=\"DCSExpertComptableId\">".$meta["N_expert_comptable"]."</Field>
		  <Field Name=\"DCSExpertComptable\">".$meta["Nom_cabinet_expertise_comptable"]."</Field>
		  <Field Name=\"DCSElu\">".$meta["Elu"]."</Field>
		  <Field Name=\"DCSSortant\">".$meta["Sortant"]."</Field>
                  "; 
                  
          if ($this->environnement == 'RECETTE' OR $this->environnement == 'PREPROD')
          {
              $xml_document .= "<Field Name=\"Remboursable\">".$meta["remboursement"]."</Field>";
          }
          else
          {
              $xml_document .= "<Field Name=\"DCSRemboursable\">".$meta["remboursement"]."</Field>";
          }

          $xml_document .= "
		  <Field Name=\"DCSSens\">".$meta["Sens_decision"]."</Field>
		  <Field Name=\"DCSSignalee\">".$meta["Decision_signalee"]."</Field>
		  <Field Name=\"DCSJurisprudence\">False</Field>
		  <Field Name=\"DCSExJurisprudence\">False</Field>
		  <Field Name=\"DCSMotsClesIndex\" IsTaxo=\"True\">".$meta["Mot_cles_indexeurs"]."</Field>
		  <Field Name=\"DCSCommentaires\">".$meta["Commentaires"]."</Field>
		  <Field Name=\"DCSAReviser\">".$meta["A_reviser"]."</Field>
		</File>";
		
		return $xml_document;
	}
	
	public function Get_document_xml_requete($meta)
	{
		$xml_document = "
		<File FilePath=\"".$meta["Path"]."\">
		  <Field Name=\"DCSCandidatId\">".$meta["N_candidat"]."</Field>
		  <Field Name=\"DCSCandidatNom1\">".$meta["Nom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatPrenom1\">".$meta["Prenom_candidat_1"]."</Field>
		  <Field Name=\"DCSCandidatNom2\">".$meta["Nom_candidat_2"]."</Field>
		  <Field Name=\"DCSCandidatPrenom2\">".$meta["Prenom_candidat_2"]."</Field>
		  <Field Name=\"DCSScrutin\">".$meta["N_scrutin"]."</Field>
		  <Field Name=\"DCSCirconscription\">".$meta["Nom_circonscription"]."</Field>
		  <Field Name=\"DCSAffaireId\">".$meta["Numero_affaire"]."</Field>
		  <Field Name=\"DCSDepartement\">".$meta["N_INSEE_departement"]."</Field>
		  <Field Name=\"DCSRegion\">".$meta["N_INSEE_Region"]."</Field>
		  <Field Name=\"DCSElection\">".$meta["Election"]."</Field>
		  <Field Name=\"DCSElectionType\">".$meta["Type_election"]."</Field>
		  <Field Name=\"DCSElectionAnnee\">".$meta["Annee_election"]."</Field>
		  <Field Name=\"DCSScrutinContentieux\">".$meta["Scrutin_contentieux"]."</Field>
		  <Field Name=\"DCSRapporteur\">".$meta["Rapporteur"]."</Field>
		  <Field Name=\"DCSRapporteurId\">".$meta["N_rapporteur"]."</Field>
		  <Field Name=\"DCSMembreCommission\">".$meta["Membre_commission"]."</Field>
		  <Field Name=\"DCSSuiviPar\" UserType=\"True\">".$meta["Suivi_par"]."</Field>
		  <Field Name=\"DCSGEDChrono\">".$meta["Chrono_GED"]."</Field>
		  <Field Name=\"DCSDateJour\" DateTime=\"True\">".$meta["Date_jour"]."</Field>
		  <Field Name=\"DCSEtiquette\">".$meta["Etiquette_politique"]."</Field>
		  <Field Name=\"DCSNuance\">".$meta["Nuance_politique"]."</Field>
		  <Field Name=\"DCSParti\">".$meta["Parti_politique"]."</Field>
		  <Field Name=\"DCSPartiId\">".$meta["N_parti"]."</Field>
		  <Field Name=\"DCSMandataireType\">".$meta["Type_mandataire"]."</Field>
		  <Field Name=\"DCSMandataire\">".$meta["Nom_mandataire"]."</Field>
		  <Field Name=\"DCSAssociation\">".$meta["N_association_financement"]."</Field>
		  <Field Name=\"DCSExpertComptableId\">".$meta["N_expert_comptable"]."</Field>
		  <Field Name=\"DCSExpertComptable\">".$meta["Nom_cabinet_expertise_comptable"]."</Field>
		  <Field Name=\"DCSElu\">".$meta["Elu"]."</Field>
		  <Field Name=\"DCSSortant\">".$meta["Sortant"]."</Field>
                  "; 
                  
          if ($this->environnement == 'RECETTE' OR $this->environnement == 'PREPROD')
          {
              $xml_document .= "<Field Name=\"Remboursable\">".$meta["remboursement"]."</Field>";
          }
          else
          {
              $xml_document .= "<Field Name=\"DCSRemboursable\">".$meta["remboursement"]."</Field>";
          }

          $xml_document .= "
		  <Field Name=\"DCSSens\">".$meta["Sens_decision"]."</Field>
		  <Field Name=\"DCSSignalee\">".$meta["Decision_signalee"]."</Field>
		  <Field Name=\"DCSJurisprudence\">False</Field>
		  <Field Name=\"DCSExJurisprudence\">False</Field>
		  <Field Name=\"DCSMotsClesIndex\" IsTaxo=\"True\">".$meta["Mot_cles_indexeurs"]."</Field>
		  <Field Name=\"DCSCommentaires\">".$meta["Commentaires"]."</Field>
		  <Field Name=\"DCSAReviser\">".$meta["A_reviser"]."</Field>
		</File>";
		
		return $xml_document;
	}
	
	public function ecrire_document_xml($fp,$meta)
	{
		$xml_document = "";
		
		switch ($meta["type_document"])
		{
			case "decision" :
			case "recours" :
			case "modificative" :
				$xml_document = $this->Get_document_xml_decision($meta);
				break;
			case "requete" :
				$xml_document = $this->Get_document_xml_requete($meta);
				break;
			case "memoire" :
				$xml_document = $this->Get_document_xml_memoire($meta);
				break;
			case "jugement" :
				$xml_document = $this->Get_document_xml_jugement($meta);
				break;
		}
		
		fwrite($fp, $xml_document);
	}
	
	public function ecrire_file_csv($array, $file_path)
	{
		$fp = fopen($file_path, 'w');
		
		if(count($array)>0)
		{
			$meta = $this->ecrire_document_csv_header($fp, $array[0]);
			fputcsv($fp, $meta,";");
		}
		
		foreach ($array as $fields)
		{
			$meta = $this->ecrire_document_csv($fp, $fields);
			fputcsv($fp, $meta,";");
		}

		fclose($fp);
	}
	
	public function ecrire_document_csv($fp, $meta)
	{
		switch ($meta["type_document"])
		{
			case "decision" :
			case "recours" :
			case "modificative" :
				$csv_document = $this->Get_document_csv_decision($meta);
				break;
			case "requete" :
				$csv_document = $this->Get_document_csv_requete($meta);
				break;
			case "memoire" :
				$csv_document = $this->Get_document_csv_memoire($meta);
				break;
			case "jugement" :
				$csv_document = $this->Get_document_csv_jugement($meta);
				break;
		}
		
		return $csv_document;
	}
	
	public function Get_document_csv_decision($meta)
	{
		return[
			"Path" => $meta["Path"],
			"DCSCandidatId" => $meta["N_candidat"],
			"DCSCandidatNom1" => utf8_decode($meta["Nom_candidat_1"]),
			"DCSCandidatPrenom1" => utf8_decode($meta["Prenom_candidat_1"]),
			"DCSCandidatNom2" => utf8_decode($meta["Nom_candidat_2"]),
			"DCSCandidatPrenom2" => utf8_decode($meta["Prenom_candidat_2"]),
			"DCSScrutin" => $meta["N_scrutin"],
			"DCSCirconscription" => utf8_decode($meta["Nom_circonscription"]),
			"DCSDepartement" => $meta["N_INSEE_departement"],
			"DCSRegion" => $meta["N_INSEE_Region"],
			"DCSElection" => utf8_decode($meta["Election"]),
			"DCSElectionType" => utf8_decode($meta["Type_election"]),
			"DCSElectionAnnee" => $meta["Annee_election"],
			"DCSScrutinContentieux" => $meta["Scrutin_contentieux"],
			"DCSRapporteur" => utf8_decode($meta["Rapporteur"]),
			"DCSRapporteurId" => $meta["N_rapporteur"],
			"DCSMembreCommission" => utf8_decode($meta["Membre_commission"]),
			"DCSSuiviPar" => utf8_decode($meta["Suivi_par"]),
			"DCSLettreId" => $meta["N_lettre"],
			"DCSGEDChrono" => $meta["Chrono_GED"],
			"DCSDate" => $meta["Date_decision"],
			"DCSDateJour" => $meta["Date_jour"],
			"DCSEtiquette" => utf8_decode($meta["Etiquette_politique"]),
			"DCSNuance" => utf8_decode($meta["Nuance_politique"]),
			"DCSParti" => utf8_decode($meta["Parti_politique"]),
			"DCSPartiId" => $meta["N_parti"],
			"DCSMandataireType" => utf8_decode($meta["Type_mandataire"]),
			"DCSMandataire" => utf8_decode($meta["Nom_mandataire"]),
			"DCSAssociation" => utf8_decode($meta["N_association_financement"]),
			"DCSExpertComptableId" => $meta["N_expert_comptable"],
			"DCSExpertComptable" => utf8_decode($meta["Nom_cabinet_expertise_comptable"]),
			"DCSElu" => $meta["Elu"],
			"DCSSortant" => $meta["Sortant"],
			"Remboursable" => $meta["remboursement"],
			"DCSSens" => $meta["Sens_decision"],
			"DCSSignalee" => $meta["Decision_signalee"],
			"DCSDoctrine" => $meta["Doctrine"],
			"DCSExDoctrine" => $meta["Ex_doctrine"],
			"DCSCasEspece" => $meta["Cas_espece"],
			"DCSMotsClesAuto" => ($meta["Mots_cles_automatiques"]!="")?$this->Adapte_mot_automatique_to_csv($meta["Mots_cles_automatiques"]):"",
			"DCSMotsClesIndex" => $meta["Mot_cles_indexeurs"],
			"DCSCommentaires" => $meta["Commentaires"],
			"DCSRemiseEnCause" => $meta["Decision_remise_cause"],
			"DCSAnonymisation" => $meta["Anonymisation"],
			"DCSAReviser" => $meta["A_reviser"]
		];
	}
	
	public function Adapte_mot_automatique_to_csv($mot_cle)
	{
		$arr = explode(";",$mot_cle);
		
		return utf8_decode(implode($arr,","));
	}
	
	public function Get_document_csv_jugement($meta)
	{
		return[
			"Path" => $meta["Path"],
			"DCSCandidatId" => $meta["N_candidat"],
			"DCSCandidatNom1" => utf8_decode($meta["Nom_candidat_1"]),
			"DCSCandidatPrenom1" => utf8_decode($meta["Prenom_candidat_1"]),
			"DCSCandidatNom2" => utf8_decode($meta["Nom_candidat_2"]),
			"DCSCandidatPrenom2" => utf8_decode($meta["Prenom_candidat_2"]),
			"DCSScrutin" => $meta["N_scrutin"],
			"DCSCirconscription" => utf8_decode($meta["Nom_circonscription"]),
			"DCSAffaireId" => $meta["Numero_affaire"],
			"DCSDepartement" => $meta["N_INSEE_departement"],
			"DCSRegion" => $meta["N_INSEE_Region"],
			"DCSElection" => utf8_decode($meta["Election"]),
			"DCSElectionType" => utf8_decode($meta["Type_election"]),
			"DCSElectionAnnee" => utf8_decode($meta["Annee_election"]),
			"DCSScrutinContentieux" => $meta["Scrutin_contentieux"],
			"DCSRapporteur" => utf8_decode($meta["Rapporteur"]),
			"DCSRapporteurId" => $meta["N_rapporteur"],
			"DCSMembreCommission" => utf8_decode($meta["Membre_commission"]),
			"DCSSuiviPar" => utf8_decode($meta["Suivi_par"]),
			"DCSLettreId" => $meta["N_lettre"],
			"DCSGEDChrono" => $meta["Chrono_GED"],
			"DCSDate" => $meta["Date_decision"],
			"DCSDateJour" => $meta["Date_jour"],
			"DCSEtiquette" => utf8_decode($meta["Etiquette_politique"]),
			"DCSNuance" => utf8_decode($meta["Nuance_politique"]),
			"DCSParti" => utf8_decode($meta["Parti_politique"]),
			"DCSPartiId" => $meta["N_parti"],
			"DCSMandataireType" => utf8_decode($meta["Type_mandataire"]),
			"DCSMandataire" => utf8_decode($meta["Nom_mandataire"]),
			"DCSAssociation" => utf8_decode($meta["N_association_financement"]),
			"DCSExpertComptableId" => $meta["N_expert_comptable"],
			"DCSExpertComptable" => $meta["Nom_cabinet_expertise_comptable"],
			"DCSElu" => $meta["Elu"],
			"DCSSortant" => $meta["Sortant"],
			"Remboursable" => $meta["remboursement"],
			"DCSSens" => utf8_decode($meta["Sens_decision"]),
			"DCSSignalee" => $meta["Decision_signalee"],
			"DCSJurisprudence" => "FALSE",
			"DCSExJurisprudence" => "FALSE",
			"DCSDoctrine" => $meta["Doctrine"],
			"DCSExDoctrine" => $meta["Ex_doctrine"],
			"DCSCasEspece" => $meta["Cas_espece"],
			"DCSMotsClesIndex" => $meta["Mot_cles_indexeurs"],
			"DCSCommentaires" => $meta["Commentaires"],
			"DCSAReviser" => $meta["A_reviser"]
		];
	}
	
	public function Get_document_csv_memoire($meta)
	{
		return[
			"Path" => $meta["Path"],
			"DCSCandidatId" => $meta["N_candidat"],
			"DCSCandidatNom1" => utf8_decode($meta["Nom_candidat_1"]),
			"DCSCandidatPrenom1" => utf8_decode($meta["Prenom_candidat_1"]),
			"DCSCandidatNom2" => utf8_decode($meta["Nom_candidat_2"]),
			"DCSCandidatPrenom2" => utf8_decode($meta["Prenom_candidat_2"]),
			"DCSScrutin" => $meta["N_scrutin"],
			"DCSCirconscription" => utf8_decode($meta["Nom_circonscription"]),
			"DCSAffaireId" => $meta["Numero_affaire"],
			"DCSDepartement" => $meta["N_INSEE_departement"],
			"DCSRegion" => $meta["N_INSEE_Region"],
			"DCSElection" => utf8_decode($meta["Election"]),
			"DCSElectionType" => utf8_decode($meta["Type_election"]),
			"DCSElectionAnnee" => $meta["Annee_election"],
			"DCSScrutinContentieux" => $meta["Scrutin_contentieux"],
			"DCSRapporteur" => utf8_decode($meta["Rapporteur"]),
			"DCSRapporteurId" => $meta["N_rapporteur"],
			"DCSMembreCommission" => utf8_decode($meta["Membre_commission"]),
			"DCSSuiviPar" => utf8_decode($meta["Suivi_par"]),
			"DCSLettreId" => $meta["N_lettre"],
			"DCSGEDChrono" => $meta["Chrono_GED"],
			"DCSDate" => $meta["Date_decision"],
			"DCSDateJour" => $meta["Date_jour"],
			"DCSEtiquette" => utf8_decode($meta["Etiquette_politique"]),
			"DCSNuance" => utf8_decode($meta["Nuance_politique"]),
			"DCSParti" => utf8_decode($meta["Parti_politique"]),
			"DCSPartiId" => $meta["N_parti"],
			"DCSMandataireType" => utf8_decode($meta["Type_mandataire"]),
			"DCSMandataire" => utf8_decode($meta["Nom_mandataire"]),
			"DCSAssociation" => utf8_decode($meta["N_association_financement"]),
			"DCSExpertComptableId" => $meta["N_expert_comptable"],
			"DCSExpertComptable" => utf8_decode($meta["Nom_cabinet_expertise_comptable"]),
			"DCSElu" => $meta["Elu"],
			"DCSSortant" => $meta["Sortant"],
			"Remboursable" => $meta["remboursement"],
			"DCSSens" => $meta["Sens_decision"],
			"DCSSignalee" => $meta["Decision_signalee"],
			"DCSJurisprudence" => "FALSE",
			"DCSExJurisprudence" => "FALSE",
			"DCSMotsClesIndex" => $meta["Mot_cles_indexeurs"],
			"DCSCommentaires" => $meta["Commentaires"],
			"DCSAReviser" => $meta["A_reviser"]
		];
	}
	
	public function Get_document_csv_requete($meta)
	{
		return[
			"Path" => $meta["Path"],
			"DCSCandidatId" => $meta["N_candidat"],
			"DCSCandidatNom1" => utf8_decode($meta["Nom_candidat_1"]),
			"DCSCandidatPrenom1" => utf8_decode($meta["Prenom_candidat_1"]),
			"DCSCandidatNom2" => utf8_decode($meta["Nom_candidat_2"]),
			"DCSCandidatPrenom2" => utf8_decode($meta["Prenom_candidat_2"]),
			"DCSScrutin" => $meta["N_scrutin"],
			"DCSCirconscription" => utf8_decode($meta["Nom_circonscription"]),
			"DCSAffaireId" => $meta["Numero_affaire"],
			"DCSDepartement" => $meta["N_INSEE_departement"],
			"DCSRegion" => $meta["N_INSEE_Region"],
			"DCSElection" => $meta["Election"],
			"DCSElectionType" => utf8_decode($meta["Type_election"]),
			"DCSElectionAnnee" => utf8_decode($meta["Annee_election"]),
			"DCSScrutinContentieux" => $meta["Scrutin_contentieux"],
			"DCSRapporteur" => utf8_decode($meta["Rapporteur"]),
			"DCSRapporteurId" => $meta["N_rapporteur"],
			"DCSMembreCommission" => utf8_decode($meta["Membre_commission"]),
			"DCSSuiviPar" => utf8_decode($meta["Suivi_par"]),
			"DCSLettreId" => $meta["N_lettre"],
			"DCSGEDChrono" => $meta["Chrono_GED"],
			"DCSDate" => $meta["Date_decision"],
			"DCSDateJour" => $meta["Date_jour"],
			"DCSEtiquette" => utf8_decode($meta["Etiquette_politique"]),
			"DCSNuance" => utf8_decode($meta["Nuance_politique"]),
			"DCSParti" => utf8_decode($meta["Parti_politique"]),
			"DCSPartiId" => $meta["N_parti"],
			"DCSMandataireType" => utf8_decode($meta["Type_mandataire"]),
			"DCSMandataire" => utf8_decode($meta["Nom_mandataire"]),
			"DCSAssociation" => utf8_decode($meta["N_association_financement"]),
			"DCSExpertComptableId" => $meta["N_expert_comptable"],
			"DCSExpertComptable" => utf8_decode($meta["Nom_cabinet_expertise_comptable"]),
			"DCSElu" => $meta["Elu"],
			"DCSSortant" => $meta["Sortant"],
			"Remboursable" => $meta["remboursement"],
			"DCSSens" => $meta["Sens_decision"],
			"DCSSignalee" => $meta["Decision_signalee"],
			"DCSJurisprudence" => "FALSE",
			"DCSExJurisprudence" => "FALSE",
			"DCSMotsClesIndex" => $meta["Mot_cles_indexeurs"],
			"DCSCommentaires" => $meta["Commentaires"],
			"DCSAReviser" => $meta["A_reviser"]
		];  
	}
	
	public function ecrire_document_csv_header($fp, $meta)
	{
		switch ($meta["type_document"])
		{
			case "decision" :
			case "recours" :
			case "modificative" :
				$csv_document = $this->Get_document_csv_decision_header($meta);
				break;
			case "requete" :
				$csv_document = $this->Get_document_csv_requete_header($meta);
				break;
			case "memoire" :
				$csv_document = $this->Get_document_csv_memoire_header($meta);
				break;
			case "jugement" :
				$csv_document = $this->Get_document_csv_jugement_header($meta);
				break;
		}
		
		return $csv_document;
	}
	
	public function Get_document_csv_decision_header($meta)
	{
		return[
			"Path" => "Path",
			"DCSCandidatId" => "DCSCandidatId",
			"DCSCandidatNom1" => "DCSCandidatNom1",
			"DCSCandidatPrenom1" => "DCSCandidatPrenom1",
			"DCSCandidatNom2" => "DCSCandidatNom2",
			"DCSCandidatPrenom2" => "DCSCandidatPrenom2",
			"DCSScrutin" => "DCSScrutin",
			"DCSCirconscription" => "DCSCirconscription",
			"DCSDepartement" => "DCSDepartement",
			"DCSRegion" => "DCSRegion",
			"DCSElection" => "DCSElection",
			"DCSElectionType" => "DCSElectionType",
			"DCSElectionAnnee" => "DCSElectionAnnee",
			"DCSScrutinContentieux" => "DCSScrutinContentieux",
			"DCSRapporteur" => "DCSRapporteur",
			"DCSRapporteurId" => "DCSRapporteurId",
			"DCSMembreCommission" => "DCSMembreCommission",
			"DCSSuiviPar" => "DCSSuiviPar",
			"DCSLettreId" => "DCSLettreId",
			"DCSGEDChrono" => "DCSGEDChrono",
			"DCSDate" => "DCSDate",
			"DCSDateJour" => "DCSDateJour",
			"DCSEtiquette" => "DCSEtiquette",
			"DCSNuance" => "DCSNuance",
			"DCSParti" => "DCSParti",
			"DCSPartiId" => "DCSPartiId",
			"DCSMandataireType" => "DCSMandataireType",
			"DCSMandataire" => "DCSMandataire",
			"DCSAssociation" => "DCSAssociation",
			"DCSExpertComptableId" => "DCSExpertComptableId",
			"DCSExpertComptable" => "DCSExpertComptable",
			"DCSElu" => "DCSElu",
			"DCSSortant" => "DCSSortant",
			"Remboursable" => "Remboursable",
			"DCSSens" => "DCSSens",
			"DCSSignalee" => "DCSSignalee",
			"DCSDoctrine" => "DCSDoctrine",
			"DCSExDoctrine" => "DCSExDoctrine",
			"DCSCasEspece" => "DCSCasEspece",
			"DCSMotsClesAuto" => "DCSMotsClesAuto",
			"DCSMotsClesIndex" => "DCSMotsClesIndex",
			"DCSCommentaires" => "DCSCommentaires",
			"DCSRemiseEnCause" => "DCSRemiseEnCause",
			"DCSAnonymisation" => "DCSAnonymisation",
			"DCSAReviser" => "DCSAReviser"
		];
	}
	
	public function Get_document_csv_jugement_header($meta)
	{
		return[
			"Path" => "Path",
			"DCSCandidatId" => "DCSCandidatId",
			"DCSCandidatNom1" => "DCSCandidatNom1",
			"DCSCandidatPrenom1" => "DCSCandidatPrenom1",
			"DCSCandidatNom2" => "DCSCandidatNom2",
			"DCSCandidatPrenom2" => "DCSCandidatPrenom2",
			"DCSScrutin" => "DCSScrutin",
			"DCSCirconscription" => "DCSCirconscription",
			"DCSAffaireId" => "DCSAffaireId",
			"DCSDepartement" => "DCSDepartement",
			"DCSRegion" => "DCSRegion",
			"DCSElection" => "DCSElection",
			"DCSElectionType" => "DCSElectionType",
			"DCSElectionAnnee" => "DCSElectionAnnee",
			"DCSScrutinContentieux" => "DCSScrutinContentieux",
			"DCSRapporteur" => "DCSRapporteur",
			"DCSRapporteurId" => "DCSRapporteurId",
			"DCSMembreCommission" => "DCSMembreCommission",
			"DCSSuiviPar" => "DCSSuiviPar",
			"DCSLettreId" => "DCSLettreId",
			"DCSGEDChrono" => "DCSGEDChrono",
			"DCSDate" => "DCSDate",
			"DCSDateJour" => "DCSDateJour",
			"DCSEtiquette" => "DCSEtiquette",
			"DCSNuance" => "DCSNuance",
			"DCSParti" => "DCSParti",
			"DCSPartiId" => "DCSPartiId",
			"DCSMandataireType" => "DCSMandataireType",
			"DCSMandataire" => "DCSMandataire",
			"DCSAssociation" => "DCSAssociation",
			"DCSExpertComptableId" => "DCSExpertComptableId",
			"DCSExpertComptable" => "DCSExpertComptable",
			"DCSElu" => "DCSElu",
			"DCSSortant" => "DCSSortant",
			"Remboursable" => "Remboursable",
			"DCSSens" => "DCSSens",
			"DCSJurisprudence" => "DCSJurisprudence",
			"DCSExJurisprudence" => "DCSExJurisprudence",
			"DCSDoctrine" => "DCSDoctrine",
			"DCSExDoctrine" => "DCSExDoctrine",
			"DCSCasEspece" => "DCSCasEspece",
			"DCSMotsClesIndex" => "DCSMotsClesIndex",
			"DCSCommentaires" => "DCSCommentaires",
			"DCSAReviser" => "DCSAReviser"
		];
	}
	
	public function Get_document_csv_memoire_header($meta)
	{
		return[
			"Path" => "Path",
			"DCSCandidatId" => "DCSCandidatId",
			"DCSCandidatNom1" => "DCSCandidatNom1",
			"DCSCandidatPrenom1" => "DCSCandidatPrenom1",
			"DCSCandidatNom2" => "DCSCandidatNom2",
			"DCSCandidatPrenom2" => "DCSCandidatPrenom2",
			"DCSScrutin" => "DCSScrutin",
			"DCSCirconscription" => "DCSCirconscription",
			"DCSAffaireId" => "DCSAffaireId",
			"DCSDepartement" => "DCSDepartement",
			"DCSRegion" => "DCSRegion",
			"DCSElection" => "DCSElection",
			"DCSElectionType" => "DCSElectionType",
			"DCSElectionAnnee" => "DCSElectionAnnee",
			"DCSScrutinContentieux" => "DCSScrutinContentieux",
			"DCSRapporteur" => "DCSRapporteur",
			"DCSRapporteurId" => "DCSRapporteurId",
			"DCSMembreCommission" => "DCSMembreCommission",
			"DCSSuiviPar" => "DCSSuiviPar",
			"DCSLettreId" => "DCSLettreId",
			"DCSGEDChrono" => "DCSGEDChrono",
			"DCSDate" => "DCSDate",
			"DCSDateJour" => "DCSDateJour",
			"DCSEtiquette" => "DCSEtiquette",
			"DCSNuance" => "DCSNuance",
			"DCSParti" => "DCSParti",
			"DCSPartiId" => "DCSPartiId",
			"DCSMandataireType" => "DCSMandataireType",
			"DCSMandataire" => "DCSMandataire",
			"DCSAssociation" => "DCSAssociation",
			"DCSExpertComptableId" => "DCSExpertComptableId",
			"DCSExpertComptable" => "DCSExpertComptable",
			"DCSElu" => "DCSElu",
			"DCSSortant" => "DCSSortant",
			"Remboursable" => "Remboursable",
			"DCSSens" => "DCSSens",
			"DCSSignalee" => "DCSSignalee",
			"DCSJurisprudence" => "DCSJurisprudence",
			"DCSExJurisprudence" => "DCSExJurisprudence",
			"DCSMotsClesIndex" => "DCSMotsClesIndex",
			"DCSCommentaires" => "DCSCommentaires",
			"DCSAReviser" => "DCSAReviser"
		];
	}
	
	public function Get_document_csv_requete_header($meta)
	{
		return[
			"Path" => "Path",
			"DCSCandidatId" => "DCSCandidatId",
			"DCSCandidatNom1" => "DCSCandidatNom1",
			"DCSCandidatPrenom1" => "DCSCandidatPrenom1",
			"DCSCandidatNom2" => "DCSCandidatNom2",
			"DCSCandidatPrenom2" => "DCSCandidatPrenom2",
			"DCSScrutin" => "DCSScrutin",
			"DCSCirconscription" => "DCSCirconscription",
			"DCSAffaireId" => "DCSAffaireId",
			"DCSDepartement" => "DCSDepartement",
			"DCSRegion" => "DCSRegion",
			"DCSElection" => "DCSElection",
			"DCSElectionType" => "DCSElectionType",
			"DCSElectionAnnee" => "DCSElectionAnnee",
			"DCSScrutinContentieux" => "DCSScrutinContentieux",
			"DCSRapporteur" => "DCSRapporteur",
			"DCSRapporteurId" => "DCSRapporteurId",
			"DCSMembreCommission" => "DCSMembreCommission",
			"DCSSuiviPar" => "DCSSuiviPar",
			"DCSLettreId" => "DCSLettreId",
			"DCSGEDChrono" => "DCSGEDChrono",
			"DCSDate" => "DCSDate",
			"DCSDateJour" => "DCSDateJour",
			"DCSEtiquette" => "DCSEtiquette",
			"DCSNuance" => "DCSNuance",
			"DCSParti" => "DCSParti",
			"DCSPartiId" => "DCSPartiId",
			"DCSMandataireType" => "DCSMandataireType",
			"DCSMandataire" => "DCSMandataire",
			"DCSAssociation" => "DCSAssociation",
			"DCSExpertComptableId" => "DCSExpertComptableId",
			"DCSExpertComptable" => "DCSExpertComptable",
			"DCSElu" => "DCSElu",
			"DCSSortant" => "DCSSortant",
			"Remboursable" => "Remboursable",
			"DCSSens" => "DCSSens",
			"DCSSignalee" => "DCSSignalee",
			"DCSJurisprudence" => "DCSJurisprudence",
			"DCSExJurisprudence" => "DCSExJurisprudence",
			"DCSMotsClesIndex" => "DCSMotsClesIndex",
			"DCSCommentaires" => "DCSCommentaires",
			"DCSAReviser" => "DCSAReviser"
		];  
	}
	
	public function get_ARRAY_DETAILS_ancien_application($document_ged, $type_document)
	{
		$row = null;
		
		if ($document_ged["ANNEE"] > 2011 or ($document_ged["ANNEE"] == 2011 and $document_ged["NUMCAND"] == 1880))
		{
			$sql = $this->get_sql_details_ancien_application_v2($document_ged,$type_document);
		}
		else
		{
			$sql = $this->get_sql_details_ancien_application_v1($document_ged,$type_document);
		}

		$req = odbc_exec($this->conn_ancien_appli, $sql);
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction get_ARRAY_DETAILS_ancien_application");
		}
		
		$nb = 0;
		$arrrow = [];
		
		while(odbc_fetch_row($req))
		{
			for($i=1; $i <= odbc_num_fields($req); $i++)
			{
				$fkey = odbc_field_name($req,$i);
				$value = utf8_encode(odbc_result($req,$i));
                $arrrow[$fkey] = $value;
			}
			break;
		}
		
		if(count($arrrow)>0)
		{
			$row = $arrrow;
		}
		
		if($row !=null)
		{
			if($row["AnElection"] != "")
			{
				if(strlen($row["AnElection"]) != 4)
				{
					$row["AnElection"] = $this->document_param["annee"];
				}
			}
			else
			{
				$row["AnElection"] = $this->document_param["annee"];
			}

			// Retirer les 'chevrons' dans le champs RSExpert  (cf. nom expert comptable)
            if(!empty($row["RSExpert"]))
            {
                $row["RSExpert"] = str_replace("<", "", $row["RSExpert"]);
                $row["RSExpert"] = str_replace(">", "", $row["RSExpert"]);
            }
		}
		
		return $row;
	}
	
	public function get_DETAILS_ancien_application($document_ged, $type_document = "decision")
	{
	    $document_ged["ANNEE"] = $this->document_param["annee"];
	    $row = $this->get_ARRAY_DETAILS_ancien_application($document_ged, $type_document);
		
		return $row;
	}

    public function get_sql_details_ancien_application_v2($document_ged, $type_document)
	{
		$NUMCAND = $document_ged["NUMCAND"];
		$annee_scrutin = ($document_ged["ANNEE"] !=" ")?$document_ged["ANNEE"]:$this->document_param["annee"];
		$eviter_recours = " AND TRES.recours = '0'";
		$WHERE = "";
		
		if($document_ged["TYPE_LETTRE"] == "X")
		{
			$WHERE = " AND TRES.recours = '1' AND LEN(TRES.datepasscomm) = 10";
			$eviter_recours = "";
		}
		
		$sql = "SELECT top 1
				TCR.NoScrutin
				,CONVERT(VARCHAR,CAST(TCR.DatePassCCFP AS DATETIME),110) as datee
				, TCR.NumCand
				, TCR.NomCand
				, TCR.PrenomCand
				, TCR.Pct1T
				, TCR.particule
				, TRAPPG.CodeDecision as id_decision
				, TS.Election
				, TS.TypeElection
				, TS.AnElection
				, TCR.PartiPol
				, PP.nom_parti
				, NomCircons
				, NoDepart
				, Date1T
				, Date2T
				, CtxInit
				, Present2Tour
				, Elu
				, NuanceCand
				, TCR.DatePassCCFP
				, TRES.numcand AS numreserve
				, TRES.id_id
				, TRES.datepasscomm
				, CONVERT(VARCHAR,CAST(TCR.DatePassCCFP AS DATETIME),110) as DatePassCCFP_candidat
				, CONVERT(VARCHAR,CAST(TS.DatePassComm AS DATETIME),110) as DatePassCCFP_scrutin
				, CONVERT(VARCHAR,CAST(TRES.datepasscomm AS DATETIME),110) as DatePassCCFP_reserver
				, TRES.recours
				, R.*
				, DEP.NoDep
				, DEP.CodeDepart
				, DEP.NomDepart
				, DEP.CodeRegion
				, TELEC.LibelleElections
				, TTELEC.LibelleTypeElection
				, MAND.CivMand
				, MAND.NomMand
				, ASS.NomAsso
				, MAND.PrenomMand
				, EXPR.NoExpert
				, EXPR.RSExpert
				, RAPG.NomRap as rappg_nom
				, RAPG.PrenomRap as rappg_prenom
				, RAP.NomRap as rapp_nom
				, RAP.PrenomRap as rapp_prenom
				, RAP.CodeRap as rappid
				, FILIERE_DH.num_filiere
				, FILIERE_DH.nom as cm_nom
				, FILIERE_DH.prenom as cm_prenom
				, DECTYPE.LibelleTypeDecision
				, DECTYPE.AbregeTypeDecision
			FROM
				BD_ELEC_".$annee_scrutin.".dbo.Table_Candidat_Rectifie AS TCR
					LEFT JOIN
					WEB_CONST.dbo.partis_politiques_N AS PP
					ON 
					(PP.id_spp = TCR.PartiPol AND TCR.PartiPol<>0 )
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Expert_Rectifie AS EXPR
					ON 
					(EXPR.NumCand = TCR.NumCand)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Mandataire_Rectifie AS MAND
					ON
					(MAND.NumCand=TCR.NumCand)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Association_Rectifie AS ASS
					ON
					(ASS.NumCand=TCR.NumCand)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Scrutin AS TS
					ON
					(TCR.NoScrutin = TS.NoScrutin)
					LEFT JOIN
					WRAPP.dbo.Table_Rapporteur AS RAPG
					ON
					(TS.RappG = RAPG.CodeRap)
					LEFT JOIN
					WRAPP.dbo.Table_Rapporteur AS RAP
					ON
					(TS.Rapp = RAP.CodeRap)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Rapport_RappG AS TRAPPG
					ON
					(TCR.NumCand = TRAPPG.NoCandidat)
					LEFT JOIN
					WEB_CONST.dbo.Table_TypeDecision AS DECTYPE
					ON
					(TRAPPG.CodeDecision = DECTYPE.CodeTypeDecision)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.comptereserve AS TRES
					ON
					(TCR.NumCand = TRES.numcand ".$eviter_recours.")
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.nouveaux_recours AS R
					ON
					(TRES.id_id = R.id_recours) 
					LEFT JOIN
					WEB_CONST.dbo.Table_Departement AS DEP
					ON
					(TS.NoDepart = DEP.NoDep) 
					LEFT JOIN
					WEB_CONST.dbo.Table_Elections AS TELEC
					ON
					(TS.Election = TELEC.NoElections) 
					LEFT JOIN
					WEB_CONST.dbo.Table_TypeElections AS TTELEC
					ON
					(TS.TypeElection = TTELEC.TypeElection)
					INNER JOIN [WRAPP].[dbo].[filieres_DH] AS FILIERE_DH ON (TS.RappG = FILIERE_DH.num_filiere)
			WHERE
				TRAPPG.CodeDecision >= '0' AND
				TCR.NumCand IS NOT NULL and TCR.NumCand=".$NUMCAND."
				AND TCR.DatePassCCFP is null --ON RECHERCHE LES 344 CANDIDATS IGNORES PARCE QUE TCR.DatePassCCFP NULLE
				AND (--Filtrer une partie des doublons, et surtout déterminer le bon cm aumoment de l'élection
						(-- Cas où ((date debut et date fin renseignées) OU (date debut = 0 et date fin renseignee))
							FILIERE_DH.Date_debut <= CAST(SUBSTRING(TS.DatePassComm,7,4)+SUBSTRING(TS.DatePassComm,4,2)+SUBSTRING(TS.DatePassComm,1,2) AS BIGINT)
							AND
							CAST(SUBSTRING(TS.DatePassComm,7,4)+SUBSTRING(TS.DatePassComm,4,2)+SUBSTRING(TS.DatePassComm,1,2) AS BIGINT) <= FILIERE_DH.Date_fin
						)
						OR
						(-- Cas où date debut renseignée et date fin nulle
							CAST(SUBSTRING(TS.DatePassComm,7,4)+SUBSTRING(TS.DatePassComm,4,2)+SUBSTRING(TS.DatePassComm,1,2) AS BIGINT) >= FILIERE_DH.Date_debut
							AND
							FILIERE_DH.Date_fin is null
						)
					)
			";
				
			return $sql;
	}
	
	public function get_sql_details_ancien_application_v1($document_ged,$type_document)
	{
		$NUMCAND = $document_ged["NUMCAND"];
		$annee_scrutin = ($document_ged["ANNEE"] != "")?$document_ged["ANNEE"]:$this->document_param["annee"];
		$eviter_recours = " AND TRES.recours = '0'";
		$WHERE = "";
		
		if($type_document=="recours")
		{
			$WHERE = " AND TRES.recours = '1' AND LEN(TRES.datepasscomm) = 10";
			$eviter_recours = "";
		}
		
		$sql = "SELECT top 1
				TCR.NoScrutin
				,CONVERT(VARCHAR,CAST(TCR.DatePassCCFP AS DATETIME),110) as datee
				, TCR.NumCand
				, TCR.NomCand
				, TCR.PrenomCand
				, TCR.Pct1T
				, TCR.particule
				, TRAPPG.CodeDecision as id_decision
				, TS.Election
				, TS.TypeElection
				, TS.AnElection
				, TCR.PartiPol
				, PP.nom_parti
				, NomCircons
				, NoDepart
				, Date1T
				, Date2T
				, CtxInit
				, Present2Tour
				, Elu
				, NuanceCand
				, TCR.DatePassCCFP
				, CONVERT(VARCHAR,CAST(TCR.DatePassCCFP AS DATETIME),110) as DatePassCCFP_candidat
				, CONVERT(VARCHAR,CAST(TS.DatePassComm AS DATETIME),110) as DatePassCCFP_scrutin
				, CONVERT(VARCHAR,CAST(TRES.datepasscomm AS DATETIME),110) as DatePassCCFP_reserver
				, TRES.numcand AS numreserve
				, TRES.id_id
				, TRES.datepasscomm
				, TRES.recours
				, R.*
				, DEP.NoDep
				, DEP.CodeDepart
				, DEP.NomDepart
				, DEP.CodeRegion
				, TELEC.LibelleElections
				, TTELEC.LibelleTypeElection
				, MAND.CivMand
				, MAND.NomMand
				, ASS.NomAsso
				, MAND.PrenomMand
				, EXPR.NoExpert
				, EXPR.RSExpert
				, RAPG.NomRap as rappg_nom
				, RAPG.PrenomRap as rappg_prenom
				, RAP.NomRap as rapp_nom
				, RAP.PrenomRap as rapp_prenom
				, RAP.CodeRap as rappid
				, FILIERE_DH.num_filiere
				, FILIERE_DH.nom as cm_nom
				, FILIERE_DH.prenom as cm_prenom
				, DECTYPE.LibelleTypeDecision
				, DECTYPE.AbregeTypeDecision
			FROM
				BD_ELEC_".$annee_scrutin.".dbo.Table_Candidat_Rectifie AS TCR
					LEFT JOIN
					WEB_CONST.dbo.partis_politiques_N AS PP
					ON 
					(PP.id_spp = TCR.PartiPol AND TCR.PartiPol<>0 )
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Expert_Rectifie AS EXPR
					ON 
					(EXPR.NumCand = TCR.NumCand)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Mandataire_Rectifie AS MAND
					ON
					(MAND.NumCand=TCR.NumCand)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Association_Rectifie AS ASS
					ON
					(ASS.NumCand=TCR.NumCand)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Scrutin AS TS
					ON
					(TCR.NoScrutin = TS.NoScrutin)
					LEFT JOIN
					WRAPP.dbo.Table_Rapporteur AS RAPG
					ON
					(TS.RappG = RAPG.CodeRap)
					LEFT JOIN
					WRAPP.dbo.Table_Rapporteur AS RAP
					ON
					(TS.Rapp = RAP.CodeRap)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.Table_Rapport_RappG AS TRAPPG
					ON
					(TCR.NumCand = TRAPPG.NoCandidat)
					LEFT JOIN
					WEB_CONST.dbo.Table_TypeDecision AS DECTYPE
					ON
					(TRAPPG.CodeDecision = DECTYPE.CodeTypeDecision)
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.comptereserve AS TRES
					ON
					(TCR.NumCand = TRES.numcand ".$eviter_recours.")
					LEFT JOIN
					BD_ELEC_".$annee_scrutin.".dbo.nouveaux_recours AS R
					ON
					(TRES.id_id = R.id_recours) 
					LEFT JOIN
					WEB_CONST.dbo.Table_Departement AS DEP
					ON
					(TS.NoDepart = DEP.NoDep) 
					LEFT JOIN
					WEB_CONST.dbo.Table_Elections AS TELEC
					ON
					(TS.Election = TELEC.NoElections) 
					LEFT JOIN
					WEB_CONST.dbo.Table_TypeElections AS TTELEC
					ON
					(TS.TypeElection = TTELEC.TypeElection) 
					INNER JOIN [WRAPP].[dbo].[filieres_DH] AS FILIERE_DH ON (TS.RappG = FILIERE_DH.num_filiere)
					WHERE
						TRAPPG.CodeDecision >= '0' AND
						TCR.NumCand IS NOT NULL and TCR.NumCand=".$NUMCAND."
						AND TCR.DatePassCCFP is null --ON RECHERCHE LES 344 CANDIDATS IGNORES PARCE QUE TCR.DatePassCCFP NULLE
						AND (--Filtrer une partie des doublons, et surtout déterminer le bon cm aumoment de l'élection
						(-- Cas où ((date debut et date fin renseignées) OU (date debut = 0 et date fin renseignee))
							FILIERE_DH.Date_debut <= CAST(SUBSTRING(TS.DatePassComm,7,4)+SUBSTRING(TS.DatePassComm,4,2)+SUBSTRING(TS.DatePassComm,1,2) AS BIGINT)
							AND
							CAST(SUBSTRING(TS.DatePassComm,7,4)+SUBSTRING(TS.DatePassComm,4,2)+SUBSTRING(TS.DatePassComm,1,2) AS BIGINT) <= FILIERE_DH.Date_fin
						)
						OR
						(-- Cas où date debut renseignée et date fin nulle
							CAST(SUBSTRING(TS.DatePassComm,7,4)+SUBSTRING(TS.DatePassComm,4,2)+SUBSTRING(TS.DatePassComm,1,2) AS BIGINT) >= FILIERE_DH.Date_debut
							AND
							FILIERE_DH.Date_fin is null
						)
					)
					";

			return $sql;
	}
	
	public function Get_mot_cle_automatiques_v1($document_ged, $DETAIL, $type_document)
	{
		$Table_ConsiderantType = $this->Get_table_considerantType_ancien($document_ged, $DETAIL, $type_document);
		
		if($type_document == "decision")
		{
            $sql = "SELECT DISTINCT CONSTYPE.MotCleIndexeur 
            FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.Table_Candidat_Rectifie AS TCR 
            LEFT JOIN BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG as RappG ON (RappG.NumCand = TCR.NumCand)
            LEFT JOIN BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG_ordre AS CONSRAP ON (CONSRAP.NumCand = TCR.NumCand)
            LEFT join ".$Table_ConsiderantType." as CONSTYPE ON (CONSTYPE.NoConsiderant = RappG.NoConsiderant)
            where  TCR.NumCand='".$DETAIL["NumCand"]."' and MotCleIndexeur is not null ;";

			///// Logguer quand il manque un considérant en base /////

            //Définition requete sur table considerants_RappG : requete1
			$requeteSqlNbConsiderantsDansRappG = "SELECT NoConsiderant
											   FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG
											   WHERE NumCand=".$DETAIL["NumCand"];

            //Définition requete sur la bonne webconst : requete2
			$requeteSqlNbConsiderantsDansWebConst = "SELECT NoConsiderant
											      FROM ". $Table_ConsiderantType."
												  WHERE NoConsiderant IN (
																		SELECT NoConsiderant
																		FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG
																		WHERE NumCand = ".$DETAIL["NumCand"]."
																	   );";

			//Execution requete1
            $resultatNbConsiderantsDansRappG = odbc_exec($this->conn_ancien_appli, $requeteSqlNbConsiderantsDansRappG);

            //Verification que requete1 OK
            if ($resultatNbConsiderantsDansRappG === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_mot_cle_automatiques_v1");
            }

            //Comptage du nombre de considérants ramenés par requete1
            $nbResultatsDansRappG = odbc_num_rows($resultatNbConsiderantsDansRappG);

            //Execution requete2
			$resultatNbConsiderantsDansWebConst = odbc_exec($this->conn_ancien_appli, $requeteSqlNbConsiderantsDansWebConst);

            //Verification que requete2 OK
            if ($resultatNbConsiderantsDansWebConst === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_mot_cle_automatiques_v1");
            }

            //Comptage du nombre de considérants ramenés par requete2
            $nbResultatsDansWebConst = odbc_num_rows($resultatNbConsiderantsDansWebConst);

			//Si pas d'égalité dans le nombre de considérants, on loggue une erreur
			if ($nbResultatsDansRappG !== $nbResultatsDansWebConst)
			{
				//Mettre les resultats de la requete1 dans un array
				$tableauNumConsiderantFromRappG = [];

				while(odbc_fetch_row($resultatNbConsiderantsDansRappG))
				{
					$numConsiderant = utf8_encode(odbc_result($resultatNbConsiderantsDansRappG,'NoConsiderant'));

					array_push($tableauNumConsiderantFromRappG, $numConsiderant);
				}

				//Mettre les resultats de la deuxieme requete dans un array

				$tableauNumConsiderantFromWebConst = [];

				while(odbc_fetch_row($resultatNbConsiderantsDansWebConst))
				{
					$numConsiderant2 = utf8_encode(odbc_result($resultatNbConsiderantsDansWebConst,'NoConsiderant'));

					array_push($tableauNumConsiderantFromWebConst, $numConsiderant2);
				}

				//Détecter les considérants présents dans "RappG" mais absents dans "WebConst" et les mettre dans un tableau
				$considerantsAbsentsDansWebConst = array_diff($tableauNumConsiderantFromRappG, $tableauNumConsiderantFromWebConst);

				//Mettre les considérants absents dans un String avec ; en séparateur
				$considerantsAbsentsDansWebConstFormatString = implode(";", $considerantsAbsentsDansWebConst);

				//Logguer un warning dans la table z_depot_traitement_message
				$this->Ecrire_log_traitement_message("warning","","Le(s) considérant(s) suivant(s) sont absents de la webconst ".$Table_ConsiderantType." : ".$considerantsAbsentsDansWebConstFormatString." pour le NUMCAND : ".$DETAIL['NumCand']);

			}//Fin du test d'égalité

            ///// FIN DU LOG /////
		}
		else
		{
			$sql = "SELECT DISTINCT CONSTYPE.MotCleIndexeur 
			FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.Table_Candidat_Rectifie AS TCR 
			LEFT JOIN BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_Recours as RappG ON (RappG.NumCand = TCR.NumCand)
			LEFT JOIN BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_Recours_ordre AS CONSRAP ON (CONSRAP.NumCand = TCR.NumCand)
			LEFT join WEB_CONST.dbo.Table_ConsiderantType as CONSTYPE ON (CONSTYPE.NoConsiderant = RappG.NoConsiderant)
			where  TCR.NumCand='".$DETAIL["NumCand"]."' and MotCleIndexeur is not null ;";

            ///// Logguer quand il manque un considérant en base /////

            //Définition requete sur table considerants_RappG : requete1
            $requeteSqlNbConsiderantsDansRappG = "SELECT NoConsiderant
											   FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG
											   WHERE NumCand=".$DETAIL["NumCand"];

            //Définition requete sur la bonne webconst : requete2
            $requeteSqlNbConsiderantsDansWebConst = "SELECT NoConsiderant
											      FROM WEB_CONST.dbo.Table_ConsiderantType
												  WHERE NoConsiderant IN (
																		SELECT NoConsiderant
																		FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG
																		WHERE NumCand = ".$DETAIL["NumCand"]."
																	   );";

            //Execution requete1
            $resultatNbConsiderantsDansRappG = odbc_exec($this->conn_ancien_appli, $requeteSqlNbConsiderantsDansRappG);

            //Verification que requete1 OK
            if ($resultatNbConsiderantsDansRappG === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_mot_cle_automatiques_v1");
            }

            //Comptage du nombre de considérants ramenés par requete1
            $nbResultatsDansRappG = odbc_num_rows($resultatNbConsiderantsDansRappG);

            //Execution requete2
            $resultatNbConsiderantsDansWebConst = odbc_exec($this->conn_ancien_appli, $requeteSqlNbConsiderantsDansWebConst);

            //Verification que requete2 OK
            if ($resultatNbConsiderantsDansWebConst === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_mot_cle_automatiques_v1");
            }

            //Comptage du nombre de considérants ramenés par requete2
            $nbResultatsDansWebConst = odbc_num_rows($resultatNbConsiderantsDansWebConst);

            //Si pas d'égalité dans le nombre de considérants, on loggue une erreur
            if ($nbResultatsDansRappG !== $nbResultatsDansWebConst)
            {
                //Mettre les resultats de la requete1 dans un array
                $tableauNumConsiderantFromRappG = [];

                while(odbc_fetch_row($resultatNbConsiderantsDansRappG))
                {
                    $numConsiderant = utf8_encode(odbc_result($resultatNbConsiderantsDansRappG,'NoConsiderant'));

                    array_push($tableauNumConsiderantFromRappG, $numConsiderant);
                }

                //Mettre les resultats de la deuxieme requete dans un array

                $tableauNumConsiderantFromWebConst = [];

                while(odbc_fetch_row($resultatNbConsiderantsDansWebConst))
                {
                    $numConsiderant2 = utf8_encode(odbc_result($resultatNbConsiderantsDansWebConst,'NoConsiderant'));

                    array_push($tableauNumConsiderantFromWebConst, $numConsiderant2);
                }

                //Détecter les considérants présents dans "RappG" mais absents dans "WebConst" et les mettre dans un tableau
                $considerantsAbsentsDansWebConst = array_diff($tableauNumConsiderantFromRappG, $tableauNumConsiderantFromWebConst);

                //Mettre les considérants absents dans un String
                $considerantsAbsentsDansWebConstFormatString = implode(";", $considerantsAbsentsDansWebConst);

                //Logguer un warning dans la table z_depot_traitement_message
                $this->Ecrire_log_traitement_message("warning","","Le(s) considérant(s) suivant(s) sont absents de la webconst WEB_CONST.dbo.Table_ConsiderantType : ".$considerantsAbsentsDansWebConstFormatString." pour le NUMCAND : ".$DETAIL['NumCand']);
            }//Fin du test d'égalité

            ///// FIN DU LOG /////
		}
		
		return $sql;
	}
	
	public function Get_mot_cle_automatiques_v2($document_ged, $DETAIL, $type_document)
	{
		$Table_ConsiderantType = $this->Get_table_considerantType_ancien($document_ged, $DETAIL, $type_document);
		
		if($type_document == "decision")
		{
			$sql = "SELECT DISTINCT CONSTYPE.MotCleIndexeur 
				FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.Table_Candidat_Rectifie AS TCR 
				LEFT JOIN BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG as RappG ON (RappG.NumCand = TCR.NumCand)
				LEFT JOIN BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG_ordre AS CONSRAP ON (CONSRAP.NumCand = TCR.NumCand)
				LEFT join ".$Table_ConsiderantType." as CONSTYPE ON (CONSTYPE.NoConsiderant = RappG.NoConsiderant)
				where  TCR.NumCand='".$DETAIL["NumCand"]."' and MotCleIndexeur is not null ;";

            ///// Logguer quand il manque un considérant en base /////

            //Définition requete sur table considerants_RappG : requete1
            $requeteSqlNbConsiderantsDansRappG = "SELECT NoConsiderant
											   FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG
											   WHERE NumCand=".$DETAIL["NumCand"];

            //Définition requete sur la bonne webconst : requete2
            $requeteSqlNbConsiderantsDansWebConst = "SELECT NoConsiderant
											      FROM ". $Table_ConsiderantType."
												  WHERE NoConsiderant IN (
																		SELECT NoConsiderant
																		FROM BD_ELEC_".$DETAIL["AnElection"].".dbo.considerants_RappG
																		WHERE NumCand = ".$DETAIL["NumCand"]."
																	   );";

            //Execution requete1
            $resultatNbConsiderantsDansRappG = odbc_exec($this->conn_ancien_appli, $requeteSqlNbConsiderantsDansRappG);

            //Verification que requete1 OK
            if ($resultatNbConsiderantsDansRappG === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_mot_cle_automatiques_v2");
            }

            //Comptage du nombre de considérants ramenés par requete1
            $nbResultatsDansRappG = odbc_num_rows($resultatNbConsiderantsDansRappG);

            //Execution requete2
            $resultatNbConsiderantsDansWebConst = odbc_exec($this->conn_ancien_appli, $requeteSqlNbConsiderantsDansWebConst);

            //Verification que requete2 OK
            if ($resultatNbConsiderantsDansWebConst === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction Get_mot_cle_automatiques_v2");
            }

            //Comptage du nombre de considérants ramenés par requete2
            $nbResultatsDansWebConst = odbc_num_rows($resultatNbConsiderantsDansWebConst);

            //Si pas d'égalité dans le nombre de considérants, on loggue une erreur
            if ($nbResultatsDansRappG !== $nbResultatsDansWebConst)
            {
                //Mettre les resultats de la requete1 dans un array
                $tableauNumConsiderantFromRappG = [];

                while(odbc_fetch_row($resultatNbConsiderantsDansRappG))
                {
                    $numConsiderant = utf8_encode(odbc_result($resultatNbConsiderantsDansRappG,'NoConsiderant'));

                    array_push($tableauNumConsiderantFromRappG, $numConsiderant);
                }

                //Mettre les resultats de la deuxieme requete dans un array

                $tableauNumConsiderantFromWebConst = [];

                while(odbc_fetch_row($resultatNbConsiderantsDansWebConst))
                {
                    $numConsiderant2 = utf8_encode(odbc_result($resultatNbConsiderantsDansWebConst,'NoConsiderant'));

                    array_push($tableauNumConsiderantFromWebConst, $numConsiderant2);
                }

                //Détecter les considérants présents dans "RappG" mais absents dans "WebConst" et les mettre dans un tableau
                $considerantsAbsentsDansWebConst = array_diff($tableauNumConsiderantFromRappG, $tableauNumConsiderantFromWebConst);

                //Mettre les considérants absents dans un String
                $considerantsAbsentsDansWebConstFormatString = implode(";", $considerantsAbsentsDansWebConst);

                //Logguer un warning dans la table z_depot_traitement_message
                $this->Ecrire_log_traitement_message("warning","","Le(s) considérant(s) suivant(s) sont absents de la webconst ".$Table_ConsiderantType." : ".$considerantsAbsentsDansWebConstFormatString." pour le NUMCAND : ".$DETAIL['NumCand']);
            }//Fin du test d'égalité

            ///// FIN DU LOG /////
		}
		else
		{
			$sql = "";
		}
		
		return $sql;
	}
	
	public function convert_date_pass_comm($date_pass_com)
	{
		$text = $date_pass_com;
		$arr = explode("-", $date_pass_com);
		
		if(count($arr) == 3)
		{
			$month=$arr[0];
			$day=$arr[1];
			$year=$arr[2];
			$text = $year.$month.$day;
		}
		
		return $text;
	}
	
	public function Get_table_considerantType_ancien($document_ged, $DETAIL, $type_document)
	{
		$date_pass_com = $this->convert_date_pass_comm($this->Get_date_commission($DETAIL));
		$text = "WEB_CONST.dbo.Table_ConsiderantType";
		
		switch($DETAIL["AnElection"])
		{
			case "2010":
				$text = "WEB_CONST.dbo.Table_ConsiderantType_27042010";
				break;
			case "2011":
				if(intval($date_pass_com) < 20110512)
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_27042010";
				}
				else
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20110512";
				}
				break;
			case "2012":
				if(intval($date_pass_com) < 20120606)
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20110512";
				}
				else
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20120606";
				}
				break;
			case "2013":
				if(intval($date_pass_com) < 20120606)
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20120606";
				}
				else
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20120606";
				}
				break;
			case "2014":
				if(intval($date_pass_com) < 20140603)
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20120606";
				}
				elseif (intval($date_pass_com) < 20140710)
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20140603";	
				}
				else
				{
					$text = "WEB_CONST.dbo.Table_ConsiderantType_20140710";
				}
				break;
		}
		
		return $text;
	}
	
	public function Get_mots_cles_automatiques($document_ged, $DETAIL, $type_document)
	{
		$mot_cle = [];

		if($type_document == "requete" or $type_document == "memoire" or $type_document == "jugement")
		{
			return "";
		}
		else
		{
			if ($document_ged["ANNEE"] > 2011 or ($document_ged["ANNEE"] == 2011 and $document_ged["NUMCAND"] == 1880))
			{

				$sql = $this->Get_mot_cle_automatiques_v2($document_ged,$DETAIL,$type_document);
			}
			else
			{
				$sql = $this->Get_mot_cle_automatiques_v1($document_ged,$DETAIL,$type_document);
			}

			if($sql != "")
			{
				$req = odbc_exec($this->conn_ancien_appli, $sql);
				
				if ($req === false)
				{
                    $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la focntion Get_mots_cles_automatiques");
				}
				
				while(odbc_fetch_row($req))
				{
					$row = [];
					
					for($i=1; $i <= odbc_num_fields($req); $i++)
					{
						$fkey = odbc_field_name($req,$i);
						$value = utf8_encode(odbc_result($req,$i));
						$row[$fkey] = $value;
						
						if($fkey == "MotCleIndexeur")
						{
							if (!empty($value))
                            {
                                array_push($mot_cle, $value);
                            }
						}
					}
				}
			}
			
			$str_mot_cle = implode($mot_cle,";");
			$arr_mot_cle = explode(",", $str_mot_cle);

			return implode($arr_mot_cle,";");
		}
	}
	
	public function Get_date_commission($DETAIL)
	{
		$recours = $DETAIL["recours"];
		$DatePassCCFP_candidat = $DETAIL["DatePassCCFP_candidat"];
		$DatePassCCFP_scrutin = $DETAIL["DatePassCCFP_scrutin"];
		$DatePassCCFP_reserver = $DETAIL["DatePassCCFP_reserver"];
		$text = $DatePassCCFP_scrutin;
		
		if(strlen($recours)>0)
		{
			$text = $DatePassCCFP_reserver;
		}
		elseif ($DatePassCCFP_scrutin=="")
		{  
			$text = $DatePassCCFP_candidat;
		}
		if($text!="")
		{
			$pos = strpos($text, "/");
			
			if ($pos === true)
			{
				$arr = explode("/", $text);

				if(count($arr)==3)
				{
					$text = $arr[1]."-".$arr[0]."-".$arr[2];
				}
			} 
		}
		
		return $text;
	}
	
	public function Get_expert_comptable_ancien_appli($DETAIL)
	{
		$text = "";
		
		if(trim($DETAIL["RSExpert"]) == "" and trim($DETAIL["RSExpert"]) != "")
		{
			$text = $DETAIL["NomRepres"]." ".$DETAIL["PrenomRepres"];
		}
		else
		{
			$text = str_replace("&", "et", $DETAIL["RSExpert"]);
		}
		
		return trim($text);
	}
	
	public function Get_n_expert_comptable_ancien_appli($DETAIL)
	{
		$text="";
		
		if(trim($DETAIL["RSExpert"]) == "" and trim($DETAIL["RSExpert"]) == "")
		{
			$text = "";
		}
		else
		{
			$text = $DETAIL["NoExpert"];
		}
		
		return $text;
	}
	
	public function metadonnes_ancien_application($document_ged, $DETAILS, $type_document)
	{
		$DETAIL = $DETAILS;
		$DETAILS_DECISION = [];
		$DETAILS_DECISION_MOT_CLE = [];
		$DETAILS_DECISION_MOT_CLE["automatique"]=$this->Get_mots_cles_automatiques($document_ged, $DETAILS, $type_document);
		$DETAILS_DECISION_MOT_CLE["indexeur"]="";
		$DETAILS_DECISION["DATE_PASS_COM"]="";
		$DETAILS_DECISION["nom_decision_sens"]="";
		$DETAILS_DECISION["chk_signalee"]="False";
		$DETAILS_DECISION["chk_doctrine"]="False";
		$DETAILS_DECISION["chk_cas_d_espece"]="False";
		$DETAILS_DECISION["commentaire_signalement"]="";
		$DETAILS_DECISION["chk_anonymiser"]="False";
		$DETAILS_DECISION["chk_completer_theme"]="False";
		
		$meta = $this->get_metadonnees_vide();
		$meta["Annee_election"] = ($DETAIL["AnElection"]!="")?$DETAIL["AnElection"]:$this->document_param['annee'];
		$meta["Dossier"]= $this->Get_numero_dossier($document_ged);
		$meta["id_decision"]= $DETAIL["id_decision"];
		$meta["id_recours"]= $DETAIL["id_recours"];
		$meta["type_document"]= $type_document;
		$meta["DATE_CREATEDON"] = $document_ged["DATE_CREATEDON"];
		$meta["Type_de_contenu"]= $this->Get_type_contenu($type_document);
		$meta["N_candidat"] = $DETAIL["NumCand"];
		$meta["Nom_candidat_1"]=$DETAIL["NomCand"];
		$meta["Prenom_candidat_1"]=$DETAIL["PrenomCand"];
		$meta["Nom_candidat_2"]="";
		$meta["Prenom_candidat_2"]="";
		$meta["N_scrutin"]=$DETAIL["NoScrutin"]; //1ere étape pour $meta["N_scrutin"]
		$meta["N_scrutin"]=$this->Get_Numero_scrutin($meta); //2eme etape pour $meta["N_scrutin"]
		$meta["Nom_circonscription"]=$DETAIL["NomCircons"];
		$meta["Numero_affaire"]=preg_replace('#[^0-9a-z]+#i', '', $document_ged['DOSSIER']);
		$meta["N_INSEE_departement"]=$DETAIL["CodeDepart"];
		$meta["N_INSEE_Region"]= $DETAIL["CodeRegion"];
		$meta["Election"]= $this->Get_Election_name($DETAIL["LibelleElections"],$DETAIL["LibelleTypeElection"]);
		$meta["Type_election"] = $DETAIL["LibelleTypeElection"]=="partielle"?"Partielle":"Générale"; 
		$meta["Scrutin_contentieux"] = $DETAIL["CtxInit"]==1?"False":"True";
		$meta["Rapporteur"] = $DETAIL["rapp_prenom"]." ".$DETAIL["rapp_nom"];
		$meta["N_rapporteur"] = $DETAIL["rappid"];
		$meta["Membre_commission"] = $DETAIL["rappg_prenom"]." ".$DETAIL["rappg_nom"] ;
		$meta["Suivi_par"] = $this->Get_suivi_par($DETAIL["cm_prenom"]." ".$DETAIL["cm_nom"]) ;
		$meta["N_lettre"] = $document_ged['NOLETTRE'];
		$meta["Chrono_GED"] = $document_ged['CHRONO'];
		$meta["Date_decision"] = $this->Get_date_commission($DETAIL);
		$meta["Date_jour"] = ($document_ged['DATE_CCFPAFF']!="")?$document_ged['DATE_CCFPAFF']:$document_ged["DATE_CREATEDONAFF"];
		$meta["Date_base_CCFP"] = $document_ged['DATE_DEPART_RETOUR'];
		$meta["Etiquette_politique"] = "";
		$meta["Nuance_politique"] = $DETAIL["NuanceCand"];

		if ($meta["Annee_election"] == 2010 or $meta["Annee_election"] == 2011)
		{
            $meta["Parti_politique"] = '';
            $meta["N_parti"] = 0;
        }
		else
		{
		    $meta["Parti_politique"] = $DETAIL["nom_parti"];
            $meta["N_parti"] = $DETAIL["PartiPol"];
        }

		$meta["Type_mandataire"] = (trim($DETAIL["NomAsso"])!="")?"Association de financement électoral":"Personne physique";
		$meta["Nom_mandataire"] = str_replace("&", "et", $DETAIL["NomMand"]);
		$meta["N_association_financement"] = trim($DETAIL["NomAsso"]);
		$meta["N_association_financement"] = preg_replace("#&#", "et", $meta["N_association_financement"]); //Pour remplacer les & par des et
		$meta["N_expert_comptable"] = $this->Get_n_expert_comptable_ancien_appli($DETAIL);
		$meta["Nom_cabinet_expertise_comptable"] = $this->Get_expert_comptable_ancien_appli($DETAIL);
		$meta["Elu"] = $DETAIL["Elu"]==1?"False":"True";
		$meta["Sortant"] = "False"; // On met False en dur pour ancienne appli (idealement il faudrait laisser vide car pas géré par ancienne appli, mais le batch d'import veut un booleen)
		$meta["pourcentage_voix_1er_tour"] = $DETAIL["Pct1T"]/100;
		$meta["remboursement"] = $this->Get_remboursement($DETAIL["Pct1T"],$meta);
		$meta["Sens_decision"] = $DETAIL["AbregeTypeDecision"];
		$meta["Decision_signalee"] = $DETAILS_DECISION["chk_signalee"]==1?"True":"False";
		$meta["Doctrine"] = $DETAILS_DECISION["chk_doctrine"]==1?"True":"False";
		$meta["Ex_doctrine"] = "False";
		$meta["Cas_espece"] = $DETAILS_DECISION["chk_cas_d_espece"]==1?"True":"False";
		$meta["Jurisprudence"] = "False";
		$meta["Ex_jurisprudence"] = "False";
		$meta["Mots_cles_automatiques"] = $DETAILS_DECISION_MOT_CLE["automatique"];
		$meta["Mots_cles_automatiques"] = $this->Corriger_interet($meta["Mots_cles_automatiques"]);
		$meta["Mot_cles_indexeurs"] = $DETAILS_DECISION_MOT_CLE["indexeur"];
		$meta["Commentaires"] = $DETAILS_DECISION["commentaire_signalement"];
		$meta["Decision_remise_cause"] = "";
		$meta["Anonymisation"] = $DETAILS_DECISION["chk_anonymiser"]==1?"Oui":"Non";
		$meta["A_reviser"] = $DETAILS_DECISION["chk_completer_theme"]==1?"True":"False";
		
		return $meta;	
	}
	
	public function Controller_pre_requi($type)
	{
	  $flag = false;
	  $this->get_Traitements_Param($type);

	  if($this->document_param!=null)
	  {
		  if($this->document_param['dernier_date_traitement']!=null or $this->document_param['dernier_date_traitement']!="")
		  {
			  if($this->date_debut_traitement > $this->document_param['DERNIER_DATE_TRAITEMENT_SQL'])
			  {
				  $flag = true;
			  }
			  else
			  {
				$this->Stopper_traitement_decision("Le traitement a deja été exécuté pour la date ".$this->date_debut_traitement);  
			  }
		  }
		  else
		  {
			  $flag = true;
		  }
	  }
	  else
	  {
		  $this->Stopper_traitement_decision("Le document paramatre non trouvé");  
	  }

	  return $flag;
	}
	
	public function Stopper_traitement_decision($message = "")
	{
	    $this->Upsert_log_traitement("Traitement export quotidien","finiMaisErreurInattendue");
		$this->Ecrire_log_traitement_message("technique","",$message);
        $this->Couper_connections_aux_bases();
		exit(99); //Code different de 0 donc erreur
	}
	
	public function Initialiser_tous_fichier_DDRT()
	{
		try
        {
			$this->Initialiser_tous_fichier_DDRT_DECISION();
			$this->Initialiser_tous_fichier_DDRT_RECOURS();
			$this->Initialiser_tous_fichier_DDRT_MODIFICATIVE();
			$this->Initialiser_tous_fichier_DDRT_REQUETE();
			$this->Initialiser_tous_fichier_DDRT_JUGEMENT();
			$this->Initialiser_tous_fichier_DDRT_MEMOIRE();

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	
	public function Initialiser_tous_fichier_DDRT_DECISION()
	{
		try
        {
			$folder = $this->Get_repertoire_type_document("decision");
			$this->supprimer_tous_fichier_DDRT($folder);
            $from = $this->repertoireDepot."/".self::REPERTOIRE_MODELES_XML."/Metadonnees.xml";
			$dest = $folder."/".self::FILE_DECISION_XML;

			copy($from, $dest);

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	
	public function Initialiser_tous_fichier_DDRT_RECOURS()
	{
		try
        {
			$folder = $this->Get_repertoire_type_document("recours");
			$this->supprimer_tous_fichier_DDRT($folder);
            $from = $this->repertoireDepot."/".self::REPERTOIRE_MODELES_XML."/Metadonnees.xml";
			$dest = $folder."/".self::FILE_RECOURS_XML;

			copy($from, $dest);

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	public function Initialiser_tous_fichier_DDRT_MODIFICATIVE()
	{
		try
		{
			$folder = $this->Get_repertoire_type_document("modificative");
			$this->supprimer_tous_fichier_DDRT($folder);
            $from = $this->repertoireDepot."/".self::REPERTOIRE_MODELES_XML."/Metadonnees.xml";
			$dest = $folder."/".self::FILE_MODIFICATIVE_XML;

			copy($from, $dest);

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	
	public function Initialiser_tous_fichier_DDRT_REQUETE()
	{
		try
		{
			$folder = $this->Get_repertoire_type_document("requete");
			$this->supprimer_tous_fichier_DDRT($folder);
            $from = $this->repertoireDepot."/".self::REPERTOIRE_MODELES_XML."/Metadonnees.xml";
			$dest = $folder."/".self::FILE_REQUETE_XML;

			copy($from, $dest);

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	
	public function Initialiser_tous_fichier_DDRT_JUGEMENT()
	{
		try
		{
			$folder = $this->Get_repertoire_type_document("jugement");
			$this->supprimer_tous_fichier_DDRT($folder);
            $from = $this->repertoireDepot."/".self::REPERTOIRE_MODELES_XML."/Metadonnees.xml";
			$dest = $folder."/".self::FILE_JUGEMENT_XML;

			copy($from, $dest);

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	
	public function Initialiser_tous_fichier_DDRT_MEMOIRE()
	{
		try
		{
			$folder = $this->Get_repertoire_type_document("memoire");
			$this->supprimer_tous_fichier_DDRT($folder);
            $from = $this->repertoireDepot."/".self::REPERTOIRE_MODELES_XML."/Metadonnees.xml";
			$dest = $folder."/".self::FILE_MEMOIRE_XML;

			copy($from, $dest);

			return true;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	public function supprimer_tous_fichier_DDRT($folder)
	{
		try
		{
			array_map('unlink', glob($folder."/*.*"));
		}
		catch(Exception $e)
		{
            throw new Exception('Problème imprévu lors de la suppression des fichiers du dépot.');
		}
	}
	
	public function Get_type_document_nomage($type_document)
	{
		$text = "";
		
		switch ($type_document)
		{
			case "decision" :
				$text = "Decision";
				break;
			case "recours" :
				$text = "Decision_recours_gracieux";
				break;
			case "modificative" :
				$text = "Decision_modificative";
				break;
			case "requete" :
				$text = "Requete";
				break;
			case "memoire" :
				$text = "Memoire";
				break;
			case "jugement" :
				$text = "Jugement";
				break;
		}
		
		return $text;
	}
	
	public function Get_nom_prenom_nomage_odl($metadonnee)
	{
		$nom_candidat = $metadonnee["Nom_candidat_1"];
		$arr_nom_candidat = explode("-", $nom_candidat);
		
		if(count($arr_nom_candidat)>1)
		{
			$nom = strtoupper(substr($this->str_to_noaccent($arr_nom_candidat[0]),0,1))."-".strtoupper(substr($this->str_to_noaccent($arr_nom_candidat[1]),0,1));
		}
		else
		{
			$nom = strtoupper(substr($this->str_to_noaccent($nom_candidat),0,1));
		}

		$prenom = utf8_decode($metadonnee["Prenom_candidat_1"]);
		
		return $nom."_".$prenom ;
	}
	
	public function Get_nom_prenom_nomage($metadonnee)
	{
		$nom_candidat = $metadonnee["Prenom_candidat_1"];
		$arr_nom_candidat = explode("-", $nom_candidat);

		if(count($arr_nom_candidat)>1)
		{
			$prenom = strtoupper(substr($this->str_to_noaccent($arr_nom_candidat[0]),0,1))."-".strtoupper(substr($this->str_to_noaccent($arr_nom_candidat[1]),0,1));
		}
		else
		{
			$prenom = strtoupper(substr($this->str_to_noaccent($nom_candidat),0,1));
		}
		
		$nom = $this->str_to_noaccent($metadonnee["Nom_candidat_1"]);
		
		return $prenom."_".$nom ;
	}
	
	public function str_to_noaccent($str)
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
	
	public function Regles_nomage_fichier($original, $type_document, $metadonnee)
	{
		$Orginal = "";
		
		if($original)
		{
			$Orginal = "[ORIG]";
		}
		
		$documentType = $this->Get_type_document_nomage($type_document);
		$NomPrenom = $this->Get_nom_prenom_nomage($metadonnee);
		$IdCandidat = $this->Get_Numero_candidat($metadonnee);
		$DocumentNomage = $this->Get_document_nomage($type_document,$metadonnee);
		
		return $Orginal.$documentType."_".$NomPrenom."_".$IdCandidat."_".$DocumentNomage.".pdf";
	}
	
	public function Get_Numero_candidat($metadonnee)
	{
		$cand = $metadonnee["N_candidat"];
		$text = $cand;
		
		if(strlen($cand)<9)
		{
			$ann = $metadonnee["Annee_election"];
			$ann = $ann."00000";
			$text = $ann + $cand;
		}
		
		return $text;
	}
	
	public function Get_Numero_scrutin($metadonnee)
	{
		$numScrutin = $metadonnee["N_scrutin"];
		$text = $numScrutin;
		
		if(strlen($numScrutin)<9)
		{
			$ann = $metadonnee["Annee_election"];
			$ann = $ann."00000";
			$text = intval($ann) + intval($numScrutin);
		}
		
		return $text;
	}
	
	public function Get_document_nomage($type_document, $metadonnee)
	{
		$text = "";
		
		switch ($type_document)
		{
			case "decision" :
			case "recours" :
			case "modificative" :
				$text = $metadonnee["N_lettre"];
				break;
			case "requete" :
			case "jugement" :
				$text = $metadonnee["Dossier"];
				$text = preg_replace('#[^0-9a-z]+#i', '', $text);
				break;
			case "memoire" :
				$text = $metadonnee["Dossier"];
				$text = preg_replace('#[^0-9a-z]+#i', '', $text);
				$text = $text."_".$metadonnee["DATE_CREATEDON"];
				break;
		}
		
		return $text;
	}
	
	public function Get_repertoire_type_document($type_document)
	{
		$folder = "";
		
		switch ($type_document)
		{
			case "decision" :
				$folder = $this->repertoireDepot."/".self::REPERTOIRE_DECISION;
				break;
			case "recours" :
				$folder = $this->repertoireDepot."/".self::REPERTOIRE_RECOURS;
				break;
			case "modificative" :
				$folder = $this->repertoireDepot."/".self::REPERTOIRE_MODIFICATIVE;
				break;
			case "requete" :
				$folder = $this->repertoireDepot."/".self::REPERTOIRE_REQUETE;
				break;
			case "memoire" :
				$folder = $this->repertoireDepot."/".self::REPERTOIRE_MEMOIRE;
				break;
			case "jugement" :
				$folder = $this->repertoireDepot."/".self::REPERTOIRE_JUGEMENT;
				break;
		}
		
		return $folder;
	}

	//Fonction de creation du PDF IMAGE à partir d'images (TIFF surtout) référencées dans la GED
	public function Convertir_document_ged_image_to_pdf($document_ged, $type_document, $metadonnee, $file_name)
	{
		$path_partage_dossier_pdfs = $this->Get_repertoire_type_document($type_document);
		$chemin_fichier_pdf = $path_partage_dossier_pdfs."/".$file_name;
		$flag = false;
		$w = array(); // ce tableau reçoit chaque élément de la condition WHERE
		// id_candidat ne doit pas être nul.

		$w[] = "DOC.GUID = '".$document_ged["GUID"]."'"; // il y a toujours un DOC.GUID dans la GED. Donc ajout sans condition

        if($type_document != "memoire" and $type_document != "requete" and $type_document != "jugement")
        {
            $w[] = "FD_09A9C5FE IS NOT NULL";
            $w[] = "PageNo > 1";
        }

		if (count($w) > 1)
		{
			$WHERE = implode (" AND ", $w);
		}
		else
		{
			$WHERE = $w[0];
		}
		
		$sql = "select DOC.GUID
			, ActRevision
			, Deleted
			, FD_E7787B05 AS ANNEE
			, FD_09A9C5FE AS NUMCAND
			, LocationSubID
			, FD_3A5E7E76 AS NOLETTRE
			, CONVERT(VARCHAR,CAST(FD_34CFCD24 AS DATETIME),103) AS DATE_CCFP
			, CONVERT(VARCHAR,CAST(FD_7378028E AS DATETIME),103) AS DATE_SCAN
			, CONVERT(VARCHAR,CAST(DOC.CreatedOn AS DATETIME),103) AS DATE_CREATEDON
			, (CASE WHEN FD_C6004355 = '?' THEN '0' ELSE FD_C6004355 END) AS TYPE_LETTRE
			, CAST(FD_CC000D17 AS TEXT) AS NOM_EXPEDITEUR
			, PageNo AS PAGE
			, StorageRev AS DOSSIER_IMAGE
			, SourceFileName AS IMAGE 
			, DOC.Size AS TAILLE_DOC 
			, IMG.Size AS IMAGE_SIZE		
			FROM dbo.FD_Documents AS DOC
					LEFT JOIN
					dbo.FD_Images AS IMG
					ON
					(DOC.GUID = IMG.DocGUID)	
			where ".$WHERE." AND ActRevision = RevNo and Deleted <> '1'
			order by GUID, FD_3A5E7E76, FD_34CFCD24, PageNo";

//Debug dans fichier texte
// $ressourceFichierLogs = fopen('logs_pourExportPonctuelDes344.txt', 'a+');
// fwrite($ressourceFichierLogs, $sql);
// fclose($ressourceFichierLogs);
//die();

			$req = sqlsrv_query($this->conn_ged_utf8, $sql, array(), array("Scrollable"=>"buffered"));
			
			if ($req === false)
			{
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base GED dans la fonction Convertir_document_ged_image_to_pdf");
			}
			
			$nb = sqlsrv_num_rows($req);

			if ($nb == 0)
            {
                $pourMsgLoggueEnBase[0] = $document_ged["GUID"];
                $this->Ecrire_log_traitement_message("fonctionnel","7", $pourMsgLoggueEnBase);
            }

			$id_lettre = "";
			$i = 1; //compteur des images du GUID en cours de traitement
			$i_nb_lettres = 0;
			$GUID = "";
			$RES = "";
			
			$array_tifs_sources = array();
			$array_tifs_destinations = array();
			$array_pdfs_sources = array();
			$array_pdfs_destinations = array();
			$array_pdfs_tiff_destinations = array();
			
			$dossier_tif = $this->repertoireDepot."/z_tif";
			$dossier_pdf = $this->repertoireDepot."/z_pdf";

			//On boucle sur chacune des images récupérées pour le GUID en cours de traitement
			while($res = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				if($this->PDF_FILES)
				{
					if ($GUID != $res['GUID'])  //On rentre dedans pour la première image
					{ 
						if (count($array_tifs_sources) > 0) //On ne rentre visiblement jamais dedans
						{
							$this->creer_pdf_from_tifs($array_tifs_sources,$array_tifs_destinations,
							$array_pdfs_sources,$array_pdfs_destinations,$array_pdfs_tiff_destinations,
							$path_partage_dossier_pdfs,$chemin_fichier_pdf,$dossier_tif,$dossier_pdf);
							
							$array_tifs_sources = array();
							$array_tifs_destinations = array();
							
							array_map('unlink', glob($dossier_tif."*.TIF"));
							array_map('unlink', glob($dossier_tif."*.JPG"));
							
							$i_nb_lettres++;
						}
						
						$GUID = $res['GUID'];
					}	
					
					$extension_source_recup = substr($res['IMAGE'],-3,3);
					
					if ($extension_source_recup == "TIF" or $extension_source_recup == "tif" or $extension_source_recup == "pdf" or $extension_source_recup == "PDF" or $extension_source_recup == "JPG" or $extension_source_recup == "jpg") //Ajout JPEG le 15/05/2018 (par EA)
					{
						$pre_dossier = nombre_sur_x_chiffres($res['LocationSubID'],8);
						$dossier_rev = nombre_sur_x_chiffres($res['DOSSIER_IMAGE'],8);

						//On remplace les espaces par des %20 pour l'appel en HTTP
						$image = str_replace(" ", "%20", $res['IMAGE']);
						
						if ($extension_source_recup == "TIF" or $extension_source_recup == "tif" or $extension_source_recup == "JPG" or $extension_source_recup == "jpg")
						{
							$array_tifs_sources[] ="http://192.168.6.5:8181/".$pre_dossier."/".$res['GUID'].".FDD/".$dossier_rev.".REV/Files/".$image;		
							$image_renommee = str_replace("%20", "_", $image);
							$array_tifs_destinations[]=$dossier_tif."/".$image_renommee;
						}
						
						if ($extension_source_recup == "pdf" or $extension_source_recup == "PDF")
						{
							$array_pdfs_sources[] ="http://192.168.6.5:8181/".$pre_dossier."/".$res['GUID'].".FDD/".$dossier_rev.".REV/Files/".$image;
							$pdf_renommee = str_replace("%20", "_", $image);
							$array_pdfs_destinations[]=$dossier_pdf."/".$pdf_renommee;
							$pdf_renommee = substr($pdf_renommee, 0 , (strrpos($pdf_renommee, ".")));
							$array_pdfs_tiff_destinations[]=$dossier_tif."/".$pdf_renommee;
						}
					}
					else //On loggue un warning
					{
                        $pourMsgLoggueEnBase[0] = $extension_source_recup;
                        $pourMsgLoggueEnBase[1] = $document_ged["NUMCAND"];
                        //$pourMsgLoggueEnBase[2] = $this->document_param['dossier'];
                        $pourMsgLoggueEnBase[2] = (!empty($this->document_param['dossier'])) ? $this->document_param['dossier'] : " ";
                        $pourMsgLoggueEnBase[3] = $document_ged["ANNEE"];
                        $pourMsgLoggueEnBase[4] = $type_document;
                        $pourMsgLoggueEnBase[5] = $res['IMAGE'];
                        $this->Ecrire_log_traitement_message("fonctionnel","11", $pourMsgLoggueEnBase);
					}// fin vérification extension

                    if ($i == $nb || $i == 50) //SI c'est la dernière image du GUID en cours OU la cinquantième (cas rajouté par EA le 26/09/2018 pour gérer le cas d'une requete de 150 pages), ALORS on créé le pdf image
                    {
                        //CREATION DU PDF IMAGE
                        $flag = $this->creer_pdf_from_tifs($array_tifs_sources, $array_tifs_destinations, $array_pdfs_sources, $array_pdfs_destinations, $array_pdfs_tiff_destinations, $path_partage_dossier_pdfs, $chemin_fichier_pdf, $dossier_tif, $dossier_pdf, $res['GUID']);
                        break; //Rajouté par EA le 26/09/2018 pour SORTIR DU WHILE après la création du PDF IMAGE de 50 pages max, et donc pour ne pas lire toutes celles après la cinquitième
                    }
                    else
                    {
                        $i++; //on incrémente le compteur des images du GUID en cours de traitement
                    }
				}
				else
				{
					$flag = false;
				}
			} // fin boucle while de récupération des donnnées GED

		return $flag;
	}

	public function creer_pdf_from_tifs($array_tifs_sources, $array_tifs_destinations, $array_pdfs_sources, $array_pdfs_destinations, $array_pdfs_tiff_destinations, $path_partage_dossier_pdfs, $chemin_fichier_pdf, $dossier_tif, $dossier_pdf, $guid)
	{
		$extension_source = "TIF";
		$taille_minimale_pour_insert = 2200; // si une image pèse moins de 2200 octets, nous considérons qu'il s'agit d'une page blanche -> à ne pas insérer.
		$array_tifs_destinations_util =[];
		$array_pdfs_destinations_util =[];
		$array_pdfs_to_tifs_destinations_util =[];
		$creationPdf = false;
		
		if (!is_file($chemin_fichier_pdf))
		{
			//Gestion des images PDF
		    for($j=0;$j<count($array_pdfs_sources);$j++)
			{
				//Recuperation de l'image au format PDF par l'appel HTTP et ecriture de celle ci sur le filesystem (cf. fputs)
			    $ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, utf8_decode($array_pdfs_sources[$j]));
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec ($ch);
				$error = curl_error($ch);
				curl_close ($ch);
				$destination = $array_pdfs_destinations[$j];
				$destination_tif = $array_pdfs_tiff_destinations[$j];
				$file = fopen(utf8_decode($destination), "w+");
				fputs($file, $data);
				fclose($file);
				clearstatcache(); // Pour supprimer du cache le résultat de filesize / fichier précédent

                //Alimenter les tableaux qui listent les images pdf traitées
				array_push($array_pdfs_destinations_util,$destination);
				array_push($array_pdfs_to_tifs_destinations_util,$destination_tif);
			}

            //Gestion des images TIF (et JPG aussi)
			for($i=0;$i<count($array_tifs_sources);$i++)
			{
                //Recuperation de l'image au format TIF par l'appel HTTP et ecriture de celle ci sur le filesystem (cf. fputs)
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $array_tifs_sources[$i]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$data = curl_exec ($ch);
				$error = curl_error($ch);
				curl_close ($ch);
				$destination = $array_tifs_destinations[$i];
				$file = fopen($destination, "w+");
				fputs($file, $data);
				fclose($file);
				clearstatcache(); // Pour supprimer du cache le résultat de filesize / fichier précédent
				$taille_fichier = filesize($destination);
				
				if ($taille_fichier < $taille_minimale_pour_insert)
				{
					unlink($destination); // suppression du fichier inutile
				}
				else
				{
                    //Alimenter le tableau qui liste les images tiff traitées (et JPG aussi)
				    array_push($array_tifs_destinations_util,$destination);
				}
			}

			//Conversion des images pdf en tiff
			if(count($array_pdfs_destinations_util)>0)
			{
				for($k=0;$k<count($array_pdfs_destinations_util);$k++)
				{
                    //// LOG POUR DIRE QU'ON NE GERE PAS LES PDF (PARCE QU'ON NE SAIT PAS LES CONVERTIR EN TIFF)
                    $pourMsgLoggueEnBase[0] = $array_pdfs_to_tifs_destinations_util[$k];
                    $pourMsgLoggueEnBase[1] = $guid;
                    $this->Ecrire_log_traitement_message("fonctionnel","2",$pourMsgLoggueEnBase);
				}
			}

			//Creation d'un fichier pdf à partir de toutes les images, toutes au format tiff à présent
			if(count($array_tifs_destinations_util) > 0)
			{
                //Si on n'est pas en local, on va créer le pdf image (car en local Imagick ne focntionne pas)
                if($this->environnement !== 'ENVIRONNEMNT_INCONNU')
                {
                    try
					{
						$fichier_pdf = new Imagick($array_tifs_destinations_util);
					}
					catch(Exception $e)
					{
						array_map('unlink', glob($dossier_tif."/*.".$extension_source)); // suppression des fichiers TIF temporaires
						array_map('unlink', glob($dossier_pdf."/*.pdf")); // suppression des fichiers PDF temporaires
						array_map('unlink', glob($dossier_tif."/*.JPG")); // suppression des fichiers JPG temporaires ///Correction bug par EA le 27/09/2018 en remplacant $dossier_pdf par $dossier_tif

						$this->Stopper_traitement_decision("Erreur technique lors de l'instanciation de la classe Imagick dans le but de fabriquer le fichier ".$chemin_fichier_pdf);
					}

                    $fichier_pdf->setImageFormat('pdf');

                    if (!$fichier_pdf->writeImages($chemin_fichier_pdf, true)) //Si la creation du pdf image s'est mal passée
                    {
						array_map('unlink', glob($dossier_tif."/*.".$extension_source)); // suppression des fichiers TIF temporaires
						array_map('unlink', glob($dossier_pdf."/*.pdf")); // suppression des fichiers PDF temporaires
						array_map('unlink', glob($dossier_tif."/*.JPG")); // suppression des fichiers JPG temporaires ///Correction bug par EA le 27/09/2018 en remplacant $dossier_pdf par $dossier_tif

						$this->Stopper_traitement_decision("Erreur technique lors de l écriture du PDF ".$chemin_fichier_pdf);
                    }
                    else
                    {
                        $creationPdf = true;
                    }
                }
                else
                {
                		$creationPdf = true; //Commme ca en LOCAL on aura l'alimentation des métadonnées dans la partie suivante
                }
			}
			else
			{
                $pourMsgLoggueEnBase[0] = $guid;
                $this->Ecrire_log_traitement_message("fonctionnel","12", $pourMsgLoggueEnBase);
			}
			
			array_map('unlink', glob($dossier_tif."/*.".$extension_source)); // suppression des fichiers TIF temporaires
			array_map('unlink', glob($dossier_pdf."/*.pdf")); // suppression des fichiers PDF temporaires
			array_map('unlink', glob($dossier_tif."/*.JPG")); // suppression des fichiers JPG temporaires ///Correction bug par EA le 27/09/2018 en remplacant $dossier_pdf par $dossier_tif

            return $creationPdf;
		}
	}
	
	public function Convertir_pdf_text_ancien_appli($document_ged, $type_document, $metadonnee, $DETAILS)
	{
		$file_name = $this->Regles_nomage_fichier(false,$type_document,$metadonnee);
		$chemin_fichier_pdf = $this->Get_repertoire_type_document($type_document)."/".$file_name;
		$chemin_fichier_pdf = utf8_decode($chemin_fichier_pdf);
		$IdCandidat = $metadonnee["N_candidat"];

		/////// Partie ci dessous commentée par EA le 19 09 2018 :

		// if($type_document=="decision")
		// {
		// 	$file_source = $this->repertoireDepot."/".self::REPERTOIRE_ANCIEN_APP."/".$metadonnee["Annee_election"]."/".$metadonnee["N_candidat"].".pdf";
		// }
		// else
		// {
		// 	$file_source = $this->repertoireDepot."/".self::REPERTOIRE_ANCIEN_APP."/".$metadonnee["Annee_election"]."/recours/".$metadonnee["N_candidat"]."_".$metadonnee["id_recours"].".pdf";
		// }
		
		// if($this->PDF_FILES)
		// {
		// 	$this->CopyPast_decision($file_source, $chemin_fichier_pdf);
		// }

		//generer_pdf_text_file($this->conn_nouvelle_appli,$IdCandidat,$id_decision,$etape,$chemin_fichier_pdf,$DETAILS); //Par EA le 19 09 2018 - Je remets la genration du pdf texte (et non plus sa récupération dans zz_decisions_pdf) => Ca plante....
		
		return $file_name;
	}
	
	public function CopyPast_decision($file_source, $chemin_fichier_pdf)
	{
		try
		{
			copy($file_source, $chemin_fichier_pdf);
		}
		catch(Exception $e)
		{
			return false;
		}
	}
	
	public function Convertir_pdf_text($document_ged, $type_document, $metadonnee, $DETAILS, $id_decision)
	{
		$file_name = $this->Regles_nomage_fichier(false, $type_document, $metadonnee);
		$chemin_fichier_pdf = $this->Get_repertoire_type_document($type_document)."/".$file_name;
		$IdCandidat = $metadonnee["N_candidat"];
		$etape = $this->Get_etap_type_document($type_document);
		generer_pdf_text_file($this->conn_nouvelle_appli,$IdCandidat,$id_decision,$etape,$chemin_fichier_pdf,$DETAILS); // Cette fonction se trouve dans le fichier ilyes_index.php qui est dans D:\DEV\dev_ia\2014\edition\decision_pdf\ et c'est cette fonction qui récu^pre une copie du pdf texte
		
		return $file_name;
	}
	
	public function Get_etap_type_document($type_document)
	{
		$etap = "";
		
		switch ($type_document)
		{
			case "decision" :
				$etap = 3;
				break;
			case "recours" :
				$etap = 4;
				break;
			case "modificative" :
				$etap = 4;
				break;	
		}
		
		return $etap;
	}
	
	public function verifier_coherence_candidat_ancien_appli($document_ged)
	{
		if (isset($document_ged["ANNEE"]))
		{
			$sql = "SELECT NumCand FROM BD_ELEC_".$document_ged["ANNEE"].".dbo.Table_Candidat_Rectifie WHERE NumCand='".$document_ged["NUMCAND"]."';";
			$req = odbc_exec($this->conn_ancien_appli, $sql);
			
			if ($req === false)
			{
                $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction verifier_coherence_candidat_ancien_appli");
			}
			
			$nb = 0;
			
			while ($rows = odbc_fetch_object($req))
			{ 
				$nb = count($rows);
			}
			
			if ($nb == 1)
			{
				return true;
			}
			else
			{
                $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
                $pourMsgLoggueEnBase[1] = $document_ged["ANNEE"];
                $this->Ecrire_log_traitement_message("fonctionnel","1", $pourMsgLoggueEnBase);

				return false;
			}
		}
		else
		{
            $pourMsgLoggueEnBase[0] = $document_ged['GUID'];
            $this->Ecrire_log_traitement_message("fonctionnel","13", $pourMsgLoggueEnBase);

			return false;
		}
	}
	
	public function verifier_coherence_candidat_nouvelle_appli($document_ged)
	{
		$sql="SELECT id_compte FROM dbo.compte WHERE id_compte='".$document_ged["NUMCAND"]."';";
		$req = sqlsrv_query($this->conn_nouvelle_appli,$sql, array(), array("Scrollable"=>"buffered"));
		
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la base ELEC dans la fonction verifier_coherence_candidat_nouvelle_appli");
		}
		
		$nb = sqlsrv_num_rows($req);
		
		if($nb == 1)
		{
			return true;
		}
		else
		{
            $pourMsgLoggueEnBase[0] = $document_ged["NUMCAND"];
            $this->Ecrire_log_traitement_message("fonctionnel","8", $pourMsgLoggueEnBase);

			return false;
		}	
	}
	
	public function Upsert_log_traitement($type='Traitement export quotidien', $fin_traitement="")
	{
        $etatTraitement = '';

	    if($this->id_depot_traitement == "")
		{
			$sql ="INSERT INTO ELEC.dbo.".self::TABLE_TRAITEMENT." (type_traitement) VALUES('".$type."');
					SELECT SCOPE_IDENTITY() AS IDENTITY_COLUMN_NAME;";

			if($fin_traitement == "fin" or $fin_traitement == "finiMaisErreurInattendue")
			{
                if($fin_traitement == "fin")
                {
                    $etatTraitement = 'OK';
                }
                elseif($fin_traitement == "finiMaisErreurInattendue")
                {
                    $etatTraitement = 'ERREUR';
                }

			    $sql ="INSERT INTO ELEC.dbo.".self::TABLE_TRAITEMENT." (type_traitement, date_fin_traitement, etat_traitement) VALUES('".$type."', getDate(), '".$etatTraitement."');
				SELECT SCOPE_IDENTITY() AS IDENTITY_COLUMN_NAME;";
			}
		}
		else
		{
			if($fin_traitement == "fin" or $fin_traitement == "finiMaisErreurInattendue")
			{
                if($fin_traitement == "fin")
                {
                    $etatTraitement = 'OK';
                }
                elseif($fin_traitement == "finiMaisErreurInattendue")
                {
                    $etatTraitement = 'ERREUR';
                }

			    $sql = "UPDATE ELEC.dbo.".self::TABLE_TRAITEMENT." SET date_fin_traitement = getDate(), 
				                                                        TOTAL_GED = '".$this->COMPTEUR_TOTAL_GED_LUS."',
                                                                        TOTAL_DECISION = '".$this->COMPTEUR_TOTAL_DECISION_CREE."',
                                                                        TOTAL_RECOURS = '".$this->COMPTEUR_TOTAL_RECOURS_CREE."',
                                                                        TOTAL_MODIFICATIVE = '".$this->COMPTEUR_TOTAL_MODIFICATIVE_CREE."',
                                                                        ECART_DECISIONS = '".$this->COMPTEUR_ECART_DECISIONS."',  
                                                                        TOTAL_REQUETE = '".$this->COMPTEUR_TOTAL_REQUETE_CREE."',
                                                                        TOTAL_MEMOIRE = '".$this->COMPTEUR_TOTAL_MEMOIRE_CREE."',
                                                                        TOTAL_JUGEMENT = '".$this->COMPTEUR_TOTAL_JUGEMENT_CREE."',
                                                                        TOTAL_DECISION_ANONYMISER = '".$this->COMPTEUR_TOTAL_DECISION_COPIE_POUR_ANONYMISER."',
                                                                        TOTAL_RECOURS_ANONYMISER = '".$this->COMPTEUR_TOTAL_RECOURS_COPIE_POUR_ANONYMISER."',
                                                                        TOTAL_MODIFICATIVE_ANONYMISER = '".$this->COMPTEUR_TOTAL_MODIFICATIVE_COPIE_POUR_ANONYMISER."',
                                                                        TOTAL_ERREUR_TECH = '".$this->COMPTEUR_TOTAL_TECH_ERROR."',
                                                                        TOTAL_ERREUR_FONC = '".$this->COMPTEUR_TOTAL_FONC_ERROR."',
                                                                        TOTAL_WARNING = '".$this->COMPTEUR_TOTAL_WARNING."',
                                                                        etat_traitement = '".$etatTraitement."'
                        WHERE id_depot_traitement='".$this->id_depot_traitement."'
                        ;";
			}				
		}

		$req = sqlsrv_query($this->conn_traitement,$sql);
		
		if( $req === false)
		{
            if( ($errors = sqlsrv_errors() ) != null)
            {
                foreach( $errors as $error ) {
                    echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br />";
                    echo "code: ".$error[ 'code']."<br />";
                    echo "message: ".$error[ 'message']."<br />";
                }
            }
		    die('Erreur technique lors d une requete sur la table du traitement d export');
		}
		
		if($this->id_depot_traitement == "")
		{
			$this->id_depot_traitement = $this->last_Insert_Id($req);
		}
	}
	
	public function Ecrire_log_traitement_message($message_type="", $code="", $pourMsgLoggueEnBase="")
	{
        $libelleErreur = "";

	    if($message_type == "fonctionnel")
        {
            // Recuperer message erreur fonctionnel en base

            $sql_1 = "
                select libelle_erreur
                from z_param_erreurs_fonctionnelles
                where numero_erreur = ".$code."
                ";

            $req_1 = sqlsrv_query($this->conn_traitement, $sql_1);

            if($req_1 === false)
            {
                $this->Stopper_traitement_decision("Erreur technique lors d une requete de type SELECT sur la table des messages du traitement d export dans la fonction Ecrire_log_traitement_message");
            }

            $tableauResultatRequete = sqlsrv_fetch_array($req_1, SQLSRV_FETCH_NUMERIC);
            $libelleErreur = $tableauResultatRequete[0];

            // Remplacer les "variables" du libelle de l'erreur

            $nbOccurences = substr_count($libelleErreur, '@');

            for($i=0;$i<$nbOccurences;$i++)
            {
                $libelleErreur = str_replace('@'.$i, $pourMsgLoggueEnBase[$i], $libelleErreur);
            }
        }
        else //si erreur technique ou warning
        {
            $libelleErreur = $pourMsgLoggueEnBase;
        }

        // Retirer les apostrophes de $libelleErreur
        $libelleErreur = str_replace("'", " ", $libelleErreur);
        
        // Insérer l'erreur en base dans les logs du traitement

	    $sql_2 ="INSERT INTO ELEC.dbo.".self::TABLE_TRAITEMENT_MESSAGE."(id_depot_traitement,message_type,code,message) 
				 VALUES('".$this->id_depot_traitement."','".$message_type."','".$code."','".$libelleErreur."');";
		
		$req_2 = sqlsrv_query($this->conn_traitement, $sql_2);
		
		if($req_2 === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete de type INSERT sur la table des messages du traitement d export dans la fonction Ecrire_log_traitement_message");
		}

		//Incrementer le bon compteur d'erreur
		switch($message_type)
        {
            case 'fonctionnel' :
                $this->COMPTEUR_TOTAL_FONC_ERROR++;
                break;
            case 'technique' :
                $this->COMPTEUR_TOTAL_TECH_ERROR++;
                break;
            case 'warning' :
                $this->COMPTEUR_TOTAL_WARNING++;
                break;
        }
	}

	//Fonction utilisée pour l'export à partir de la lecture d'un fichier csv
	public function get_Traitements_Param($type)
	{
		$param = null;

		$sql = "SELECT *,CONVERT(VARCHAR,CAST(date_scan AS DATETIME),112) AS DATE_SCAN_SQL
		,CONVERT(VARCHAR,CAST(date_scan_debut AS DATETIME),112) AS DATE_SCAN_DEBUT_SQL
		,CONVERT(VARCHAR,CAST(date_scan_fin AS DATETIME),112) AS DATE_SCAN_FIN_SQL
		,CONVERT(VARCHAR,CAST(dernier_date_traitement AS DATETIME),112) AS DERNIER_DATE_TRAITEMENT_SQL		
		FROM ELEC.dbo.z_depot_traitment_param WHERE type='".$type."';"; 
		
		$req = sqlsrv_query($this->conn_traitement,$sql, array(), array("Scrollable"=>"buffered"));
				
		if ($req === false)
		{
            $this->Stopper_traitement_decision("Erreur technique lors d une requete sur la table de paramétrage des exports à partir d un fichier csv, fonction get_Traitements_Param");
		}
			
		$nb = sqlsrv_num_rows($req);
		
		if ($nb > 0)
		{
			while($rs = sqlsrv_fetch_array($req, SQLSRV_FETCH_ASSOC))
			{
				$param = $rs;
				break;
			}
		}
		
		$this->document_param = $param;
	}
	
    public function last_Insert_Id($queryID)
	{
		sqlsrv_next_result($queryID);
		sqlsrv_fetch($queryID);
		
		return sqlsrv_get_field($queryID, 0);
	}
	
	public function initialiser_METADONNEE_CSV()
	{
		$meta_title = $this->Get_metadonnee_vide_csv();
		
		array_push($this->METADONNEE_DECISION,$meta_title);
		array_push($this->METADONNEE_RECOURS,$meta_title);
		array_push($this->METADONNEE_MODIFICATIVE,$meta_title);
		array_push($this->METADONNEE_REQUETE,$meta_title);
		array_push($this->METADONNEE_MEMOIRE,$meta_title);
		array_push($this->METADONNEE_JUGEMENT,$meta_title);
	}
	
	public function Get_metadonnee_vide_csv($arr)
	{
		return [
			"Type_de_contenu" => "Type de contenu",
			"N_candidat" => "N°  candidat",
			"Nom_candidat_1" => "Nom du candidat 1",
			"Prenom_candidat_1" => "Prénom du candidat 1",
			"Nom_candidat_2" => "Nom du candidat 2",
			"Prenom_candidat_2" => "Prénom du candidat 2",
			"N_scrutin" => "N° de scrutin",
			"Nom_circonscription" => "Nom de la circonscription",
			"Numero_affaire" => "Numéro de l'affaire",
			"N_INSEE_departement" => "N° INSEE département",
			"N_INSEE_Region" => "N° INSEE de la Région",
			"Election" => "Élection",
			"Type_election" => "Type élection",
			"Annee_election" => "Année de l'élection",
			"Scrutin_contentieux" => "Scrutin contentieux",
			"Raporteur" => "Raporteur",
			"N_rapporteur" => "N° du rapporteur",
			"Membre_commission" => "Membre de la commission",
			"Suivi_par" => "Suivi par",
			"N_lettre" => "N° de la lettre",
			"Chrono_GED" => "Chrono GED",
			"Date_decision" => "Date de la décision",
			"Date_jour" => "Date du jour",
			"Date_base_CCFP" => "Date base CCFP",
			"Etiquette_politique" => "Étiquette politique",
			"Nuance_politique" => "Nuance politique",
			"Parti_politique" => "Parti politique",
			"N_parti" => "N° du parti",
			"Type_mandataire" => "Type de mandataire (personne physique ou morale)",
			"Nom_mandataire" => "Nom du mandataire",
			"N_association_financement" => "N° de l'association de financement au Répertoire National des Assoications",
			"N_expert_comptable" => "N° expert-comptable",
			"Nom_cabinet_expertise_comptable" => "Nom du cabinet d'expertise comptable",
			"Elu" => "Élu",
			"Sortant" => "Sortant",
			"pourcentage_voix_1er_tour" => "% de voix du 1er tour",
			"remboursement"=>"Remboursement",
			"Sens_decision" => "Sens de la décision",
			"Decision_signalee" => "Décision signalée",
			"Doctrine" => "Doctrine",
			"Ex_doctrine" => "Ex-doctrine",
			"Cas_espece" => "Cas d'espèce",
			"Jurisprudence" => "Jurisprudence",
			"Ex_jurisprudence" => "Ex-jurisprudence",
			"Mots_cles_automatiques" => "Mots-clés \"automatiques\"",
			"Mot_cles_indexeurs" => "Mot-clés \"indexeurs\"",
			"Commentaires" => "Commentaires",
			"Decision_remise_cause" => "Décision remise en cause",
			"Anonymisation" => "Anonymisation",
			"A_reviser" => "À réviser",
			"Path" => "Path",
		];
	} //Fin de la fonction Get_metadonnee_vide_csv()
  } //Fin de la classe Outil_consultation_sp
?>