<?php
namespace SupraSpider;

/**
 * SupraSpider 
 * 
 * @package 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
class SupraSpider 
{
    /**
     * COOKIE_FILE 
     * 
     * Where curl will store cookie files
     *
     * @const string
     */
    private $cookie_file = 'cookiejar/cookies.txt';

    /**
     * USER_AGENT 
     * 
     * A default user agent to spoof servers with
     *
     * @const string
     */
    private $user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

    /**
     * NIL_RECORDS_INDICATOR 
     * 
     * @const string
     */
    const NIL_RECORDS_INDICATOR = "No records match the current search criteria. Click back to enter different criteria.";


    /**
     * ch 
     * 
     * Curl Handle
     *
     * @var CURLHandle
     * @access protected
     */
    protected $ch;

    /**
     * dbal 
     * 
     * @var SupraSpiderDBALInterface
     * @access protected
     */
    protected $dbal; 

    /**
     * last_log_msg 
     * 
     * @var string
     * @access protected
     */
    protected $last_log_msg;

    /**
     * SupraSpiderJob
     * 
     * @var SupraSpiderJob
     * @access protected
     */
    protected $job;

    /**
     * is_debug_mode_enabled 
     * 
     * @var boolean
     * @access protected
     */
    protected $is_debug_mode_enabled;

    /**
     * dom_parser 
     * 
     * @var SupraSpiderDomParserInterface
     * @access protected
     */
    protected $dom_parser;

    /**
     * lastContent 
     * 
     *  Stores the last response retrieved from curl
     *
     * @var string
     * @access protected
     */
    protected $lastContent;

    /**
     * __construct
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->ch = curl_init();
        $this->initialize = FALSE;
    }

    /**
     * setDebugMode
     * 
     * @param boolean $is_mode_enabled 
     * @access public
     * @return void
     */
    public function setDebugMode($is_debug_mode_enabled)
    {
        $this->is_debug_mode_enabled = $is_debug_mode_enabled;
    }

    public function setCookieFile($cookieFile) {
        $this->cookie_file = $cookieFile;
    }

    public function getUserAgent() {

        return $this->user_agent;
    }

    public function setUserAgent($userAgent) {
        $this->user_agent = $userAgent;
    }

    /**
     * setDomParser
     * 
     * @param mixed $dom_parser 
     * @access public
     * @return void
     */
    public function setDomParser(Interfaces\SupraSpiderDomParserInterface $dom_parser)
    {
        $this->dom_parser = $dom_parser;
    }

    /**
     * setDBAL
     * 
     * mutator dependency injection for the database abstraction layer 
     *
     * @param SupraSpiderDBALInterface $dbal 
     * @access public
     * @return void
     */
    public function setDBAL(Interfaces\SupraSpiderDBALInterface $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * setJob
     * 
     * @param SupraSpiderJob $job 
     * @access public
     * @return void
     */
    public function setJob(Interfaces\SupraSpiderJobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * base_curl_call
     * 
     * @param mixed $args 
     * @param string $request_type 
     * @access protected
     * @return void
     */
    protected function base_curl_call($args, $request_type = "post")
    {
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE );

        sleep(.62 * rand(2, 5));

        $args['url'] = html_entity_decode($args['url']); 

        if($this->is_debug_mode_enabled)
        {
            var_dump($args); 
            //curl_setopt($this->ch, CURLOPT_VERBOSE, true);
        }

        $headers[] = "Accept: text/html, application/xhtml+xml, */*";
        $headers[] = "Connection: Keep-Alive";
        //basic curl options for all requests
        curl_setopt($this->ch, CURLOPT_HTTPHEADER,  $headers);

        if(!$this->initialize)
        {
            $cookie_file = $this->cookie_file;

            curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
            curl_setopt($this->ch, CURLOPT_COOKIESESSION, true);
            curl_setopt($this->ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($this->ch, CURLOPT_COOKIEFILE, $cookie_file);
            $this->initialize = TRUE;
        }
        
        curl_setopt($this->ch, CURLOPT_URL, $args['url']);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);

        if($request_type == "post")
        {
            curl_setopt($this->ch, CURLOPT_POST, 1);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, @$args['post_data']);
        }
        else
        {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        }

        $header = curl_getinfo($this->ch);

        if( ! $content = curl_exec($this->ch))
        {
            trigger_error(curl_error($this->ch));
        }

        $this->lastContent = compact('content','header');

        return $this->lastContent;
    }

    public function getLastContent() {

        return $this->lastContent;
    }

    protected function parseLocationFromHeaders($content)
    {
        if ( ! preg_match('#Location: (.*)#', $content, $location))
        {
            throw new Exception('No Location found');
        }

        $location = str_replace('\r','',trim($location[1]));

        return $location;
    }   

    /**
     * debug_msg
     * 
     * @param string $msg 
     * @access protected
     * @return void
     */
    protected function debug_msg($msg)
    {
        $msg = "Debug: " . $msg . "\r\n";

        $this->save_log('debug', $msg);

        echo $msg;
    }

    /**
     * error_msg
     * 
     * @param string $msg 
     * @access protected
     * @return void
     */
    protected function error_msg($msg)
    {
        $msg = "Error: " . $msg . "\r\n";

        $this->save_log('error', $msg);

        $this->job->hasFailed();

        echo $msg;
    }

    /**
     * save_log
     * 
     * unlike the _debug methods this method just saves to the log file
     * and acts as a proxy method for the DBAL saveLog method
     *
     * @param mixed $log_type 
     * @param mixed $msg 
     * @access protected
     * @return void
     */
    protected function save_log($log_type, $msg)
    {
        $this->last_log_msg = $msg;

        if(strstr($msg, self::VIEWSTATE_FAILURE_MSG))
        {
            $this->job->hasFailed();
        }
            
        $this->dbal->saveLog($log_type, $msg, $this->job->getId());
    }

    /**
     * parseDomFromArray
     * 
     * @param mixed $dom_array 
     * @access protected
     * @return void
     */
    protected function parseDomFromArray($dom_array, $plaintext_property = 'plaintext')
    {
        $html = null;

        foreach($dom_array as $dom_item)
        {
            $html = $dom_item->$plaintext_property; 
        }

        return $html; 
    }

    /**
     * baseDomFromUrl
     * 
     * @param string $url 
     * @param string $method 
     * @access protected
     * @return $dom
     */
    protected function baseDomFromUrl($url, $method = "get")
    {
        $url = html_entity_decode($url);

        $response = $this->base_curl_call(array('url'=>$url), $method);

        $dom = $this->dom_parser->load($response['content']);

        if(!is_object($dom))
        {
            $this->error_msg("Could not get DOM from: " . $url);

            return FALSE;
        }

        return $dom;
    }

    /**
     * getParamFromUrl
     * 
     * @param string $param 
     * @param string $url 
     * @access protected
     * @return string
     */
    protected function getParamFromUrl($param, $url)
    {
        $url = html_entity_decode($url);

        $url_fragments = explode('?',$url);

        $last_url_fragment = end($url_fragments);

        parse_str($last_url_fragment, $parsed);

        return $parsed[$param];
    }

    /**
     * mapSelectorValsToObject
     * 
     * @param HTMLDOM $dom 
     * @param array $selectors 
     * @param referenced Object $object 
     * @access protected
     * @return void
     */
    protected function mapSelectorValsToObject($dom, $selectors, &$object)
    {

        foreach((array)$selectors as $property_name => $dom_selector)
        {
            $selector_val_node = $dom->find($dom_selector, 0);

            if(strstr(' a', $dom_selector) || strstr(' a[', $dom_selector))
            {
                @ $selector_val = $selector_val_node->href;
            }
            elseif(strstr('meta[', $dom_selector))
            {
                @ $selector_val = $selector_val_node->content;
            }
            else
            {
                @ $selector_val = $selector_val_node->innertext;
            }

            $object->$property_name = $selector_val;
        }
   }
}
