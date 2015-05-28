<?php

class DBBlock {

        private $id = "generic";
        private $title = "Custom Block";
        
        public $pRow = 0;
        public $pCol = 0;
        
        public $canRefresh = true;
        public $defaultRefreshRate = 60;
        
        private $widget = null;
        
	public function __construct($id = "generic", $config = array()) {
            $this->id = $id;
            $this->config = $config;
            
            if(isset($config['refreshrate'])){
                $this->defaultRefreshRate = $config['refreshrate'];
            }
            
            $this->canRefresh = isset($config['refreshable']) && $config['refreshable'] == 1;
            
            
            if(isset($config['title'])){
                $this->title = $config['title'];
            }
            
            $this->pRow = $this->getSetting('row', '1');
            $this->pCol = $this->getSetting('col', '1');
            
            $this->init();
	}
        
        public function init(){
            
        }
        
        public function getWidget(){
            if(isset($this->widget)){
                return $this->widget;
            }
            $this->widget = new CCollapsibleUiWidget(
                    $this->getID(),
                    $this->getContent());
            $this->setHeader();
            $this->setFooter();
            return $this->widget;
        }
        
        protected function getSetting($setting, $default){
            return CProfile::get('web.dashboard.widget.'.$this->id.'.'.$setting, $default);
        }
        
        public function getContent($refresh=false){
            $refreshScript = $this->getRefreshScript();
            return new CDiv(array($this->_getContent($refresh), $refreshScript));
        }
        
        public function _getContent($refresh=false){
            return new CSpan(_("Content goes here"));
        }
        
        public function getRefreshScript(){
            return new CJsScript(get_js("jQuery('#".$this->getID()."_footer').html('"._s('Updated: %s', zbx_date2str(_('H:i:s')))."')"));
        }
        
        public function getFooterContent(){
            return "Updated ".date();
        }
                
        public function getMenuIcons(){
            $icons = array();
            if($this->canRefresh()){
                $icon = new CIcon(_('Menu'), 'iconmenu');
                $icon->setMenuPopup(CMenuPopupHelper::getRefresh($this->getID(), $this->getSetting('rf_rate', $this->defaultRefreshRate)));
                $icons[] = $icon;
            }
            return $icons;
        }
        
        public function getTitle(){
            return $this->title;
        }
        
        public function getID(){
            return $this->id;
        }
        
        public function setHeader(){
            $this->widget->setHeader(_($this->getTitle()), $this->getMenuIcons());
        }
        
        public function setFooter(){
            if($this->canRefresh()){
                $this->widget->setFooter(new CDiv(SPACE, 'textwhite', $this->getID().'_footer'));
            }
        }
        
        public function canRefresh(){
            return $this->canRefresh;
        }
        
        public function addToRefreshList(&$widgetRefreshParams){
            $widgetRefreshParams[$this->getID()] = array(
		'frequency' => $this->getSetting('rf_rate', $this->defaultRefreshRate),
		'url' => '?output=html',
		'counter' => 0,
		'darken' => 0,
		'params' => array('widgetRefresh' => $this->getID())
            );
        }
}


