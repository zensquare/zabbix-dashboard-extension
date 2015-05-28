<?php

require_once dirname(__FILE__) . '/dbblock.inc.php';

class DBEmailMonitor extends DBBlock {

    public function _getContent($refresh = false) {
        global $DB;
        $table = new CTableInfo(_('No email log data found'));
        $table->setHeader(array(
            _('System'),
            _('Interval'),
            _('Next Expected'),
            _('Last Seen'),
            _('Total Emails'),
            _('Actions')
        ));

        $data = array();
        $db = mysql_connect("192.168.211.7","zabbix","asmd213)A)SDM@**@@");
        
        $result = DbFetchArray(DBselect(
                '
                    SELECT email_schedule.*,COUNT(email_match.schedule) as `email_count` FROM email_tracker.email_schedule LEFT JOIN email_tracker.email_match ON email_match.schedule = email_schedule.id GROUP BY email_schedule.id ORDER BY exec_order 
                '));
        
	foreach ($result as $row) {
//            $link_link = new CLink($row['name'],$row['url']);
//            $link_link->setTarget("_blank");
//            $map_link  = new CLink("map","/spider_report.php?wid=$row[aid]");
//            $map_link->setTarget("_blank");
            
            $color = $row['missed']==0?'green':'red';
            $last_seen = (empty($row['last_seen'])||$row['last_seen']=='0000-00-00 00:00:00')?'Never':$row['last_seen'];
            
            
            
            $table->addRow(array(
				new CDiv($row['name']),//array($link_link, " [ ", $map_link ," ]")),
                                new CDiv($row['expected_interval']),
                                new CDiv($row['next_expected']),
                                new CDiv(new CSpan($last_seen, $color)),
                                new CDiv($row['email_count']),
                                new CLink('Ack','/zabbix/email.php?action=ack&id='.$row['id'])
			));
//		if (!isset($httpTestData[$row['httptestid']])) {
//			$data[$row['groupid']]['unknown'] = empty($data[$row['groupid']]['unknown']) ? 1 : ++$data[$row['groupid']]['unknown'];
//		}
//		elseif ($httpTestData[$row['httptestid']]['lastfailedstep'] != 0) {
//			$data[$row['groupid']]['failed'] = empty($data[$row['groupid']]['failed']) ? 1 : ++$data[$row['groupid']]['failed'];
//		}
//		else {
//			$data[$row['groupid']]['ok'] = empty($data[$row['groupid']]['ok']) ? 1 : ++$data[$row['groupid']]['ok'];
//		}
	}
           
        return $table;
    }
}