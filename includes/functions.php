<?php
/* Functions for Networking */

/**
 * Get a human readable data size string from a number of bytes.
 *
 * @param  long $numbytes  The number of bytes.
 * @param  int  $precision The number of numbers to round to after the dot/comma.
 * @return string Data size in units: PB, TB, GB, MB or KB otherwise an empty string.
 */
function getHumanReadableDatasize($numbytes, $precision = 2)
{
    $humanDatasize = '';
    $kib = 1024;
    $mib = $kib * 1024;
    $gib = $mib * 1024;
    $tib = $gib * 1024;
    $pib = $tib * 1024;
    if ($numbytes >= $pib) {
        $humanDatasize = ' ('.round($numbytes / $pib, $precision).' PB)';
    } elseif ($numbytes >= $tib) {
        $humanDatasize = ' ('.round($numbytes / $tib, $precision).' TB)';
    } elseif ($numbytes >= $gib) {
        $humanDatasize = ' ('.round($numbytes / $gib, $precision).' GB)';
    } elseif ($numbytes >= $mib) {
        $humanDatasize = ' ('.round($numbytes / $mib, $precision).' MB)';
    } elseif ($numbytes >= $kib) {
        $humanDatasize = ' ('.round($numbytes / $kib, $precision).' KB)';
    }

    return $humanDatasize;
}

/**
 * Converts a netmask to CIDR notation string
 *
 * @param string $mask
 * @return string
 */
function mask2cidr($mask)
{
    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return 32-log(($long ^ $base)+1, 2);
}

/**
 * Converts a CIDR notation string to a netmask
 *
 * @param string $cidr
 * @return string
 */
function cidr2mask($cidr)
{
    $ipParts = explode('/', $cidr);
    $ip = $ipParts[0];
    $prefixLength = $ipParts[1];

    $ipLong = ip2long($ip);
    $netmaskLong = bindec(str_pad(str_repeat('1', $prefixLength), 32, '0'));
    $netmask = long2ip(intval($netmaskLong));

    return $netmask;
}

/**
 * Removes a dhcp configuration block for the specified interface
 *
 * @param string $iface
 * @param object $status
 * @return boolean $result
 */
function removeDHCPConfig($iface,$status)
{
    $orgin_str = file_get_contents(RASPI_DHCPCD_CONFIG);
    if ($iface == "eth0") {
        $count = strpos($orgin_str, "denyinterfaces");
        if ($_POST['wan-multi'] == '1') {
            $dhcp_cfg = substr_replace($orgin_str, 'denyinterfaces eth1 wlan0 eth0' . PHP_EOL, number_format($count), 31);
        } else {
            $dhcp_cfg = substr_replace($orgin_str, 'denyinterfaces eth1 wlan0     ' . PHP_EOL, number_format($count), 31);
        }
    } else {
        $dhcp_cfg = $orgin_str;
    }
    
    $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)([\s]+)/ms', '', $dhcp_cfg, 1);

    file_put_contents("/tmp/dhcpddata", $dhcp_cfg);
    system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $result);
    if ($result == 0) {
        $status->addMessage('DHCP configuration for '.$iface.'  removed.', 'success');
    } else {
        $status->addMessage('Failed to remove DHCP configuration for '.$iface.'.', 'danger');
        return $result;
    }
}

/**
 * Removes a dhcp configuration block for the specified interface
 *
 * @param string $dhcp_cfg
 * @param string $iface
 * @return string $dhcp_cfg
 */
function removeDHCPIface($dhcp_cfg,$iface)
{
    $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)([\s]+)/ms', '', $dhcp_cfg, 1);
    return $dhcp_cfg;
}

/**
 * Removes a dnsmasq configuration block for the specified interface
 *
 * @param string $iface
 * @param object $status
 * @return boolean $result
 */
