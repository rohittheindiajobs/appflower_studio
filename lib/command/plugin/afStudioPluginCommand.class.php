<?php
/**
 * Studio Plugin Command Class
 *
 * @package appFlowerStudio
 * @author Sergey Startsev <startsev.sergey@gmail.com>
 */
class afStudioPluginCommand extends afBaseStudioCommand
{
    /**
     * Get list modules, actions 
     * 
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processGetList()
    {
        $root_dir = afStudioUtil::getRootDir();
        
        $data = array();
        $pluginFolders = afStudioPluginCommandHelper::getSubFolders("{$root_dir}/plugins", 'plugin');
        
        $deprecated = afStudioPluginCommandHelper::getDeprecatedList();
        
        foreach ($pluginFolders as $pluginFolder) {
            $plugin = $pluginFolder["text"];
            
            if (in_array($plugin, $deprecated)) continue;
            
            $moduleFolders = afStudioPluginCommandHelper::getSubFolders("{$root_dir}/plugins/{$plugin}/modules/");
            $mod_datas = array();
            
            foreach ($moduleFolders as $moduleFolder) {
                $modulename = $moduleFolder["text"];
                $configfiles = afStudioPluginCommandHelper::getFiles($plugin, $modulename, ".xml");
                
                $moduleFolder["children"] = $configfiles;
                if (count($configfiles) == 0) {
                    $moduleFolder["leaf"] = true;
                    $moduleFolder["iconCls"] = "icon-folder";
                }
                array_push($mod_datas, $moduleFolder);
            }
            
            $pluginFolder["children"] = $mod_datas;
            if (count($mod_datas) == 0) {
                $pluginFolder["leaf"] = true;
                $pluginFolder["iconCls"] = "icon-folder";
            }
            array_push($data, $pluginFolder);
        }
        
        $meta = (isset($data[0])) ? array_keys($data[0]) : array();
        $total = count($data);
        
        return afResponseHelper::create()->success(true)->data($meta, $data, $total);
    }
    
    /**
     * Rename plugin functionality
     * 
     * @return afResponse
     */
    protected function processRename()
    {
        $response = afResponseHelper::create();
        
        $filesystem = new sfFileSystem();
        $afConsole = afStudioConsole::getInstance();
        
        $root_dir = afStudioUtil::getRootDir();
        
        $oldValue = $this->getParameter('oldValue');
        $newValue = $this->getParameter('newValue');
        
        $console = $afConsole->execute('afs fix-perms');
        
        $oldDir = "{$root_dir}/plugins/{$oldValue}/";
        $newDir = "{$root_dir}/plugins/{$newValue}/";
        
        if (file_exists($newDir)) return $response->success(false)->message("Plugin '{$newValue}' already exists");
        
        // $filesystem->rename($oldDir, $newDir);
        $console .= $afConsole->execute("mv {$oldDir} {$newDir}");
        
        if (!file_exists($oldDir) && file_exists($newDir)) {
            $console .= $afConsole->execute('sf cc');
            return $response->success(true)->message("Renamed plugin from <b>{$oldValue}</b> to <b>{$newValue}</b>!")->console($console);
        }
        
        return $response->success(false)->message("Can't rename plugin from <b>{$oldValue}</b> to <b>{$newValue}</b>!");
    }
    
    /**
     * Delete plugin
     * 
     * @return afResponse
     */
    protected function processDelete()
    {
        $name = $this->getParameter('name');
        
        $pluginDir = afStudioUtil::getRootDir() . "/plugins/{$name}/";
        
        $afConsole = afStudioConsole::getInstance();
        $response = afResponseHelper::create();
        
        $console = $afConsole->execute('afs fix-perms');
        $console .= $afConsole->execute('rm -rf '.$pluginDir);
        
        if (!file_exists($pluginDir)) {
            $console .= $afConsole->execute('sf cc');
            return $response->success(true)->message("Deleted plugin <b>{$name}</b>")->console($console);
        }
        
        return $response->success(false)->message("Can't delete plugin <b>{$name}</b>!");
    }
    
    /**
     * Add plugin functionality
     * 
     * @return afResponse
     * @author Sergey Startsev
     */
    protected function processAdd()
    {
        $name = $this->getParameter('name');
        
        $filesystem = new sfFileSystem;
        
        $root = sfConfig::get('sf_root_dir');
        $afConsole = afStudioConsole::getInstance();
        $response = afResponseHelper::create();
        
        $dir = "{$root}/plugins/{$name}";
        
        if (empty($name)) return $response->success(false)->message('Please enter plugin name');
        if (substr($name, -6) != 'Plugin') return $response->success(false)->message("Plugin '{$name}' should Contains 'Plugin in the end'");
        if (file_exists($dir)) return $response->success(false)->message("Plugin '{$name}' already exists");
        
        $dirs = array(
            $dir,
            "{$dir}/config",
            "{$dir}/modules",
            // "{$dir}/lib",
        );
        
        // Should be changed when security policy will be reviewed
        $dirs = implode(' ', $dirs);
        $console = $afConsole->execute("mkdir -m 775 {$dirs}");
        
        if (file_exists($dir)) {
            // create config file with auto enable all modules in current plugin
            $created = afStudioUtil::writeFile(
                "{$dir}/config/config.php", 
                afStudioPluginCommandTemplate::config($name) // Config file definition
            );
            
            return $response->success(true)->message("Plugin '{$name}' successfully created")->console($console);
        }
        
        return $response->success(false)->message("Some problems to create dirs via console")->console($console);
    }
    
}
