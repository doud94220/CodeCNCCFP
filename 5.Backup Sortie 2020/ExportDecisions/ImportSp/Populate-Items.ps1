
<#

.SYNOPSIS
    Importe des éléments dans un portail SharePoint.
	Adapté pour CNCCFP, gestion des champs Multi-utilisateurs par nom complet, logs, remplacement des fichiers, etc.
	
.NOTES
    Auteur: Vincent GUERLET
    Email: vincent.guerlet@infeeny.com
    Entreprise: INFEENY
	
#>

param(
    [string]$XmlLocation = ".",
	[string]$FileLocation = "",
    [string]$SiteUrl = "",
	[string]$ListUrl = "",
    [string]$XmlName = "Populate-Items.xml",
    [switch]$DisablePages,
    [switch]$DisableItems,
	[switch]$HasPrivateFolder
)

##########################################################################
#						         DECLARATIONS
##########################################################################

#Returns script directory
function Get-ScriptDirectory
{
	$Invocation = (Get-Variable MyInvocation -Scope 1).Value
	Split-Path $Invocation.MyCommand.Path
}

Add-PSSnapin Microsoft.SharePoint.PowerShell -ErrorAction SilentlyContinue
$scriptDirectory = Get-ScriptDirectory
Push-Location $scriptDirectory
. .\Utils.ps1

##########################################################################
#									FUNCTIONS
##########################################################################

#Returns executing script's directory
function Get-ScriptDirectory
{
	$Invocation = (Get-Variable MyInvocation -Scope 1).Value
	Split-Path $Invocation.MyCommand.Path
}

#Returns taxonomy value
function GetTaxoFieldValue([Microsoft.SharePoint.SPWeb]$Web,[Microsoft.SharePoint.Taxonomy.TaxonomyField]$Field,[string]$Value){
    
	$taxoValue = $null;
	$taxoSession = Get-SPTaxonomySession -Site $Web.Site
    $termStore = $taxoSession.TermStores[$Field.SspId]
    $termSet = $termStore.GetTermSet($Field.TermSetId)
    $termcollection = $termSet.GetTerms($value,$true)
    if ($termCollection.Count -gt 0){
        $term = $termCollection[0]
		$taxoValue = New-Object Microsoft.SharePoint.Taxonomy.TaxonomyFieldValue($Field)
		$taxoValue.TermGuid = $term.Id.ToString()
		$taxoValue.Label = $term.Name 
    }
    else{
	
		Write-Warning "Terme introuvable: $Value."
    }
	
    return $taxoValue
}

