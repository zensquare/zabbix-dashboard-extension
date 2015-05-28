<?php

/*
 * WORK IN PROGRESS
 * this file wont really help you at this point - it only displays data, the
 * logic to collect that data isn't ready for release
 */


require_once dirname(__FILE__) . '/include/config.inc.php';
require_once dirname(__FILE__) . '/include/hosts.inc.php';
require_once dirname(__FILE__) . '/include/events.inc.php';
require_once dirname(__FILE__) . '/include/actions.inc.php';
require_once dirname(__FILE__) . '/include/discovery.inc.php';
require_once dirname(__FILE__) . '/include/html.inc.php';


$CSV_EXPORT = false;

$page['title'] = _('Email Monitoring Log');
$page['file'] = 'emailbrowser.php';
$page['hist_arg'] = array('groupid', 'hostid');
$page['scripts'] = array('class.calendar.js', 'gtlc.js');
$page['type'] = detect_page_type(PAGE_TYPE_HTML);

//if (PAGE_TYPE_HTML == $page['type']) {
//    define('ZBX_PAGE_DO_REFRESH', 0);
//}


require_once dirname(__FILE__) . '/include/page_header.php';
echo '<script type="text/javascript" src="emailmonitor/email.js"></script>';

/*
 * Ajax
 */
if (isset($_REQUEST['favobj'])) {
    if ('filter' == $_REQUEST['favobj']) {
        CProfile::update('web.emaillog.filter.state', $_REQUEST['favstate'], PROFILE_TYPE_INT);
    }
    // saving fixed/dynamic setting to profile
    if ('timelinefixedperiod' == $_REQUEST['favobj']) {
        if (isset($_REQUEST['favid'])) {
            CProfile::update('web.emaillog.timelinefixed', $_REQUEST['favid'], PROFILE_TYPE_INT);
        }
    }
}


if (isset($_REQUEST['period'])) {
    $_REQUEST['period'] = getRequest('period', 63072000);
    CProfile::update('web.emaillog.period', $_REQUEST['period'], PROFILE_TYPE_INT);
} else {
    $_REQUEST['period'] = 63072000;//CProfile::get('web.emaillog.period');
}
    $d = new DateTime();
    $d->sub(new DateInterval('P1Y'));   
if (isset($_REQUEST['stime'])) {
    $_REQUEST['stime'] = getRequest('stime', $d->format('YmdHis'));
    CProfile::update('web.emaillog.stime', $_REQUEST['stime'], PROFILE_TYPE_INT);
} else {
    $_REQUEST['stime'] = $d->format('YmdHis');//CProfile::get('web.emaillog.stime');
}


if ($page['type'] == PAGE_TYPE_JS || $page['type'] == PAGE_TYPE_HTML_BLOCK) {
    require_once dirname(__FILE__) . '/include/page_footer.php';
    exit();
}


$selected_schedule = "";
if (isset($_REQUEST['schedule'])) {
    $selected_schedule = mysql_real_escape_string($_REQUEST['schedule']);
}

$effectiveperiod = navigation_bar_calc();
$from = zbxDateToTime($_REQUEST['stime']);
$till = $from + $effectiveperiod;


$scheduleFilter = "";
if ($selected_schedule != "" && $selected_schedule != "0") {
    $scheduleFilter = " AND schedule = '$selected_schedule'";
}

$timeFilter = " unix_timestamp(`received`) > $from AND unix_timestamp(`received`) < $till ";




$eventsWidget = new CWidget();

// header
$frmForm = new CForm();

$eventsWidget->addPageHeader(
        _('Email Monitor Log') . SPACE . '[' . zbx_date2str(_('d M Y H:i:s')) . ']', array(
    $frmForm,
    SPACE,
    get_icon('fullscreen', array('fullscreen' => $_REQUEST['fullscreen']))
        )
);

