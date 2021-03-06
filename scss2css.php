<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
/**
 * convert scss files to css
 * 
 * a 'full'description
 * multilene is cool
 * and big number is more cool
 * @author Jesus Christian Cruz Acono <devel@compermisosnetwork.info>
 * @version 0.0.1
 * @package sample
*/


function parser($dirPath = './', $fileType = 'scss', $recursive = 10 , $basedir = './'){
	if($recursive == 0){
		return;
	}	
	$retval = array();
	$fileTypePattern = '/\.'. $fileType . '$/';
	if(substr($dirPath, -1) != "/"){
		$dirPath .= "/";
	} 
	if ($handle = opendir($dirPath)) {
		while (false !== ($file = readdir($handle))) {
			$fileEntry = $dirPath. $file;
			if ($file != "." && $file != "..") {
				if (is_dir($fileEntry)) {
					$retval[] = array(
						"name" => $file,
						"type" => filetype($fileEntry),
						"size" => 0,
						"lastmod" => filemtime($fileEntry),
						"content" => parser($fileEntry, $fileType, $recursive - 1, $basedir)
					);
				}else {
					if(preg_match($fileTypePattern, $file)){
						$retval[] = array(
							"name" => $file,
							"namenotype" => str_replace('.' . $fileType, '', $file),
							"pathname" => str_replace($basedir, '', str_replace($file , '', $fileEntry)),
							"size" => filesize($fileEntry),
							"lastmod" => filemtime($fileEntry)
						);
					} 
				}
			}
		}
		closedir($handle);
	}
	
	return $retval;
}
function cleaner($tree = array()){
	$cant = count($tree);
	$dummy_array = array();
	$rtree = array();
	for($i = 0; $i< $cant; $i++){
		if(isset($tree[$i]['content'])){
			if($tree[$i]['content'] === $dummy_array){
				unset($tree[$i]); /*no it is nesseray */
			}else{
				$tree[$i]['content'] = cleaner($tree[$i]['content']);
				$rtree[] = $tree[$i];
			}
		}else{
			$rtree[] = $tree[$i];
		}
	}
	return $rtree;
}
function deTree($tree, &$out = array()){
	$newTree = array();
	foreach($tree as $file){
		if(isset($file['content'])){
			deTree($file['content'], $out);
		}else{
			$out[] = $file;
		}
	}
	return $out;
}

function compileFile($fname, $outFname = null) {
	if (!is_readable($fname)) {
		throw new Exception('load error: failed to find '.$fname);
	}
	
	$pi = pathinfo($fname);
	
	require "scss.inc.php";
	require "CssMin.php";
	$scss = new scssc();
	
	$out = $result = CssMin::minify($scss->compile(file_get_contents($fname), $fname));
	
	if ($outFname !== null) {
		return file_put_contents($outFname, $out);
	}
	return $out;
}

// compile only if changed input has changed or output doesn't exist
function checkedCompile($in, $out) {
	if (!is_file($out) || filemtime($in) > filemtime($out)) {
		compileFile($in, $out);
		return true;
	}
	return false;
}

function genCSS($scssDir = 'scss/', $cssDir = 'css/', $scssExt = 'scss' ){
	$unclean = 1;
	$tree = parser($scssDir, $scssExt, 10, $scssDir);
	$cleanTree = array();
	while($unclean){
		if($cleanTree == $tree){
			$unclean = 0;
		}else{
			$cleanTree = cleaner($tree);
			$tree = $cleanTree;
		}
	}
	$tree = deTree($tree);
	foreach($tree as $file){
		$cssName = $cssDir . $file['pathname'] . $file['namenotype'] . '.css';
		$scssName = $scssDir . $file['pathname'] . $file['namenotype'] . '.' . $scssExt;
		$cssCDir = $cssDir . $file['pathname'];
		if(!is_dir($cssCDir)){
			mkdir($cssCDir, 0755, TRUE);
		}
		try {
			checkedCompile($scssName, $cssName);
		} catch (exception $e) {
			echo "fatal error: " . $e->getMessage();
		}
		
	}
	
}
/*generate('less/', 'css/', 'less');
var_dump($argv);
var_dump($argc);*/
echo('Usage scss2css.php scss/ css/ scss' . "\n");
$var = array();
if(isset($argv[1])){
	$var[1] = $argv[1];
}else{
	$var[1] = 'scss/';
}
if(isset($argv[2])){
	$var[2] = $argv[2];
}else{
	$var[2] = 'css/';
}
if(isset($argv[3])){
	$var[3] = $argv[3];
}else{
	$var[3] = 'scss';
}

genCSS($var[1],$var[2],$var[3]);

#genCSS();
