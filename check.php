<?php
// Check extension, https://github.com/annaesvensson/yellow-check

class YellowCheck {
    const VERSION = "0.9.7";
    public $yellow;     // access to API
    public $links;      // number of total links
    public $broken;     // number of broken links
    public $errors;     // number of errors

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

    // Process command to find broken links
    public function processCommandCheck($command, $text) {
        $statusCode = 0;
        list($path, $location) = $this->yellow->toolbox->getTextArguments($text);
        if (is_string_empty($location) || substru($location, 0, 1)=="/") {
            if ($this->checkStaticSettings()) {
                $statusCode = $this->checkFiles($path, $location);
            } else {
                $statusCode = 500;
                $this->links = 0;
                $this->broken = 0;
                $this->errors = 1;
                if (!$this->yellow->extension->isExisting("generate")) {
                    echo "ERROR checking files: This extension requires the 'generate' extension!\n";
                } else {
                    $fileName = $this->yellow->system->get("coreExtensionDirectory").$this->yellow->system->get("coreSystemFile");
                    echo "ERROR checking files: Please configure GenerateStaticUrl in file '$fileName'!\n";
                }
            }
            echo "Yellow $command: $this->links link".($this->links!=1 ? "s" : "");
            echo ", $this->broken broken";
            echo ", $this->errors error".($this->errors!=1 ? "s" : "")."\n";
        } else {
            $statusCode = 400;
            echo "Yellow $command: Invalid arguments\n";
        }
        return $statusCode;
    }
    
    // Check and show broken links
    public function checkFiles($path, $location) {
        $this->links = $this->broken = $this->errors = 0;
        $path = rtrim(is_string_empty($path) ? $this->yellow->system->get("generateStaticDirectory") : $path, "/");
        list($statusCodeGenerate, $fileNames) = $this->generateFiles($path, $location);
        list($statusCodeAnalyse, $links) = $this->analyseFiles($path, $location, $fileNames);
        list($statusCodeLinks, $broken, $redirected) = $this->analyseLinks($path, $links);
        $statusCodeClean = $this->cleanFiles($path, $location);
        if ($statusCodeLinks!=200) {
            $this->showLinks($broken, "Broken links");
            $this->showLinks($redirected, "Redirected links");
        }
        return max($statusCodeGenerate, $statusCodeAnalyse, $statusCodeLinks, $statusCodeClean);
    }
    
    // Generate files
    public function generateFiles($path, $location) {
        $statusCode = 200;
        if ($this->yellow->extension->isExisting("generate")) {
            $this->yellow->extension->get("generate")->errors = 0;
            $path = rtrim(is_string_empty($path) ? $this->yellow->system->get("generateStaticDirectory") : $path, "/");
            if (is_string_empty($location)) {
                $statusCode = $this->yellow->extension->get("generate")->cleanStatic($path, $location);
                foreach ($this->yellow->extension->data as $key=>$value) {
                    if (method_exists($value["object"], "onUpdate")) $value["object"]->onUpdate("clean");
                }
            }
            $statusCode = max($statusCode, $this->yellow->extension->get("generate")->generateStaticContent(
                $path, $location, "\rFinding broken links", 5, 45));
            $statusCode = max($statusCode, $this->yellow->extension->get("generate")->generateStaticMedia(
                $path, $location));
            $statusCode = max($statusCode, $this->yellow->extension->get("generate")->generateStaticSystem(
                $path, $location));
            $this->errors += $this->yellow->extension->get("generate")->errors;
        }
        $regex = "/^[^.]+$|".$this->yellow->system->get("generateStaticDefaultFile")."$/";
        $fileNames = $this->yellow->toolbox->getDirectoryEntriesRecursive($path, $regex, false, false);
        return array($statusCode, $fileNames);
    }
    