function removeDnsmasqConfig($iface,$status)
{
    system('sudo rm '.RASPI_DNSMASQ_PREFIX.$iface.'.conf', $result);
    if ($result == 0) {
        $status->addMessage('Dnsmasq configuration for '.$iface.' removed.', 'success');
    } else {
        $status->addMessage('Failed to remove dnsmasq configuration for '.$iface.'.', 'danger');
    }
    return $result;
}

/**
 * Scans dnsmasq configuration dir for the specified interface
 * Non-matching configs are removed, optional adblock.conf is protected
 *
 * @param string $dir_conf
 * @param string $interface
 * @param object $status
 */
function scanConfigDir($dir_conf,$interface,$status)
{
    $syscnf = preg_grep('~\.(conf)$~', scandir($dir_conf));
    foreach ($syscnf as $cnf) {
        if ($cnf !== '090_adblock.conf' && !preg_match('/.*_'.$interface.'.conf/', $cnf)) {
            system('sudo rm /etc/dnsmasq.d/'.$cnf, $result);
        }
    }
    return $status;
}

/**
 * Returns a default (fallback) value for the selected service, interface & setting
 * from /etc/raspap/networking/defaults.json
 *
 * @param string $svc
 * @param string $iface
 * @return string $value
 */
function getDefaultNetValue($svc,$iface,$key)
{
    $json = json_decode(file_get_contents(RASPI_CONFIG_NETWORK), true);
    if ($json === null) {
        return false;
    } else {
        return $json[$svc][$iface][$key][0];
    }
}

/**
 * Returns default options for the specified service
 *
 * @param string $svc
 * @param string $key
 * @return object $json
 */
function getDefaultNetOpts($svc,$key)
{
    $json = json_decode(file_get_contents(RASPI_CONFIG_NETWORK), true);
    if ($json === null) {
        return false;
    } else {
        return $json[$svc][$key];
    }
}

/* Functions to write ini files */

/**
 * Writes a configuration to an .ini file
 *
 * @param array $array
 * @param string $file
 * @return boolean
 */
