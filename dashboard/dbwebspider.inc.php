<?php

require_once dirname(__FILE__) . '/dbblock.inc.php';

class DBWebSpider extends DBBlock {

    protected $reportUrl = 'spider/report.php';
    
    public function init(){
        if(!empty($this->config['report_url'])){
            $this->reportUrl = $this->config['report_url'];
        }
    }
    
    public function _getContent($refresh = false) { 
        $table = new CTableInfo(_('No web scenarios found.'));
	$table->setHeader(array(
		_('Website'),
		_('Links'),
		_('Broken Links')
	));

	$data = array();

	// fetch links between HTTP tests and host groups
	$result = DbFetchArray(DBselect(
                'SELECT website.*, sum(status LIKE "2%") as links_ok, sum(status != "" AND NOT status LIKE "2%") as links_404, sum(status = "" OR status is null) as links_unchecked FROM zabbix_spider.website LEFT JOIN zabbix_spider.page ON page.website = Website.aid group by page.website;'));

        
	foreach ($result as $row) {
            $deadlinks = DbFetchArray(DBselect(
                'SELECT * FROM zabbix_spider.page  WHERE NOT status IS NULL AND status != "200" AND status != "" and website = '.$row['aid']
                ));
            
            $deadlink_output = array();
            $link_options = array(
                "target"=>"_blank"
            );
            foreach ($deadlinks as $deadlink) {
                $link_link = new CLink($deadlink['url']==""?"root":substr($deadlink['url'],0,50),($deadlink['url']==""||$deadlink['url'][0]=="/"?$row['url']:"").$deadlink['url']);
                $link_link->setTarget("_blank");
                $map_link  = new CLink("map",$this->reportUrl."?pid=$deadlink[aid]");
                $map_link->setTarget("_blank");
                $info_link = new CLink($deadlink["status"],"http://www.checkupdown.com/status/E$deadlink[status].html");
                $info_link->setTarget("_blank");
                
                $deadlink_output[] = new CDiv(array($link_link, " [ ", $map_link ," | ",$info_link," ]"));
            }
            $link_link = new CLink($row['name'],$row['url']);
            $link_link->setTarget("_blank");
            $map_link  = new CLink("map",$this->reportUrl."?wid=$row[aid]");
            $map_link->setTarget("_blank");
            
            $table->addRow(array(
				new CDiv(array($link_link, " [ ", $map_link ," ]")),
                                new CDiv(array(new CSpan($row['links_ok'], 'green')," / ",new CSpan($row['links_404'], 'red')," / ",new CSpan($row['links_unchecked'], 'gray'))),
                                new CDiv($deadlink_output)
			));
	}
        return $table;
    }
}