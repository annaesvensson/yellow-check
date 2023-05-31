<?php
// Check extension, https://github.com/annaesvensson/yellow-check

class YellowCheck {
    const VERSION = "0.8.1";
    public $yellow;                       // access to API
    public $links;                        // number of links
    public $errors;                       // number of errors

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
    }
    
    // Handle command
    public function onCommand($command, $text) {
        switch ($command) {
            case "check":    $statusCode = $this->processCommandCheck($command, $text); break;
            default:         $statusCode = 0;
        }
        return $statusCode;
    }
    
    // Handle command help
    public function onCommandHelp() {
        return "check [directory location]";
    }

    // Process command to check static files for broken links
    public function processCommandCheck($command, $text) {
        $statusCode = 0;
        list($path, $location) = $this->yellow->toolbox->getTextArguments($text);
        if (is_string_empty($location) || substru($location, 0, 1)=="/") {
            if ($this->checkStaticSettings()) {
                $statusCode = $this->checkStaticFiles($path, $location);
            } else {
                $statusCode = 500;
                $this->links = 0;
                $this->errors = 1;
                $fileName = $this->yellow->system->get("coreExtensionDirectory").$this->yellow->system->get("coreSystemFile");
                echo "ERROR checking files: Please configure GenerateStaticUrl in file '$fileName'!\n";
            }
            echo "Yellow $command: $this->links link".($this->links!=1 ? "s" : "");
            echo ", $this->errors error".($this->errors!=1 ? "s" : "")."\n";
        } else {
            $statusCode = 400;
            echo "Yellow $command: Invalid arguments\n";
        }
        return $statusCode;
    }
    
    // Check static files for broken links
    public function checkStaticFiles($path, $locationFilter) {
        $path = rtrim(is_string_empty($path) ? $this->yellow->system->get("generateStaticDirectory") : $path, "/");
        $this->links = $this->errors = 0;
        $regex = "/^[^.]+$|".$this->yellow->system->get("generateStaticDefaultFile")."$/";
        $fileNames = $this->yellow->toolbox->getDirectoryEntriesRecursive($path, $regex, false, false);
        list($statusCodeFiles, $links) = $this->analyseLinks($path, $locationFilter, $fileNames);
        list($statusCodeLinks, $broken, $redirect) = $this->analyseStatus($path, $links);
        if ($statusCodeLinks!=200) {
            $this->showLinks($broken, "Broken links");
            $this->showLinks($redirect, "Redirect links");
        }
        return max($statusCodeFiles, $statusCodeLinks);
    }
    
    // Analyse links in static files
    public function analyseLinks($path, $locationFilter, $fileNames) {
        $statusCode = 200;
        $links = array();
        if (!is_array_empty($fileNames)) {
            $staticUrl = $this->yellow->system->get("generateStaticUrl");
            list($scheme, $address, $base) = $this->yellow->lookup->getUrlInformation($staticUrl);
            foreach ($fileNames as $fileName) {
                if (is_readable($fileName)) {
                    $locationSource = $this->getStaticLocation($path, $fileName);
                    if (!preg_match("#^$base$locationFilter#", "$base$locationSource")) continue;
                    $fileData = $this->yellow->toolbox->readFile($fileName);
                    preg_match_all("/<(.*?)href=\"([^\"]+)\"(.*?)>/i", $fileData, $matches);
                    foreach ($matches[2] as $match) {
                        $location = rawurldecode($match);
                        if (preg_match("/^(.*?)#(.*)$/", $location, $tokens)) $location = $tokens[1];
                        if (preg_match("/^(\w+):\/\/([^\/]+)(.*)$/", $location, $matches)) {
                            $url = $location.(is_string_empty($matches[3]) ? "/" : "");
                            if (!isset($links[$url])) {
                                $links[$url] = $locationSource;
                            } else {
                                $links[$url] .= ",".$locationSource;
                            }
                            if ($this->yellow->system->get("coreDebugMode")>=2) {
                                echo "YellowCheck::analyseLinks detected url:$url<br/>\n";
                            }
                        } elseif (substru($location, 0, 1)=="/") {
                            $url = "$scheme://$address$location";
                            if (!isset($links[$url])) {
                                $links[$url] = $locationSource;
                            } else {
                                $links[$url] .= ",".$locationSource;
                            }
                            if ($this->yellow->system->get("coreDebugMode")>=2) {
                                echo "YellowCheck::analyseLinks detected url:$url<br/>\n";
                            }
                        }
                    }
                    if ($this->yellow->system->get("coreDebugMode")>=1) {
                        echo "YellowCheck::analyseLinks location:$locationSource<br/>\n";
                    }
                } else {
                    $statusCode = 500;
                    ++$this->errors;
                    echo "ERROR reading files: Can't read file '$fileName'!\n";
                }
            }
            $this->links = count($links);
        } else {
            $statusCode = 500;
            ++$this->errors;
            echo "ERROR reading files: Can't find files in directory '$path'!\n";
        }
        return array($statusCode, $links);
    }
    
    // Analyse link status
    public function analyseStatus($path, $links) {
        $statusCode = 200;
        $remote = $broken = $redirect = $data = array();
        $staticUrl = $this->yellow->system->get("generateStaticUrl");
        $staticUrlLength = strlenu(rtrim($staticUrl, "/"));
        list($scheme, $address, $base) = $this->yellow->lookup->getUrlInformation($staticUrl);
        $staticLocations = $this->getContentLocations(true);
        foreach ($links as $url=>$value) {
            if (preg_match("#^$staticUrl#", $url)) {
                $location = substru($url, $staticUrlLength);
                $fileName = $path.substru($url, $staticUrlLength);
                if (is_readable($fileName)) continue;
                if (in_array($location, $staticLocations)) continue;
            }
            if (preg_match("/^(http|https):/", $url)) $remote[$url] = $value;
        }
        $remoteNow = 0;
        uksort($remote, "strnatcasecmp");
        foreach ($remote as $url=>$value) {
            echo "\rChecking static website ".$this->getProgressPercent(++$remoteNow, count($remote), 5, 95)."%... ";
            if ($this->yellow->system->get("coreDebugMode")>=1) echo "YellowCheck::analyseStatus url:$url\n";
            $referer = "$scheme://$address$base".(($pos = strposu($value, ",")) ? substru($value, 0, $pos) : $value);
            $statusCodeUrl = $this->getLinkStatus($url, $referer);
            if ($statusCodeUrl!=200) {
                $statusCode = max($statusCode, $statusCodeUrl);
                $data[$url] = "$statusCodeUrl,$value";
            }
        }
        foreach ($data as $url=>$value) {
            $locations = preg_split("/\s*,\s*/", $value);
            $statusCodeUrl = array_shift($locations);
            foreach ($locations as $location) {
                if ($statusCodeUrl==302) continue;
                if ($statusCodeUrl>=300 && $statusCodeUrl<=399) {
                    $redirect["$scheme://$address$base$location -> $url - ".$this->getStatusFormatted($statusCodeUrl)] = $statusCodeUrl;
                } else {
                    $broken["$scheme://$address$base$location -> $url - ".$this->getStatusFormatted($statusCodeUrl)] = $statusCodeUrl;
                }
                ++$this->errors;
            }
        }
        echo "\rChecking static website 100%... done\n";
        return array($statusCode, $broken, $redirect);
    }

    // Show links
    public function showLinks($data, $text) {
        if (!is_array_empty($data)) {
            echo "$text\n\n";
            uksort($data, "strnatcasecmp");
            $data = array_slice($data, 0, 99);
            foreach ($data as $key=>$value) {
                echo "$key\n";
            }
            echo "\n";
        }
    }
    
    // Check static settings
    public function checkStaticSettings() {
        return preg_match("/^(http|https):/", $this->yellow->system->get("generateStaticUrl"));
    }
    
    // Return content locations
    public function getContentLocations($includeAll = false) {
        $locations = array();
        if ($this->yellow->extension->isExisting("generate")) {
            $locations = $this->yellow->extension->get("generate")->getContentLocations($includeAll);
        }
        return $locations;
    }

    // Return static location
    public function getStaticLocation($path, $fileName) {
        $location = substru($fileName, strlenu($path));
        if (basename($location)==$this->yellow->system->get("generateStaticDefaultFile")) {
            $defaultFileLength = strlenu($this->yellow->system->get("generateStaticDefaultFile"));
            $location = substru($location, 0, -$defaultFileLength);
        }
        return $location;
    }
    
    // Return human readable status
    public function getStatusFormatted($statusCode) {
        return $this->yellow->toolbox->getHttpStatusFormatted($statusCode, true);
    }
    
    // Return progress in percent
    public function getProgressPercent($now, $total, $increments, $max) {
        $percent = intval(($max/$total) * $now);
        if ($increments>1) $percent = intval($percent/$increments) * $increments;
        return min($max, $percent);
    }
    
    // Return link status
    public function getLinkStatus($url, $referer) {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_REFERER, $referer);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; YellowCheck/".YellowCheck::VERSION."; LinkChecker)");
        curl_setopt($curlHandle, CURLOPT_NOBODY, 1);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($curlHandle);
        $statusCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        curl_close($curlHandle);
        if ($statusCode<200) $statusCode = 404;
        if ($this->yellow->system->get("coreDebugMode")>=2) {
            echo "YellowCheck::getLinkStatus status:$statusCode url:$url<br/>\n";
        }
        return $statusCode;
    }
}
