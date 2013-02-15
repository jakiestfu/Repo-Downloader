<?PHP

if(!isset($_POST['payload'])){die();}

function recursiveChmod($path, $filePerm=0644, $dirPerm=0755){
  if(!file_exists($path)){
	return false;
	}
	if(is_file($path)){
		chmod($path, $filePerm);
	} elseif(is_dir($path)) {
		$foldersAndFiles = scandir($path);
		$entries = array_slice($foldersAndFiles, 2);
		foreach($entries as $entry){
			recursiveChmod($path."/".$entry, $filePerm, $dirPerm);
		}
		chmod($path, $dirPerm);
	}
	return true;
}
function recursiveDelete($directory, $empty=false) {
	if(substr($directory,-1) == '/'){
		$directory = substr($directory,0,-1);
	}
	if(!file_exists($directory) || !is_dir($directory)){
		return false;
	} elseif(is_readable($directory)){
		$handle = opendir($directory);
		while (false !== ($item = readdir($handle))) {
			if($item != '.' && $item != '..') {
				$path = $directory.'/'.$item;
				if(is_dir($path)) {
					recursiveDelete($path);
				}else{
					unlink($path);
				}
			}
		}
		closedir($handle);
		if($empty == false) {
			if(!rmdir($directory)) {
				return false;
			}
		}
	}
	return true;
}
function moveFiles($src, $dst){
	if (file_exists ( $dst )){
        recursiveDelete( $dst );
	}
    if (is_dir ( $src )) {
      mkdir ( $dst );
      $files = scandir ( $src );
      foreach ( $files as $file ){
        if ($file != "." && $file != ".."){
          moveFiles( "$src/$file", "$dst/$file" );
        }
      }
    } elseif (file_exists ( $src )){
      copy ( $src, $dst );
  }
}



require 'RepoDownloader.php';


$file = 'myRepo.zip';
$dest = 'path/to/move/to';

$repoDL = new RepoDownloader();
$repoDL->download(array(
	'user' => 'jakiestfu',
	'token' => 'my-access-token',
	'repo' => 'MyRepoName',
	'saveAs' => $file
));

/* Do not edit below this line unless you know what you're doing! */

$zip = new ZipArchive;
if ($zip->open('./'.$file) === TRUE) {
  $zip->extractTo('./latest/');
  $zip->close();
	unlink($file);
	
	
	$files = scandir('./latest/');
	$src = './latest/'.$files[2];
	
	recursiveChmod($src, 0777, 0777);
	recursiveDelete($dest);
	moveFiles($src, $dest);
	recursiveDelete($src);
	recursiveChmod($dest, 0777, 0777);
	
	file_put_contents( 'push.json', $_POST['payload'] );
}
