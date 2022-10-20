Repo Downloader
===============

A small PHP class to download repo zipballs from GitHub

## Usage

```php
require 'RepoDownloader.php';

$repoDL = new RepoDownloader();
$result = $repoDL -> download([
    'user'   => 'jakiestfu',
    'token'  => 'your-access-token',
    'repo'   => 'YourRepoName',
    'saveAs' => 'myRepo-latest.zip',
	
	//download branch (i.e. 'master' or 'main')
	'branch' => 'master',

	//download size progress callback (i.e. Closure|callable method)
	'progress' => function($size, $total=0, $done=false){
		if ($done && !$total) $total = $size;
		if ($total) echo '- progress: ' . ($size/$total*100) . '%';
		else echo '- progress: ' . $size . ' bytes (indeterminate)';
		@ob_flush();
		@flush();
	},
], $error);
if ($result === false) echo $error;
```

Calling the `download` function will download the Zip file of the specified repo. Access token is required as this uses GitHubs API


### Repo Updater

This class comes bundled with a repo-updating script. Simply upload this script to your server, then go to You Repo -> Settings -> Service Hooks -> Web Hook. Enter the URL of the scripts location, and everytime you push to your repo, the script will download a ZIP of your repo, extract it, and move it to the folder specified in the script, successfully updating your code on your server by simply pushing to GitHub
