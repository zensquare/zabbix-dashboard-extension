<?php
/* CONFIG STUFF */
$DATABASE_HOST = "localhost";
$DATABASE_USER = "root";
$DATABASE_PASSWORD = "";
$DATABASE_SCHEMA = "zabbix_spider";

$time_limit  = 3;
$max_queries = 30;

$debug = false;

mysql_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD);
mysql_select_db($DATABASE_SCHEMA);


$pid = filter_input(INPUT_GET, 'pid');
$wid = filter_input(INPUT_GET, 'wid');


if(!$pid && $wid){
    $sql = "SELECT aid FROM $DATABASE_SCHEMA.page WHERE website = '$wid' AND url = ''";
    $result = mysql_query($sql);
    if($row = mysql_fetch_assoc($result)){
        $pid = $row['aid'];
    }
}

$data = filter_input(INPUT_GET, 'data');
$x = filter_input(INPUT_GET, 'x');
$y = filter_input(INPUT_GET, 'y');

if (!$x) {
    $x = 0;
}
if (!$y) {
    $y = 0;
}


if ($data) {
    loadData($pid);
    exit();
}
?>

<html><head><title>Web crawl report</title>

        <script src="sigma/sigma.min.js"></script>
        <script src="sigma/plugins/sigma.layout.forceAtlas2.min.js"></script>
        <script src="sigma/plugins/sigma.parsers.json.min.js"></script>
        <script src="sigma/plugins/sigma.renderers.customShapes.min.js"></script>
        <script src="jquery/jquery-1.7.2.min.js"></script>
        <style>
            #sigma-container {
                top: 0;
                bottom: 0;
                left: 240px;
                right: 240px;
                position: absolute;
                height: 100%;
            }
            
            #selected {
                width: 200px;
                padding-left: 10px;
            }
            
            #options {
                width: 200px;
                position: fixed;
                top: 10px;
                right: 10px;
                background: #eee;
                padding: 10px;
            }
            
            * {
                font-family: Arial;
                font-size: 8pt;
                color: #555;
            }
            
            h1 {
                font-size: 10pt;
                margin-left: -10px;
            }
            h2 {
                font-size: 9pt;
            }
            
            #name,#url,li {
                overflow: visible;
                width: 1000px;
                display: block;
            }
            
            ul {
                padding-left:10px;
            }
            
            .fade li {
                overflow: hidden;
/*                height: 50px;
                line-height: 50px;*/
                position: relative;
                width:200px;
                white-space: nowrap;
            }
            .fade li:after {
                content: "";
                width: 100px;
                height: 50px;
                position: absolute;
                top: 0;
                right: 0;
                background: -moz-linear-gradient(left, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 56%, rgba(255,255,255,1) 100%);
                background: -webkit-gradient(linear, left top, right top, color-stop(0%,rgba(255,255,255,0)), color-stop(56%,rgba(255,255,255,1)), color-stop(100%,rgba(255,255,255,1)));
                background: -webkit-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 56%,rgba(255,255,255,1) 100%);
                background: -o-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 56%,rgba(255,255,255,1) 100%);
                background: -ms-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 56%,rgba(255,255,255,1) 100%);
                background: linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 56%,rgba(255,255,255,1) 100%);
                filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#00ffffff', endColorstr='#ffffff',GradientType=1 );
            }
            
            li:hover {
                width:1000px;
                color:#000;
            }
        </style>
    </head>
    <body>
        <div id="selected">
            <h1 id="name"></h1>
            <a id="url"></a>
            <div id="status"></div>
            <div id="type"></div>
            <div id="last_checked"></div>
            <h2>Outbound</h2>
            <ul id="outbound" class="fade"></ul>
            <h2>Inbound</h2>
            <ul id="inbound" class="fade"></ul>
        </div>
        <div id="options">
            <div class="option"><input type="checkbox" id="show-resources" checked="checked" /> Show Resources</div>
        </div>
        <div id="sigma-container"></div>

        <script>
            var show_resources = true;
            
            jQuery(document).ready(function(){
                show_resources = jQuery('#show-resources').attr('checked') === "checked";
                jQuery('#show-resources').on('click', function(){
                    show_resources = $('#show-resources').attr('checked') === "checked";
                    filter();
                });
            });

            sigma.classes.graph.addMethod('neighbors', function(nodeId) {
                var k,
                        neighbors = {},
                        index = this.allNeighborsIndex[nodeId] || {};

                for (k in index)
                    neighbors[k] = this.nodesIndex[k];

                return neighbors;
            });

            // Now, let's use the renderer
            var i,
                    s,
                    N = 50,
                    E = 150,
                    g = <?php loadData($pid); ?>,
                    colors = [
                        '#617db4',
                        '#668f3c',
                        '#c6583e',
                        '#b956af'
                    ];
                    
            s = new sigma({
                graph: g,
                renderer: {
                    container: document.getElementById('sigma-container'),
                    type: 'canvas'
                }
            });
            
            filteredNodes = [];
            filteredEdges = {};
            
            function filter(){
                var readded = [];
                for(var i = 0; i < filteredNodes.length; i++){ 
                    if(!filterNode(filteredNodes[i])){
                        s.graph.addNode(filteredNodes[i]);
                        readded.push(filteredNodes[i]);
                        filteredNodes[i] = null;
                    }
                }
                readded.forEach(function(n){
                    if(filteredEdges[n.id]){
                        filteredEdges[n.id].forEach(function(e){
                            s.graph.addEdge(e);
                        });
                    }
                });
                
                s.graph.nodes().forEach(function(n) {
                                if(filterNode(n)){
                                    filteredNodes.push(n);
                                }
                            });
                            
                            
                filteredEdges = {};
                
                var newFilteredNodes = [];
                for(var i = 0; i < filteredNodes.length; i++){ 
                    if(filteredNodes[i] != null){
                        newFilteredNodes.push(filteredNodes[i]);
                        filteredEdges[filteredNodes[i].id] = [];
                    }
                };
                filteredNodes = newFilteredNodes;
                
                s.graph.edges().forEach(function(e,index) {
                    if(filteredEdges[e.target]){
                        filteredEdges[e.target].push(e);
                    } else if(filteredEdges[e.source]){
                        filteredEdges[e.source].push(e);
                    }
                });
                
                filteredNodes.forEach(function(n) {
                    s.graph.dropNode(n.id);
                });
                
                s.refresh();
                s.startForceAtlas2();
            }
            
            function filterNode(node){
                if(node.raw.status != "200"){
                    return false;
                }
                
                return !show_resources && node.raw.type == "text/css";
            }

            s.bind('clickNode', function(e) {
                var node = e.data.node;
                $("#name").text(node.label);
                $("#url").text(node.raw.url);
                $("#url").attr("href",node.raw.url);
                $("#status").text("Last Status : " + node.raw.status);
                $("#type").text("Content Type : " + node.raw.type);
                $("#last_checked").text("Last Checked : " + node.raw.ts_last);
                $("#outbound").empty();
                $("#inbound").empty();
                
                sigma.parsers.json('?data=true&pid=' + e.data.node.pid + "&x=" + e.data.node.x + "&y=" + e.data.node.y,
                        function(graph) {
                            var sg = e.data.renderer.graph;
                            var existing = sg.nodes();
                            existing = existing.concat(filteredNodes);
//                            console.log(existing);
                            for (index = 0; index < graph.nodes.length; index++) {
                                var hit = false;
                                for (j = existing.length - 1; j >= 0; j--) {
                                    if (existing[j].id == graph.nodes[index].id) {
                                        hit = true;
                                        break;
                                    }
                                }
                                if(hit){
                                    continue;
                                }
                                existing.push(graph.nodes[index]);
                                if(filterNode(graph.nodes[index])){
                                    filteredNodes.push(graph.nodes[index]);
                                    if(!filteredEdges[graph.nodes[index].id]){
                                        filteredEdges[graph.nodes[index].id] = [];
                                    }
                                } else {
                                    sg.addNode(graph.nodes[index]);
                                }
                            }
                            existing = sg.edges();
                            
                            filteredNodes.forEach(function(item,index){
                                filteredEdges[item.id].forEach(function(e){
                                    existing.push(e);
                                });
                            });
                            
                            
                            for (index = 0; index < graph.edges.length; index++) {
                                var hit = false;
                                for (j = existing.length - 1; j >= 0; j--) {
                                    if (existing[j].id == graph.edges[index].id) {
                                        hit = true;
                                        break;
                                    }
                                }
                                if(hit){
                                    continue;
                                }
                                existing.push(graph.edges[index]);
                                if(filteredEdges[graph.edges[index].target]){
                                    filteredEdges[graph.edges[index].target].push(graph.edges[index]);
                                } else if(filteredEdges[graph.edges[index].source]){
                                    filteredEdges[graph.edges[index].source].push(graph.edges[index]);
                                } else {
                                    sg.addEdge(graph.edges[index]);
                                }
                            }

                            var nodeId = e.data.node.id;
                            
                            e.data.node.color = '#666';
                            e.data.node.size = 3;

                            sg.nodes().forEach(function(n) {
                                if (nodeId != n.id){
                                    n.color = n.originalColor;
                                    n.size  = n.originalSize;
                                }
                            });

                            sg.edges().forEach(function(e) {
                                if (e.source == nodeId) {
                                    var target = sg.nodes(e.target);
                                    
                                    e.color = target.color;
                                    $("#outbound").append("<li><a target=\"_blank\" href=\""+target.raw.url+"\" style=\"color: "+target.color+";\">"+target.label+"</a></li>");
                                } else if (e.target == nodeId) {
                                    e.color = e.originalColor;
                                    var target = sg.nodes(e.source);
                                    $("#inbound").append("<li><a target=\"_blank\" href=\""+target.raw.url+"\" style=\"color: "+target.color+";\">"+target.label+"</a></li>");
                                } else {
                                    e.color = '#eee';
                                }
                            });

                            s.refresh();
                            s.startForceAtlas2();
                            if(timeout){
                                clearTimeout(timeout);
                            }
                            timeout = setTimeout(function() {
                                s.stopForceAtlas2();
                            }, 10000);
                        }
                );
            });