function write_php_ini($array, $file)
{
    $res = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $res[] = "[$key]";
            foreach ($val as $skey => $sval) {
                $res[] = "$skey = $sval";
            }
        } else {
            $res[] = "$key = $val";
        }
    }
    if (safefilerewrite($file, implode(PHP_EOL, $res))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Writes to a file without conflicts
 *
 * @param string $fileName
 * @param string $dataToSave
 * @return boolean
 */
function safefilerewrite($fileName, $dataToSave)
{
    if ($fp = fopen($fileName, 'w')) {
        $startTime = microtime(true);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if (!$canWrite) {
                usleep(round(rand(0, 100)*1000));
            }
        } while ((!$canWrite)and((microtime(true)-$startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave.PHP_EOL);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    } else {
        return false;
    }
}

/**
 * Prepends data to a file if not exists
 *
 * @param string $filename
 * @param string $dataToSave
 * @return boolean
 */
function file_prepend_data($filename, $dataToSave)
{
    $context = stream_context_create();
    $file = fopen($filename, 'r', 1, $context);
    $file_data = readfile($file);

    if (!preg_match('/^'.$dataToSave.'/', $file_data)) {
        $tmp_file = tempnam(sys_get_temp_dir(), 'php_prepend_');
        file_put_contents($tmp_file, $dataToSave);
        file_put_contents($tmp_file, $file, FILE_APPEND);
        fclose($file);
        unlink($filename);
        rename($tmp_file, $filename);
        return true;
    } else {
        return false;
    }
}

/**
 * Fetches a meta value from a file
 *
 * @param string $filename
 * @param string $pattern
 * @return string
 */
function file_get_meta($filename, $pattern)
{
    if(file_exists($filename)) {
        $context = stream_context_create();
        $file_data = file_get_contents($filename, false, $context);
        preg_match('/^'.$pattern.'/', $file_data, $matched);
        return $matched[1];
    } else {
        return false;
    }
}

/**
 * Callback function for array_filter
 *
 * @param string $var
 * @return filtered value
 */
function filter_comments($var)
{
    return $var[0] != '#';
}

/**
 * Saves a CSRF token in the session
 */
function ensureCSRFSessionToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Add CSRF Token to form
 */
function CSRFTokenFieldTag()
{
    $token = htmlspecialchars($_SESSION['csrf_token']);
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Retuns a CSRF meta tag (for use with xhr, for example)
 */
function CSRFMetaTag()
{
    $token = htmlspecialchars($_SESSION['csrf_token']);
    return '<meta name="csrf_token" content="' . $token . '">';
}

/**
 * Validate CSRF Token
 */
function CSRFValidate()
{
    $post_token   = $_POST['csrf_token'];
    $header_token = $_SERVER['HTTP_X_CSRF_TOKEN'];

    if (empty($post_token) && empty($header_token)) {
        return false;
    }

    $request_token = $post_token;
    if (empty($post_token)) {
        $request_token = $header_token;
    }

    if (hash_equals($_SESSION['csrf_token'], $request_token)) {
        return true;
    } else {
        error_log('CSRF violation');
        return false;
    }
}

/**
 * Should the request be CSRF-validated?
 */
function csrfValidateRequest()
{
    $request_method = strtolower($_SERVER['REQUEST_METHOD']);
    return in_array($request_method, [ "post", "put", "patch", "delete" ]);
}

/**
 * Handle invalid CSRF
 */
function handleInvalidCSRFToken()
{
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain');
    echo 'Invalid CSRF token';
    exit;
}

/**
 * Test whether array is associative
 */
function isAssoc($arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

/**
 * Display a selector field for a form. Arguments are:
 *
 * @param string $name:     Field name
 * @param array  $options:  Array of options
 * @param string $selected: Selected option (optional)
 * @param string $id:       $options is an associative array this should be the key
 * @param string $event:    onChange event (optional)
 * @param string $disabled  (optional)
 */
function SelectorOptions($name, $options, $selected = null, $id = null, $event = null, $disabled = null)
{
    echo '<select class="form-control" name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="' . htmlspecialchars($id, ENT_QUOTES) .'"';
    }
    if (isset($event)) {
        echo ' onChange="' . htmlspecialchars($event, ENT_QUOTES).'()"';
    }
    echo '>' , PHP_EOL;
    foreach ($options as $opt => $label) {
        $select = '';
        $key = isAssoc($options) ? $opt : $label;
        if ($key == $selected) {
            $select = ' selected="selected"';
        }
        if ($key == $disabled) {
            $disabled = ' disabled';
        }
        echo '<option value="'.htmlspecialchars($key, ENT_QUOTES).'"'.$select.$disabled.'>'.
            htmlspecialchars($label, ENT_QUOTES).'</option>' , PHP_EOL;
    }

    echo '</select>' , PHP_EOL;
}

/**
 *
 * @param  string $input
 * @param  string $string
 * @param  int    $offset
 * @param  string $separator
 * @return $string
 */
function GetDistString($input, $string, $offset, $separator)
{
    $string = substr($input, strpos($input, $string)+$offset, strpos(substr($input, strpos($input, $string)+$offset), $separator));
    return $string;
}

/**
 *
 * @param  array $arrConfig
 * @return $config
 */
function ParseConfig($arrConfig)
{
    $config = array();
    foreach ($arrConfig as $line) {
        $line = trim($line);
        if ($line == "" || $line[0] == "#") {
            continue;
        }

        list($option, $value) = array_map("trim", explode("=", $line, 2));

        if (empty($config[$option])) {
            $config[$option] = $value ?: true;
        } else {
            if (!is_array($config[$option])) {
                $config[$option] = [ $config[$option] ];
            }
            $config[$option][] = $value;
        }
    }
    return $config;
}

/**
 * Fetches DHCP configuration for an interface, returned as JSON data
 *
 * @param  string $interface
 * @return json $jsonData
 */
function getNetConfig($interface)
{
    $URI = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'] .'/ajax/networking/get_netcfg.php?iface='.$interface;
    $jsonData = file_get_contents($URI);
    return $jsonData;
}

/**
 *
 * @param  string $freq
 * @return $channel
 */
function ConvertToChannel($freq)
{
    if ($freq >= 2412 && $freq <= 2484) {
        $channel = ($freq - 2407)/5;
    } elseif ($freq >= 4915 && $freq <= 4980) {
        $channel = ($freq - 4910)/5 + 182;
    } elseif ($freq >= 5035 && $freq <= 5865) {
        $channel = ($freq - 5030)/5 + 6;
    } else {
        $channel = -1;
    }
    if ($channel >= 1 && $channel <= 196) {
        return $channel;
    } else {
        return 'Invalid Channel';
    }
}

/**
 * Converts WPA security string to readable format
 *
 * @param  string $security
 * @return string
 */
function ConvertToSecurity($security)
{
    $options = array();
    preg_match_all('/\[([^\]]+)\]/s', $security, $matches);
    foreach ($matches[1] as $match) {
        if (preg_match('/^(WPA\d?)/', $match, $protocol_match)) {
            $protocol = $protocol_match[1];
            $matchArr = explode('-', $match);
            if (count($matchArr) > 2) {
                $options[] = htmlspecialchars($protocol . ' ('. $matchArr[2] .')', ENT_QUOTES);
            } else {
                $options[] = htmlspecialchars($protocol, ENT_QUOTES);
            }
        }
    }

    if (count($options) === 0) {
        // This could also be WEP but wpa_supplicant doesn't have a way to determine
        // this.
        // And you shouldn't be using WEP these days anyway.
        return 'Open';
    } else {
        return implode(' / ', $options);
    }
}

/**
 * Renders a simple PHP template
 */
function renderTemplate($name, $__template_data = [])
{
    $file = realpath(dirname(__FILE__) . "/../templates/$name.php");
    if (!file_exists($file)) {
        return "template $name ($file) not found";
    }

    if (is_array($__template_data)) {
        extract($__template_data);
    }

    ob_start();
    include $file;
    return ob_get_clean();
}

function expandCacheKey($key)
{
    return RASPI_CACHE_PATH . "/" . $key;
}

function hasCache($key)
{
    $cacheKey = expandCacheKey($key);
    return file_exists($cacheKey);
}

function readCache($key)
{
    $cacheKey = expandCacheKey($key);
    if (!file_exists($cacheKey)) {
        return null;
    }
    return file_get_contents($cacheKey);
}

function writeCache($key, $data)
{
    if (!file_exists(RASPI_CACHE_PATH)) {
        mkdir(RASPI_CACHE_PATH, 0777, true);
        $cacheKey = expandCacheKey($key);
        file_put_contents($cacheKey, $data);
    }
}

function deleteCache($key)
{
    if (hasCache($key)) {
        $cacheKey = expandCacheKey($key);
        unlink($cacheKey);
    }
}

function cache($key, $callback)
{
    if (hasCache($key)) {
        return readCache($key);
    } else {
        $data = $callback();
        writeCache($key, $data);
        return $data;
    }
}

// insspired by
// http://markushedlund.com/dev/php-escapeshellarg-with-unicodeutf-8-support
function mb_escapeshellarg($arg)
{
    $isWindows = strtolower(substr(PHP_OS, 0, 3)) === 'win';
    if ($isWindows) {
        return '"' . str_replace(array('"', '%'), '', $arg) . '"';
    } else {
        return "'" . str_replace("'", "'\\''", $arg) . "'";
    }
}

function dnsServers()
{
    $data = json_decode(file_get_contents("./config/dns-servers.json"));
    return (array) $data;
}

function blocklistProviders()
{
    $data = json_decode(file_get_contents("./config/blocklists.json"));
    return (array) $data;
}

function optionsForSelect($options)
{
    $html = "";
    foreach ($options as $key => $value) {
        // optgroup
        if (is_array($value)) {
            $html .= "<optgroup label=\"$key\">";
            $html .= optionsForSelect($value);
            $html .= "</optgroup>";
        }
        // option
        else {
            $key = is_int($key) ? $value : $key;
            $html .= "<option value=\"$value\">$key</option>";
        }
    }
    return $html;
}

function blocklistUpdated($file)
{
    $blocklist = RASPI_CONFIG.'/adblock/'.$file;
    if (file_exists($blocklist)) {
        $lastModified = date ("F d Y H:i:s.", filemtime($blocklist));
        $lastModified = formatDateAgo($lastModified);
        return $lastModified;
    } else {
        return 'Never';
    }
}

function formatDateAgo($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function getThemeOpt()
{
    if (!isset($_COOKIE['theme'])) {
        $theme = "custom.php";
    } else {
        $theme = $_COOKIE['theme'];
    }
    return 'app/css/'.htmlspecialchars($theme, ENT_QUOTES);
}

function getColorOpt()
{
    if (!isset($_COOKIE['color'])) {
        $color = "#3392CC";
    } else {
        $color = "#3392CC";
    }
    return $color;
}
function getSidebarState()
{
    if ($_COOKIE['sidebarToggled'] == 'true' ) {
        return"toggled";
    }
}

// Returns bridged AP mode status
function getBridgedState()
{
    $arrHostapdConf = array();
    if (file_exists(RASPI_CONFIG.'/hostapd.ini')) {
        $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
    }
    // defaults to false
    return  $arrHostapdConf['BridgedEnable'];
}

/**
 * Validates the format of a CIDR notation string
 *
 * @param string $cidr
 * @return bool
 */
function validateCidr($cidr)
{
    $parts = explode('/', $cidr);
    if(count($parts) != 2) {
        return false;
    }
    $ip = $parts[0];
    $netmask = intval($parts[1]);

    if($netmask < 0) {
        return false;
    }
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $netmask <= 32;
    }
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return $netmask <= 128;
    }
    return false;
}    

// Validates a host or FQDN
function validate_host($host)
{
  return preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $host);
}

// Gets night mode toggle value
// @return boolean
function getNightmode()
{
    if ($_COOKIE['theme'] == 'lightsout.css') {
        return true;
    } else {
        return false;
    }
}	
	
// search array for matching string and return only first matching group
function preg_only_match($pat,$haystack)
{
  $match = "";
  if(!empty($haystack) && !empty($pat)) {
    if(!is_array($haystack)) $haystack = array($haystack);
    $str = preg_grep($pat,$haystack);
    if (!empty($str) && preg_match($pat,array_shift($str),$match) === 1 ) $match = $match[1];
  }
  return $match;
}

// Sanitizes a string for QR encoding
// @param string $str
// @return string
function qr_encode($str)
{
    return preg_replace('/(?<!\\\)([\":;,])/', '\\\\\1', $str);
}

function evalHexSequence($string) {
    $evaluator = function ($input) {
	return hex2bin($input[1]);
    };
    return preg_replace_callback('/\\\x(..)/', $evaluator, $string);
}

function hexSequence2lower($string) {
 return preg_replace_callback('/\\\\x([0-9A-F]{2})/', function($b){ return '\x'.strtolower($b[1]); }, $string);
}

/* File upload callback object
 *
 */
class validation
{
    public function check_name_length($object)
    {
        if (strlen($object->file['filename']) > 255) {
            $object->set_error('File name is too long.');
        }
    }
}

/* Resolves public IP address
 *
 * @return string $public_ip
 */
function get_public_ip()
{
    exec('wget https://ipinfo.io/ip -qO -', $public_ip);
    return $public_ip[0];
}

function getModel()
{
    exec('cat /etc/fw_model', $model);
    return $model[0];
}

function getTarget()
{
    exec('cat /etc/target_model', $target);
    return $target[0];
}

function isBinExists($name)
{
    exec("which $name", $path);
    if ($path[0] == null) {
        return false;
    } else {
        return true;
    }
}

function getBetweenStrings($src, $string)
{
    $tmp = strstr($src, $string);
    $substring = substr($tmp, strlen($string));
    $dest = strstr($substring, $string, true);

    return $dest != null ? $dest : $tmp;
}

// Load non default JS/ECMAScript in footer
function loadFooterScripts($extraFooterScripts)
{
    foreach ($extraFooterScripts as $script) {
        echo '<script type="text/javascript" src="' , $script['src'] , '"';
        if ($script['defer']) {
            echo ' defer="defer"';
        }
        echo '></script>' , PHP_EOL;
    }
}

function handlePageActions($extraFooterScripts, $page, $config)
{
    // handle page actions
    switch ($page) {
        case "/dashboard":
            DisplayDashboard($extraFooterScripts);
            break;
        case "/dhcpd_conf":
            DisplayDHCPConfig();
            break;
        case "/wpa_conf":
            DisplayWPAConfig();
            break;
        case "/network_conf":
            DisplayNetworkingConfig();
            break;
        case "/hostapd_conf":
            DisplayHostAPDConfig();
            break;
        case "/detection_conf":
            DisplayDetectionConfig();
            break;
        case "/adblock_conf":
            DisplayAdBlockConfig();
            break;
        case "/openvpn":
            DisplayOpenVPNConfig();
            break;
        case "/wireguard":
            DisplayWireGuardConfig();
            break;
        case "/torproxy_conf":
            DisplayTorProxyConfig();
            break;
        case "/auth_conf":
            DisplayAuthConfig($config['admin_user'], $config['admin_pass']);
            break;
        case "/save_hostapd_conf":
            SaveTORAndVPNConfig();
            break;
        case "/theme_conf":
            DisplayThemeConfig($extraFooterScripts);
            break;
        case "/data_use":
            DisplayDataUsage($extraFooterScripts);
            break;
        case "/system_info":
            DisplaySystem();
            break;
        case "/about":
            DisplayAbout();
            break;
        case "/basic_conf":
            DisplayBasic();
            break;
        case "/interfaces_conf":
            DisplayInterfaces();
            break;
        case "/modbus_conf":
            DisplayModbus();
            break;
        case "/ascii_conf":
            DisplayAscii();
            break;
        case "/s7_conf":
            DisplayS7();
            break;
        case "/fx_conf":
            DisplayFx();
            break;
        case "/mc_conf":
            DisplayMc();
            break;
        case "/iec104_conf":
            DisplayIEC104();
            break;
        case "/io_conf":
            DisplayIO();
            break;
        case "/server_conf":
            DisplayServer();
            break;
        case "/ddns":
            DisplayDDNS();
            break;
        case "/opcua":
            DisplayOpcua();
            break;
        case "/bacnet":
            DisplayBACnet();
            break;
        case "/dnp3":
            DisplayDnp3();
            break;
        case "/modbus_slave":
            DisplayModbusSlave();
            break;
        case "/datadisplay":
            dataDisplay();
            break;
        case "/macchina":
            DisplayMacchina();
            break;
        case "/lorawan_conf":
            DisplayLorawan();
            break;
        case "/terminal":
            DisplayTerminal();
            break;
        case "/gps":
            DisplayGps();
            break;
        case "/firewall_conf":
            DisplayFirewall();
            break;
        case "/opcuacli_conf":
            DisplayOpcuaClient();
            break;
        case "/baccli_conf":
            DisplayBACnetClient();
            break;
        case "/dnp3cli_conf":
            DisplayDnp3Client();
            break;
        case "/nodered":
            DisplayNodered();
            break;
        case "/docker":
            DisplayDocker();
            break;
        case "/chirpstack":
            DisplayChirpstack();
            break;
        case "/bacnet_router":
            DisplayBacnetRouter();
            break;
        case "/backup_update":
            DisplayBackupUpdate();
            break;
        default:
            DisplayDashboard($extraFooterScripts);
    }
}

function InputControlCustom($title, $name, $id = null, $descr = null, $defaultValue = null, $event = null)
{
    echo '<div class="cbi-value">
      <label class="cbi-value-title">'.htmlspecialchars($title, ENT_QUOTES).'</label>' , PHP_EOL;
    echo '<input type="text" class="cbi-input-text" name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="'.htmlspecialchars($id, ENT_QUOTES).'"';
    }
    if (isset($id)) {
        echo ' value="'.htmlspecialchars($defaultValue, ENT_QUOTES).'"';
    }
    if (isset($event)) {
        echo ' onChange="' . htmlspecialchars($event, ENT_QUOTES). '"';
    }
    echo '>' , PHP_EOL;
    if (isset($descr)) {
        echo '<label class="cbi-value-description">' . htmlspecialchars($descr, ENT_QUOTES) . '</label>';
    }
    echo '</div>';
}

