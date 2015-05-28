<?php

require_once dirname(__FILE__) . '/dbblock.inc.php';

class DBEventLog extends DBBlock {

    public function _getContent($refresh = false) {
        $table = new CTableInfo(_('No eventlog data found'));
        $table->setHeader(array(
            _('System'),
            _('Notifications')
        ));

        $data = array();
        $db = mysql_connect($this->config['server'],$this->config['user'],$this->config['password']);
        
        $sql = 'SELECT host, priority, COUNT(priority) as eventCount FROM logs.logs GROUP BY host, priority;';
        $result = mysql_query($sql, $db);
        
        
        if(mysql_num_rows($result) == 0){
            return $table;
        }
        
        $table = new CTableInfo(_('No eventlog data found'));
        
        $systems = array();
        $headers = array();
        
        $pMerge = array(
            'info'=>'Notice',
            'notice'=>'Notice',
            'warn'=>'Warning',
            'warning'=>'Warning',
            'err'=>'Error',
            'error'=>'Error',
            'crit'=>'Critical',
            'critical'=>'Critical'          
        );
        
        foreach ($pMerge as $key => $value) {
            $headers[$value] = $value;
        }
        
        while ($row = mysql_fetch_assoc($result)) {
            if(!isset($systems[$row['host']])){
                $systems[$row['host']] = array();
            }
            if(isset($pMerge[$row['priority']])){
                $row['priority'] = $pMerge[$row['priority']];
            }
            $systems[$row['host']][$row['priority']] = $row['eventCount'];
            $headers[$row['priority']] = $row['priority'];
                    
        }
        
        $headersCompile = array(_("Host"));
        foreach ($headers as $value) {
            $headersCompile[] = _($value);
        }
        
        $table->setHeader($headersCompile);
        
        foreach ($systems as $systemName => $eventData) {
            
            $systemData = array(new CLink($systemName,'/zabbix/eventlog.php?server='.$systemName));
            foreach ($headers as $value) {
                if(isset($eventData[$value])){
                    $systemData[] = _($eventData[$value]);
                } else {
                    $systemData[] = _("");
                }
                
            }
            
            $table->addRow($systemData);
        }
           
        return $table;
    }
}