php -f X_moulinette_outil_consultation_sharepoint_batch_quotidien_pourExportDes344.php

@echo off

pause

echo %errorlevel% 

pause 

if %errorlevel% NEQ 0 (
	echo une erreur est survenue
	pause
)else (
	echo ok l'export s'est bien passé, fin du traitement
	pause
)