function SelectControlCustom($title, $name, $options, $selected = null, $id = null, $descr = null, $event = null, $disabled = null)
{
    echo '<div class="cbi-value">
        <label class="cbi-value-title">' . htmlspecialchars($title, ENT_QUOTES) . '</label>' , PHP_EOL;
    echo '<select class="cbi-input-select" name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="'.htmlspecialchars($id, ENT_QUOTES).'"';
    }
    if (isset($event)) {
        echo ' onChange="' . htmlspecialchars($event, ENT_QUOTES). '"';
    }
    echo '>' , PHP_EOL;
    if (is_array($options)) {
        foreach ($options as $opt => $label) {
            $select = '';
            // $key = isAssoc($options) ? $opt : $label;
            if ($label == $selected) {
                $select = ' selected="selected"';
            }
            if ($label == $disabled) {
                $disabled = ' disabled';
            }
            echo '<option value="'.htmlspecialchars($opt, ENT_QUOTES).'"'.$select.$disabled.'>'.
                htmlspecialchars($label, ENT_QUOTES).'</option>' , PHP_EOL;
        }
    }

    echo '</select>' , PHP_EOL;
    if (isset($descr)) {
        echo '<label class="cbi-value-description">' . htmlspecialchars($descr, ENT_QUOTES) . '</label>';
    }
    echo '</div>' , PHP_EOL;
}

