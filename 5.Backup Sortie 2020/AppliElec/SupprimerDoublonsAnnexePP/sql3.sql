-- REQUETE QUI RAMENE UNE OCCURENCE PAR DOUBLON (Y ou A)
SELECT
max(annexe.id_annexe)
,compte.id_scrutin as scrutin
,scrutin.nom_circons as circonscription
,annexe.id_compte as compte
,cand.nom_cand as candidat
,compte.id_util_cm as num_cm
,util.nom_util as nom_cm
,compte.id_groupe_rapporteur as num_rap
,util2.nom_util as nom_rap
,pc
,[partie_versante]
,[date_versement]
,[montant_annexe]
, count(*) as nombre_occurences
FROM [ELEC].[dbo].[compte_annexe] as annexe
inner join [ELEC].[dbo].[compte] as compte on compte.id_compte = annexe.id_compte
inner join [ELEC].[dbo].[scrutin] as scrutin on scrutin.id_scrutin = compte.id_scrutin
inner join [ELEC].[dbo].[candidat] as cand on cand.id_candidat = compte.id_compte
inner join [ELEC].[dbo].[utilisateur] as util on util.id_util = compte.id_util_cm
inner join [ELEC].[dbo].[utilisateur] as util2 on util2.id_util = compte.id_groupe_rapporteur
where pc in (7031,7032,7021,7026,7027,7050,7051,7052)
and annexe.id_compte like '2020%'
group by compte.id_scrutin, scrutin.nom_circons, annexe.id_compte, cand.nom_cand, compte.id_util_cm, util.nom_util, compte.id_groupe_rapporteur, util2.nom_util, pc, partie_versante, date_versement, montant_annexe
having count(*) > 1