#Upload a document from specified location to specified folder of target web
#Parameter LocalPath - Path of the file to upload
#Parameter TargetWeb - SPWeb in which to upload file
#Parameter TargetFolder - Folder (relative to spweb) in which to upload file
#Parameter FieldValues - Values for different fields of the file
function Upload-File([string]$LocalPath, [Microsoft.SharePoint.SPWeb]$TargetWeb, [Microsoft.SharePoint.SPFieldCollection]$ContentTypeFields, [Microsoft.SharePoint.SPFolder]$TargetFolder, [Collections.ArrayList]$ExistingFiles, [System.Xml.XmlElement[]]$FieldValues, [bool]$overwrite=$false)
{
    #Get all file info and metadata
	$fileExistsOnDisk = Test-Path -LiteralPath $LocalPath; # -LiteralPath resolves issue with square brackets.
	if($fileExistsOnDisk -ne $true)
	{
		Write-Warning "Fichier introuvable: <$LocalPath>.";
		$hasError = $true;
	}
	else
	{
	    $hasError = $false;
        $fileInfo = Get-ChildItem -literalPath $LocalPath;
        $fileName = $fileInfo.Name;
                
        $fileExist = $ExistingFiles -icontains $fileName;
      
        $welcomePage = $false;
        $rootFolder = $TargetWeb.RootFolder;
        $welcomePageUrl = "";
    
        if($fileExist -eq $true -and $overwrite -ne $true)
	    {
		    Write-Warning "Le fichier existe déjà"
		    Write-Warning "Ajout impossible."
	    }
	    else
	    {
		    $fileContent = Get-Content -literalPath $fileInfo -encoding Byte -ReadCount 0
		    $fileMetadata = New-Object HashTable
		    $fileTaxonomyValues =  New-Object HashTable
		
            # CHECK REQUIRED FIELD ###
            $requiredFields = $ContentTypeFields | where{ ($_.InternalName -ne "FileLeafRef") -and ($_.Required -eq $true) };
		    #Write-Host "Required fields:" $requiredFields;
		
            #$requiredFields | Foreach-Object {
            ForEach ($requiredFieldOccurence in $requiredFields){
		
                $internalName = $requiredFieldOccurence.InternalName;
			    #Write-Host "Internal name:" $_.InternalName;
			
                $foundField = $FieldValues | where{$_.GetAttribute("Name") -eq $internalName};
                if($foundField -eq $null)
                {
            	    Write-Warning "Colonne obligatoire manquante: $internalName.";
            	    $hasError = $true;
                }
            }
		
		    if($FieldValues -ne $null -and $hasError -ne $true){
		
			    #$FieldValues | Foreach-Object {
                ForEach ($fieldValueOccurence in $FieldValues){
				
				    $fieldName = $fieldValueOccurence.GetAttribute("Name");
				
                    # CHECK FIELD EXISTS ###
                    $fieldExists = $ContentTypeFields.ContainsField($fieldName); #$folder.DocumentLibrary.Fields.ContainsField($fieldName);

				    #Write-Host "Field exists:$fieldExists";
			
				    if($fieldExists -ne $true)
				    {
					    Write-Warning "Colonne invalide: $fieldName.";
					    $hasError = $true;
				    }
				    else
				    {
					    #Resolve encoding issue for accentuated words (french by example).
					
					    $fieldValue = $fieldValueOccurence.InnerText.Replace("%%SITEURL%%", $TargetWeb.Site.ServerRelativeUrl.TrimEnd('/'));
					    $fieldValue = $fieldValue.Replace("%%WEBURL%%", $TargetWeb.ServerRelativeUrl.TrimEnd('/'));
					    $fieldValue = $fieldValue.Replace("%%TODAYISO%%", [Microsoft.SharePoint.Utilities.SPUtility]::CreateISO8601DateTimeFromSystemDateTime([DateTime]::Now))
					    #Write-Host $_.GetAttribute("Name") " : " $fieldValue ;
					
					    if ($fieldValueOccurence.GetAttribute("IsTaxo") -ne $null -and $fieldValueOccurence.GetAttribute("IsTaxo") -eq "True"){
						    #$field = [Microsoft.SharePoint.Taxonomy.TaxonomyField]$folder.DocumentLibrary.Fields.GetFieldByInternalName($_.GetAttribute("Name"))
						    $field = [Microsoft.SharePoint.Taxonomy.TaxonomyField]$ContentTypeFields.GetFieldByInternalName($fieldValueOccurence.GetAttribute("Name"))
					
						    if($field.AllowMultipleValues) {
							    $taxoTermsValues = $fieldValue.Split(';', [System.StringSplitOptions]::RemoveEmptyEntries)

							    $taxoMultipleValue = New-Object Microsoft.SharePoint.Taxonomy.TaxonomyFieldValueCollection($field)
							    
                                #$taxoTermsValues | ForEach-Object {
                                ForEach ($taxoTermValueOccurence in $taxoTermsValues){

								    $taxoValue = GetTaxoFieldValue -Web $TargetWeb -Field $field -Value $taxoTermValueOccurence
								    if($taxoValue -ne $null)
								    {
									    $taxoMultipleValue.Add($taxoValue)
								    }
								    else
								    {
									    $hasError = $true; #SKIP ADD IF TAXO VALUE IS NOT CORRECT.
								    }
							    }

							    [Microsoft.SharePoint.Taxonomy.TaxonomyFieldValueCollection]$fileTaxonomyValues[$field.InternalName] = $taxoMultipleValue
						    }
						    else {
							    $fieldValue = GetTaxoFieldValue -Web $TargetWeb -Field $field -Value $fieldValue
						  
							    if($fieldValue -ne $null)
								    {
									    [Microsoft.SharePoint.Taxonomy.TaxonomyFieldValue]$fileTaxonomyValues[$field.InternalName] = $fieldValue
								    }
								    else
								    {
									    $hasError = $true; #SKIP ADD IF TAXO VALUE IS NOT CORRECT.
								    }
						    }
					    }
					    else{
						    if($fieldValueOccurence.Attributes["UserType"] -ne $null)
						    {
							    if($fieldValueOccurence.GetAttribute("UserType").Length -ne 0){
								    $findUser = $null
								    $findUser = Get-User -TargetWeb $TargetWeb -FieldValue $fieldValue -Permission $fieldValueOccurence.GetAttribute("UserType")
								
								    #Write-Host "FindUser: $findUser"
								
						    	    if($findUser -eq $null)
						    	    {
									    # User not found by login name, try to found it by display name
						    		    $findUser = Get-UsersByDisplayName -TargetWeb $TargetWeb -FieldValue $fieldValue;
						    	    }
								
								    if($findUser -ne $null)
								    {
									    $fileMetadata[$fieldValueOccurence.GetAttribute("Name")] = $findUser;
								    }
						    	    else
						    	    {
									    #Write-Host "User not found: " $fieldValue
									    #Write-Warning "Utilisateur(s) introuvable(s): $fieldValue.";

						    		    $field = $ContentTypeFields.GetFieldByInternalName($fieldValueOccurence.GetAttribute("Name"));
						    		    if($field.Required -eq $true -and ([string]::IsNullOrEmpty($fieldValue)))
						    		    {
						    			    Write-Warning "Colonne obligatoire non renseignée: $fieldName."
						    		    }
									
						    		    $hasError = $true;
						    	    }
							    }
						    }
						    else
						    {
							    #$field = $folder.DocumentLibrary.Fields.GetFieldByInternalName($_.GetAttribute("Name"));
							    $field = $ContentTypeFields.GetFieldByInternalName($fieldValueOccurence.GetAttribute("Name"));
							
							    $fieldType = $field.Type;
							    #Write-Host "FIELD TYPE: " $fieldType;

							    #Write-Output "$field : " $field.Required;
							    if($field.Required -eq $true -and ([string]::IsNullOrEmpty($fieldValue) -eq $true))
							    {
							        Write-Warning "Colonne obligatoire non renseignée: $fieldName.";
							        $hasError = $true;
							    }
							    else
							    {
								    if([string]::IsNullOrEmpty($fieldValueOccurence.GetAttribute("DateTime")) -eq $false)
								    {
								        try 
                                        {
                                            $fieldValue = [DateTime]$fieldValue
                                        }
                                        catch
                                        {
								    	    Write-Warning "Date invalide dans la colonne $fieldName : $fieldValue.";
								    	    $hasError = $true;
								        }
								    }

								    if([string]::IsNullOrEmpty($fieldValue) -eq $false)
								    {
									    switch($fieldType)
									    {
									        "Boolean" {
									    	    try {
									    		    $boolean = [bool]::Parse($fieldValue);
									    	    }
									    	    catch
									    	    {
									    		    Write-Warning "Booléen invalide dans la colonne $fieldName : $fieldValue (valeurs possibles: True ou False).";
									    		    $hasError = $true;
									    	    }
									        }
									        "Choice" {
									    	    if([string]::IsNullOrEmpty($fieldValue) -ne $true -and $field.Choices.Contains($fieldValue) -ne $true)
									    	    {
									    		    Write-Warning "Valeur introuvable dans la liste de choix de la colonne $fieldName : $fieldValue.";
									    		    $hasError = $true;
									    	    }
									        }
									        "Number" {
								        
									    	    try {
									    		    $number = [Double]::Parse($fieldValue, [cultureinfo]::InvariantCulture);
									    	    }
									    	    catch
									    	    {
									    		    Write-Warning "Nombre invalide dans la colonne $fieldName : $fieldValue.";
									    		    $hasError = $true;
									    	    }
								        
									        }
									        default{}
									    }

									    $fileMetadata[$fieldValueOccurence.GetAttribute("Name")] = $fieldValue;
								    }
							    }
						    }
					    }
				    }
			    }
		    }

            if($hasError -ne $true)
	        {
		        $spFile = $TargetFolder.Files.Add($fileName, $fileContent, $fileMetadata, $true)
		        $spFile.Update()

                $ExistingFiles.Add($fileName)

		        $spListItem = $spFile.ListItemAllFields;
			
		        if($fileTaxonomyValues -ne $null -and $spListItem -ne $null){
				
                    #$fileTaxonomyValues.Keys | ForEach-Object {
                    ForEach ($fileTaxoValueOccurence in $fileTaxonomyValues.Keys){

				        $spListItem[$fileTaxoValueOccurence] = $fileTaxonomyValues[$fileTaxoValueOccurence]
			        }

			        $spListItem.SystemUpdate($false)
		        }

		        try{[void]$spFile.CheckIn("Provisioning checkin")}catch{}
		        try{[void]$spFile.Publish("Provisioning publish")}catch{}
		        try{[void]$spFile.Approve("Provisioning approve")}catch{}
		        if($welcomePage)
		        {
			        $rootFolder.WelcomePage=$welcomePageUrl
			        $rootFolder.Update() 
		        }
	        }
	        else
	        {
		        Write-Warning "Ajout impossible."
	        }
        }
    }
}