function CheckboxControlCustom($title, $name, $id = null, $checked = null, $descr = null, $event = null)
{
    echo '<div class="cbi-value">
      <label class="cbi-value-title">'.htmlspecialchars($title, ENT_QUOTES).'</label>' , PHP_EOL;
    echo '<input type="checkbox" value="1" ';
    if (isset($checked)) {
        echo htmlspecialchars($checked, ENT_QUOTES);
    }
    echo ' name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="'.htmlspecialchars($id, ENT_QUOTES).'"';
    }
    if (isset($event)) {
        echo ' onChange="' . htmlspecialchars($event, ENT_QUOTES). '"';
    }
    echo '>' , PHP_EOL;
    if (isset($descr)) {
        echo '<label class="cbi-value-description">' . htmlspecialchars($descr, ENT_QUOTES) . '</label>';
    }
    echo '</div>';
}

function LabelControlCustom($title, $name, $id = null, $value = null, $descr = null, $event = null)
{
    echo '<div class="cbi-value">
        <label class="cbi-value-title">'.htmlspecialchars($title, ENT_QUOTES).'</label>' , PHP_EOL;
    echo '<label name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    if (isset($id)) {
        echo ' id="'.htmlspecialchars($id, ENT_QUOTES).'"';
    }
    if (isset($event)) {
        echo ' onChange="' . htmlspecialchars($event, ENT_QUOTES). '"';
    }
    echo '>';
    echo _(empty($value) ? '-' : $value);
    echo '</label>', PHP_EOL;
    if (isset($descr)) {
        echo '<label class="cbi-value-description">' . htmlspecialchars($descr, ENT_QUOTES) . '</label>';
    }
    echo '</div>';
}

