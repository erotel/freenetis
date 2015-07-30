<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/** File with XML config generated by generate_unit_config.pl at same path */
define("filename", APPPATH . "vendors/unit_tester/unit_testing_config.xml");

/**
 * Exception handler for disabling Kohana handler
 * @param Exception $exception 
 */
function unit_tester_exception_handler($exception)
{
	throw $exception;
}

/**
 * Error handler for disabling Kohana handler
 * @param Exception $exception 
 */
function unit_tester_error_handler($errno, $errstr, $errfile, $errline)
{
	throw new Exception($errstr, $errno);
}

/**
 * Fatal error handler
 */
function unit_tester_handle_shutdown()
{
	$error = error_get_last();
	if ($error !== NULL)
	{
		throw new Exception($error['message']);
	}
}

/**
 * Unit tester controller, test models, helpers and controllers.
 * Invoke testers and displays results.
 * 
 * Unit tester has to be enabled in config file, because it is not secure to run
 * it in product server.
 * 
 * Unit tester is not called directly, but using sh script stored in:
 * 
 * /application/vendors/unit_tester/tester.sh
 * 
 * For help see:
 * 
 * $ /application/vendors/unit_tester/tester.sh --help
 * 
 * @see http://wiki.freenetis.org/index.php/Automatické_testování
 * @author Ondřej Fibich
 * @package Controller
 * @version 1.2
 */
class Unit_tester_Controller extends Controller
{
	/** Type for test of models */
	const MODELS = 1;
	/** Type for test of constrollers */
	const CONTROLLERS = 2;
	/** Type for test of helpers */
	const HELPERS = 3;
	
	/**
	 * Test models
	 * 
	 * @param mixed $stats	Indicator if stats are enabled
	 */
	public function models($stats = NULL)
	{
		$this->test(self::MODELS, !empty($stats));
	}
	
	/**
	 * Test helpers
	 */
	public function helpers()
	{
		$this->test(self::HELPERS, FALSE);
	}

	/**
	 * Invokes test suites and displays results
	 * 
	 * @param type $type	One of ALL, MODELS, CONTROLLERS, HELPERS
	 *						which can be combined using bit or
	 * @param bool $stats	Indicator if stats are enabled
	 */
	private function test($type, $stats = FALSE)
	{
		// check type of test
		if ($type < self::MODELS || $type > self::HELPERS)
		{
			die("Wrong argument.");
		}
		// check access
		if (!Config::get('unit_tester'))
		{
			echo "Enable Unit Test by adding ";
			echo "<code>\$config['unit_tester'] = TRUE;</code>";
			echo " into <code>config.php</code>.";
			exit();
		}
		// overload Kohana error handlers
		register_shutdown_function('unit_tester_handle_shutdown');
		set_error_handler('unit_tester_error_handler');
		set_exception_handler('unit_tester_exception_handler');
		// extend safe limit
		set_time_limit(60);
		// results
		$results = array();
		/* testing */
		try
		{
			// test models
			if ($type == self::MODELS)
			{
				$info_array = $this->test_util("model", $stats);
				$results["title"]  = "Models";
				$results["valids"] = $info_array[0];
				$results["errors"] = $info_array[1];
			}
			// test helpers
			else if ($type == self::HELPERS)
			{
				$info_array = $this->test_util("helper", $stats);
				$results["title"]  = "Helpers";
				$results["valids"] = $info_array[0];
				$results["errors"] = $info_array[1];
			}
		}
		catch (Exception $e)
		{
			die("<pre>" . $e . "</pre>");
		}
		/* Display results */
		$view = new View("unit_tester/index");
		$view->title = "Unit tester controller";
		$view->results = $results;
		$view->stats = $stats;
		$view->render(TRUE);
	}
	
