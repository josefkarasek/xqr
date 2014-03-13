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
        var $LEFT;
        var $OPERATOR;
        var $RIGHT; 
    }

    function getAction($xqr, $argc, $argv, $stderr) {
        $arguments = $argv;

        foreach ($arguments as $value) {
            unset($arguments[0]);

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
                    $xqr->root = TRUE;
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
                $xqr->qf = substr($value, 5);
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
                $xqr->input = substr($value, 8);
                unset($arguments[array_search($value, $arguments)]);
            }
            //--output
            if(preg_match("/--output=.+/", $value)) {
                $xqr->output = substr($value, 9);
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
            	// print_r ($words);
            	parseQuery($xqr, $argc, $words, $stderr);
            }
        }
// var_dump($arguments);
// var_dump($xqr);
//         foreach ($arguments as $value) {
//             fwrite($stderr, "Error: neplatne argumenty\n");
//             fwrite($stderr, "Run $argv[0] --help instead\n");
//             exit (1);
//         }
    }

    function parseQuery($xqr, $argc, $arguments, $stderr) {

    	foreach($arguments as $value) {
    		if(preg_match("/SELECT/", $value)) {
	            if(isset($arguments[array_search($value, $arguments) +1])) {
	                if( preg_match('/\A(?!XML)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->SELECT = $arguments[array_search($value, $arguments) +1];
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
	                    $xqr->LIMIT = intval($arguments[array_search($value, $arguments) +1]);
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
	                    $xqr->FROM[1] = $arguments[array_search($value, $arguments) +1];
	                    $xqr->FROM[0] = "ROOT";
	                }
	                //element.atribut
	                elseif(preg_match('/\A(?!XML)[a-z][\w0-9-]*\.[a-z][\w0-9-]*$/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = $arguments[array_search($value, $arguments) +1];
	                    $xqr->FROM[0] = "ELEMENT.ATTRIBUTE";
	                }
	                //atribut
	                elseif(preg_match('/\A\.(?!XML)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = $arguments[array_search($value, $arguments) +1];
	                    $xqr->FROM[0] = "ATTRIBUTE";
	                }
	                //element
	                elseif(preg_match('/\A(?!XML|\.)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +1])) {
	                    $xqr->FROM[1] = $arguments[array_search($value, $arguments) +1];
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
	                    $xqr->LEFT = $arguments[array_search($value, $arguments) +$offset];
	                }
	                //atribut
	                elseif(preg_match('/\A\.(?!XML)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +$offset])) {
	                    $xqr->LEFT = $arguments[array_search($value, $arguments) +$offset];
	                }
	                //element
	                elseif(preg_match('/\A(?!XML|\.)[a-z][\w0-9-]*/i', $arguments[array_search($value, $arguments) +$offset])) {
	                    $xqr->LEFT = $arguments[array_search($value, $arguments) +$offset];
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
	                	if($xqr->OPERATOR === "CONTAINS") {
							fwrite($stderr, "Error: WHERE: ciselny literal po CONTAINS\n");
	                    	exit (1);
	                	} else
	                    	$xqr->RIGHT = $arguments[array_search($value, $arguments) +$offset+2];
	                }
	                //string
	                elseif(preg_match('/^[a-zA-Z]+[[:alpha:]\d-_]*/', $arguments[array_search($value, $arguments) +$offset+2])) {
	                    $xqr->RIGHT = $arguments[array_search($value, $arguments) +$offset+2];
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

    function printHelp() {
        printf("Obsah napovedy\n");
    }

    $stderr = fopen('php://stderr', 'a');

    $xqr = new Query;

    getAction($xqr, $argc, $argv, $stderr);
    // var_dump($xqr);
    

?>