//            s.startForceAtlas2();
            filter();
            timeout = setTimeout(function() {
                s.stopForceAtlas2();
            }, 10000);
        </script>
    </body>
</html> 
<?php

function loadData($pid) {
    echo "{\n\"nodes\":\n[\n";
    loadNodes($pid);
    echo "\n]\n,\n\"edges\":\n[\n";
    loadEdges($pid);
    echo "\n]\n}";
}

function loadNodes($pid) {
    $first = true;
    //$sql = "SELECT * FROM page WHERE aid in ( SELECT child FROM link WHERE parent = '$pid') OR aid = '$pid'";
    $sql = "SELECT p.*, w.url as website_url, GREATEST(1,round(log(count(*)))) size, count(*) child_count FROM zabbix_spider.page p LEFT JOIN zabbix_spider.website w ON w.aid = p.website LEFT JOIN zabbix_spider.link l1 ON (l1.parent = p.aid OR l1.parent is null) WHERE p.aid IN ( SELECT child FROM zabbix_spider.link l2 WHERE l2.parent = $pid) OR p.aid = $pid GROUP BY p.aid;";
    $result = mysql_query($sql);
    echo mysql_error();
    $loaded = array();
    while ($row = mysql_fetch_assoc($result)) {
        $loaded[$row['aid']] = true;
        if ($first) {
            $first = false;
        } else {
            echo ",\n";
        }
        echoNode($row);
    }
        //$sql = "SELECT * FROM page WHERE aid in ( SELECT child FROM link WHERE parent = '$pid') OR aid = '$pid'";
    $sql = "SELECT p.*, w.url as website_url, GREATEST(1,round(log(count(*)))) size, count(*) child_count FROM zabbix_spider.page p LEFT JOIN zabbix_spider.website w ON w.aid = p.website LEFT JOIN zabbix_spider.link l1 ON (l1.child = p.aid) WHERE p.aid IN ( SELECT parent FROM zabbix_spider.link l2 WHERE l2.child = $pid) GROUP BY p.aid;";
    $result = mysql_query($sql);
    echo mysql_error();
    while ($row = mysql_fetch_assoc($result)) {
        if(isset($loaded[$row['aid']])){
            continue;
        }
        
        if ($first) {
            $first = false;
        } else {
            echo ",\n";
        }
        echoNode($row);
    }
}