function RadioControlCustom($title, $name, $id, $event, $num = null, $descr = null)
{
    echo '<div class="cbi-value">
        <label class="cbi-value-title">'.htmlspecialchars($title, ENT_QUOTES).'</label>' , PHP_EOL;
    echo '<input class="cbi-input-radio" id="'.htmlspecialchars($id, ENT_QUOTES).'_enable'.$num.'" ';
    echo 'name="'.htmlspecialchars($name, ENT_QUOTES).$num.'" value="1" type="radio" checked ';
    echo 'onchange="' . htmlspecialchars($event, ENT_QUOTES). '(true'.(($num != null)?(','.$num):'').', \''.$id.'\')">';
    echo '<label >' . _('Enable') . '</label>';
    echo PHP_EOL;
    echo '<input class="cbi-input-radio" id="'.htmlspecialchars($id, ENT_QUOTES).'_disable'.$num.'" ';
    echo 'name="'.htmlspecialchars($name, ENT_QUOTES).$num.'" value="0" type="radio" ';
    echo 'onchange="' . htmlspecialchars($event, ENT_QUOTES). '(false'.(($num != null)?(','.$num):'').', \''.$id.'\')">';
    echo '<label >' . _('Disable') . '</label>';
    echo '</div>';
}

function UploadFileControlCustom($title, $btn_id, $text_id, $file_name, $file_id, $event)
{
    echo '<div class="cbi-value">
    <label class="cbi-value-title">'.htmlspecialchars($title, ENT_QUOTES).'</label>' , PHP_EOL;
    echo '<label class="cbi-file-lable">
        <input type="button" class="cbi-file-btn" id="'.htmlspecialchars($btn_id, ENT_QUOTES).'" value="'._("Choose file").'">
        <span id="'.htmlspecialchars($text_id, ENT_QUOTES).'">'._("No file chosen").'</span>
        <input type="file" class="cbi-file" name="'.htmlspecialchars($file_name, ENT_QUOTES).
        '" id="'.htmlspecialchars($file_id, ENT_QUOTES).
        '" onchange="' . htmlspecialchars($event, ENT_QUOTES). '">
    </label>
    </div>';
}

