<?PHP

class RepoDownloader{

    private function exec_redirects($ch, &$redirects) {
        $data = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 301 || $http_code == 302) {
            list($header) = explode("\r\n\r\n", $data, 2);

            $matches = array();
            preg_match("/(Location:|URI:)[^(\n)]*/", $header, $matches);
            $url = trim(str_replace($matches[1], "", $matches[0]));

            $url_parsed = parse_url($url);
            if (isset($url_parsed)) {
                curl_setopt($ch, CURLOPT_URL, $url);
                $redirects++;
                return $this->exec_redirects($ch, $redirects);
            }
        }

        if ($curlopt_header) {
            return $data;
        } else {
            list(, $body) = explode("\r\n\r\n", $data, 2);
            return $body;
        }
    }

    public function download($opts){
        extract($opts);

        $url = $repo;
        $file = $saveAs;
        $endpoint = 'https://api.github.com/repos/'.$user.'/'.$repo.'/zipball/master';

        $ch = curl_init($endpoint); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: token '.$token));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = $this->exec_redirects($ch, $out); 
        curl_close($ch);

        file_put_contents($file, $data);
    }
}
