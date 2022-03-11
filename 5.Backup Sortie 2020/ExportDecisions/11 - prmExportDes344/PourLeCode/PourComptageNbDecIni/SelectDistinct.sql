select distinct Numcand
from BD_ELEC_2012.dbo.Table_Candidat_Rectifie
where Numcand in
(
	SELECT TCR.NumCand
	FROM BD_ELEC_2012.dbo.Table_Candidat_Rectifie AS TCR
		LEFT JOIN
		WEB_CONST.dbo.partis_politiques_N AS PP
		ON 
		(PP.id_spp = TCR.PartiPol AND TCR.PartiPol<>0 )
		LEFT JOIN
		BD_ELEC_2012.dbo.Table_Expert_Rectifie AS EXPR
		ON 
		(EXPR.NumCand = TCR.NumCand)
		LEFT JOIN
		BD_ELEC_2012.dbo.Table_Mandataire_Rectifie AS MAND
		ON
		(MAND.NumCand=TCR.NumCand)
		LEFT JOIN
		BD_ELEC_2012.dbo.Table_Association_Rectifie AS ASS
		ON
		(ASS.NumCand=TCR.NumCand)
		LEFT JOIN
		BD_ELEC_2012.dbo.Table_Scrutin AS TS
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
		BD_ELEC_2012.dbo.Table_Rapport_RappG AS TRAPPG
		ON
		(TCR.NumCand = TRAPPG.NoCandidat)
		LEFT JOIN
		WEB_CONST.dbo.Table_TypeDecision AS DECTYPE
		ON
		(TRAPPG.CodeDecision = DECTYPE.CodeTypeDecision)
		LEFT JOIN
		BD_ELEC_2012.dbo.comptereserve AS TRES
		ON
		(TCR.NumCand = TRES.numcand AND TRES.recours = '0') --hope it will be ok
		LEFT JOIN
		BD_ELEC_2012.dbo.nouveaux_recours AS R
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
				TRAPPG.CodeDecision >= '0'
				AND TCR.NumCand IS NOT NULL
				AND TCR.DatePassCCFP is null --ON RECHERCHE LES CANDIDATS AVEC "DATE PASSAGE CCFP" NULLE
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
)
