<?php
/**
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Myron Turner <turnermm02@shaw.ca>
 * 
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define('REPLACE_DIR', DOKU_INC . 'data/meta/macros/');
define('MACROS_FILE', REPLACE_DIR . 'macros.ser');


/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_textinsert extends DokuWiki_Syntax_Plugin {
   var $macros;
   var $translations;
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
           'date'   => '2011-05-13',
            'name'   => 'word replacement Plugin',
            'desc'   => 'replace Macros with words',
            'url'    => 'http://www.dokuwik.org/plugin:wordreplace',
        );
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * Where to sort in?
     */ 
    function getSort(){
        return 155;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('#@[\w\-\._]+@#',$mode,'plugin_textinsert');
		$this->Lexer->addSpecialPattern('#@[\w\-\._]+~.*?~@#',$mode,'plugin_textinsert');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
		
        $html=false;
		$translation = false;
        $match = substr($match,2,-2); 
        $match = trim($match);   
        if(strpos($match, 'HTML')) $html=true;
        if(strpos($match, 'LANG_') !== false) {
		    $translation=true;
			list($prefix,$trans) = explode('_',$match,2);
			}

		if($translation) {
			global $ID;
			list($ns,$rest) = explode(':',$ID,2);			 
				if(@file_exists($filename = DOKU_PLUGIN . "textinsert/lang/$ns/lang.php")) {
					include $filename;
					$this->translations = $lang;
           }
		}
		
        $this->macros = $this->get_macros();
		
		if(preg_match('/(.*?)~(.*)~$/',$match,$subtitution)) {
		   	$match=$subtitution[1];
		   	$substitutions=explode(',',$subtitution[2]);			
		}

        if(!array_key_exists($match, $this->macros)) {
           msg("$match macro was not found in the macros database", -1);  
           $match = "";              
        }
        else {
			if($translation && isset($this->translations[$trans])){
				$match = $this->translations[$trans];
			}
			else {
				$match =$this->macros[$match];
			}
		   }
		   
		
		for($i=0; $i<count($substitutions); $i++) {
	            $search = '%' . ($i+1);
	            $match = str_replace ($search ,  $substitutions[$i], $match);
        }	
        
        $match = $this->get_inserts($match,$translation); 
		 
        if($html) {
          $match =  str_replace('&lt;','<',$match);
          $match =  str_replace('&gt;','>',$match);
        }
					
        return array($state,$match);
    }

    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            list($state, $word) = $data;
            $renderer->doc .= $word;
            return true;
        }
        return false;
    }
    
    function get_macros() {
       if(file_exists(MACROS_FILE)) {
          return unserialize(file_get_contents(MACROS_FILE));
       }
       return array();
    }

   function get_inserts($match,$translation) {
      $inserts = array();    
	  
	  // replace embedded macros
      if(preg_match_all('/#@(.*?)@#/',$match,$inserts)) {        
		$keys = $inserts[1]; 
		$pats = $inserts[0];        

		for($i=0; $i<count($keys); $i++) {
		   $insert = $this->macros[$keys[$i]];
			if($translation && strpos($keys[$i], 'LANG_') !== false)  {
					list($prefix,$trans) = explode('_',$keys[$i],2);
					$insert = $this->translations[$trans];
			}
			$match = str_replace($pats[$i],$insert,$match);
          }
		  
      }  // end replace embedded macros
    
      $entities =  getEntities();
      $e_keys = array_keys($entities);
      $e_values =  array_values($entities);

      $match = str_replace($e_keys,$e_values,$match);  
      return  $match;
   }
  
  function write_debug($what) {
	  return;
	  $what=print_r($what,true);
	   $handle=fopen("textinsert.txt",'a');
	   fwrite($handle,"$what\n");
	   fclose($handle);
  }
}