function UploadFileMultipleControlCustom($title, $btn_id, $text_id, $file_name, $file_id, $event)
{
    echo '<div class="cbi-value">
    <label class="cbi-value-title">'.htmlspecialchars($title, ENT_QUOTES).'</label>' , PHP_EOL;
    echo '<label class="cbi-file-lable">
        <input type="button" class="cbi-file-btn" id="'.htmlspecialchars($btn_id, ENT_QUOTES).'" value="'._("Choose file").'">
        <span id="'.htmlspecialchars($text_id, ENT_QUOTES).'">'._("No file chosen").'</span>
        <input type="file" multiple="multiple" class="cbi-file" name="'.htmlspecialchars($file_name, ENT_QUOTES).
        '" id="'.htmlspecialchars($file_id, ENT_QUOTES).
        '" onchange="' . htmlspecialchars($event, ENT_QUOTES). '">
    </label>
    </div>';
}

function BtnSaveApplyCustom($save_name, $apply_name)
{
    echo '<div class="cbi-page-actions">
        <input type="submit" class="btn btn-outline btn-primary" value="' . _("Save settings") . '" name="'.htmlspecialchars($save_name, ENT_QUOTES).'" />
        <input type="submit" class="btn btn-success" value="' . _("Apply settings") . '" data-toggle="modal" data-target="#hostapdModal" name="'.htmlspecialchars($apply_name, ENT_QUOTES).'" />
    </div>';
}
