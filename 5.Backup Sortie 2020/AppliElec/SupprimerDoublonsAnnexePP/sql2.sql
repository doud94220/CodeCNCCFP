--REQUETE QUI RAMENE TOUT : Singletons + Doublons (X+Z ou A+B+C), ET QUE LES ID_ANNEXE DANS LE SELECT
SELECT annexe.id_annexe
FROM [ELEC].[dbo].[compte_annexe] as annexe
inner join [ELEC].[dbo].[compte] as compte on compte.id_compte = annexe.id_compte
inner join [ELEC].[dbo].[scrutin] as scrutin on scrutin.id_scrutin = compte.id_scrutin
inner join [ELEC].[dbo].[candidat] as cand on cand.id_candidat = compte.id_compte
inner join [ELEC].[dbo].[utilisateur] as util on util.id_util = compte.id_util_cm
inner join [ELEC].[dbo].[utilisateur] as util2 on util2.id_util = compte.id_groupe_rapporteur
where pc in (7031,7032,7021,7026,7027,7050,7051,7052)
and annexe.id_compte like '2020%'
group by annexe.id_annexe