    // Analyse files
    public function analyseFiles($path, $locationFilter, $fileNames) {
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
                                echo "YellowCheck::analyseFiles detected url:$url<br />\n";
                            }
                        } elseif (substru($location, 0, 1)=="/") {
                            $url = "$scheme://$address$location";
                            if (!isset($links[$url])) {
                                $links[$url] = $locationSource;
                            } else {
                                $links[$url] .= ",".$locationSource;
                            }
                            if ($this->yellow->system->get("coreDebugMode")>=2) {
                                echo "YellowCheck::analyseFiles detected url:$url<br />\n";
                            }
                        }
                    }
                    if ($this->yellow->system->get("coreDebugMode")>=2) {
                        echo "YellowCheck::analyseFiles location:$locationSource<br />\n";
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
    
    // Analyse links
    public function analyseLinks($path, $links) {
        $statusCode = 200;
        $remote = $broken = $redirected = $data = array();
        $staticUrl = $this->yellow->system->get("generateStaticUrl");
        $staticUrlLength = strlenu(rtrim($staticUrl, "/"));
        list($scheme, $address, $base) = $this->yellow->lookup->getUrlInformation($staticUrl);
        $availableLocations = $this->getAvailableLocations();
        foreach ($links as $url=>$value) {
            if (preg_match("#^$staticUrl#", $url)) {
                $location = substru($url, $staticUrlLength);
                $fileName = $path.substru($url, $staticUrlLength);
                if (is_readable($fileName)) continue;
                if (in_array($location, $availableLocations)) continue;
            }
            if (preg_match("/^(http|https):/", $url)) {
                $remote[$url] = $value;
                if ($this->yellow->system->get("coreDebugMode")>=2) echo "YellowCheck::analyseLinks remote url:$url<br />\n";
            }
        }
        $remoteNow = count($remote);
        $remoteTotal = $remoteNow*2;
        uksort($remote, "strnatcasecmp");
        foreach ($remote as $url=>$value) {
            echo "\rFinding broken links ".$this->getProgressPercent(++$remoteNow, $remoteTotal, 5, 95)."%... ";
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
                    $redirected["$scheme://$address$base$location -> $url - ".
                        $this->getStatusFormatted($statusCodeUrl)] = $statusCodeUrl;
                } else {
                    $broken["$scheme://$address$base$location -> $url - ".
                        $this->getStatusFormatted($statusCodeUrl)] = $statusCodeUrl;
                    ++$this->broken;
                }
            }
        }
        echo "\rFinding broken links 100%... done\n";
        return array($statusCode, $broken, $redirected);
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
    
    // Clean files and directories
    public function cleanFiles($path, $location) {
        $statusCode = 0;
        if ($this->yellow->extension->isExisting("generate")) {
            $statusCode = $this->yellow->extension->get("generate")->cleanStatic($path, $location);
        }
        return $statusCode;
    }
    
    // Check static settings
    public function checkStaticSettings() {
        return preg_match("/^(http|https):/", $this->yellow->system->get("generateStaticUrl"));
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
    
    // Return available locations
    public function getAvailableLocations() {
        $locations = array();
        $staticUrl = $this->yellow->system->get("generateStaticUrl");
        list($scheme, $address, $base) = $this->yellow->lookup->getUrlInformation($staticUrl);
        $this->yellow->page->setRequestInformation($scheme, $address, $base, "", "", false);
        foreach ($this->yellow->content->getChildrenRecursive("", true) as $page) {
            array_push($locations, $page->location);
        }
        if (!$this->yellow->content->find("/") && $this->yellow->system->get("coreMultiLanguageMode")) array_unshift($locations, "/");
        return $locations;
    }

    // Return human readable status
    public function getStatusFormatted($statusCode) {
        return $this->yellow->toolbox->getHttpStatusFormatted($statusCode, true);
    }
    
    // Return progress in percent
    public function getProgressPercent($now, $total, $increments, $max) {
        $max = intval($max/$increments) * $increments;
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
        if (PHP_VERSION_ID<80000) curl_close($curlHandle);
        if ($statusCode<200) $statusCode = 404;
        if ($this->yellow->system->get("coreDebugMode")>=2) {
            echo "YellowCheck::getLinkStatus status:$statusCode url:$url<br />\n";
        }
        return $statusCode;
    }
}
