<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/


require_once 'av_init.php';
Session::logcheck("report-menu", "ReportsReportServer");


$year = (GET('year') != "") ? intval(GET('year')) : date("Y");
$user = Session::get_session_user();

// define colors
define('COLOR1','#D6302C');
define('COLOR2','#3933FC');
define('COLOR3','green');
define('COLOR4','yellow');
define('COLOR5','pink');
define('COLOR6','#40E0D0');
define('COLOR7','#00008B');
define('COLOR8','#800080');
define('COLOR9','#FFA500');
define('COLOR10','#A52A2A');
define('COLOR11','#228B22');
define('COLOR12','#D3D3D3');
//
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$conn->Execute('use datawarehouse');

$sql="SELECT * FROM ( SELECT * FROM
(select 'Availability' as category, s.month as mois, sum(c.D) as volume from datawarehouse.ssi_user s,
datawarehouse.category c
where s.year =".$year." AND s.user = '".$user."' AND s.sid=c.sid GROUP BY 1,2) as imp_D
UNION SELECT * FROM
(select 'Integrity' as category, s.month as mois, sum(c.I) as volume from datawarehouse.ssi_user s,
datawarehouse.category c
where s.year =".$year." AND s.user = '".$user."' AND s.sid=c.sid GROUP BY 1,2) as imp_I
UNION SELECT * FROM
(select 'Confidentiality' as category, s.month as mois, sum(c.C) as volume from datawarehouse.ssi_user s,
datawarehouse.category c
where s.year =".$year." AND s.user = '".$user."' AND s.sid=c.sid GROUP BY 1,2) as imp_C
) AS allalarms;";
//echo $sql;

$rs = $conn->Execute($sql);

if (!$rs) {
    print $conn->ErrorMsg();
    return;
}

// test perms for source or destination ips
$var=array();
while (!$rs->EOF) {
    $var1 = $rs->fields["category"];
    $var2 = $rs->fields["volume"];
    $var3 = $rs->fields["mois"];
    $var[]=array(
        'var1'=>$var1,
        'var2'=>$var2,
        'var3'=>$var3
    );
    $rs->MoveNext();
}
$db->close($conn);
// creamos dos grupos y a�adimos sus valores
// Availability
$data1=array(
    'value'=>array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0)
    );
// Integrity
$data2=array(
    'value'=>array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0)
);
// Confidentiality
$data3=array(
    'value'=>array(0=>0,1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0)
);

foreach ($var as $value){
    if($value['var1']=='Availability'){
        $data1['value'][$value['var3']-1]=$value['var2'];
    }elseif($value['var1']=='Integrity'){
        $data2['value'][$value['var3']-1]=$value['var2'];
    }elseif($value['var1']=='Confidentiality'){
        $data3['value'][$value['var3']-1]=$value['var2'];
    }
}
//
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once ("$jpgraph/jpgraph_line.php");
// Setup the graph.
$graph = new Graph(600, 300, "auto");
$graph->SetScale("textlin");
$graph->SetMargin(100,10,20,86);

$graph->SetMarginColor("#fafafa");

$xlabels = array(
    _('Jan'), 
    _('Feb'), 
    _('Mar'), 
    _('Apr'), 
    _('May'), 
    _('Jun'), 
    _('Jul'), 
    _('Aug'), 
    _('Sep'), 
    _('Oct'), 
    _('Nov'), 
    _('Dec')
);
$graph->xaxis->SetTickLabels($xlabels);

$graph->SetColor("#fafafa");
$graph->SetFrame(true,'#fafafa',0);

$dplot[0] = new LinePLot($data1['value']);
$dplot[1] = new LinePLot($data2['value']);
$dplot[2] = new LinePLot($data3['value']);

$dplot[0]->SetColor(COLOR1);
$dplot[0]->SetLegend('Availability');
$dplot[0]->mark->SetType(MARK_SQUARE);
$dplot[0]->mark->SetColor(COLOR1);
$dplot[0]->mark->SetFillColor(COLOR1);
//
$dplot[1]->SetColor(COLOR2);
$dplot[1]->SetLegend('Integrity');
$dplot[1]->mark->SetType(MARK_UTRIANGLE);
$dplot[1]->mark->SetColor(COLOR2);
$dplot[1]->mark->SetFillColor(COLOR2);
//
$dplot[2]->SetColor(COLOR3);
$dplot[2]->SetLegend('Confidentiality');
$dplot[2]->mark->SetType(MARK_DTRIANGLE);
$dplot[2]->mark->SetColor(COLOR3);
$dplot[2]->mark->SetFillColor(COLOR3);

// Add the plot to the graph
$graph->Add($dplot[0]);
$graph->Add($dplot[1]);
$graph->Add($dplot[2]);
$graph->legend->SetPos(0.58,0.95,'center','bottom');
$graph->legend->SetShadow('#fafafa',0);
$graph->legend->SetFrameWeight(0);
$graph->legend->SetFillColor('#fafafa');
$graph->legend->SetColumns(3);

//$b1plot = new BarPlot($data['value']);
// color@transparencia
//$b1plot->SetFillColor(COLOR1);
//
//$b1plot->SetShadow("#D6302C@0.7",5,5);
//$b1plot->SetColor("#D6302C@1");
//
//$graph->Add($b1plot);
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);
?>