$r_form = new CForm('get', 'emailbrowser.php');
$r_form->addVar('fullscreen', $_REQUEST['fullscreen']);
$r_form->addVar('stime', getRequest('stime'));
$r_form->addVar('period', getRequest('period'));
$r_form->addVar('triggerid', 0);


$schedules = array(" - All - ");
$result = DbFetchArray(DBselect(
                '
                    SELECT email_schedule.*,COUNT(email_match.schedule) as `email_count` FROM email_tracker.email_schedule LEFT JOIN email_tracker.email_match ON email_match.schedule = email_schedule.id GROUP BY email_schedule.id
                '));
        
foreach ($result as $row) {
    $schedules[$row['id']] = $row['name'];
}



$r_form->addItem(array(
    SPACE . _('Mail Filter Rule') . SPACE,
    new CComboBox('schedule', $selected_schedule, 'javascript: submit();', $schedules),
));

$eventsWidget->addHeader(_('Emails'), $r_form);
$eventsWidget->addHeaderRowNumber();

$filterForm = null;
//
//if ($source == EVENT_SOURCE_TRIGGERS) {
$filterForm = new CFormTable(null, null, 'get');


//$eventsWidget->addFlicker(null, CProfile::get('web.events.filter.state', 0));

$scroll = new CDiv();
$scroll->setAttribute('id', 'scrollbar_cntr');
$eventsWidget->addFlicker($scroll, CProfile::get('web.maillog.filter.state', 0));

/*
 * Display
 */
$table = new CTableInfo(_('No emails found.'), 'tableinfo email-log');





$config = select_config();
// source not discovery i.e. trigger
$table->setHeader(array(
    new CCol(_('Recieved'), "el_time"),
    new CCol(_('From/To'), "el_from_to")
));

$result = DbFetchArray(DBselect(
                '
                    SELECT count(*) as `total` FROM email_tracker.email_match JOIN email_tracker.email ON email_match.email = email.id  WHERE ' . $timeFilter .' ' . $scheduleFilter
                ));
$total = 0;
if (isset($result[0])) {
    $total = $result[0]['total'];
}
$pager_page = 1;
if(isset($_REQUEST['page'])){
    $pager_page = $_REQUEST['page'];
}
$pager_page = max($pager_page,1);

$limit = 25;
$start = $limit*($pager_page-1);


$result = DbFetchArray(DBselect(
                "SELECT email.* FROM email_tracker.email_match JOIN email_tracker.email ON email_match.email = email.id  WHERE $timeFilter $scheduleFilter ORDER BY email.received DESC LIMIT $start,$limit"
                ));

//$sql = "SELECT * FROM logs.logs WHERE $timeFilter $serverFilter $priorityFilter ORDER BY `datetime` DESC LIMIT $start,$limit";

//echo $sql;

//$results = mysql_query($sql, $db);
//echo mysql_error($db);

foreach ($result as $row) {
//    $status = isset($pMerge[$row['priority']])?$pMerge[$row['priority']]:$row['priority'];
//    $class  = isset($cMerge[$status])?$cMerge[$status]:$status;
    $crow = new CRow(array(
        $row['received'],
            array(new CDiv($row['subject'],'email-subject'),new CDiv($row['from'],'email-from'), new CDiv(implode(' ',explode(',',$row['to'])),'email-to'))
        
        ));
    $crow->attr('data-emailid',$row['id']);
    $table->addRow($crow);
}


$paging = pager($total, $limit, $pager_page);

$t2 = new CTable('','email-browser');
$t2->addRow(
        array(
            new CDiv('','email-above-list'),
            array(
                new CDiv(array(new CDiv('','email-header')),'email-viewer')
                )
            )
        );
$t2->addRow(
        array(
            new CDiv(array($table, $paging),'email-list'),
            new CIFrame('',null,null,'yes','email-body')
            )
        );

//$table = array('<table><tr><td>',,'</td><td>',,'</td></tr></table>');

$eventsWidget->addItem($t2);

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