#Create a list item  in specified list of target web
#Parameter TargetWeb - SPWeb in which to create item
#Parameter List - SPList in which to create item
#Parameter FieldValues - Values for different fields of the file
function New-SPListItem([Microsoft.SharePoint.SPWeb]$TargetWeb, [Microsoft.SharePoint.SPList]$List, [System.Xml.XmlElement[]]$FieldValues)
{
    $spli = $List.AddItem();

    $FieldValues | Foreach-Object {

        $fieldValue = $_.InnerText.Replace("%%SITEURL%%", $TargetWeb.Site.ServerRelativeUrl.TrimEnd('/'));
        $fieldValue = $fieldValue.Replace("%%WEBURL%%", $TargetWeb.ServerRelativeUrl.TrimEnd('/'));
        $fieldValue = $fieldValue.Replace("%%TODAYISO%%", [Microsoft.SharePoint.Utilities.SPUtility]::CreateISO8601DateTimeFromSystemDateTime([DateTime]::Now))
		
        if ($_.GetAttribute("IsTaxo") -ne $null -and $_.GetAttribute("IsTaxo") -eq "True"){
            $field = [Microsoft.SharePoint.Taxonomy.TaxonomyField]$spli.Fields.GetFieldByInternalName($_.GetAttribute("Name"))
            
            if($field.AllowMultipleValues) {
                $taxoTermsValues = $fieldValue.Split(';', [System.StringSplitOptions]::RemoveEmptyEntries)

                $taxoMultipleValue = New-Object Microsoft.SharePoint.Taxonomy.TaxonomyFieldValueCollection($field)
                $taxoTermsValues | ForEach-Object {
                    $taxoValue = GetTaxoFieldValue -Web $TargetWeb -Field $field -Value $_
                    $taxoMultipleValue.Add($taxoValue)
                }
                [Microsoft.SharePoint.Taxonomy.TaxonomyFieldValueCollection]$spli[$field.InternalName] = $taxoMultipleValue
            }
            else {
                $fieldValue = GetTaxoFieldValue -Web $TargetWeb -Field $field -Value $fieldValue
                [Microsoft.SharePoint.Taxonomy.TaxonomyFieldValue]$spli[$field.InternalName] = $fieldValue
            }
        }
        else{
            if($_.Attributes["UserType"] -ne $null)
            {
                if($_.GetAttribute("UserType").Length -ne 0){
                    $findUser = $null
                    $findUser = Get-User -TargetWeb $TargetWeb -FieldValue $fieldValue -Permission $_.GetAttribute("UserType")
                    if( $findUser -ne $null)
                    {
                        $spli[$_.GetAttribute("Name")] =$findUser
                    }
                }
            }
            else{
                if([string]::IsNullOrEmpty($_.GetAttribute("DateTime")) -eq $false)
                {
                    try {$fieldValue = [DateTime]$fieldValue}catch{}
                }
                $spli[$_.GetAttribute("Name")] = $fieldValue
            }
        }
        #Write-Host $_.GetAttribute("Name") " : " $fieldValue
    }
    try{
        $spli.Update();
    }
    catch{}
}

