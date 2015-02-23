<?php
/**
 * BaseSpider 
 * 
 * @package 
 * @version $id$
 * @copyright 
 * @author Joseph Persie <joseph@supraliminalsolutions.com> 
 * @license 
 */
class BaseSpider 
{
    /**
     * COOKIE_FILE 
     * 
     * Where curl will store cookie files
     *
     * @const string
     */
    const COOKIE_FILE = 'cookiejar/cookies.txt';

    /**
     * USER_AGENT 
     * 
     * A default user agent to spoof servers with
     *
     * @const string
     */
    const USER_AGENT = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

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
     * @var mixed
     * @access protected
     */
    protected $ch;

    /**
     * dbal 
     * 
     * @var mixed
     * @access protected
     */
    protected $dbal; 

    /**
     * last_log_msg 
     * 
     * @var mixed
     * @access protected
     */
    protected $last_log_msg;

    /**
     * job 
     * 
     * @var mixed
     * @access protected
     */
    protected $job;

    /**
     * is_debug_mode_enabled 
     * 
     * @var mixed
     * @access protected
     */
    protected $is_debug_mode_enabled;

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

    /**
     * setHtmlDomParser
     * 
     * @param mixed $html_dom_parser 
     * @access public
     * @return void
     */
    public function setHtmlDomParser($html_dom_parser)
    {
        $this->html_dom_parser = $html_dom_parser;
    }



    /**
     * setDBAL
     * 
     * mutator dependency injection for the database abstraction layer 
     *
     * @param FleetDBALInterface $dbal 
     * @access public
     * @return void
     */
    public function setDBAL(SpiderDBALInterface $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * setJob
     * 
     * @param SpiderJob $job 
     * @access public
     * @return void
     */
    public function setJob(SpiderJob $job)
    {
        $this->job = $job;
    }

    /**
     * _base_curl_call
     * 
     * @param mixed $args 
     * @param string $request_type 
     * @access protected
     * @return void
     */
    protected function _base_curl_call($args, $request_type = "post")
    {
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE );

        //sleep(1.72 * rand(1, 3));

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
            $cookie_file = dirname(__FILE__) . '/../' . self::COOKIE_FILE;

            curl_setopt($this->ch, CURLOPT_USERAGENT, self::USER_AGENT);
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
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $args['post_data']);
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

        return compact('content','header');
    }

    protected function _parseLocationFromHeaders($content)
    {
        if ( ! preg_match('#Location: (.*)#', $content, $location))
        {
            throw new Exception('No Location found');
        }

        $location = str_replace('\r','',trim($location[1]));

        return $location;
    }   

    /**
     * _debug_msg
     * 
     * @param string $msg 
     * @access protected
     * @return void
     */
    protected function _debug_msg($msg)
    {
        $msg = "Debug: " . $msg . "\r\n";

        $this->_save_log('debug', $msg);

        echo $msg;
    }

    /**
     * _error_msg
     * 
     * @param string $msg 
     * @access protected
     * @return void
     */
    protected function _error_msg($msg)
    {
        $msg = "Error: " . $msg . "\r\n";

        $this->_save_log('error', $msg);

        $this->job->hasFailed();

        echo $msg;
    }

    /**
     * _save_log
     * 
     * unlike the _debug methods this method just saves to the log file
     * and acts as a proxy method for the DBAL saveLog method
     *
     * @param mixed $log_type 
     * @param mixed $msg 
     * @access protected
     * @return void
     */
    protected function _save_log($log_type, $msg)
    {
        $this->last_log_msg = $msg;

        if(strstr($msg, self::VIEWSTATE_FAILURE_MSG))
        {
            $this->job->hasFailed();
        }
            
        $this->dbal->saveLog($log_type, $msg, $this->job->id, $this->current_entity_id);
    }

    /**
     * _parseDomFromArray
     * 
     * @param mixed $dom_array 
     * @access protected
     * @return void
     */
    protected function _parseDomFromArray($dom_array)
    {
        $html = null;

        foreach($dom_array as $dom_item)
        {
            $html = $dom_item->plaintext; 
        }

        return $html; 
    }

    /**
     * _baseDomFromUrl
     * 
     * @param string $url 
     * @param string $method 
     * @access protected
     * @return HTMLDOM
     */
    protected function _baseDomFromUrl($url, $method = "get")
    {
        $url = html_entity_decode($url);

        $response = $this->_base_curl_call(array('url'=>$url), $method);

        $dom = $this->html_dom_parser->load($response['content']);

        if(!is_object($dom))
        {
            $this->_error_msg("Could not get DOM from: " . $url);

            return FALSE;
        }

        return $dom;
    }

    /**
     * _getParamFromUrl
     * 
     * @param string $param 
     * @param string $url 
     * @access protected
     * @return string
     */
    protected function _getParamFromUrl($param, $url)
    {
        $url = html_entity_decode($url);

        $url_fragments = explode('?',$url);

        $last_url_fragment = end($url_fragments);

        parse_str($last_url_fragment, $parsed);

        return $parsed[$param];
    }

    /**
     * _mapSelectorValsToObject
     * 
     * @param HTMLDOM $dom 
     * @param array $selectors 
     * @param referenced Object $object 
     * @access protected
     * @return void
     */
    protected function _mapSelectorValsToObject($dom, $selectors, &$object)
    {

        foreach($selectors as $property_name => $dom_selector)
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
