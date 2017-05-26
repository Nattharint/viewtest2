<?php
################################################################################
##              -= YOU MAY NOT REMOVE OR CHANGE THIS NOTICE =-                 #
## --------------------------------------------------------------------------- #
##  ApPHP TreeMenu version 2.0.2 (07.08.2010)                                  #
##  Developed by:  ApPHP <info@apphp.com>                                      #
##  License:       GNU GPL v.2                                                 #
##  Site:          http://www.apphp.com/php-tree-menu/                         #
##  Copyright:     ApPHP TreeMenu (c) 2010. All rights reserved.               #
##                                                                             #
##  Additional modules (embedded):                                             #
##  -- jQuery v1.4.2 (JavaScript Library)                                      #
##                                                                             #
################################################################################
/**
 *	class TreeMenu
    represents a tree menu
    #001 [07.08.2010] last date modified: 
    #002 [29.06.2011] added fix for expanded nodes!!!
    #003 [28.09.2011] added fix for IE wrong SEO links
 *
 */
 class TreeMenu
 {
    // PUBLIC
    // -------
    // constructor
    // AddNode
    // AddNodeWithInnerHTML
    // AddNodeAction
    // ShowTree
    // ShowNodes
    // ShowContent
    // BuildFromFolder
    // BuildNodeFromFolder
    // SetHttpVars
    // UseDefaultFolderIcons
    // UseDefaultFileIcons
    // ShowNumSubNodes

    // SetPostBackMethod
    // SetId
    // GetId
    // SetCaption
    // GetPath
    // SetStyle
    // GetStyle
    // GetSecondaryPath
    // SetSecondaryPath
    // GetNumChildren
    // Debug
    // Version

    // PRIVATE
    // --------
    // ShowNode
    // LoadFiles
    // ShowDebugInformation

    // PUBLIC STATIC
    // --------
    // SimplifyPath

    // PRIVATE STATIC
    // --------
    // GetFormattedMicrotime
    // Simplify
    // DefaultEmptyMessage


    //--- PRIVATE DATA MEMBERS --------------------------------------------------
    private $nodes;
    private $numChildren;
    private $caption;
    private $id;
    private $isDebug;
    private $postBackMethod;
    private $style;
    private $path;
    private $width;
    private $height;
    private $httpVars;
    private $useDefaultFolderIcons;
    private $useDefaultFileIcons;
    private $nodesWithInnerHTML;
    private $showNumSubNodes=false;
    private $secondaryPath;


    //--- CONSTANTS -------------------------------------------------------------
    const version="2.0.2";

    //--- PRIVATE STATIC DATA MEMBERS -------------------------------------------
    private static $bad_chars="><|?*:,\"";

   /**
    *	Creates a tree menu
    *
    */
    public function __construct()
    {
    	$this->nodes=array();
    	$this->numChildren=0;
    	$this->caption="";
    	$this->id=1;
    	$this->isDebug=false;
    	$this->postBackMethod="ajax";
    	$this->style="default";
    	if(defined("TREEMENU_DIR")) $this->path = TREEMENU_DIR;
        else $this->path = "";
        $this->secondaryPath=$this->path;
    	$this->width="auto";
    	$this->height="auto";
    	$this->httpVars=array();
    	$this->nodesWithInnerHTML=array();
        $this->direction = "ltr";
    }

    public function SetDirection($dir = "rtl")
    {
         $this->direction = (strtolower($dir) == "rtl") ? "rtl" : "ltr";
    }

  /**
	*	Adds a new child node to this menu (calculates new node's id and calls function AddNodeAction)
	      @param $caption - node's caption
	      @param $file - file associated with this node
	      @param $icon - icon associated with this node
	      @param $isFolder - is true when this node corresponds to a folder
	*
	*/
    public function AddNode($caption,$file="",$icon="undefined",$isFolder=false)
    {
         $id=$this->GetId()."_".++$this->numChildren;
         return $this->AddNodeAction($caption,$id,$file,$icon,$isFolder);
    }

   /**
	*   Adds a node to list of nodes with inner HTML content
	     @param $node
	*
	*/
    public function AddNodeWithInnerHTML($node)
    {
    	if($node->HasInnerHTML())
       	   $this->nodesWithInnerHTML[count($this->nodesWithInnerHTML)]=$node;
    }

  /**
	*	Adds a new child node to this menu
	      @param $caption - node's caption
	      @param $id - node's id
	      @param $file - file associated with this node
	      @param $icon - icon associated with this node
	      @param $isFolder - is true when this node corresponds to a folder
	*
	*/
    public function AddNodeAction($caption,$id,$file,$icon,$isFolder)
    {
       if(preg_match("/^[0-9|_]+$/",$id)==0)
          return;
       if($pos=strrpos($file,"?"))
          $filename=substr($file,0,$pos);
       else $filename=$file;
       //if(strpbrk($filename,self::$bad_chars) || !file_exists($filename))
       if(strpbrk($filename,self::$bad_chars))
          $file="";
       if(strpbrk($icon,self::$bad_chars))
          $icon="";
       $this->nodes[$id]=new Node($caption,$id,$file,$this,$icon,$isFolder);
       return $this->nodes[$id];
    }

  /**
	*	Shows tree structure on the screen
	    (is used when this is an independent menu)
	*
	*/
    public function ShowTree()
    {
       if($this->numChildren==0)
        {
            self::DefaultEmptyMessage();
            return;
        }
       if($this->isDebug)
          $startTime = $this->GetFormattedMicrotime();
       $this->LoadFiles();

       if($this->postBackMethod!="ajax")
       {
          $params="";
          if($this->postBackMethod=="post")
          {
             foreach($this->httpVars as $key)
                if(isset($_GET[$key])&&self::CheckInput($_GET[$key]))
                   $params .= "&".$key."=".$_GET[$key];
             if($params!="")
                $params[0]="?";
          }
          echo "\n<form name='frmnodes".$this->GetId()."' id='frmnodes".$this->GetId()."' action='".$_SERVER["SCRIPT_NAME"].$params."' method='".$this->postBackMethod."'>";
       }
       echo "<div class='nodes'>";
       if($this->postBackMethod!="ajax")
       {
          if($this->postBackMethod=="get")
             foreach($this->httpVars as $key)
                if(isset($_GET[$key])&&self::CheckInput($_GET[$key]))
                   echo"<input type='hidden' id='".$key."' name='".$key."' value='".$_GET[$key]."' />";
       }
       else
       {
       	  echo "<input type='hidden' id='path' value='".$this->GetPath()."' />";
       	  echo "<input type='hidden' id='style".$this->GetId()."' value='".$this->GetStyle()."' />";
       	  echo "<input type='hidden' id='folderIcons".$this->GetId()."' value='".$this->useDefaultFolderIcons."' />";
       	  echo "<input type='hidden' id='fileIcons".$this->GetId()."' value='".$this->useDefaultFileIcons."' />";
       	  echo "<input type='hidden' id='showNumFiles".$this->GetId()."' value='".$this->showNumSubNodes."' />";
       }
       
       if(isset($_REQUEST["nodeid".$this->GetId()])){
         $selectedNodeId = $_REQUEST["nodeid".$this->GetId()];
       }else{
         $selectedNodeId = "";
       }
       
       $paneled = ($this->style == "paneled");
       echo "<input type='hidden' id='nodeid".$this->GetId()."' value='".$selectedNodeId."' />";
       if(!$paneled){
         echo "<ul class='tmTree'>";
       }else{
         echo "<table cellpadding='0' cellspacing='0' style='width:100%;'>";
       }
       
       for($i=1;$i<=$this->numChildren;$i++)
       {
         //zzz111
         if($paneled) echo "<tr class='tmRow'><td><ul class='tmTree'>";
         $this->ShowNode($this->id."_".$i,$i==$this->numChildren,true,$paneled);
         if($paneled) echo "</ul></tr></td>";
       }
       if(!$paneled) echo "</ul>";
       else echo "</table>";

       echo "</div>";
       if($this->postBackMethod!="ajax")
          echo "</form>";

       echo "\n<!-- END This script was generated by treemenu.class.php v.".self::version."(http://www.apphp.com/php-tree-menu/index.php) END -->\n";
       if($this->isDebug)
       		$this->ShowDebugInformation($startTime);

    }

  /**
    *   Shows tree structure on the screen
        (is used when this menu is a part of some bigger menu)
    *
    */
    public function ShowNodes()
    {
       echo "\n<ul class='tmSubTree'>";
       for($i=1;$i<=$this->numChildren;$i++)
       {
          $this->ShowNode($this->id."_".$i,$i==$this->numChildren,false);
       }
       echo "\n</ul>\n";
   	}

  /**
	*	Shows the loaded content
	*
	*/
    public function ShowContent()
    {
       echo "\n<div class='tmContainer' id='container".$this->GetId()."'>";
       if(isset($_REQUEST["nodeid".$this->GetId()]))
       {
          $selectedNodeId = $_REQUEST["nodeid".$this->GetId()];
          if(isset($this->nodes[$selectedNodeId]))
             $selectedNode = $this->nodes[$selectedNodeId];
          else $selectedNode = null;
       }
       else $selectedNode = null;

       foreach($this->nodesWithInnerHTML as $nodeWithInnerHTML)
       {
           if($selectedNode!=null&&$nodeWithInnerHTML==$selectedNode)
              echo "\n<div id='code".$nodeWithInnerHTML->GetId()."'>".$nodeWithInnerHTML->GetInnerHTML()."</div>";
           else// if($this->postBackMethod=="ajax")
              echo "\n<div id='code".$nodeWithInnerHTML->GetId()."' style='display:none'>".$nodeWithInnerHTML->GetInnerHTML()."</div>";
       }
       echo "<div id='innercontainer".$this->GetId()."'>";

       if($this->postBackMethod!="ajax" && $selectedNode !=null)
          $selectedNode->ShowContent();
       echo "</div>";
       echo "\n</div>";

    }

  /**
	*	Shows a node
	      @param $id - node's id
	      @param $last - whether the node is the last one on this level
	      @param $isIndependent - is true when this is an independent menu
	      @param $isStandAlone - is true when this is a stand-alone menu
	*
	*/
    private function ShowNode($id,$last,$isIndependent=true,$isStandAlone=false)
    {

        $node=$this->nodes[$id];
        if($this->useDefaultFolderIcons && $node->IsFolder() && ($node->GetIcon()==""||$node->GetIcon()=="undefined"))
           $node->ChooseIcon();
        if(!$node->IsFolder() && $node->GetIcon()=="" && $this->useDefaultFileIcons)
           $node->ChooseIcon();
        // defining if this node is selected
        if(isset($_REQUEST["nodeid".$this->GetId()]) && $_REQUEST["nodeid".$this->GetId()]==$node->GetId())
           $class="tmSelected";
        else $class="tmRegular";
        if(!$isStandAlone)
        {
        if ($last)
        	echo "<li class='tmLast tm_bg_position_right'>";
        else if($node->GetId()==$this->id."_1" && $isIndependent)
        	echo "<li class='tmFirst tm_bg_position_right'>";
        else
        	echo "<li class='tmSingle tm_bg_position_right'>";
        	}
       $pathToFile=substr($_SERVER["SCRIPT_NAME"],0,strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
        // if this node has subnodes
        if($node->getNumChildren()>0 || $node->Getfolder()!="")
        {
        	// if this node is expanded
        	if((isset($_REQUEST["node".$node->GetId()]) && $_REQUEST["node".$node->GetId()]=='e') || $class=="tmSelected")
        	{
                $liclass="tmExpanded";
                if($this->style=="paneled"&&$node->GetLevel()==1) $symbol="minus-first";
                else $symbol="minus";
                $value="e";
            }
            // if this node is collapsed
            else
            {
                $liclass="tmCollapsed";               
                if($this->style=="paneled"&&$node->GetLevel()==1)
                    $symbol="plus-first";
                else $symbol="plus";
                $value="c";
            }
        	echo "<div class='".$liclass."' id='".$node->GetId()."'>";
        	// displaying plus or minus sign to the left of the text
        	echo "<img id='img".$node->GetId()."' class='tmImage' alt='' ";
        	echo " src='".$this->secondaryPath."styles/".$this->style."/images/".$symbol.".gif'";
        	if($node->GetFolder()!="")
           		echo " onclick=\"__tmSwitch('".$node->GetId()."','".self::SimplifyPath($pathToFile.$node->GetFolder())."')\" />";
           	else echo " onclick=\"__tmSwitch('".$node->GetId()."','')\" />";
        	// hidden field which stores this node's state ('c' for collapsed and 'e' for expanded)
        	echo "<input type='hidden' id='node".$node->GetId()."' name='node".$node->GetId()."' value='".$value."' />";

        }
        // if node has no subnodes
        else
            echo "<div class='tmNoChildren' id='".$node->GetId()."'>";


        echo "<span id='span".$node->GetId()."' class='tmNode' ";
        if(!$isIndependent)
           echo "onmouseover='__tmHighlight(this)' onmouseout='__tmLowlight(this)'";

        if($node->HasInnerHTML())
        {
	        echo "onclick=\"__tmPostBackAjax('".$node->GetId()."','".$node->GetFile()."','code')\">";
        }
        else if($node->IsOpenNewWindow())
        {
            echo " onclick='window.location.href=\"".APPHP_BASE.$node->GetFile()."\"'>";
        }
        else if($this->postBackMethod=="ajax")
        {
           if($node->GetFolder()=="")
           {
              if($node->IsPicture())
                 echo "onclick=\"__tmPostBackAjax('".$node->GetId()."','".$node->GetFile()."','file')\">";
              else echo "onclick=\"__tmPostBackAjax('".$node->GetId()."','".self::SimplifyPath($pathToFile.$node->GetFile())."','file')\">";
           }
           else echo "onclick=\"__tmPostBackAjax('".$node->GetId()."','".self::SimplifyPath($pathToFile.$node->GetFolder())."','filesystem')\">";
        }
        else echo "onclick=\"__tmPostBack('".$node->GetId()."')\">";

        //displaying this node's icon
        if($node->GetIcon()!="undefined" && file_exists($node->GetIcon()))
        {
            echo "<img id='pic".$node->GetId()."' class='tmIcon' src='".$node->GetIcon()."' />";
        }
        // displaying this node's text
        echo "<span id='text".$node->GetId()."' class='".$class."'>".$node->GetCaption();
        if($this->showNumSubNodes && $node->GetNumChildren()>0)
          echo"&nbsp;(".$node->GetNumChildren().")";
        echo "</span></span>";

        //displaying this node's subnodes if there are any
        if($node->getNumChildren()>0)
        {
        	echo "<br />";
        	if($last)
        	   echo "<ul class='tmSubTree-last'>";
         	else echo "<ul class='tmSubTree'>";
        	for($i=1;$i<=$node->getNumChildren();$i++)
               $this->ShowNode($node->GetId()."_".$i,$i==$node->getNumChildren());
            echo "</ul>";
        }
           echo "</div>";
           if(!$isStandAlone)
        echo "</li>";
    }

  /**
	*	Displays default message when treemenu is empty
	*
	*/
    private static function DefaultEmptyMessage()
    {
    	echo "No nodes defined";
    }

  /**
	*	Builds a tree menu from contents of a folder
	       @param $folder - relative path to the folder
	       @param $isIndependent - is true when this is an independent menu
	*
	*/
    public function BuildFromFolder($folder,$isIndependent=true)
	{
		$this->BuildNodeFromFolder($this,$folder,$isIndependent);
    }

   /**
	*	Builds a tree menu from contents of a folder
	      @param $root - the node
	      @param $folder - relative path to the folder
	      @param $isIndependent - is true when this is an independent menu
	*
	*/
    public function BuildNodeFromFolder($root,$folder,$isIndependent)
	{
        $folder=self::SimplifyPath($folder);
        $dirs=array();
        $files=array();
        if (is_dir($folder)) {
           if ($dir = opendir($folder)) {
           	   while (false !== ($file = readdir($dir))){
           	   	  if($file != "." && $file != ".." && is_dir($folder."/".$file))
           	   	     $dirs[count($dirs)]=$file;
           	   	  else if($file != "." && $file != "..")
           	   	     $files[count($files)]=$file;
               }
               closedir($dir);
    	    }
        }
        natcasesort($dirs);
        natcasesort($files);

        foreach($dirs as $dir)
        {
            $node=$root->AddNode($dir,"","",true);
            if($this->postBackMethod!="ajax")
               $this->BuildNodeFromFolder($node,$folder."/".$dir,$isIndependent);
            else $node->SetFolder($folder."/".$dir);
        }
        foreach($files as $file)
        {
        	$node=$root->AddNode($file,$folder."/".$file,"",false);
            if($node->IsPicture()&&!$isIndependent)
            	$node->SetFile($this->secondaryPath."inc/".$node->GetFile());
        }
    }

   /**
	*	Loads CSS and JS files
	*
	*/
	private function LoadFiles()
	{
	  if(!file_exists($this->path."styles/".$this->style."/style.css")){          
         $this->style="default";
      }
      if($this->direction == "rtl") $style_dir = "_rtl";
      else $style_dir = "";
      
      echo "<link href='".$this->path."styles/".$this->style."/style".$style_dir.".css' rel='stylesheet' type='text/css' />\n";
       
      echo "<link href='".$this->path."styles/common.css' rel='stylesheet' type='text/css' />\n";
      echo "<script type='text/javascript' defer='defer' src='".$this->path."js/script.js'></script>\n";
      echo "<link href='".$this->path."styles/commonIE.css' rel='stylesheet' type='text/css' />";
      echo "<link href='".$this->path."styles/".$this->style."/styleIE.css' rel='stylesheet' type='text/css' />\n";
       ///echo "<script src='".$this->path."js/jquery-1.6.3.min.js' type='text/javascript'></script>";

    }

  /**
    *	Sets variables that used to get access to the page (like: my_page.php?act=34&id=56 etc.)
           @param $vars
    *
	*/
    public function SetHttpVars($vars)
    {
    	$this->httpVars = $vars;
    }

  /**
	*	Sets whether or not default icons are used for folder nodes
			@param $use - false|true
	*
	*/
	public function UseDefaultFolderIcons($use = false)
	{
		if($use === true || strtolower($use) == "true") $this->useDefaultFolderIcons = true;
	}

  /**
	*	Sets whether or not default icons are used for file nodes
			@param $use - false|true
	*
	*/
	public function UseDefaultFileIcons($use = false)
	{
		if($use === true || strtolower($use) == "true") $this->useDefaultFileIcons = true;
	}

  /**
	*	Sets if number of sub-nodes is displayed in brackets to the left of every node
			@param $show - false|true
	*
	*/
	public function ShowNumSubNodes($show = false)
	{
		if($show === true || strtolower($show) == "true") $this->showNumSubNodes = true;
	}

   /**
	*	Sets postback method
			@param $postback_method
	*
	*/
	public function SetPostBackMethod($postback_method = "post")
	{
		if(strtolower($postback_method) == "get") $this->postBackMethod = "get";
		else if(strtolower($postback_method) == "ajax") $this->postBackMethod = "ajax";
		else $this->postBackMethod = "post";
	}

   /*
    * Sets menu's unique identifier
          @param $id
    *
    */
    public function SetId($id)
    {
        $this->id=$id;
    }

  /**
	*	Returns menu's unique identifier
	*
	*/
    public function GetId()
    {
       return $this->id;
    }

  /**
	*	Sets menu's caption
	     @param $caption - new caption
	*
	*/
    public function SetCaption($caption)
    {
    	$this->caption=$caption;
    }

  /**
	*	Returns menu's path
	*
	*/
    public function GetPath()
    {
    	return $this->path;
    }

  /**
	*	Sets style
	     @param $style - new style
	*
	*/
    public function SetStyle($style)
    {
    	if(file_exists($this->path."styles/".$style."/style.css"))
    	   $this->style=$style;
    }

  /**
	*	Returns menu's style
	*
	*/
    public function GetStyle()
    {
    	return $this->style;
    }

  /**
	*	Returns menu's style
	*
	*/
    public function GetSecondaryPath()
    {
    	return $this->secondaryPath;
    }

  /**
	*	Sets menu's secondary path
	*
	*/
    public function SetSecondaryPath($secondaryPath)
    {
    	$this->secondaryPath=$secondaryPath;
    }

  /**
	*	Returns number of nodes on the first level
	*
	*/
	public function GetNumChildren()
    {
       return $this->numChildren;
    }

  /**
    *	Sets debug mode
    		@param $mode
	*
	*/
	public function Debug($mode = false)
	{
		if($mode === true || strtolower($mode) == "true") $this->isDebug = true;
	}


  /**
	*	Returns TreeMenu's version
	*
	*/
    public function Version()
    {
       return self::version;
    }

  /**
    *	Gets formatted microtime
	*
	*/
    private static function GetFormattedMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

  /**
	*	Shows debug information
	      @param $startTime - time when the script started
	*
	*/
    private function ShowDebugInformation($startTime)
    {
       $endTime = $this->GetFormattedMicrotime();
	   echo "<div style='margin: 10px auto; text-align:left; color:#000096;'>";

	   echo "Debug Info: (Total running time: ".round((float)$endTime - (float)$startTime, 6)." sec.) <br />========<br />";

	   echo "<br />GET: <br />--------<br />";
	   echo "<pre>";
	   print_r($_GET);
	   echo "</pre><br />";
	   echo "POST: <br />--------<br />";
	   echo "<pre>";
	   print_r($_POST);
	   echo "</pre><br />";

	   echo "NODES: <br />--------<br />";
	   echo "<pre>";
	   echo "<table style='color:#000096;'>";
       foreach($this->nodes as $node)
       {
          echo "<tr>";
          echo "<td>".$node->GetId()."</td>";
          echo "<td>".$node->GetCaption()."</td>";
          echo "</tr>";
       }
       echo "</table>";
       echo "</pre>";
       echo "</div>";
    }

  /**
	*	Simplifies dirs like /./dir ordir/../../dir/ and returns a simplified pathname
	    	@param $path - path to be simplified
	*
	*/
    public static function SimplifyPath($path)
    {
       if(strlen($path)>0 && $path[0]=="/")
          return "/".self::SimplifyPath(substr($path,1));
       if($path=="")
          return $path;
       $dirs = explode('/',$path);
       for($i=0; $i<count($dirs);$i++)
       {
          if($dirs[$i]=="." || $dirs[$i]=="")
          {
             array_splice($dirs,$i,1);
             $i--;
          }
          if($dirs[$i]=="..")
          {
            $c = count($dirs);
            $dirs=self::Simplify($dirs, $i);
            $i-= $c-count($dirs);
          }
       }
       return implode('/',$dirs);
    }

  /**
	*	Auxiliary function
	       @param $dirs
	       @param $idx
	*
	*/
    private static function Simplify($dirs, $idx)
    {
       if($idx==0) return $dirs;
       if($dirs[$idx-1]=="..") self::Simplify($dirs, $idx-1);
       else  array_splice($dirs,$idx-1,2);
       return $dirs;
    }

  /**
	*	Checks input string for suspicious code
	       @param $input - input string
		   @param $level - security level
	*
	*/
    private static function CheckInput($input, $level = "medium")
    {
    	if($input == "") return true;
    	$error = 0;
    	$bad_string = array("%20union%20", " /*", "*/ union /*", "+union+", "load_file", "outfile", "document.cookie", "onmouse", "<script", "<iframe", "<applet", "<meta", "<style", "<form", "<img", "<body", "<link", "_GLOBALS", "_REQUEST", "_GET", "_POST", "include_path", "prefix", "http://", "https://", "ftp://", "smb://" );
    	foreach($bad_string as $string_value)
    	{
    		if(strstr($input, $string_value)) $error = 1;
    	}
    	if((preg_match("/<[^>]*script*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*object*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*iframe*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*applet*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*meta*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*style*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*form*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*img*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*onmouseover*\"?[^>]*>/i", $input)) ||
        (preg_match("/<[^>]*body*\"?[^>]*>/i", $input)) ||
        (preg_match("/\([^>]*\"?[^)]*\)/i", $input)) ||
        (preg_match("/ftp:\/\//i", $input)) ||
        (preg_match("/https:\/\//i", $input)) ||
        (preg_match("/http:\/\//i", $input)) )
        {
        	$error = 1;
        }
        $ss = $_SERVER['HTTP_USER_AGENT'];
        if ((preg_match("/libwww/i",$ss)) ||
        (preg_match("/^lwp/i",$ss))  ||
        (preg_match("/^Jigsaw/i",$ss)) ||
        (preg_match("/^Wget/i",$ss)) ||
        (preg_match("/^Indy\ Library/i",$ss)) )
    	{
        	$error = 1;
    	}
    	if($error){
    		return false;
    		}
    	return true;
	}
}
 /**
 *	class Node
      represents a separate node
      last date modified: 07.08.2010
 *
 */
class Node
{
    // PUBLIC
    // -------
    // constructor
    // AddNode
    // BuildFromFolder
    // ShowContent
    // GetCaption
    // GetLevel
    // GetFile
    // SetFile
    // GetIcon
    // SetIcon
    // GetId
    // GetNumChildren
    // IsPicture
    // IsHTML
    // IsPHP
    // IsText
    // ChooseIcon
    // SetFolder
    // GetFolder
    // SetInnerHTML
    // HasInnerHTML
    // GetInnerHTML
    // OpenNewWindow
    // IsOpenNewWindow

    //--- PRIVATE DATA MEMBERS --------------------------------------------------
    private $caption;
    private $selected=false;
    private $id;
    private $parent;
    private $numChildren=0;
    private $file;
    private $icon;
    private $level;
    private $innerHTML;
    private $openNewWindow;
    private $folder;
    public $isFolder=false;

    //--- PRIVATE STATIC DATA MEMBERS -------------------------------------------
    private static $bad_chars="><|?*:,\"";
    private $saveNodeState;

    /**
	 *	Creates a new node
	        @param $caption - node's caption
            @param $id - node's id
            @param $file - name of the file associated with this node
            @param $parent - menu which contains this node
            @param $icon - icon associated with this node
            @param $isFolder - is true when this node corresponds to a folder
	*
	*/
    function __construct($caption,$id,$file,$parent,$icon,$isFolder=false)
	{
        $this->saveNodeState = true;

    	if(preg_match("/^[0-9|_]/",$id)==0)
        	$id=0;
        $this->id=$id;
        $this->caption=$caption;
        $this->parent=$parent;

        $this->level=substr_count($id,"_");
        if($pos=strrpos($file,"?"))
           $filename=substr($file,0,$pos);
        else $filename=$file;
        if(strpbrk($filename,self::$bad_chars)){
            $this->file="";
        }else{
            // [#002] fix for expanded nodes
            $param_sign = (preg_match("/\?/", $file)) ? "&amp;" : "?";
            $file_postfix = "";
            $temp_id = explode("_", $id);
            if(count($temp_id) >= 2){
               $file_postfix .= (($this->saveNodeState) ? $param_sign."node".$temp_id[0]."_".$temp_id[1]."=e" : "");
               if(isset($temp_id[2])) $file_postfix .= (($this->saveNodeState) ? "&amp;node".$temp_id[0]."_".$temp_id[1]."_".$temp_id[2]."=e" : "");
               if(isset($temp_id[3])) $file_postfix .= (($this->saveNodeState) ? "&amp;node".$temp_id[0]."_".$temp_id[1]."_".$temp_id[2]."_".$temp_id[3]."=e" : "");
            }            
            $this->file=$file.$file_postfix;            
        }
        $this->isFolder=$isFolder;
        if(strpbrk($icon,self::$bad_chars))
           $this->icon="";
        else $this->icon=$icon;
	}

   /**
	*	Adds a new child node to this node
	      @param $caption - node's caption
	      @param $file - file associated with this node
	      @param $icon - icon associated with this node
	      @param $isFolder - is true when this node corresponds to a folder
	*
	*/
    public function AddNode($caption,$file="",$icon="undefined",$isFolder=false)
    {
        if(!is_a($this->parent,"TreeMenu"))
        {
           echo "<span style='color:#FF0000'>Error: node ".$this->caption." has no valid parent object</span>";
           return;
        }
        $id=$this->GetId()."_".++$this->numChildren;
        return $this->parent->AddNodeAction($caption,$id,$file,$icon,$isFolder);
    }


  /**
	*	Builds a tree menu from contents of a folder
	      @param $folder - relative path to the folder
	*
	*/
    public function BuildFromFolder($folder)
    {
    	if(!is_a($this->parent,"TreeMenu"))
        {
           echo "<span style='color:#FF0000'>Error: node ".$this->caption." has no valid parent object</span>";
           return;
        }
        $this->parent->BuildNodeFromFolder($this,$folder,true);
    }

  /**
	*	Shows contents of the file which is associated with this node
	*
	*/
    public function ShowContent()
    {
         echo"<br />\n\n";
         if($pos=strrpos($this->file,"?"))
         {
            $get_parameters=substr($this->file,$pos+1);
            $this->file=substr($this->file,0,$pos);
            $get_parameters_array = explode("&", $get_parameters);
            foreach($get_parameters_array as $get_parameter)
            {
            	$key_value=explode("=",$get_parameter);
            	$_GET[$key_value[0]]=$key_value[1];
            }
         }
         if($this->file=="" && !$this->IsFolder())
         {
         	echo "No content associated with this node";
         	return;
         }
         if($this->IsPicture())
         {
       	    echo "<img id='tmTree_image' src='".$this->file."' />";
    	 }
    	 else if($this->IsPHP())
    	 {
    	    require_once($this->file);
    	 }
    	 else if($this->IsHTML())
    	 {
            $str = file_get_contents($this->file);
    	    if(preg_match("/<head.*?>(.+?)<\/head>/si",$str,$head)!=0)
    	    {
               if(preg_match_all("/<script.*?>(.*?)<\/script>/si",$head[1],$scripts)!=0)
    	          foreach($scripts[0] as $script)
    	             echo $script;
         	   if(preg_match_all("/<style.*?>(.*?)<\/style>/si",$head[1],$styles)!=0)
    	          foreach($styles[0] as $style)
    	             echo $style;
    	    }
    	    if(preg_match("/<body.*?>(.+?)<\/body>/si",$str,$body)!=0)
        	   print_r($body[1]);
        	else print_r($str);
    	 }
    	 else if($this->IsText())
    	    echo htmlspecialchars(file_get_contents($this->file), ENT_QUOTES);

    }

  /**
    *	Returns the node's caption
    *
    */
    public function GetCaption()
    {
    	 return $this->caption;
    }

  /**
    *	Returns the node's level
	*
	*/
    public function GetLevel()
    {
    	return $this->level;
    }

  /**
	*	Returns the node's file
	*
	*/
    public function GetFile()
    {
    	return $this->file;
    }

  /**
	*	Sets the node's file
	*
	*/
    public function SetFile($file)
    {
        $this->file=$file;
    }

  /**
	*	Returns the node's icon
	*
	*/
    public function GetIcon()
    {
       return $this->icon;
    }

  /**
    *	Sets node's icon
	       @param $icon
	*
	*/
    public function SetIcon($icon)
    {
		$this->icon=$icon;
    }

  /**
	*	Returns the node's id
	*
	*/
    public function GetId()
    {
		return $this->id;
    }

  /**
	*	Returns amount of subnodes associated with this node
	*
	*/
    public function GetNumChildren()
    {
        return $this->numChildren;
    }

  /**
    *	Checks if file associated with this node is a graphic file
	*
	*/
 	public function IsPicture()
  	{
        $extension=strtolower(substr(strrchr($this->file,"."),1));
        if($extension=="jpg"||$extension=="gif"||$extension=="bmp"||$extension=="tif"||$extension=="png"||$extension=="jpeg")
           return true;
        else return false;
    }

  /**
	*	Checks if file associated with this node is a hypertext file
	*
	*/
    public function IsHTML()
    {
        if($pos=strrpos($this->file,"?"))
           $filename=substr($this->file,0,$pos);
        else $filename=$this->file;
        $extension=strtolower(substr(strrchr($filename,"."),1));
        if($extension=="htm"||$extension=="xml"||$extension=="html")
           return true;
        else return false;
    }

  /**
	*	Checks if file associated with this node is a PHP file
	*
	*/
    public function IsPHP()
    {
        if($pos=strrpos($this->file,"?"))
           $filename=substr($this->file,0,$pos);
        else $filename=$this->file;
        $extension=strtolower(substr(strrchr($filename,"."),1));
        if($extension=="php")
           return true;
        else return false;
    }

  /**
	*	Checks if file associated with this node is a text file
	*
	*/
    public function IsText()
    {
        $extension=strtolower(substr(strrchr($this->file,"."),1));
        if($extension=="txt")
           return true;
        else return false;
    }

  /**
    * Chooses icon according to file's type if it wasn't defined by user
    *
    */
    public function ChooseIcon()
    {
    	$this->icon=$this->parent->GetSecondaryPath()."styles/".$this->parent->GetStyle()."/images/";
        if($this->IsFolder())
        {
            if(isset($_REQUEST["node".$this->GetId()]) && $_REQUEST["node".$this->GetId()]=='e')
               $this->icon=$this->icon."folderopened";
            else $this->icon=$this->icon."folder";
        }
        else if($this->IsPicture())
           $this->icon=$this->icon."picture";
        else if($this->IsText())
           $this->icon=$this->icon."text";
        else if($this->IsHTML())
           $this->icon=$this->icon."html";
        else $this->icon=$this->icon."file";
        if(file_exists($this->icon.".jpg"))
           $this->icon=$this->icon.".jpg";
        else $this->icon=$this->icon.".gif";
    }

  /**
	*	Sets folder which this node corresponds to
			@param $folder
	*
	*/
    public function SetFolder($folder)
    {
    	$this->folder = $folder;
    }

  /**
	*	Returns folder which this node corresponds to
	*
	*/
    public function GetFolder()
    {
        return $this->folder;
    }

  /**
	*	Sets this node's inner HTML content
	       @param $innerHTML
	*
	*/
    public function SetInnerHTML($innerHTML)
    {
    	$this->innerHTML=$innerHTML;
    	if($innerHTML!="")
    	   $this->parent->AddNodeWithInnerHTML($this);

    }

  /**
	*	Returns true if this node has inner HTML content (false otherwise)
	*
	*/
    public function HasInnerHTML()
    {
       return $this->innerHTML!="";
    }

  /**
	*	Gets this node's inner HTML content
	*
	*/
    public function GetInnerHTML()
    {
       return $this->innerHTML;
    }

  /**
	*	Sets if this node must be opened in a new window
	       @param $open
	*
	*/
    public function OpenNewWindow($open = false)
	{
		if($open === true || strtolower($open) == "true")
		   $this->openNewWindow = true;
	}

  /**
	*	Returns if this node must be opened in a new window
	*
	*/
    public function IsOpenNewWindow()
    {
        return $this->openNewWindow;
    }

   /*
    *  Returns true if this node corresponds to a folder or has sub-nodes
    *
    */
    public function IsFolder()
    {
       return $this->numChildren > 0 || $this->isFolder;
    }
}
?>