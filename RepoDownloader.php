<?PHP
class RepoDownloader
{
	/**
	 * redirects count (max = 3)
	 * 
	 * @var int
	 */
	protected $redirects = 0;

	/**
	 * error message
	 * 
	 * @var string
	 */
	protected $error = '';

	/**
	 * saveAs file path
	 * 
	 * @var string
	 */
	private $_file;
	
	/**
	 * saveAs write file resource
	 * 
	 * @var resource
	 */
	private $_fw;

	/**
	 * curl resource
	 * 
	 * @var resource
	 */
	private $_ch;

	/**
	 * progress callback Closure/method i.e. (function($size, $total, $done){...})
	 * 
	 * @var callable
	 */
	private $_progress;

	/**
	 * download total size
	 * 
	 * @var int
	 */
	private $_size_total;

	/**
	 * download current size
	 * 
	 * @var int
	 */
	private $_size_now;

	/**
	 * download previous size
	 * 
	 * @var int
	 */
	private $_size_prev;

	/**
	 * curl progress handler
	 * 
	 * @param  resource	$ch			- curl resource
	 * @param  int		$d_total	- download size
	 * @param  int		$d_now		- downloaded
	 * @param  int		$u_total	- upload size
	 * @param  int		$u_now		- uploaded
	 * @return void
	 */
	private function progress($ch=null, $d_total=null, $d_now=null, $u_total=null, $u_now=null){
		if (!($done = is_null($ch))){
			$this -> _size_total = $d_total;
			$this -> _size_now = $d_now;
			if ($this -> _size_prev && $this -> _size_prev === $d_now) return; //ignore unchanged
			$this -> _size_prev = $d_now;
		}
		if (!($size = $this -> _size_now)) return; //ignore no bytes
		if (!is_callable($cb = $this -> _progress)) return; //ignore no callback
		call_user_func_array($cb, [$size, $this -> _size_total, $done]);
	}

	/**
	 * close resources
	 * 
	 * @return void
	 */
	private function close(){
		if (is_resource($this -> _ch)){
			curl_close($this -> _ch);
			$this -> _ch = null;
		}
		if (is_resource($this -> _fw)){
			fclose($this -> _fw);
			$this -> _fw = null;
		}
	}

	/**
	 * failure handler
	 * 
	 * @param  string  $error - error message
	 * @return false
	 */
	private function failure($error){
		$this -> error = $error;
		$this -> close();
		if (is_file($file = $this -> _file)) unlink($file);
		return false;
	}

	/**
	 * curl exec - handle redirects
	 * 
	 * @param  resource	$ch	- curl resource
	 * 
	 */
    private function exec_redirects(){
		$ch = &$this -> _ch;
		$data = curl_exec($ch);
		if ($n = curl_errno($ch)) return $this -> failure('Curl Error ' . $n . ': ' . curl_error($ch));
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code === 301 || $http_code === 302){
            list($header) = explode("\r\n\r\n", $data, 2);
			$url = preg_match("/(Location:|URI:)[^(\n)]*/i", $header, $matches) ? trim(str_replace($matches[1], "", $matches[0])) : null;
            if (filter_var($url, FILTER_VALIDATE_URL)){
				$this -> redirects ++;
				if (($r = $this -> redirects) >= 3) return $this -> failure('Too many redirects (' . $r . ')');
                curl_setopt($ch, CURLOPT_URL, $url);
				if (strpos($url, '.zip') !== false){
					curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, [$this, 'progress']);
					curl_setopt($ch, CURLOPT_NOPROGRESS, false);
					curl_setopt($ch, CURLOPT_BUFFERSIZE, 4096);
					if ($file = trim($this -> _file)){
						if (!is_resource($this -> _fw = fopen($file, 'w+'))) return $this -> failure('File fopen failed. (' . $file . ')');
						curl_setopt($ch, CURLOPT_FILE, $this -> _fw);
					}
				}
                return $this -> exec_redirects();
            }
        }
		elseif ($http_code === 200 && $this -> _size_now) $this -> progress();
		return count($arr = explode("\r\n\r\n", $data, 2)) > 1 ? $arr[1] : $data;
    }

	/**
	 * download github repository
	 * 
	 * @param  array	$options	- download options
	 * @param  string	$error		- ByRef error message
	 * @return bool					- true on success or false on error 
	 */
    public function download($options, &$error=null){
		$error = null;
		$options = is_array($options) ? $options : [];
		if (!(isset($options['user']) && ($user = trim($options['user'])))){
			$error = 'Undefined options -> user.';
			return false;
		}
		if (!(isset($options['token']) && ($token = trim($options['token'])))){
			$error = 'Undefined options -> token.';
			return false;
		}
		if (!(isset($options['repo']) && ($repo = trim($options['repo'])))){
			$error = 'Undefined options -> repo.';
			return false;
		}
		if (!(isset($options['saveAs']) && ($file = trim($options['saveAs'])))) $file = $repo . '-latest.zip';
		if (!(isset($options['branch']) && ($branch = trim($options['branch'])))) $branch = 'master';
		if (!(isset($options['progress']) && is_callable($progress = $options['progress']))) $progress = null;
		$this -> _file = $file;
		$this -> _progress = $progress;
        $endpoint = 'https://api.github.com/repos/' . $user . '/' . $repo . '/zipball/' . $branch;
        $this -> _ch = curl_init($endpoint);
        curl_setopt($ch = &$this -> _ch, CURLOPT_HTTPHEADER, ['Authorization: token ' . $token, 'User-Agent: GhRepoDownloader']);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (($result = $this -> exec_redirects()) === false){
			$error = $this -> error;
			return false;
		}
		$this -> close();
		if ($result === true) return true;
		$error = 'Unexpected Result: ' . json_encode($result);
		return false;
    }
}
