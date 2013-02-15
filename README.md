Repo Downloader
===============

A small PHP class to download repo zipballs from GitHub

## Usage

```php
require 'RepoDownloader.php';

$repoDL = new RepoDownloader();

$repoDL->download(array(
    'user'   => 'jakiestfu',
    'token'  => 'your-access-token',
    'repo'   => 'YourRepoName',
    'saveAs' => 'myRepo-latest.zip'
));
```

Calling the `download` function will download the Zip file of the specified repo. Access token is required as this uses GitHubs API


### Repo Updater

This class comes bundled with a repo-updating script. Simply upload this script to your server, then go to You Repo -> Settings -> Service Hooks -> Web Hook. Enter the URL of the scripts location, and everytime you push to your repo, the script will download a ZIP of your repo, extract it, and move it to the folder specified in the script, successfully updating your code on your server by simply pushing to GitHub
