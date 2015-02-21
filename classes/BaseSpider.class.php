<?php
class BaseSpider 
{
    const COOKIE_FILE = 'cookiejar/cookies.txt';

    const USER_AGENT = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

    const VIEWSTATE_FAILURE_MSG = 'Cant get the VIEWSTATE';

    const NIL_RECORDS_INDICATOR = "No records match the current search criteria. Click back to enter different criteria.";

    const RUN_SCRIPT = '../run';

    protected $ch;

    protected $dbal; 

    protected $current_entity_id;

    protected $last_log_msg;

    protected $job;

    protected $is_debug_mode_enabled;

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

    public function setJob(SpiderJob $job)
    {
        $this->job = $job;
    }

    protected function _base_curl_call($args, $request_type = "post")
    {
        var_dump($args); 

        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->ch, CURLOPT_AUTOREFERER, TRUE );

        if($this->debug_mode_on)
        {
        //    curl_setopt($this->ch, CURLOPT_HEADER, true);
        //    curl_setopt($this->ch, CURLOPT_VERBOSE, true);
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

    protected function _debug_msg($msg)
    {
        $msg = "Debug: " . $msg . "\r\n";

        $this->_save_log('debug', $msg);

        echo $msg;
    }

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

    protected function _parseDomFromArray($dom_array)
    {
        $html = null;

        foreach($dom_array as $dom_item)
        {
            $html = $dom_item->plaintext; 
        }

        return $html; 
    }
}
