<?php
/**
 * SINRI Slow Sql log Analyzer
 * ===========================
 *   All Hail Sinri Edogawa!
 * ---------------------------
 *  Updated 2016 Jan 23
 *  Version Bet [ב]
 * ===========================
 *
 *  # USAGE
 *  php SinriSSA.php [-eE] [-s SORT=ave_time] [-v MIN,MAX] [-t TOP=10] -f FILE
 *  -e show one sample sql
 *  -E show all sample sqls
 *  -f determine the log file, non-optional
 *  -s determine the sort method, `min_time`, `ave_time` and `freq_sum` supported
 *  -t the number of sqls to be displayed by order after sorting, 10 for default, 0 for all
 *  -v sql average time range filter, such as `50,10000`, used as [50,10000)
 */
$opt=getopt('heEs:t:f:v:');
// print_r($opt);die();
if(empty($opt)){
	$opt=array('h'=>true);
}
if(isset($opt['h'])){
	echo "SINRI Slow Sql log Analyzer Version Aleph [א]".PHP_EOL;
	echo " *  # USAGE
 *  php SinriSSA.php [-eE] [-s SORT=ave_time][-v MIN,MAX] [-t TOP=10] -f FILE
 *  -e show one sample sql
 *  -E show all sample sqls
 *  -f determine the log file, non-optional
 *  -s determine the sort method, `min_time`, `ave_time` and `freq_sum` supported
 *  -t the number of sqls to be displayed by order after sorting, 10 for default, 0 for all
 *  -v sql average time range filter, such as `50,10000`, used as [50,10000)";
	echo PHP_EOL;
	die();
}
if(!isset($opt['s'])){
	$opt['s']='ave_time';
}else{
	if (!in_array($opt['s'], array('ave_time','freq_sum'))){
		$opt['s']='ave_time';
	}
}
if(!isset($opt['t'])){
	$opt['t']=10;
}else{
	$opt['t']=intval($opt['t']);
	if($opt['t']<0){
		$opt['t']=10;
	}
}

if(isset($opt['v'])){
	$mm=explode(',', $opt['v']);
	if(count($mm)==2){
		$min=intval($mm[0]);
		$max=intval($mm[1]);
		echo "Notice: Time range has been changed to [$min,$max) as [min,max)".PHP_EOL;
	}
}
if(!isset($min) or !isset($max)){
	$min=50;
	$max=10000;
	echo "Notice: Time range has been changed to [$min,$max) as [min,max)".PHP_EOL;
}

if(empty($opt['f'])){
	die("FILE ! EMPTY!");
}
$file=$opt['f'];

// MAIN

$time_begin_to_read_file=microtime(true);

$slow=array();
$index=-1;
$sql="";
$time=0;
$who="";

