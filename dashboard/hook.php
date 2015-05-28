<?php
require_once dirname(__FILE__).'/dbblock.inc.php'; 

class CustomDashboard {

    private $blockList = array();
    private $blocks = array();
   
    private $cssFiles = array('dashboard.css'=>1);
    
    /* TODO: some kind of config to workout what blocks need to load */
    public function __construct() {
        $files = scandir(dirname(__FILE__));
        foreach($files as $file){
            if(strlen($file) > 4 && substr_compare($file, ".ini", -4, 4) === 0){
                $this->loadINI($file);
            }
        }
    } 
    
    public function loadINI($file){
        $newBlocks = parse_ini_file($file,true);
        foreach($newBlocks as $key => $newBlock){
            if(!isset($newBlock['enabled']) || $newBlock['enabled'] == 1){
                $this->blockList[$key] = $newBlock;
            }
        }
    }
    
    public function loadAll(){
        foreach ($this->blockList as $blockID => $blockConfig) {
            $this->blocks[$blockID] = $this->loadBlock($blockID);
        } 
    }
    
    public function loadBlock($blockID){
        if(isset($this->blockList[$blockID])){
            $config = $this->blockList[$blockID];
            $class = "DBBlock";
            if(isset($config['source'])){
                $sources = explode(',',$config['source']);
                foreach($sources as $source){
                    require_once dirname(__FILE__).'/'.$source;
                }
            }
            if(isset($config['class'])){
                $class = $config['class'];
            }
            return new $class($blockID,$this->blockList[$blockID]);
        }
        return FALSE;
    }
    
    public function refresh($blockID){
        $this->loadAll();
        $bid = $blockID;
        if(strpos("hat_",$blockID) === 0){
            $bid = substr($blockID,4);
        }
        
        if(isset($this->blockList[$bid])){            
            $block = $this->loadBlock($bid);
            $block->getContent(true)->show();
            return true;
        }
        return false;
    }

    /* Add the widgets to the dashboard */
    public function addToRefreshList(&$widgetRefreshParams){
        foreach ($this->blocks as $block) {
            if($block->canRefresh()){
                $block->addToRefreshList($widgetRefreshParams);
            }
        }
    }
    
    /* Add the widgets to the dashboard */
    public function loadWidgets(&$columns){
        foreach ($this->blocks as $blockid => $block) {
            $this->addToDashboard($columns, $block);
        }
    }
    
    
    protected function addToDashboard(&$columns, $block){
        $col = $block->pCol;
        $row = $block->pRow;
        
        if (!isset($columns[$col][$row])) {
            $columns[$col][$row] = $block->getWidget();
        }
        else {
            if(isset($columns[$col])){
                $columns[$col][] = $block->getWidget();
            } else {
                $columns[0][] = $block->getWidget();
            }
        }
    }
    
    public function addCss($pageType='html'){
        if($pageType!='html'){
            return;
        }
        
        $cssFiles = $this->cssFiles;
        foreach($this->blockList as $block){
            if(isset($block->config['css'])){
                if(is_array($block->config['css'])){
                    foreach($block->config['css'] as $path){
                        $cssFiles[$file] = true;
                    }
                } else {
                    $cssFiles[$block->config['css']];
                }
            }
        }
        foreach($cssFiles as $path => $foo){
            if($path[0] != "/"){
                $path = 'dashboard/'.$path;
            }
            echo '<link rel="stylesheet" type="text/css" href="'.$path.'" />'."\n";
        }
        
    }
}





