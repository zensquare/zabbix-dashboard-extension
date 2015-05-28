<?php

/*
 * * Zabbix
 * * Copyright (C) 2001-2014 Zabbix SIA
 * *
 * * This program is free software; you can redistribute it and/or modify
 * * it under the terms of the GNU General Public License as published by
 * * the Free Software Foundation; either version 2 of the License, or
 * * (at your option) any later version.
 * *
 * * This program is distributed in the hope that it will be useful,
 * * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * * GNU General Public License for more details.
 * *
 * * You should have received a copy of the GNU General Public License
 * * along with this program; if not, write to the Free Software
 * * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * */


require_once dirname(__FILE__) . '/include/config.inc.php';
require_once dirname(__FILE__) . '/include/hosts.inc.php';
require_once dirname(__FILE__) . '/include/events.inc.php';
require_once dirname(__FILE__) . '/include/actions.inc.php';
require_once dirname(__FILE__) . '/include/discovery.inc.php';
require_once dirname(__FILE__) . '/include/html.inc.php';


$CSV_EXPORT = false;

$page['title'] = _('Central Event Log ');
$page['file'] = 'eventlog.php';
$page['hist_arg'] = array('groupid', 'hostid');
$page['scripts'] = array('class.calendar.js', 'gtlc.js');
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

if (PAGE_TYPE_HTML == $page['type']) {
    define('ZBX_PAGE_DO_REFRESH', 1);
}


require_once dirname(__FILE__) . '/include/page_header.php';

/*
 * Ajax
 */
if (isset($_REQUEST['favobj'])) {
    if ('filter' == $_REQUEST['favobj']) {
        CProfile::update('web.events.filter.state', $_REQUEST['favstate'], PROFILE_TYPE_INT);
    }
    // saving fixed/dynamic setting to profile
    if ('timelinefixedperiod' == $_REQUEST['favobj']) {
        if (isset($_REQUEST['favid'])) {
            CProfile::update('web.events.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
        }
    }
}

if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
    require_once dirname(__FILE__) . '/include/page_footer.php';
    exit();
}


$selected_server = "";
if (isset($_REQUEST['server'])) {
    $selected_server = mysql_real_escape_string($_REQUEST['server']);
}

$selectedPriority = "";
if (isset($_REQUEST['priority'])) {
    $selectedPriority = mysql_real_escape_string($_REQUEST['priority']);
}



$effectiveperiod = navigation_bar_calc();
$from = zbxDateToTime($_REQUEST['stime']);
$till = $from + $effectiveperiod;


$serverFilter = "";
if ($selected_server != "" && $selected_server != "0") {
    $serverFilter = " AND host = '$selected_server'";
}

$priorityFilter = "";
if($selectedPriority != "" && $selectedPriority != "0"){
    $priorityFilter = " AND priority = '$selectedPriority'";
}

$timeFilter = " msg != '' AND unix_timestamp(`datetime`) > $from AND unix_timestamp(`datetime`) < $till ";




$eventsWidget = new CWidget();

// header
$frmForm = new CForm();

$eventsWidget->addPageHeader(
        _('Central Event Log') . SPACE . '[' . zbx_date2str(_('d M Y H:i:s')) . ']', array(
    $frmForm,
    SPACE,
    get_icon('fullscreen', array('fullscreen' => $_REQUEST['fullscreen']))
        )
);

$r_form = new CForm('get', 'eventlog.php');
$r_form->addVar('fullscreen', $_REQUEST['fullscreen']);
$r_form->addVar('stime', getRequest('stime'));
$r_form->addVar('period', getRequest('period'));
$r_form->addVar('triggerid', 0);


$db = mysql_connect("192.168.211.7", "zabbix", "asmd213)A)SDM@**@@");


$sql = "SELECT host FROM logs.logs GROUP BY host;";
$results = mysql_query($sql, $db);

$servers = array(" - All - ");
while ($row = mysql_fetch_assoc($results)) {
    $servers[$row['host']] = $row['host'];
}