function echoNode($row) {
    global $x, $y;
    if ($row['title'] == '') {
        $row['title'] = $row['url'];
    }
    $color = "#617db4";
    if($row['url'] == '' || $row['url'][0] == '/'){
        $row['url'] = $row['website_url'].'/'.$row['url'];
        $color = "#50BF44";
    }
    
    
    $size = min(3,$row[size]);
    
    if($row['status'] != '200'){
        $color = '#ec5148';
        $size = 2;
    }
    
    if($row['status'] == "404"){
        $color = "#ec5148";
        $size = 3;
    }
    
    $node = array(
        "pid" => $row[aid],
        "id" => "n-$row[aid]",
        "label" => "$row[title]",
        "x" => ($x + rand(-10, 10)),
        "y" => ($y + rand(-10, 10)),
        "size" => $size,
        "type"=>startsWith($row["type"],"text/html")?"circle":"square",
        "color" => $color ,
        "raw" => $row
    );
    $node["originalColor"] = $node["color"];
    $node["originalSize"] = $node["size"];
    echo json_encode($node);

    //echo "{\"pid\":$row[aid],\"id\":\"n-$row[aid]\",\"label\":\"$row[title]\",\"x\":" . ($x+rand(-10, 10)) . ",\"y\":" . ($y+rand(-10, 10)) . ",\"size\":$row[size],\"children\":$row[child_count],\"color\":\"#617db4\"}";
}

