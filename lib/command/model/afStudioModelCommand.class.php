<?php
/**
 * Studio model command class
 *
 * @package appFlowerStudio
 * @author Sergey Startsev <startsev.sergey@gmail.com>
 */
class afStudioModelCommand extends afBaseStudioCommand
{
    /**
     * Model name
     */
    protected $modelName = null;
    
    /**
     * Schema file path
     */
    protected $schemaFile = null;
    
    /**
     * Modificator instance
     */
    private $modificator = null;
    
    /**
     * Getting modificator instance - with lazy load
     *
     * @return afStudioModelCommandModificator
     * @author Sergey Startsev
     */
    protected function getModificator()
    {
        if (is_null($this->modificator)) {
            $this->modificator = afStudioModelCommandModificator::create()->setModelName($this->modelName)->setSchemaFile($this->schemaFile);
        }
        
        return $this->modificator;
    }
    
    /**
     * Pre-process method
     *
     * @author Sergey Startsev
     */
    protected function preProcess()
    {
        $this->modelName = $this->getParameter('model');
        
        $schema = $this->getParameter('schema');
        if (!empty($schema)) {
            $this->schemaFile = $this->getParameter('schema');
        }
    }
    
    /**
     * Checking existed model or not
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processHas()
    {
        if (!is_null($this->getModificator()->getSchemaByModel($this->modelName))) {
            return afResponseHelper::create()->success(true)->message("Model <b>{$this->modelName}</b> exists");
        }
        
        return afResponseHelper::create()->success(false)->message("Model <b>{$this->modelName}</b> doesn't exists");
    }
    
    /**
     * Process get command
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processGet()
    {
        $models = $this->getModificator()->getList();
        
        return afResponseHelper::create()->success(true)->data(array(), $models, 0);
    }
    
    /**
     * Add model functionality
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processAdd()
    {
        return $this->getModificator()->addModel((bool) $this->getParameter('with_primary', false));
    }
    
    /**
     * Delete model command
     * 
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processDelete()
    {
        return $this->getModificator()->deleteModel();
    }
    
    /**
     * Rename model functionality
     * 
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processRename()
    {
        return $this->getModificator()->renameModel($this->getParameter('renamedModel'));
    }
    
    /**
     * Process read command
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processRead()
    {
        $rows = $this->getModificator()->readModelFields();
        
        return afResponseHelper::create()->success(true)->data(array(), $rows, count($rows));
    }
    
    /**
     * Process alter model command
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processAlterModel()
    {
        try {
            $response = $this->getModificator()->alterModel(json_decode($this->getParameter('fields')));
            if ($response->getParameter(afResponseSuccessDecorator::IDENTIFICATOR)) {
                $response->message("{$this->modelName} structure was successfully updated");
            }
            
            return $response;
        } catch ( Exception $e ) {
            return afResponseHelper::create()->success(false)->message($e->getMessage());
        }
    }
    
    /**
     * Update schemas command
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processUpdateSchemas()
    {
        return afResponseHelper::create()->success(true)->console(afStudioModelCommandHelper::updateSchemas());
    }
    
    /**
     * Get relations list for autocomplete
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processReadrelation()
    {
        return afResponseHelper::create()->success(true)->data(array(), $this->getModificator()->buildRelationComboModels($this->getParameter('query')), 0);
    }
    
    /**
     * Altering model - update field
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processAlterModelUpdateField()
    {
        try {
            $field = $this->getParameter('field', null);
            $fieldDef = json_decode($this->getParameter('fieldDef'));
            
            $response = $this->getModificator()->changeModelField($fieldDef, $field);
            
            if ($response->getParameter(afResponseSuccessDecorator::IDENTIFICATOR)) {
                $response->message("Field '{$fieldDef->name}' was successfully updated");
            }
            
            return $response;
        } catch ( Exception $e ) {
            return afResponseHelper::create()->success(false)->message($e->getMessage());
        }
    }
    
    /**
     * Lists the YML (*.yml, *.yaml) files in the project's fixture dir;
     * 
     * @return afResponse
     */
    protected function processGetFixtures() 
    {
        try {    
            $files = array();
            foreach ((array) afsFileSystem::create()->readDirectory(afStudioUtil::getFixturesDir(), "yml, yaml") as $key => $file_name) {
                $files[] = array('id' => $key, 'file' => $file_name,);
            }
            
            return afResponseHelper::create()->success(true)->data(array(), $files, 0);
        } catch (Exception $e) {
            return afResponseHelper::create()->success(false)->message("Couldn't list fixtures, an error occured!");
        }
    }
    