# ===============================================================================
#  New-SPPublishingPage
# ===============================================================================  
function New-SPPublishingPage([Microsoft.SharePoint.SPWeb]$TargetWeb, [string]$PageFileUrl, [object[]]$Metadata, [Object[]]$WebParts)
{
    $pweb = $null;

    try
    {
        $pweb = [Microsoft.SharePoint.Publishing.PublishingWeb]::GetPublishingWeb($TargetWeb);
        $ppage = $pweb.AddPublishingPage($PageFileUrl, $pweb.DefaultPageLayout);
        $Metadata | Foreach-Object {
            $fieldValue = $_.InnerText.Replace("%%SITEURL%%", $TargetWeb.Site.Url.TrimEnd('/'));
            $fieldValue = $fieldValue.Replace("%%WEBURL%%", $TargetWeb.ServerRelativeUrl.TrimEnd('/'));
            $fieldValue = $fieldValue.Replace("%%TODAYISO%%", [Microsoft.SharePoint.Utilities.SPUtility]::CreateISO8601DateTimeFromSystemDateTime([DateTime]::Now));
            if(![string]::IsNullOrEmpty($_.GetAttribute("Path")) -and ![string]::IsNullOrEmpty($_.GetAttribute("TargetFolder")))
            {
                Upload-File -LocalPath $_.GetAttribute("Path") -TargetWeb $TargetWeb -TargetFolder $_.GetAttribute("TargetFolder") -FieldValues $null;
            }

            if ($_.GetAttribute("IsTaxo") -ne $null -and $_.GetAttribute("IsTaxo") -eq "True"){
                $field = [Microsoft.SharePoint.Taxonomy.TaxonomyField]$ppage.ListItem.Fields.GetFieldByInternalName($_.GetAttribute("Name"))
            
                if($field.AllowMultipleValues) {
                    $taxoTermsValues = $fieldValue.Split(';', [System.StringSplitOptions]::RemoveEmptyEntries)

                    $taxoMultipleValue = New-Object Microsoft.SharePoint.Taxonomy.TaxonomyFieldValueCollection($field)
                    $taxoTermsValues | ForEach-Object {
                        $taxoValue = GetTaxoFieldValue -Web $TargetWeb -Field $field -Value $_
                        $taxoMultipleValue.Add($taxoValue)
                    }
                    [Microsoft.SharePoint.Taxonomy.TaxonomyFieldValueCollection]$ppage.ListItem[$field.InternalName] = $taxoMultipleValue
                }
                else {
                    $fieldValue = GetTaxoFieldValue -Web $TargetWeb -Field $field -Value $fieldValue
                    [Microsoft.SharePoint.Taxonomy.TaxonomyFieldValue]$ppage.ListItem[$field.InternalName] = $fieldValue
                }
            }
            else{
                if($_.Attributes["UserType"] -ne $null)
                {
                    if($_.GetAttribute("UserType").Length -ne 0){
                        $findUser = Get-User -TargetWeb $TargetWeb -FieldValue $fieldValue -Permission $_.GetAttribute("UserType")
                        if( $findUser -ne $null)
                        {
                            $ppage.ListItem[$_.GetAttribute("Name")] = $findUser
                        }
                    }
                }
                else{
                    $ppage.ListItem[$_.GetAttribute("Name")] = $fieldValue;
                }
            }
            #Write-host $_.GetAttribute("Name") "/" $fieldValue;

        }
        $ppage.ListItem.Update(); 
        $ppage.Update(); 
        $ppageFile = $ppage.ListItem.File; 

        if($WebParts)
        {
            #Add publicity webpart
            $wpmanager = $ppageFile.GetLimitedWebPartManager([System.Web.UI.WebControls.WebParts.PersonalizationScope]::Shared);
            $WebParts | ForEach-Object{
                $webpart = New-Object $_.GetAttribute("Type");
                $wpmanager.AddWebPart($webpart, $_.GetAttribute("Zone"),$_.GetAttribute("Order"));
            }
        }
        #checkin
        try{[void]$ppageFile.CheckIn("Provisioning checkin")}catch{}
        try{[void]$ppageFile.Publish("Provisioning publish")}catch{}
        try{[void]$ppageFile.Approve("Provisioning approve")}catch{}
    }
    catch [Exception]
    {
        $_;
    }
    finally
    {

        if($pweb -ne $null) {$pweb.Close();}
        if($spWeb -ne $null) {$spWeb.Dispose();}
        if($spSite -ne $null) {$spSite.Dispose(); }
        if($wpmanager -ne $null) {$wpmanager.Dispose();}
    }
}