function startsWith($haystack, $needle)
{
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function loadEdges($pid) {
    $first = true;
    $sql = "SELECT * FROM link WHERE parent = '$pid' AND child != 0";
    $result = mysql_query($sql);
    $edge_ids = array();
    while ($row = mysql_fetch_assoc($result)) {
        if ($first) {
            $first = false;
        } else {
            echo ",\n";
        }
        $edge = array(
            "id"=>"e-$row[parent]-$row[child]",
            "source"=>"n-$row[parent]",
            "target"=>"n-$row[child]",
            "size"=>2,
            "type"=>"curvedArrow"
        );
        $edge_ids[$edge['id']] = true ;
        echo json_encode($edge);
//        echo "{\"id\":\"e-$row[parent]-$row[child]\",\"source\":\"n-$row[parent]\",\"target\":\"n-$row[child]\",\"size\":1}";
    }    
    $sql = "SELECT * FROM link WHERE child = '$pid' AND parent != 0";
    $result = mysql_query($sql);
    while ($row = mysql_fetch_assoc($result)) {
        $eid = "e-$row[parent]-$row[child]";
        if(isset($edge_ids[$eid])){
            continue;
        }
        if ($first) {
            $first = false;
        } else {
            echo ",\n";
        }
        $edge = array(
            "id"=>$eid,
            "source"=>"n-$row[parent]",
            "target"=>"n-$row[child]",
            "size"=>2,
            "type"=>"curvedArrow"
        );
        echo json_encode($edge);
////        echo "{\"id\":\"e-$row[parent]-$row[child]\",\"source\":\"n-$row[parent]\",\"target\":\"n-$row[child]\",\"size\":1}";
    }
}