    /**
     * Imports various data files from fixtures (YAML) directory or via file upload (CSV,YAML,Spreadsheets).
     * 
     * @throws Exception
     * @return afResponse
     * @author Tamas Geshitz
     */
    protected function processImportData() {
    	
    	$remote_files = json_decode($this->getParameter("remote_files", '{}'), true);
    	sort($remote_files);
    	
    	$formats = array
    	(
    	"yml" => "yml",
    	"yaml" => "yml",
    	"csv" => "csv",
    	"xlsx" => "xls",
    	"xls" => "xls",
    	"ods" => "xls",
    	);
    	
    	foreach(array("has_headers","append","model","name","tmp","code","delimeter","enclosure","raw","worksheet","worksheets_as_models") as $item) {
    		$params[$item] = $this->getParameter($item);
    	}
    	
    	$params["append"] = ($params["append"] === "true" || $params["append"] === "on") ? true : false;
    	$params["delimeter"] = $params["delimeter"] ? $params["delimeter"] : ",";
    	$params["enclosure"] = $params["enclosure"] ? $params["enclosure"] : '"';

    	try {
    		if(!$params["name"]) {
	    		foreach($remote_files as $file) {
	    			$files[] = afStudioUtil::getFixturesDir()."/".$file;
	    		}	
	    		$class = "YmlImporter";
    		} else {
    			$ext = afsFileSystem::create()->getExtension($params["name"]);
    			if(!array_key_exists($ext, $formats)) {
    				throw new Exception("Unsupported file: ".$params["name"]);
    			}
    			$class = ucfirst($formats[$ext]."Importer");
    			if($params["code"] !== 0) {
    				throw new Exception("Failed to upload file: ".$params["name"]);
    			} 
    			
    			$uploaded_file = afStudioUtil::getUploadsDir()."/".$params["name"];
    			
    			if(!@move_uploaded_file($params["tmp"], $uploaded_file)) {
    				throw new Exception("Couldn't process uploaded file: ".$params["name"]);
    			}
    			
    			$files = array($uploaded_file);
    		}
    		
    		$importer = new $class($params);
    		$importer->setProperty("all",$files);
    		
    		foreach ($files as $k => &$file) {
				
    			$importer->setProperty("current",$file);
	    		$importer->loadData($file);
	    		if(!($importer instanceof CsvImporter)) {
	    			$importer->insertData($file);	
	    		}
	    		
			}
  
	    	if($params["name"]) {
	    		if(!@unlink($uploaded_file)) {
	    			throw new Exception("Couldn't delete tmp file: ".$params["name"]);
	    		}
	    	}
    		
    	} catch(Exception $e) {
    		return afResponseHelper::create()->success(false)->message("An error has been occured!<br/><br/>".$e->getMessage());	
    	}
    	
    	$errors = $importer->getProperty("errors");
    	
    	if($class == "YmlImporter" && $errors) {
    		return afResponseHelper::create()->success(false)->message("Error(s) have been occured:<br/><br/>".implode("<br />", $errors));	
    	}
    	
    	return afResponseHelper::create()->success(true)->message("Data has been successfully inserted!");
    	
    }
    
    
    /**
     * Validate schema
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processValidateSchema()
    {
        return $this->getModificator()->validateSchema();
    }
    
    /**
     * Export data for a model
     * 
     * @return afResponse
     * @author Radu Topala
     */
    protected function processExportData()
    {
        $model = new $this->modelName();
        $peer = $model->getPeer();
        $table= $peer::TABLE_NAME;
        
        $db = afStudioUtil::getDbParams();
        $backupDir = sfConfig::get('sf_root_dir').'/data/sql/backup/';
        $backupFile = $backupDir.$table.'_'.time().'.sql';
        $webFile = 'data/sql/backup/'.$table.'_'.time().'.sql';
        
        $console_result = afStudioConsole::getInstance()->execute(array("mkdir {$backupDir}","mysqldump -u{$db['username']} -p{$db['password']} {$db['dbname']} {$table} --no-create-info --lock-tables --quick --complete-insert > {$backupFile}"));
        
        return afResponseHelper::create()->success(true)->message("A data backup of {$this->modelName} model was created at <a href=\"/studio#file#{$webFile}\">./{$webFile}</a>")->console($console_result);
    }
    
    /**
     * Export model definition 
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processExport()
    {
        $model = $this->getParameter('model');
        $response = $this->getModificator()->getModelDefinition($model);
        
        if ($response->getParameter(afResponseSuccessDecorator::IDENTIFICATOR)) {
            $definition = $response->getParameter(afResponseDataDecorator::IDENTIFICATOR_DATA);
            $temp_file = sys_get_temp_dir() . "/{$model}Model.txt";
            
            if (file_put_contents($temp_file, $definition)) {
                $response->data(array(), $temp_file, 0);
            }
        }
        
        return $response;
    }
    
    /**
     * Import model definition
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processImport()
    {
        $response = afResponseHelper::create();
        $model = $this->getParameter('model');
        if (empty($model)) return $response->success(false)->message("You haven't defined model name");
        
        $temp_file = tempnam(sys_get_temp_dir(), 'import_model');
        
        if (!empty($_FILES) && array_key_exists('file', $_FILES) && ($params = $_FILES['file']) && ($params['size'] > 0) ) {
            if ($params['size'] > 512000) return $response->success(false)->message("Model definition file shouldn't be so huge");
            $name = explode('.',$params['name']);
            $ext = $name[count($name)-1];
            if (!in_array($ext, array("yml"))) {
                return $response->success(false)->message("Invalid file type. Must be of *.yml type.");
            }
            
            if (move_uploaded_file($params["tmp_name"], $temp_file )) {
                $tempResponse = $this->getModificator()->setModelDefinition($model, sfYaml::load(file_get_contents($temp_file)));
                
                if ($tempResponse->getParameter(afResponseSuccessDecorator::IDENTIFICATOR)) {
                    $response->success(true)->message("Definition was updated successfully!");
                }
        
                return $response;
            }
        }
        
        return $response->success(false)->message("You haven't defined model definition for upload");
    }
    
    /**
     * Getting schemas structure
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processGetStructure()
    {
        return afResponseHelper::create()->data(array(), $this->getModificator()->getOriginalSchema(), 0);
    }
    
    /**
     * Setting schemas structure
     *
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processSaveStructure()
    {
        return $this->getModificator()->updateOriginalSchema(json_decode($this->getParameter('structure'), true));
    }
    
}