#Create a folder specified list of target web
#Parameter TargetWeb - SPWeb in which to create item
#Parameter List - SPList in which to create item
#Parameter Name - Name of the folder
function New-Folder([Microsoft.SharePoint.SPWeb]$TargetWeb, [Microsoft.SharePoint.SPList]$List, [string]$Name)
{
	try{
		$spli = $List.Items.Add("",[Microsoft.SharePoint.SPFileSystemObjectType]::Folder,$Name);
		$spli.Update();
	}
	catch [Exception]
    {
        Write-Warning $_.Exception.Message;
    }
}

#get user by login, if not exist, get the first user with the same permission
function Get-User([Microsoft.SharePoint.SPWeb]$TargetWeb, [string]$FieldValue, [string]$Permission)
{
    #Write-Host "get User" $FieldValue
	#$TargetWeb.AllUsers | ForEach-Object{ Write-Host "User:" $_.LoginName }
	
	$findUser = $null;
	
	if([string]::IsNullOrEmpty($FieldValue) -eq $false)
	{
		#$findUser = $TargetWeb.AllUsers |where{$_.LoginName.Contains($FieldValue)}
		#Write-Host "USERS:" $TargetWeb.AllUsers
		$findUser = $TargetWeb.AllUsers |where{ [string]::Compare($_.LoginName, $FieldValue, $true) }
	}
	
    if($findUser -eq $null)
    {
        $notFound = $true
        for ( $i = 0;( ($i -le ($TargetWeb.RoleAssignments.Count - 1)) -and $notFound); $i++) {
            $WebRoleAssignment = $TargetWeb.RoleAssignments[$i]
            if($WebRoleAssignment.Member.userlogin) 
            {
                if($WebRoleAssignment.RoleDefinitionBindings[0].Name -eq $Permission)
                {
                    $findUser = $WebRoleAssignment.Member
                }
            }
            else 
            {
                for ( $j = 0; (($j -le ($WebRoleAssignment.member.users.Count - 1)) -and $notFound); $j++) {
                    if($WebRoleAssignment.RoleDefinitionBindings[0].Name -eq $Permission)
                    {
                        $findUser = $WebRoleAssignment.member.users[$j]
                    }
                }
            }
        }
    }
    try{
		#Write-host "Login name:" $findUser.LoginName
        $findUser = $TargetWeb.EnsureUser($findUser.LoginName)
    }catch{
		$findUser = $null;
	}
    return $findUser
}