	/**
	 * Testing utility.
	 * Check parse errors and exception throws from objects.
	 * @param string $tag	Tag model or helper
	 * @param bool $stats	Indicator if stats are enabled
	 * @return array		Array with valid and error array.
	 *						Valid array contaions counts of valid methods and models.
	 *						Item of error array contains keys obj, type, error, trace.
	 */
	private function test_util($tag, $stats)
	{
		$errors = array();
		$valids = array
		(
			"models"  => 0,
			"methods" => 0
		);
		$f = false;
		$xml_dom = new DOMDocument("1.0", "UTF-8");

		// open file
		if (($f = file_exists(filename)) === false)
		{
			echo "Cannot find file: `" . filename . "`\n";
			echo "Run unit_testing_config.pl\n";
			exit(1);
		}

		// read whole file
		$source = file_get_contents(filename);

		// parse file
		if (!$xml_dom->loadXML($source))
		{
			echo "Cannot parse config file: `" . filename . "`\n";
			exit(2);
		}
		
		ob_start();

		$elements = $xml_dom->getElementsByTagName($tag);

		// each model
		foreach ($elements as $element)
		{
			$file_name = $element->getAttribute("name");
			$file_path = APPPATH . $tag . "s/" . $file_name . EXT;

			/* File exist catcher */
			if (!file_exists($file_path))
			{
				$errors[] = array
				(
					"obj"   => $file_name,
					"type"  => "FILE ERROR on " . $tag,
					"trace" => null,
					"error" => "Cannot find " . $tag . " file: `" . $file_path . "`\n"
				);
				// next file
				continue;
			}

			$class_name = "";
			/* Load model */
			if ($tag == "model")
			{
				$class_name = ucfirst($file_name) . "_Model";
			}
			else
			{
				$class_name = $file_name;

				if (!class_exists($class_name))
				{
					if (class_exists($class_name . "_Core"))
					{
						$class_name = $class_name . "_Core";
					}
				}
			}

			if (!class_exists($class_name))
			{
				$errors[] = array
				(
					"obj"   => $class_name,
					"type"  => "EXCEPTION ERROR in " . $tag,
					"trace" => null,
					"error" => "Model does not exists."
				);
				// next model
				continue;
			}

			try 
			{
				$obj = new $class_name;
				$valids["models"]++;
			}
			catch (Exception $e)
			{
				$errors[] = array
				(
					"obj"   => $class_name,
					"type"  => "EXCEPTION ERROR during loading " . $tag,
					"trace" => $e->getTraceAsString(),
					"error" => $e->__toString()
				);
				// next file
				continue;
			}

			/* Check object methods */
			$methods = $element->getElementsByTagName("method");

			foreach ($methods as $method)
			{
				$method_name = $method->getAttribute("name");
				$attributes = array();

				/* Get attributes */
				$attrs = $method->getElementsByTagName("attributes")
								->item(0)
								->getElementsByTagName("attribute");		

				foreach ($attrs as $attr)
				{
					$attr_name = $attr->getAttribute("name");
					$attributes[$attr_name] = $attr->getAttribute("default_value");

					if (preg_match("/^array\(.*\)$/", $attributes[$attr_name]))
					{
						$attributes[$attr_name] = self::array_parse($attributes[$attr_name]);
					}
				}

				/* Call for all inputs */
				$inputs = $method->getElementsByTagName("values")
								 ->item(0)
								 ->getElementsByTagName("input");

				foreach ($inputs as $input)
				{
					$unprocessed_input = array();
					/* Get params */
					$paramsarray = array();
					$i = 0;
					
					if ($input->hasChildNodes())
					{
						$params = $input->getElementsByTagName("param");

						foreach ($params as $param)
						{
							$paramsarray[$i] = $param->getAttribute("value");
							$unprocessed_input[$i] = $paramsarray[$i];
							
							if (strtolower($paramsarray[$i]) == 'true')
							{ // bool true
								$paramsarray[$i] = true;
							}
							else if (strtolower($paramsarray[$i]) == 'false')
							{ // bool false
								$paramsarray[$i] = false;
							}
							else if (preg_match("/^array\(.*\)$/", $paramsarray[$i]))
							{ // array
								$paramsarray[$i] = self::array_parse($paramsarray[$i]);
							}
							else
							{
								$unprocessed_input[$i] = "'" . $paramsarray[$i] . "'";
							}
							
							$i++;
						}
					}

					/* Call method */
					try
					{					
						if ($stats)
						{
							$mtime = microtime(true);
						}
						
						call_user_func_array(array($obj, $method_name), $paramsarray);
						$valids["methods"]++;
						
						if ($stats)
						{
							$valids[] = array
							(
								"obj"   => $class_name . "#" . $method_name,
								"type"  => $method_name . "(".  implode(",", $unprocessed_input).")",
								"time"	=> round((microtime(true) - $mtime), 4)
							);
						}
					}
					catch (Exception $e)
					{
						$errors[] = array
						(
							"obj"   => $class_name . "#" . $method_name,
							"type"  => "EXCEPTION ERROR in " . $tag . " during call "
									 . $method_name . "(".  implode(",", $unprocessed_input).")",
							"trace" => $e->getTraceAsString(),
							"error" => $e->getMessage()
						);
						// next
						continue;
					}
				}
			}
		}
		
		ob_clean();
		
		return array($valids, $errors);
	}
	
	/**
	 * Parse string with array
	 * @param type $str		String with array PHP syntax
	 * @return array		Parsed array or null on error
	 */
	public static function array_parse($str)
	{
		if ($str === "array()")
		{
			return array();
		}
		else
		{
			// get content of array
			$str = substr($str, 6);
			$str = substr($str, 0, strlen($str) - 1);
			// check content
			$items = preg_split("/\s*,\s*/", $str);
			$result = array();
			// for each item of array
			foreach ($items as $item)
			{
				if (strlen($item) > 0)
				{
					if (preg_match("/^[\"']?(.*?)[\"']?\s*=>\s*[\"']?(.*?)[\"']?$/", $item, $r))
					{
						$result[$r[1]] = $r[2];
					}
					else if (preg_match("/^[\"']?(.*?)[\"']?$/", $item, $r))
					{
						$result[] = $r[1];
					}
				}
			}
			// return parsed array
			return $result;
		}
		return null;
	}
	
}
