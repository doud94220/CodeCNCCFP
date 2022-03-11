<?php

session_start();

if(sizeof($_FILES) > 0)
{
    $fileUploader = new FileUploader($_FILES['files'], $_POST['repertoire']."/");
}

class FileUploader
{
    public function __construct($uploads, $repertoire)
    {
//echo $repertoire;
        // Split the string containing the list of file paths into an array
        $paths = explode("###",rtrim($_POST['paths'],"###"));
        
        $cheminsDesFichiers = '';
        
        // Loop through files sent
        foreach($uploads['name'] as $key => $current)
        {
            // Stores full destination path of file on server
            $this->uploadFile=$repertoire.rtrim($paths[$key],"/.");
//echo $this->uploadFile;
            // Stores containing folder path to check if dir later
            $this->folder = substr($this->uploadFile,0,strrpos($this->uploadFile,"/"));
            
            // Upload current file
            $this->upload($current,$this->uploadFile,$uploads['tmp_name'][$key]);
            $cheminsDesFichiers .= $this->uploadFile.';';
        }
    }
    
    private function upload($current, $uploadFile, $tmp_name)
    {
        // Checks whether the current file's containing folder exists, if not, it will create it.
        if(!is_dir($this->folder))
        {
            mkdir($this->folder,0700,true);
        }
        
        // Moves current file to upload destination
        if(move_uploaded_file($tmp_name,$uploadFile))
            return true;
            else
                return false;
    }
}

header('Location: requete_ctx.php?id='.$_GET['id'].'&upload=reussi');