#get user by display name
function Get-UsersByDisplayName([Microsoft.SharePoint.SPWeb]$TargetWeb, [string]$FieldValue)
{
    #Write-Host "get User" $FieldValue
	#$TargetWeb.AllUsers | ForEach-Object{ Write-Host "User:" $_.LoginName }
	
	$Users = $FieldValue.Split(@(';'), [StringSplitOptions]::RemoveEmptyEntries)

	#$Users | ForEach-Object{
    ForEach ($userOccurence in $Users){
	
		$Site = $TargetWeb.Site
		$ProviderName = $Site.WebApplication.IisSettings[$Site.Zone].WindowsClaimsAuthenticationProvider.ClaimProviderName
		$Providers = @($ProviderName)
		$Uri = New-Object Uri($TargetWeb.Url)
		$Options = [Microsoft.SharePoint.Administration.Claims.SPClaimProviderOperationOptions]::None
		$SearchTypes = @( "User" )
		
		$UserEntities = [Microsoft.SharePoint.Administration.Claims.SPClaimProviderOperations]::Search($Uri, $Options, $Providers, $SearchTypes, $userOccurence, 10) | %{ $_.EntityData }     

		if($UserEntities.Count -gt 0)
		{
			$UserEntity = $UserEntities | Select -First 1
			
			try{
				$User = $TargetWeb.EnsureUser($UserEntity.Key)
				$findUser = $findUser + $User.ID.ToString() + ";#;#"
				#Write-Host "Found User:" $findUser
			}
			catch
			{
				Write-Warning "Utilisateur introuvable: " $UserEntity.Key;
				$findUser = $null;
				return;
			}
		}
		else
		{
			Write-Warning "Utilisateur introuvable: $userOccurence"
			$findUser = $null;
			return;
		}
	}
    return $findUser
}