$sql = "SELECT priority FROM logs.logs GROUP BY priority;";
$results = mysql_query($sql, $db);
echo mysql_error($db);

$level = array(" - Any - ");
while ($row = mysql_fetch_assoc($results)) {
    $level[$row['priority']] = $row['priority'];
}


$r_form->addItem(array(
    SPACE . _('Server') . SPACE,
    new CComboBox('server', $selected_server, 'javascript: submit();', $servers),
    SPACE . _('Server') . SPACE,
    new CComboBox('priority', $selectedPriority, 'javascript: submit();', $level)
));
// add host and group filters to the form
//if ($source == EVENT_SOURCE_TRIGGERS) {
//    $r_form->addItem(array(
//        SPACE . _('Host') . SPACE,
//        $pageFilter->getHostsCB(true)
//    ));
//}

$eventsWidget->addHeader(_('Events'), $r_form);
$eventsWidget->addHeaderRowNumber();

$filterForm = null;
//
//if ($source == EVENT_SOURCE_TRIGGERS) {
$filterForm = new CFormTable(null, null, 'get');


$eventsWidget->addFlicker(null, CProfile::get('web.events.filter.state', 0));

$scroll = new CDiv();
$scroll->setAttribute('id', 'scrollbar_cntr');
$eventsWidget->addFlicker($scroll, CProfile::get('web.events.filter.state', 1));

/*
 * Display
 */
$table = new CTableInfo(_('No events found.'), 'tableinfo Eventlog');



if (isset($_REQUEST['period'])) {
    $_REQUEST['period'] = getRequest('period', ZBX_PERIOD_DEFAULT);
    CProfile::update('web.eventlog.period', $_REQUEST['period'], PROFILE_TYPE_INT);
} else {
    $_REQUEST['period'] = CProfile::get('web.eventlog.period');
}



$config = select_config();
// source not discovery i.e. trigger
$table->setHeader(array(
    new CCol(_('Time'), "el_time"),
    new CCol(_('Server'), "el_server"),
    new CCol(_('Program'), "el_program"),
    new CCol(_('Tag'), "el_tag"),
    new CCol(_('Severity'), "el_severity"),
    new CCol(_('Event ID'), "el_event_id"),
    new CCol(_('Message'), "el_message")
));

$sql = "SELECT count(*) as total FROM logs.logs WHERE $timeFilter $serverFilter $priorityFilter ORDER BY `datetime` DESC LIMIT 25";
$results = mysql_query($sql, $db);
$total = 0;
if ($row = mysql_fetch_assoc($results)) {
    $total = $row['total'];
}
$pager_page = 1;
if(isset($_REQUEST['page'])){
    $pager_page = $_REQUEST['page'];
}
$pager_page = max($pager_page,1);

$limit = 25;
$start = $limit*($pager_page-1);


$sql = "SELECT * FROM logs.logs WHERE $timeFilter $serverFilter $priorityFilter ORDER BY `datetime` DESC LIMIT $start,$limit";

//echo $sql;

$results = mysql_query($sql, $db);
echo mysql_error($db);


        $pMerge = array(
            'info'=>'Info',
            'notice'=>'Info',
            'warn'=>'Warning',
            'warning'=>'Warning',
            'err'=>'Error',
            'error'=>'Error',
            'crit'=>'Critical',
            'critical'=>'Critical'          
        );
        
        $cMerge = array(
            'Info'=>'low',
            'Warning'=>'average',
            'Error'=>'high',
            'Critical'=>'disaster'
        );

while ($row = mysql_fetch_assoc($results)) {
    $status = isset($pMerge[$row['priority']])?$pMerge[$row['priority']]:$row['priority'];
    $class  = isset($cMerge[$status])?$cMerge[$status]:$status;
    $table->addRow(array(
        $row['datetime'],
        $row['host'],
        $row['facility'],
        $row['tag'],
        new CCol($status,$class),
        isset($row['eventID'])?$row['eventID']:"",
        $row['msg']));
}


$paging = pager($total, $limit, $pager_page);

$table = array($paging, $table, $paging);

