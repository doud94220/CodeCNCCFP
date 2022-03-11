
<#
.SYNOPSIS
    Importe les décisions de la CNCCFP dans le portail SharePoint associé.
	
.NOTES
    Auteur: Vincent GUERLET
    Email: vincent.guerlet@infeeny.com
    Entreprise: INFEENY
#>


# VARIABLES

#$portalUrl = "http://vgu-sp2013/sites/cnccfp" #DEV
#$portalUrl = "http://cnccfp-shp/sites/decisions" #RECETTE INTERNE
#$portalUrl = "http://portail-test.cnccfp.local/sites/decisions" #R7
$portalUrl = "http://ccfp.cnccfp.local/sites/decisions" #PROD

$metadataFile = "Metadonnees.xml"
$logMetadataFile = "LogFiles.xml"

#$dataFolder = "C:\TFS\CNCCFP\Main\Data\20170316" #DEV
#$dataFolder = "\\Cnccfp-shp\data" #"\\10.32.31.152\e`$\IMPORT\DATA" #RECETTE INTERNE
#net use I: \\S8APP1\Import AdminProd1 /user:CNCCFP\adminproduct /PERSISTENT:YES #COMMENTEE LE 01102018 PAR EA PARCE QUE SUR S8APP1 IL FAUT POINTER SUR UN LECTEUR DIFFERENT PENDANT PROBLEME EN PROD
net use J: \\S8APP1\Import2 AdminProd1 /user:CNCCFP\adminproduct /PERSISTENT:YES

$dataFolder = "J:\" #PROD

# REFERENCES
#. .\Utils.ps1
C:\ImportDecisions\Utils.ps1

# FUNCTIONS
function Populate-ContentType([string]$folder, [string]$listUrl, [switch]$hasPrivateFolder)
{
	Write-Output "`n--------------------------------------------------------------------------`n"
	
	# Start logging
	Start-Transcript -Path $dataFolder\$folder\$folder.log

	Write-Output "`nImport $folder dans liste $listUrl du site $portalUrl."

	if($hasPrivateFolder -eq $true)
	{
		C:\ImportDecisions\Populate-Items.ps1 -SiteUrl $portalUrl -XmlLocation $dataFolder\$folder -XmlName $metadataFile -FileLocation $dataFolder\$folder -ListUrl $listUrl -HasPrivateFolder
	}
	else
	{
		C:\ImportDecisions\Populate-Items.ps1 -SiteUrl $portalUrl -XmlLocation $dataFolder\$folder -XmlName $metadataFile -FileLocation $dataFolder\$folder -ListUrl $listUrl
	}

	Write-Output ""
	
	# Stop logging
	Stop-Transcript
	
	Write-Output ""
}

# MAIN

Populate-ContentType -folder "Decisions_initiales" -listUrl "/DecisionsInitiales" -hasPrivateFolder
Populate-ContentType -folder "Decisions_rec_gr" -listUrl "/DecisionsRecours" -hasPrivateFolder
Populate-ContentType -folder "Decisions_rectif" -listUrl "/DecisionsRectificatives" -hasPrivateFolder
Populate-ContentType -folder "Jugements" -listUrl "/Jugements"
Populate-ContentType -folder "Memoires" -listUrl "/Memoires"
Populate-ContentType -folder "Requetes" -listUrl "/Requetes"

# LOGS
C:\ImportDecisions\Populate-Items.ps1 -SiteUrl $portalUrl -XmlName $logMetadataFile -FileLocation $dataFolder

#Par EA le 18 04 2019 : Renommer les fichiers de logs pour leur ajouter date précise
$today = Get-Date -format "ddMMyyyy-HHmm"

$newDecIniFileName = "Decisions_initiales_"+$today+".log"
Rename-Item -Path "J:\Decisions_initiales\Decisions_initiales.log" -NewName $newDecIniFileName

$newDecRecoursFileName = "Decisions_rec_gr_"+$today+".log"
Rename-Item -Path "J:\Decisions_rec_gr\Decisions_rec_gr.log" -NewName $newDecRecoursFileName

$newDecModFileName = "Decisions_rectif_"+$today+".log"
Rename-Item -Path "J:\Decisions_rectif\Decisions_rectif.log" -NewName $newDecModFileName

$newJugFileName = "Jugements_"+$today+".log"
Rename-Item -Path "J:\Jugements\Jugements.log" -NewName $newJugFileName

$newMemFileName = "Memoires_"+$today+".log"
Rename-Item -Path "J:\Memoires\Memoires.log" -NewName $newMemFileName

$newReqFileName = "Requetes_"+$today+".log"
Rename-Item -Path "J:\Requetes\Requetes.log" -NewName $newReqFileName
#Fin modif EA

Write-Output ""