##########################################################################
#									MAIN
##########################################################################
try
{
    if ($SiteUrl -eq ""){
        $SiteUrl = AskForSiteCollectionUrl
    }
    #loading xml
    if (![System.IO.Path]::IsPathRooted($XmlLocation)){
        $XmlLocation = Join-Path(Get-ScriptDirectory) $XmlLocation
    }
    $XmlPath = Join-Path -Path $XmlLocation -ChildPath $XmlName
    [xml]$configContent = Get-Content($XmlPath) -Encoding UTF8 -ReadCount 0 #to resolve encoding issue (accentuated words in french)
    if ($configContent -eq $null) 
    {
        #Write-host "Could not load xml file" -ForegroundColor Red; 
		Write-Warning "Impossible de charger le fichier XML.";
        return ;
    }


    $configContent.Webs.Web | ForEach-Object{

        $path = $SiteUrl.TrimEnd('/') + $_.GetAttribute("Url").TrimEnd('/');
        #WriteDecoredLine -Message ("Site de destination : " + $path)
		#Write-Output "IMPORT DANS LE SITE $path EN COURS..."
		
        try
        {
            #WriteDecoredLine -Message ("Valeur de variable path : " + $path);
			$spSite = New-Object Microsoft.SharePoint.SPSite($path);
            $spWeb = $spSite.OpenWeb();
            if($_.List -and ($DisableItems -eq $false))
            {
                $_.List | ForEach-Object{

                    try
                    {
                        if(([string]::IsNullOrEmpty($_.GetAttribute("Url")) -eq $false) -or ([string]::IsNullOrEmpty($ListUrl) -eq $false))
                        {
							if ($ListUrl -eq "")
							{
								$listUrl = $_.GetAttribute("Url");
							}
							else
							{
								$listUrl = $ListUrl;
							}
							
                            $curList = $spWeb.GetList($spWeb.ServerRelativeUrl.TrimEnd('/') + $listUrl);
                            $contentTypeFields = $curList.ContentTypes[0].Fields;
                            
                            $targetFolder = $curList.RootFolder;
                            [System.Collections.ArrayList]$existingFilesInFolder = $targetFolder.Files | select -ExpandProperty Name           
                            
                            if ($_.Folder){
                                
                                $_.Folder | Foreach-Object{

                                    try{
										$today = Get-Date -format "yyyyMMdd";
										$folderName = $_.GetAttribute("Name").Replace("%%TODAY%%", $today);
                                        #Write-Host "Creating folder " $folderName;
                                        New-Folder -TargetWeb $spWeb -List $curList -Name $folderName;
                                    }
                                    finally{}
                                }
                            }
                            if ($_.File){
                                $totalFilesCount = $_.File.Count;
                                $currentFilePosition = 0;

                                $_.File | Foreach-Object{
                                    
                                    $currentFilePosition++;

                                    try{
										if([string]::IsNullOrEmpty($FileLocation) -eq $false)
										{
											$folderpath = Join-Path -path $FileLocation -childpath $_.GetAttribute("FilePath");
										}
										else
										{
											$folderpath = $_.GetAttribute("FilePath");
										}
										
										#$filepath = Join-Path -path $scriptDirectory -childpath $folderpath
										$filepath = $folderpath
										$folder = $_.GetAttribute("Folder");
										$today = Get-Date -format "yyyyMMdd";
										$folder = $folder.Replace("%%TODAY%%", $today);
										
										if($folder -eq "")
										{
											##########################################################################
											#CNCCFP : move to private folder if date < 04-01-2017 (MM-dd-yyyy)
											##########################################################################
											if($HasPrivateFolder -eq $true)
											{
												$dateField = $_.Field | where{$_.GetAttribute("Name").Contains("DCSDateJour")}
												if($dateField -ne $null)
												{
													#Write-Output "Vérification de la date $dateValue.";
													try 
													{
														$dateValue = [DateTime]$dateField.InnerText;
														$dueDate = [DateTime]"04-01-2017";
													
														#Write-Output "dateValue:$dateValue vs dueDate:$dueDate";

														if($dateValue -lt $dueDate)
														{
															$folder = "/AccesRestreint";
															#Write-Output "Déplacement dans dossier : $folder";
														}
													}
													catch [Exception]
													{
														Write-Output $_.Exception.Message;
													}
												}
											}
											##########################################################################
										}
										
                                        #Write-Host "Uploading file " $filepath " in " $folder;
										if($folder -eq "")
										{
											Write-Output "`nImport $currentFilePosition/$totalFilesCount $folderpath.";
										}
										else
										{
											Write-Output "`nImport $currentFilePosition/$totalFilesCount $folderpath dans dossier $folder.";
										}
										
										$overwrite= ($_.GetAttribute("Overwrite") -ne $null) -and ($_.GetAttribute("Overwrite") -eq "True");

                                        #Get SharePoint target folder
                                        $targetFolderUrl = ($listUrl + $folder).Trim('/'); 
                                        if($targetFolderUrl -ne $targetFolder.Url) 
                                        {
                                            $targetFolder = $spWeb.GetFolder($spWeb.ServerRelativeUrl.TrimEnd('/') + "/" + $targetFolderUrl)
                                            [System.Collections.ArrayList]$existingFilesInFolder = $targetFolder.Files | select -ExpandProperty Name                                       
                                        }
                                        
                                        if($existingFilesInFolder -eq $null) {
                                            $existingFilesInFolder = New-Object System.Collections.ArrayList($null)
                                        }

                                        $timer = [System.Diagnostics.Stopwatch]::StartNew();
                                        
										Upload-File -LocalPath "$filepath" -TargetWeb $spWeb -ContentTypeFields $contentTypeFields -TargetFolder $targetFolder -ExistingFiles $existingFilesInFolder -FieldValues $_.Field -Overwrite $overwrite;
                                        Write-Output "`n... DONE IN" $timer.Elapsed.ToString()
                                    }
									catch [Exception]
									{
										Write-Output $_.Exception.Message;
									}
                                    finally{}
                                }
                            }
                            if($_.ListItem)
                            {
                                $_.ListItem | ForEach-Object{
                                
                                    try
                                    {
                                        Write-Host "Creating item in " $_.GetAttribute("ListUrl")
                                        New-SPListItem -TargetWeb $spWeb -List $curList -FieldValues $_.Field
                                    }
                                    finally {}
                                }
                            }
                        }
                    }
                    finally {}
                }
            }

            if($_.Page -and ($DisablePages -eq $false))
            {
                $_.Page | ForEach-Object {
                    try
                    {
                        Write-Host "Creating page " $_.GetAttribute("FileUrl")
                        New-SPPublishingPage -TargetWeb $spWeb -PageFileUrl $_.GetAttribute("FileUrl") -Metadata $_.Fields.Field -WebParts $_.WebParts.WebPart 
                    }
                    finally{}
                }
            }
        }
        finally{
            if ($spWeb -ne $null){
                $spWeb.Dispose()
            }
            if ($spSite -ne $null){
                $spSite.Dispose()
            }
        }
    }
}
# catch [Exception]
# {
# Write-Host "Erreur : " $_.Exception.Message -ForegroundColor Red
# }
finally
{
}