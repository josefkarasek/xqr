#!/bin/env php
<?php
    
    class Query {
        var $n;
        var $root;
        var $qf;
        var $input;
        var $output;
        var $SELECT;
        var $LIMIT;
        var $FROM = array();
        var $NOT;
        var $LEFT = array();
        var $OPERATOR;
        var $RIGHT; 
    }

    function getAction($xqr, $argc, $argv, $stderr) {
        $arguments = $argv;
        unset($arguments[0]);
        foreach ($arguments as $value) { 

            //--help
            if(preg_match("/--help/", $value)) {
                printHelp();
                exit (0);
            }
            //-n
            if(preg_match("/^-n$/", $value)) {
                $xqr->n = TRUE;
                unset($arguments[array_search($value, $arguments)]);
            }
            //--root
            if(preg_match("/--root=/", $value))  {
                if(preg_match('/\A(?!XML)[a-z][\w0-9-]*/i', substr($value, 7))) {
                    $xqr->root = trim(substr($value, 7), "\n");
                    unset($arguments[array_search($value, $arguments)]);
                } else {
                    fwrite($stderr, "Error: ROOT neni platny element\n");
                    exit (1);
                }
            }
            //--qf
            if(preg_match("/--qf=.+/", $value)) {
                if(isset($xqr->input)) {
                    fwrite($stderr, "Error: kombinace --query & --qf\n");
                    exit (1);
                }
                $xqr->qf = trim(substr($value, 5), "\n");
                unset($arguments[array_search($value, $arguments)]);

                if(! file_exists($xqr->qf)) {
                    fwrite($stderr, "Error: soubor $xqr->qf neexistuje\n");
                    exit (1);
                }
                if(($qf = fopen($xqr->qf, 'r')) === FALSE) {
                    fwrite($stderr, "Error: nepodarilo se otevrit soubor $xqr->qf\n");
                    exit (1); //TODO: error code
                }
                $filetext = fread($qf, filesize($xqr->qf));
                // print_r($filetext);
                $words = split(" ", $filetext);
                // foreach($words as $item) {
                // 	array_push($arguments, $item);
                // }
                parseQuery($xqr, $argc, $words, $stderr);
            }
            //--input
            if(preg_match("/--input=.+/", $value)) {
                $xqr->input = trim(substr($value, 8), "\n");
                unset($arguments[array_search($value, $arguments)]);
            }
            //--output
            if(preg_match("/--output=.+/", $value)) {
                $xqr->output = trim(substr($value, 9), "\n");
                unset($arguments[array_search($value, $arguments)]);
            }

            if(preg_match("/--query=SELECT/", $value)) {
            	if(isset($xqr->qf)) {
            		fwrite($stderr, "Error: kombinace --query & --qf\n");
	                exit (1);
            	}
            	$words[0] = substr($value, 8);
            	// print(count($argv)."\n");
            	for($i = array_search($value, $argv)+1; $i < count($argv); $i++) {
            		if(preg_match("/^--/", $argv[$i]))
            			break;
            		if(preg_match("/^-[[:alpha:]]/", $argv[$i]))
            			break;
            		else
            			array_push($words, $argv[$i]);
            	}
            	for($i=$i-1; $i >= array_search($value, $argv); $i--)
            		unset($arguments[$i]);
            	// print_r ($words);
            	parseQuery($xqr, $argc, $words, $stderr);
            }
        }
// var_dump($arguments);
// var_dump($xqr);
        foreach ($arguments as $value) {
            fwrite($stderr, "Error: neplatne argumenty\n");
            fwrite($stderr, "Run $argv[0] --help instead\n");
            exit (1);
        }
    }

    function parseQuery($xqr, $argc, $arguments, $stderr) {

    	foreach($arguments as $value) {
    		if(preg_match("/SELECT/", $value)) {
	            if(isset($arguments[array_search($value, $arguments) +1])) {
	                if( preg_match('/\A(?!XML)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->SELECT = trim($arguments[array_search($value, $arguments) +1], "\n");
	                    unset($arguments[array_search($value, $arguments) +1]);
	                    unset($arguments[array_search($value, $arguments)]);
	                } else {
	                    fwrite($stderr, "Error: missing SELECT element\n");
	                    exit (1);
	                }
	            } else {
	                fwrite($stderr, "Error: SELECT\n");
	                exit (1);
	            }
            }
        
	        //LIMIT n
	        if(preg_match("/LIMIT/", $value))
	            if(isset($arguments[array_search($value, $arguments) +1]))
	                if(is_numeric($arguments[array_search($value, $arguments) +1])) {
	                    $xqr->LIMIT = trim(intval($arguments[array_search($value, $arguments) +1]), "\n");
	                    unset($arguments[array_search($value, $arguments) +1]);
	                    unset($arguments[array_search($value, $arguments)]);
	                } else {
	                    fwrite($stderr, "Error: LIMIT\n");
	                    exit (1);
	                }
	        //FROM
	        if(preg_match("/FROM/", $value)) {
	            if(isset($arguments[array_search($value, $arguments) +1])) {
	                //ROOT
	                if(preg_match("/ROOT/", $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = trim($arguments[array_search($value, $arguments) +1], "\n");
	                    $xqr->FROM[0] = "ROOT";
	                }
	                //element.atribut
	                elseif(preg_match('/\A(?!XML)[a-z][\w0-9-]*\.[a-z][\w0-9-]*$/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = trim($arguments[array_search($value, $arguments) +1], "\n");
	                    $xqr->FROM[0] = "ELEMENT.ATTRIBUTE";
	                }
	                //atribut
	                elseif(preg_match('/\A\.(?!XML)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = trim($arguments[array_search($value, $arguments) +1], "\n");
	                    $xqr->FROM[0] = "ATTRIBUTE";
	                }
	                //element
	                elseif(preg_match('/\A(?!XML|\.)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = trim($arguments[array_search($value, $arguments) +1], "\n");
	                    $xqr->FROM[0] = "ELEMENT";
	                } else {
	                    fwrite($stderr, "Error: missing FROM statement\n");
	                    exit (1);
	                }                   
	            } else {
	                fwrite($stderr, "Error: FROM\n");
	                exit (1);
	            }
	            unset($arguments[array_search($value, $arguments) +1]);
	            unset($arguments[array_search($value, $arguments)]);
	        }
	        //WHERE
	        if(preg_match("/WHERE/", $value)) {
	            //<ELEMENT-OR-ATRIBUTE>
	            if(isset($arguments[array_search($value, $arguments) +1])) {
	                //negace
	                if(preg_match("/NOT/", $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->NOT = TRUE;
	                    $offset = 2;
	                    if (! isset($arguments[array_search($value, $arguments) +$offset])) {
	                        fwrite($stderr, "Error: WHERE\n");
	                        exit (1);
	                    }
	                } else {
	                    $xqr->NOT = FALSE;
	                    $offset = 1;
	                }
	                //element.atribut
	                if(preg_match('/\A(?!XML)[a-z][\w0-9-]*\.[a-z][\w0-9-]*$/i', $arguments[array_search($value, $arguments) +$offset])) {
	                    $xqr->LEFT[1] = $arguments[array_search($value, $arguments) +$offset];
	                    $xqr->LEFT[0] = "ELEMENT.ATTRIBUTE";
	                }
	                //atribut
	                elseif(preg_match('/\A\.(?!XML)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +$offset])) {
	                    $xqr->LEFT[1] = $arguments[array_search($value, $arguments) +$offset];
	                    $xqr->LEFT[0] = "ATTRIBUTE";
	                }
	                //element
	                elseif(preg_match('/\A(?!XML|\.)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +$offset])) {
	                    $xqr->LEFT[1] = $arguments[array_search($value, $arguments) +$offset];
	                    $xqr->LEFT[0] = "ELEMENT";
	                } else {
	                    fwrite($stderr, "Error: WHERE: bad element or attribute\n");
	                    exit (1);
	                }
	            } else {
	                fwrite($stderr, "Error: WHERE: missing statement\n");
	                exit (1);
	            }
	            //<RELATION-OPERAND>
	            if(isset($arguments[array_search($value, $arguments) +$offset+1])) {
	                //CONTAINS
	                if(preg_match('/^CONTAINS$/', $arguments[array_search($value, $arguments) +$offset+1])) {
	                    $xqr->OPERATOR = $arguments[array_search($value, $arguments) +$offset+1];
	                }
	                //< = >
	                elseif(preg_match('/^>$/', $arguments[array_search($value, $arguments) +$offset+1])) {
	                    $xqr->OPERATOR = $arguments[array_search($value, $arguments) +$offset+1];
	                }
	                elseif(preg_match('/^=$/', $arguments[array_search($value, $arguments) +$offset+1])) {
	                    $xqr->OPERATOR = $arguments[array_search($value, $arguments) +$offset+1];
	                }
	                elseif(preg_match('/^<$/', $arguments[array_search($value, $arguments) +$offset+1])) {
	                    $xqr->OPERATOR = $arguments[array_search($value, $arguments) +$offset+1];
	                } else {
	                    fwrite($stderr, "Error: WHERE: bad operator\n");
	                    exit (1);
	                }
	            } else {
	                fwrite($stderr, "Error: WHERE: missing statement\n");
	                exit (1);
	            }
	            //literal
	            if(isset($arguments[array_search($value, $arguments) +$offset+2])) {
	                //number (integer)
	                if(preg_match('/^-?\d+$/', $arguments[array_search($value, $arguments) +$offset+2])) {
                    	$xqr->RIGHT = trim($arguments[array_search($value, $arguments) +$offset+2], "\n");
	                }
	                //string
	                elseif(preg_match('/^[[:alpha:]\d-_\.]+/', $arguments[array_search($value, $arguments) +$offset+2])) {
	                    $xqr->RIGHT = trim($arguments[array_search($value, $arguments) +$offset+2], "\n");
	                } else {
	                    fwrite($stderr, "Error: WHERE: bad literal\n");
	                    exit (1);
	                }
	                $temp = array_search($value, $arguments);
	                unset($arguments[$temp+$offset+2]);
	                unset($arguments[$temp+$offset+1]);
	                unset($arguments[$temp+$offset]);
	                if($xqr->NOT)
	                    unset($arguments[$temp+1]);
	                unset($arguments[$temp]);
	            } else {
	                fwrite($stderr, "Error: WHERE: missing literal\n");
	                exit (1);
	            }
	        }
	    }

        foreach ($arguments as $value) {
            fwrite($stderr, "Error: neplatne argumenty\n");
            // fwrite($stderr, "Run $argv[0] --help instead\n");
            exit (1);
        }
    }

    function formQuery($xqr, $stderr) {

    	#------ <FROM> ------------------------------------
    	if($xqr->FROM[0] === "ROOT")
    		$query = "";   //puvodne "//*[1]""
    	elseif($xqr->FROM[0] === "ELEMENT")
			$query = "//".$xqr->FROM[1]."[1]";
		elseif($xqr->FROM[0] === "ATTRIBUTE")
			$query = "(//@".substr($xqr->FROM[1], 1)."/..)[1]";
		elseif($xqr->FROM[0] === "ELEMENT.ATTRIBUTE") {
			$x = explode(".", $xqr->FROM[1]);
			$query = "//".$x[0]."[@".$x[1]."][1]";
		}
		#------ </FROM> ------------------------------------

		#------ <SELECT> ------------------------------------
		$query .= "//".$xqr->SELECT;
		#------ </SELECT> ------------------------------------

		#------ <WHERE> ------------------------------------
		//ELEMENT
		if($xqr->LEFT[0] === "ELEMENT") {
			//CONTAINS
			if($xqr->OPERATOR === "CONTAINS") {
				if($xqr->NOT) {
					if($xqr->LEFT[1] == $xqr->SELECT)
						$query .= "[not(contains(text(), '".$xqr->RIGHT."'))]";
					else
						$query .= "[not(contains(//".$xqr->LEFT[1].", '".$xqr->RIGHT."'))]";
				} else {
					if($xqr->LEFT[1] == $xqr->SELECT)
						$query .= "[contains(text(), '".$xqr->RIGHT."')]";
					else
						$query .= "[contains(//".$xqr->LEFT[1].", '".$xqr->RIGHT."')]";
				}
			}
			//OPERATOR
			else {
				if($xqr->NOT) {
					$query .= "[not(".$xqr->LEFT[1].$xqr->OPERATOR.$xqr->RIGHT.")]";
				} else {
					$query .= "[".$xqr->LEFT[1].$xqr->OPERATOR.$xqr->RIGHT."]";
				}
			}
		}
		//ELEMENT.ATTRIBUTE
		elseif($xqr->LEFT[0] === "ELEMENT.ATTRIBUTE") {
			//CONTAINS
			if($xqr->OPERATOR === "CONTAINS") {
				if($xqr->NOT) {
					if($xqr->LEFT[1] == $xqr->SELECT) {
						$x = explode(".", $xqr->LEFT[1]);
						$query .= "[not(".$x[0]."[contains(text(), '".$xqr->RIGHT."')])]";
					} else {
						$query .= "[not(".$x[0]."[contains(".$x[1].", '".$xqr->RIGHT."')])]";//TODO: @
					}
				} else {
					if($xqr->LEFT[1] == $xqr->SELECT) {
						$x = explode(".", $xqr->LEFT[1]);
						$query .= "[".$x[0]."[contains(text(), '".$xqr->RIGHT."')]]";
					} else {
						$x = explode(".", $xqr->LEFT[1]);
						$query .= "[".$x[0]."[contains(@".$x[1].", '".$xqr->RIGHT."')]]";
					}
				}
			} else {
			//OPERATOR
				if($xqr->NOT) {
					$x = explode(".", $xqr->LEFT[1]);
					$query .= "[not(".$x[0]."[@".$x[1].$xqr->OPERATOR.$xqr->RIGHT."])]";
				} else {
					$x = explode(".", $xqr->LEFT[1]);
					$query .= "[".$x[0]."[@".$x[1].$xqr->OPERATOR.$xqr->RIGHT."]]";
				}
			}
		}
		//.ATTRIBUTE
		elseif($xqr->LEFT[0] === "ATTRIBUTE") {
			//CONTAINS
			if($xqr->OPERATOR === "CONTAINS") {
				if($xqr->NOT) {
					$query .= "[not(contains(@".substr($xqr->LEFT[1], 1).", '".$xqr->RIGHT."'"."))]";
				} else {
					$query .= "[contains(@".substr($xqr->LEFT[1], 1).", '".$xqr->RIGHT."'".")]";
				}
			} else {
			//OPERATOR
				if($xqr->NOT) {
					$query .= "[not(@".substr($xqr->LEFT[1], 1).$xqr->OPERATOR.$xqr->RIGHT.")]";
				} else {
					$query .= "[@".substr($xqr->LEFT[1], 1).$xqr->OPERATOR.$xqr->RIGHT."]";
				}
			}
		}
		#------ </WHERE> ------------------------------------
		print $query."\n";
		return $query;
    }


    function printResult($xqr, $stderr, $query) {
    	if(isset($xqr->output)) {
	    	if(($output = @fopen($xqr->output, 'w')) == FALSE) {
	    		fwrite($stderr, "Error: Nepodarilo se otevrit vysputni soubor\n");
				exit(1);
	    	}
	    } else
    		$output = STDOUT;
		if(($xml = @simplexml_load_file($xqr->input)) == FALSE) {
			fwrite($stderr, "Error: Spatny vstupni soubor\n");
			exit(1);
		}
		if($xqr->n != TRUE)
			fwrite($output, '<?xml version="1.0" encoding="utf-8"?>');

		if(isset($xqr->root))
			fwrite($output, "<".$xqr->root.">");

		$result = $xml->xpath($query);

		$i = 1; //TODO:musi byt od 0
		foreach ($result as $line) {
			fwrite($output, $line->asXML());
			if(isset($xqr->LIMIT) and $xqr->LIMIT == $i)
				break; 
			$i++;
		}

		if(isset($xqr->root))
			fwrite($output, "</".$xqr->root.">");
		fwrite($output, "\n");
    }


    function printHelp() {
        echo("--help                 - Show help\n" .
			"--input=filename.ext   - Input file with xml\n" .
			"--output=filename.ext  - Output file with xml\n" .
			"--query='query'        - Query under xml - can not be used with -qf attribute\n" .
			"--qf=filename.ext      - Filename with query under xml\n" .
			"-n                     - Xml will be generated without XML header\n" .
			"-r=element             - Name of root element\n");
    }

    $stderr = fopen('php://stderr', 'a');

    $xqr = new Query;

    getAction($xqr, $argc, $argv, $stderr);

    $query = formQuery($xqr, $stderr);
    printResult($xqr, $stderr, $query);
    

?>