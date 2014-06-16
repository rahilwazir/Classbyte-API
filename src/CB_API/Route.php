<?php
namespace CB_API;

class Route
{
	// Array of URL segments
	public $segments = null;

	public function __construct()
	{
		$req_uri = str_replace(ROOT_URL, '', $_SERVER['REQUEST_URI']);

		$req = explode('/', $req_uri);
		
		array_walk($req, array($this, 'urlFormatter'));
        
		if (!$this->segments) {
			$this->segments = array_filter($req);
		}
	}

    /**
     * @param $item string
     * @param $key mixed
     * @return void
     */
    private function urlFormatter(&$item, $key)
	{
		$item = str_replace('-', '_', $item);
        
		$url_replacements = preg_replace('#[^\w]#', '', convert_to_cc($item));

		if ($key < 1) {
			$item = ucfirst($url_replacements);
		} else {
			$item = $url_replacements;
		}
	}

	public function callee()
	{
		if (!isset($this->segments[0], $this->segments[1])) {
		    send_header(401);
        }
        
		$class_name = __NAMESPACE__ . '\\' . $this->segments[0];

		if (!class_exists($class_name)) {
            send_header(404);
        }
        
		$cb_si = new $class_name();
        $method = ltrim($this->segments[1], '_');
		
        if (!method_exists($class_name, $method)) {
            send_header(404);
        }
        
        $reflection = new \ReflectionMethod($class_name, $method);
        
        if (!$reflection->isPublic()) {
            send_header(401);
        }
        
		if (is_callable(array($cb_si, $this->segments[1]))) {
			$args = array();
			
			$params = array_slice($this->segments, 2);
			if (!empty($params) && count($params) > 0) {
				$args = $params;
			}
            
			call_user_func_array(array($cb_si, $this->segments[1]), $args);
		}
	}
}