$handle = @fopen($file, "r");
if ($handle) {
    while (($buffer = fgets($handle, 40960)) !== false) {
        $line=$buffer;
        if(strstr($line, '# Time:')!==false){
			//echo "HERE TIME".PHP_EOL;
			//# Time: 150803  6:07:06
			saveit($slow,$index,$sql,$who,$time,$min,$max);
			$index+=1;
			$sql="";
			$time=0;
			$who="";
		}elseif(strstr($line, '# User@Host:')!==false){
			//echo "HERE USER".PHP_EOL;
			//# User@Host: hbai[hbai] @  [192.168.0.21]
			$sql="";
			$time=0;
			$who=$line;
		}elseif (strstr($line, '# Query_time:')!==false) {
			//echo "HERE QUERY".PHP_EOL;
			//# Query_time: 58  Lock_time: 0  Rows_sent: 0  Rows_examined: 541643
			$ar=explode(' ', $line);
			$time=$ar[2];
			$sql="";
		}else{
			//echo "HERE SQL".PHP_EOL;
			$sql.=$line;
		}        
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}

saveit($slow,$index,$sql,$who,$time,$min,$max);

$time_end_reading_file=microtime(true);

echo "READ FILE [$file] for ".count($slow)." sqls, cost ".($time_end_reading_file-$time_begin_to_read_file)." seconds".PHP_EOL;

echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
echo "==============".PHP_EOL;
echo "ANALYZE BEGIN!".PHP_EOL;
echo "==============".PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;

$type_group=array();

foreach ($slow as $item) {
	$time=$item['time'];
	$sql=$item['sql'];
	$n_sql=$item['n_sql'];
	$type_md5=$item['type_md5'];
	$who=$item['who'];

	if(isset($type_group[$type_md5])){
		$type_group[$type_md5]['time_sum']+=$time;
		$type_group[$type_md5]['freq_sum']+=1;
		$type_group[$type_md5]['samples'][]=$sql;
		if($type_group[$type_md5]['min_time']>$time){
			$type_group[$type_md5]['min_time']=$time;
		}
		if($type_group[$type_md5]['max_time']<$time){
			$type_group[$type_md5]['max_time']=$time;
		}
		$type_group[$type_md5]['who'][$who]=$who;
	}else{
		$type_group[$type_md5]=array(
			'time_sum'=>$time,
			'freq_sum'=>1,
			'samples'=>array($sql),
			'sql'=>$n_sql,
			'type_md5'=>$type_md5,
			'min_time'=>$time,
			'max_time'=>$time,
			'who'=array($who=>$who),
		);
	}
}

$time_end_analyzing=microtime(true);

if($opt['s']=='ave_time'){
	// SORT BY AVE TIME
	usort($type_group, 'cmp_ave_time');
}elseif ($opt['s']=='freq_sum') {
	usort($type_group, 'cmp_freq_sum');
}elseif ($opt['s']=='min_time') {
	usort($type_group, 'cmp_min_time');
}
else{
	die ('PARAMETER [s] ONLY ave_time or freq_sum'.PHP_EOL);
}

$i=1;
foreach ($type_group as $no => $item) {
	if($opt['t']>0 && $opt['t']>=$i){
		echo "# ".$i.' TYPE MD5: '.$item['type_md5'].PHP_EOL;
		echo "AVE TIME: ".($item['freq_sum']==0?0:($item['time_sum']/$item['freq_sum']))." second ".PHP_EOL;
		echo "MIN TIME: ".$item['min_time']." s; MAX TIME: ".$item['max_time']." s;";
		echo "FREQUENCY: ".$item['freq_sum'].PHP_EOL;
		echo "CALLED BY: ".PHP_EOL.implode(PHP_EOL, $item['who']).PHP_EOL;
		echo "NORMALIZED SQL: ".PHP_EOL;
		echo $item['sql'].PHP_EOL;
		if(isset($opt['e'])){
			echo "ONE SAMPLE SQL: ".PHP_EOL;
			echo $item['samples'][0];
		}elseif (isset($opt['E'])) {
			echo "ALL SAMPLE SQL: ".PHP_EOL;
			for ($sql_sample_index=0; $sql_sample_index < count($item['samples']); $sql_sample_index++) { 
				echo "SQL SAMPLE ".$sql_sample_index." : ".PHP_EOL;
				echo $item['samples'][$sql_sample_index].PHP_EOL;
			}
		}
		echo PHP_EOL;
	}else{
		break;
	}
	$i+=1;
}


echo PHP_EOL;

echo "ANALYZE DONE IN ".($time_end_analyzing-$time_end_reading_file).' SECONDS'.PHP_EOL;

// FUNCTIONS

function normalizeSQL($sql){
	$normalizer_version=2;
	// Normalizer Verion I: deprecated for Segmentation Fault Issue
	if($normalizer_version==1){
		$sql=preg_replace("/= *\d+ *;/", '=@;', $sql);
		$sql=preg_replace("/(\\'[^\\']*\\')/", '@', $sql);
		$sql=preg_replace("/(?<=[\s\=\(,])[\-]?[\\d]+(?=[\s\=\),])/", '#', $sql);
		$sql=preg_replace("/ *@ */", '@', $sql);
		$sql=preg_replace("/ *# */", '#', $sql);
		$sql=preg_replace("/[#@](,[#@])+/", '~', $sql);
		$sql=preg_replace("/\-\-/", ' --', $sql);
		$sql=preg_replace("/ *# */", ' # ', $sql);
		$sql=preg_replace("/ *@ */", ' @ ', $sql);
	}
	elseif($normalizer_version==2){
		$sql=preg_replace('/(?<=[\s\=\(\<\>])(\d+)(?=[\s,;\)\<\>\!])/', '@', $sql);
		$sql=preg_replace('/\'(([^\\\']|(\\.))*)\'/', '#', $sql);
		$sql=preg_replace('/\([\s,#@]+\)/', '(~)', $sql);
	}
	return $sql;
}

function saveit(&$array,$index,$sql,$who,$time,$min=100,$max=10000){
	if($index>=0){
		//if existed
		if($time>=$min && $time<$max){
			$n_sql=normalizeSQL($sql);
			$array[]=array('time'=>$time,'sql'=>$sql,'n_sql'=>$n_sql,'type_md5'=>md5($n_sql),'who'=>$who);
		}
	}
}

function cmp_freq_sum($a,$b){
	if ($a['freq_sum'] == $b['freq_sum']) {
        return 0;
    }
    return ($a['freq_sum'] < $b['freq_sum']) ? 1 : -1;
}

function cmp_ave_time($a,$b){
	$a_at=($a['freq_sum']==0?0:($a['time_sum']/$a['freq_sum']));
	$b_at=($b['freq_sum']==0?0:($b['time_sum']/$b['freq_sum']));

	if ($a_at == $b_at) {
        return 0;
    }
    return ($a_at < $b_at) ? 1 : -1;
}

function cmp_min_time($a,$b){
	$a_at=$a['min_time'];
	$b_at=$b['min_time'];

	if ($a_at == $b_at) {
        return 0;
    }
    return ($a_at < $b_at) ? 1 : -1;
}

