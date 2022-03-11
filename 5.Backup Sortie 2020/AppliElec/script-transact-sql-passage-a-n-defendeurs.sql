---------------------- Etape 1 : CREATION DE LA TABLE DE MAPPING
USE [ELEC]
GO

SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[requete_ctx_defendeur](
	[id_mapping] [int] IDENTITY(1,1) NOT NULL,
	[id_requete_ctx] [int] NOT NULL,
	[id_defendeur] [int] NULL,
	[nom_prenom_defendeur][nvarchar](60) NULL,
	[defendeur][nvarchar](60) NULL
 CONSTRAINT [PK_requete_ctx_defendeur] PRIMARY KEY CLUSTERED
(
	[id_mapping] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
) ON [PRIMARY]

GO

ALTER TABLE [dbo].[requete_ctx_defendeur]  WITH CHECK ADD  CONSTRAINT [FK_requete_ctx] FOREIGN KEY([id_requete_ctx])
REFERENCES [dbo].[requete_ctx] ([id_requete])
GO
ALTER TABLE [dbo].[requete_ctx_defendeur] CHECK CONSTRAINT [FK_requete_ctx]
GO

ALTER TABLE [dbo].[requete_ctx_defendeur]  WITH CHECK ADD  CONSTRAINT [FK_defendeur] FOREIGN KEY([id_defendeur])
REFERENCES [dbo].[candidat] ([id_candidat])
GO
ALTER TABLE [dbo].[requete_ctx_defendeur] CHECK CONSTRAINT [FK_defendeur]
GO





---------------------- Etape 2 : Alimenter la table de mapping à partir de la table requete_ctx
DECLARE @idRequete INT
DECLARE @idDefendeur INT
DECLARE @defendeur VARCHAR

DECLARE requete_cursor CURSOR FOR
SELECT id_requete, id_defendeur, defendeur
FROM [ELEC].[dbo].[requete_ctx]

OPEN requete_cursor

FETCH NEXT FROM requete_cursor INTO @idRequete, @idDefendeur, @defendeur

WHILE @@FETCH_STATUS = 0
   BEGIN
   		print @idRequete

   		insert into [ELEC].[dbo].[requete_ctx_defendeur]
   		values (@idRequete, @idDefendeur, NULL, @defendeur)

   		FETCH NEXT FROM requete_cursor INTO @idRequete, @idDefendeur, @defendeur
   	END

CLOSE requete_cursor
DEALLOCATE requete_cursor





---------------------- Etape 3 : Suppression de la colonne id_defendeur et de sa contrainte dans la table requete_ctx
ALTER TABLE [ELEC].[dbo].[requete_ctx] DROP CONSTRAINT [candidat_requete_ctx_init_fk1] --VERIFIER QU'EN PROD, ELLE A LE MEME NOM
ALTER TABLE [ELEC].[dbo].[requete_ctx] DROP COLUMN [id_defendeur]

ALTER TABLE [ELEC].[dbo].[requete_ctx] DROP COLUMN [defendeur]
