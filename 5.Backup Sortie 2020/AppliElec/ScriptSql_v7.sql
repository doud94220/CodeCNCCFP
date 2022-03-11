--ETAPE 1
--AJOUT COLONNES TABLE MANDATAIRE
ALTER TABLE [ELEC].[dbo].[mandataire] ADD commentaire_controle_mandataire nvarchar(250)
ALTER TABLE [ELEC].[dbo].[mandataire] ADD chk_irregularite_mandataire tinyint
ALTER TABLE [dbo].[mandataire] ADD  CONSTRAINT [DF_mandataire_chk_irregularite_mandataire]  DEFAULT ((2)) FOR [chk_irregularite_mandataire]


--ETAPE 2 --Durre ~8 minutes
--RECOPIE DE CERTAINES DATA DE LA TABLE CANDIDAT VERS LA TABLE MANDATAIRE POUR Y MIGRER LES INFORMATIONS SUR LE MANDATAIRE
DECLARE @idCandidat	INT
DECLARE @commentaireControleMandataire NVARCHAR(250)
DECLARE @chkIrregulariteMandataire INT

DECLARE candidats_cursor CURSOR FOR

SELECT id_candidat, commentaire_controle_mandataire, chk_irregularite_mandataire
FROM [ELEC].[dbo].[candidat]
--where id_candidat in (201810074, 201810075, 201810076) --TEMPOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO

OPEN candidats_cursor

FETCH NEXT FROM candidats_cursor INTO @idCandidat, @commentaireControleMandataire, @chkIrregulariteMandataire

WHILE @@FETCH_STATUS = 0
   BEGIN
		  print @idCandidat

		  update [ELEC].[dbo].[mandataire]
		  set commentaire_controle_mandataire = @commentaireControleMandataire, chk_irregularite_mandataire = @chkIrregulariteMandataire
		  where id_candidat = @idCandidat

		  FETCH NEXT FROM candidats_cursor INTO @idCandidat, @commentaireControleMandataire, @chkIrregulariteMandataire
   END

CLOSE candidats_cursor
DEALLOCATE candidats_cursor


--ETAPE 3
--SUPPRESSION DE 2 COLONNES OBSOLETES DANS LA TABLE CANDIDAT
ALTER TABLE [dbo].[candidat] DROP CONSTRAINT [DF__candidat__chk_ir__3C9FD11A] --VERIF EN PROD SI LA CONTRAINTE S'APELLE COMME CA
ALTER TABLE [ELEC].[dbo].[candidat] DROP COLUMN commentaire_controle_mandataire, chk_irregularite_mandataire


--ETAPE 4
--CREATION DE LA TABLE DE MAPPING
USE [ELEC]
GO

SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[candidat_mandataire](
	[id_mapping] [int] IDENTITY(1,1) NOT NULL,
	[id_candidat] [int] NOT NULL,
	[id_mandataire] [int] NOT NULL,
	[date_debut_validite] [date] NULL,
	[date_fin_validite] [date] NULL,
	[periodicite_mandataire] [nvarchar](10) NULL
 CONSTRAINT [PK_candidat_mandataire] PRIMARY KEY CLUSTERED
(
	[id_mapping] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[candidat_mandataire]  WITH CHECK ADD  CONSTRAINT [FK_candidat] FOREIGN KEY([id_candidat])
REFERENCES [dbo].[candidat] ([id_candidat])
GO
ALTER TABLE [dbo].[candidat_mandataire] CHECK CONSTRAINT [FK_candidat]
GO

ALTER TABLE [dbo].[candidat_mandataire]  WITH CHECK ADD  CONSTRAINT [FK_mandataire] FOREIGN KEY([id_mandataire])
REFERENCES [dbo].[mandataire] ([id_mandataire])
GO
ALTER TABLE [dbo].[candidat_mandataire] CHECK CONSTRAINT [FK_mandataire]
GO


--ETAPE 5 --Durre ~2 minute
--RECOPIE DE id_mandataire et id_candidat DE LA TABLE MANDATAIRE VERS LA TABLE DE MAPPING
DECLARE @idMandataire INT
DECLARE @idCandidat	INT

DECLARE mandataires_cursor CURSOR FOR

SELECT id_mandataire, id_candidat
FROM [ELEC].[dbo].[mandataire]
--where id_candidat in (201810074, 201810075, 201810076) --TEMPOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO

OPEN mandataires_cursor

FETCH NEXT FROM mandataires_cursor INTO @idMandataire, @idCandidat

WHILE @@FETCH_STATUS = 0
   BEGIN
		print @idMandataire

		INSERT INTO [ELEC].[dbo].[candidat_mandataire]
           ([id_candidat]
           ,[id_mandataire]
		   ,[periodicite_mandataire]
		   ,[date_debut_validite]
		   ,[date_fin_validite])
		VALUES
           (@idCandidat
		   ,@idMandataire
		   ,'actif'
		   , NULL
		   , NULL) --On est obligé de renseigner quelque chose pour les dates de validité sinon le code PHP va planter

		FETCH NEXT FROM mandataires_cursor INTO @idMandataire, @idCandidat
   END

CLOSE mandataires_cursor
DEALLOCATE mandataires_cursor

--Sur DEVMIA : Il y aura 2 mandataires que ne seront pas insérés dans la table de mapping, car ils ne sont rattachés à aucun candidat (aussi bien dans la table candidat que dans la mandataire) => leurs id_mandataire : 9914, 14971