$eventsWidget->addItem($table);

$timeline = array(
    'period' => $effectiveperiod,
//    'starttime' => date(TIMESTAMP_FORMAT, $starttime),
    'usertime' => date(TIMESTAMP_FORMAT, $till)
);

$objData = array(
    'id' => 'timeline_1',
    'loadSBox' => 0,
    'loadImage' => 0,
    'loadScroll' => 1,
    'dynamic' => 0,
    'mainObject' => 1,
    'periodFixed' => CProfile::get('web.events.timelinefixed', 1),
    'sliderMaximumTimePeriod' => ZBX_MAX_PERIOD
);

zbx_add_post_js('jqBlink.blink();');
zbx_add_post_js('timeControl.addObject("scroll_events_id", ' . zbx_jsvalue($timeline) . ', ' . zbx_jsvalue($objData) . ');');
zbx_add_post_js('timeControl.processObjects();');

$eventsWidget->show();

require_once dirname(__FILE__) . '/include/page_footer.php';

function pager($items, $itemsPerPage, $currentPage, $urlParams = array(), $removeUrlParams = array()) {
    $startPage = 1;
    $pagesCount = floor($items/$itemsPerPage)+1;
    $endPage = $pagesCount;


    $url = new Curl();
    $pageLine = array();

    if (is_array($urlParams) && $urlParams) {
        foreach ($urlParams as $key => $value) {
            $url->setArgument($key, $value);
        }
    }

    $removeUrlParams = array_merge($removeUrlParams, array('go', 'form', 'delete', 'cancel'));
    foreach ($removeUrlParams as $param) {
        $url->removeArgument($param);
    }

    if ($startPage > 1) {
        $url->setArgument('page', 1);
        $pageLine[] = new CLink('<< ' . _x('First', 'page navigation'), $url->getUrl(), null, null, true);
        $pageLine[] = '&nbsp;&nbsp;';
    }

    if ($currentPage > 1) {
        $url->setArgument('page', $currentPage - 1);
        $pageLine[] = new CLink('< ' . _x('Previous', 'page navigation'), $url->getUrl(), null, null, true);
        $pageLine[] = ' | ';
    }

    for ($p = $startPage; $p <= $pagesCount; $p++) {
        if ($p > $endPage) {
            break;
        }

        if ($p == $currentPage) {
            $pagespan = new CSpan($p, 'bold textcolorstyles');
        } else {
            $url->setArgument('page', $p);
            $pagespan = new CLink($p, $url->getUrl(), null, null, true);
        }

        $pageLine[] = $pagespan;
        $pageLine[] = ' | ';
    }

    array_pop($pageLine);

    if ($currentPage < $pagesCount) {
        $pageLine[] = ' | ';

        $url->setArgument('page', $currentPage + 1);
        $pageLine[] = new CLink(_x('Next', 'page navigation') . ' >', $url->getUrl(), null, null, true);
    }

    if ($p < $pagesCount) {
        $pageLine[] = '&nbsp;&nbsp;';

        $url->setArgument('page', $pagesCount);
        $pageLine[] = new CLink(_x('Last', 'page navigation') . ' >>', $url->getUrl(), null, null, true);
    }

    $table = new CTable(null, 'paging');
    $table->addRow(new CCol($pageLine));



    $pageView = array();
    $pageView[] = _('Displaying') . SPACE;
    if ($items > $itemsPerPage) {
        $pageView[] = new CSpan($itemsPerPage*$currentPage, 'info');
        $pageView[] = SPACE . _('to') . SPACE;
    }

    $to = min($itemsPerPage*$currentPage + $itemsPerPage, $items );
    
    $pageView[] = new CSpan($to, 'info');
    $pageView[] = SPACE . _('of') . SPACE;
    $pageView[] = new CSpan($items, 'info');
    $pageView[] = SPACE . _('found');

    $pageView = new CSpan($pageView);

    zbx_add_post_js('insertInElement("numrows", ' . zbx_jsvalue($pageView->toString()) . ', "div");');




    return $table;
}
