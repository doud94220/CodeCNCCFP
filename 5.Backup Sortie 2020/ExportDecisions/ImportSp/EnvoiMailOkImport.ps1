$anonUser = "application@cnccfp.fr"
$anonPass = ConvertTo-SecureString "Zorglub@15" -AsPlainText -Force
$anonCred = New-Object System.Management.Automation.PSCredential($anonUser, $anonPass)
$encodingMail = [System.Text.Encoding]::UTF8
$body = "<font color='green'>Le traitement d'import quotidien (qui succède à celui d'export quotidien) s'est bien passé.</font><br><br>Vous pouvez analyser les logs sur S8APP1 dans 'F:\DossierTraitement\PROD\Import'"
Send-MailMessage -To dominique.huber@cnccfp.fr -From application@cnccfp.fr -Subject "[Import][Decisions][PROD] Confirmation De Bonne Execution" -BodyAsHtml $body -SmtpServer 192.168.6.30 -Encoding $encodingMail -Credential $anonCred
