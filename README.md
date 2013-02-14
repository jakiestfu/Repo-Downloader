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

Calling the `download` function will download the Zip file of the specified repo. Access token is required as this uses GitHubs API

```
