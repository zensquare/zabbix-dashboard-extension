<?php

require_once dirname(__FILE__) . '/dbblock.inc.php';

class DBLogin extends DBBlock {

    public function _getContent($refresh = false) {
        $table = new CTableInfo(_('No Login data found'));
        $table->setHeader(array(
            _('User'),
            _('System')
        ));

        $data = array();
        $db = mysql_connect($this->config['server'],$this->config['user'],$this->config['password']);
        
        $sql = 'SELECT DISTINCT  username, GROUP_CONCAT(DISTINCT address SEPARATOR "\n") as connected_to FROM logs.auth_log WHERE address <> "-" AND NOT username IN ("ANONYMOUS LOGON") AND (current_timestamp-ts_last)<900 GROUP BY username ORDER BY username;';
        $result = mysql_query($sql, $db);
        if(mysql_num_rows($result) == 0){
            return $table;
        }
        
        while ($row = mysql_fetch_assoc($result)) {
            
            $table->addRow(array(
                $row['username'],
                new CObject(str_replace("\n", "<br/>", $row['connected_to']))
            ));
        }
           
        return $table;
    }

}
