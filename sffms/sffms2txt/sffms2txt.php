<?php 
/* sffms2txt is a LaTeX to plain text conversion utility.
   This script takes an uploaded file and returns a text file.  
   It requires an html form to upload the file and an .htaccess file 
   to serve the results as the correct file type.                       */
$newname="output";
$oldnames=explode(".", $_FILES['userfile']['name']);
if ($oldnames[0] && $oldnames[0] != "") $newname = $oldnames[0];
$italicstyle = trim($_POST["italicstyle"]);
$thoughtstyle = trim($_POST["thoughtstyle"]);
$boldstyle = trim($_POST["boldstyle"]);
$smallcapstyle = trim($_POST["smallcapstyle"]);
$mtime = array_sum(explode(" ",microtime()));
$longname=$newname . $mtime;
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=".$newname.".txt"); ?>
<?php

$italicchar = "*";
if ($italicstyle == "underline") {$italicchar = "_";}
if ($italicstyle == "doubleunderline") {$italicchar = "__";}
if ($italicstyle == "asterisk") {$italicchar = "*";}
if ($italicstyle == "doubleasterisk") {$italicchar = "**";}
if ($italicstyle == "none") {$italicchar = "";}
$thoughtchar = $italicchar;
if ($thoughtstyle == "error") {$thoughtchar = "[[thought]]";}
$boldchar = $italicchar;
if ($boldstyle == "error") {$boldchar = "[[bold]]";}
$smallcapchar = $italicchar;
if ($smallcapstyle == "error") {$smallcapchar = "[[smallcap]]";}

function cleanline($line) {
   $newline = $line;
   /* clean out slashes - need to handle escaped characters */
     /* this line just matches the slashes - modify */
   $newline = str_replace("\\", "[tEx]", $newline);
   //eat space at start of line
   $newline = preg_replace("/^( *)(.*)/", "\${2}", $newline);
   //handle LaTeX comments and \% here
   $newline = str_replace("[tEx]%", "[tEx:percent]", $newline);
   $newline = str_replace("~", " ", $newline);
   $newline = preg_replace("/(.*)\%(.*)/", "\${1}", $newline);
   $newline = str_replace("[tEx:percent]", "%", $newline);
   // escape sequences: \{, \}, \\, literal openbrace, closebrace, or backslash
   $newline = str_replace("[tEx]{", "[tEx:obr]", $newline);
   $newline = str_replace("[tEx]}", "[tEx:cbr]", $newline);
   $newline = str_replace("[tEx]#", "#", $newline);
   $newline = preg_replace("/\[tEx\]\[tEx\]([ ]+)/", "\n ", $newline);
   $newline = str_replace("[tEx][tEx]", "\n ", $newline);
   //handle some accents in a bad way
   $newline = str_replace( "[tEx]v{", "[ ^", $newline );
   $newline = str_replace( "[tEx]'{", "[ '", $newline );
   //handle known LaTeX font commands
   $newline = str_replace( "[tEx]emph{", "[iTaLiC]", $newline );
   $newline = str_replace( "{[tEx]em ", "[iTaLiC]", $newline );
   $newline = str_replace( "[tEx]thought{", "[tHoUgHt]", $newline );
   $newline = str_replace( "[tEx]textit{", "[iTaLiC]", $newline );
   $newline = str_replace( "{[tEx]it ", "[iTaLiC]", $newline );
   $newline = str_replace( "[tEx]textsl{", "[iTaLiC]", $newline );
   $newline = str_replace( "{[tEx]sl ", "[iTaLiC]", $newline );
   $newline = str_replace( "{[tEx]slshape ", "[iTaLiC]", $newline );
   // for double underline, use \uldb, for actual smallcap use \scaps
   $newline = str_replace( "[tEx]textsc{", "[sMaLlCaP]", $newline );
   $newline = str_replace( "{[tEx]sc ", "[sMaLlCaP]", $newline );
   $newline = str_replace( "{[tEx]scshape ", "[sMaLlCaP]", $newline );
   $newline = str_replace( "[tEx]textbf{", "[bOlD]", $newline );
   $newline = str_replace( "{[tEx]bf ", "[bOlD]", $newline );
   $newline = str_replace( "{[tEx]bfseries ", "[bOlD]", $newline );
   //handle some introduced commands
   $newline = str_replace( "[tEx]dots[tEx]", ". . .", $newline );   
   $newline = str_replace( "[tEx]dots", ". . .", $newline );   
   return $newline;
}

$theline = "";
$thehead = "";
$breaking = false;
$end = false;
$tempdir="/home/mcd/sitesupport/temp/"; 
  /* initialize variables that may be omitted from the header */
$title = "YOUR TITLE";
$author = "You Yourself";
$address = "";
$wordcount = "??";


move_uploaded_file($_FILES['userfile']['tmp_name'], $tempdir . $longname);

$handle = fopen($tempdir . $longname, "r");
if (!$handle) {
        echo "[ERROR--unable to open file]";
    } else {

/* convert slashes in the header, just for practice */
while (!ereg("\\begin\{document\}", $theline)) {
    $theline = fgets($handle, 4096);
    $theline = cleanline($theline);
    $thehead .= str_replace( "\r\n", "[HeadNewLine]", $theline );
    /* save newlines from the header for the address */
    $thehead .= str_replace( "\n ", "[HeadNewLine]", $theline );
    $thehead .= str_replace( "\n", "[HeadNewLine]", $theline );
    }
  /* get the sffms options, though we're not using them yet;
     will need to split the string on commas */
preg_match("/(\[tEx\]documentclass\[)(.*?)(\])/", $thehead, $matches);
$options=$matches[2];
  /* get the title, author, wc, etc. */
preg_match("/(\[tEx\]title\{)(.*?)(\})/", $thehead, $matches);
$title=$matches[2];
$title = strtoupper($title);
preg_match("/(\[tEx\]author\{)(.*?)(\})/", $thehead, $matches);
$author=$matches[2];
$authorname=$author; // in case no real name is given
preg_match("/(\[tEx\]authorname\{)(.*?)(\})/", $thehead, $matches);
if ($matches[2]) {$authorname=$matches[2];}
preg_match("/(\[tEx\]address\{)(.*?)(\})/", $thehead, $matches);
$address=$matches[2];
$address=str_replace( "[HeadNewLine]", "\n", $address );
//$address=str_replace( "[tEx][tEx]", "\n", $address );
preg_match("/(\[tEx\]wordcount\{)(.*?)(\})/", $thehead, $matches);
$wordcount=$matches[2];

?>
<?php echo $authorname; ?>

<?php echo $address; ?>


<?php echo $wordcount; ?> words




<?php echo $title; ?> 

by 

<?php echo $author; ?>

<?php

  /* take in text by lines (switch to pars later) */
while (!ereg("\\end\{document\}", $theline)&&!$end) {
    $theline = fgets($handle, 4096);
    $theline = cleanline($theline);
    if ($theline=="\n" || ereg("^ *$", $theline)) {
	// paragraph break
	if (!$breaking) {
	   $breaking = true;
	   $thebreak = "\n\n";}
	} else {   // declare breaking over and handle line
	$breaking = false;
	// replacing newline and various spaces w/ space
	$theline = str_replace( "\n", " ", $theline );
	$theline = str_replace( "\t", " ", $theline );
	$theline = str_replace( "[tEx] ", " ", $theline );
	$theline = str_replace( "~", " ", $theline );
	//	$theline = str_replace( "~", "\~", $theline );
        $theline = str_replace( "--", "-", $theline );
        $theline = str_replace( "{``}", "\"", $theline );
        $theline = str_replace( "{''}", "\"", $theline );
        $theline = str_replace( "``", "\"", $theline );
        $theline = str_replace( "''", "\"", $theline );
	if (ereg("\[tEx\]scenebreak", $theline)) {
            $theline = str_replace( "[tEx]scenebreak", "\n\n                                  #", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]newscene", $theline)) {
            $theline = str_replace( "[tEx]newscene", "\n\n                                  #", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]chapter\{", $theline)) {
	    $theline = str_replace( "[tEx]chapter{", "\n\n\n         [iTaLiC]", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]chapter\{", $theline)) {
	    $theline = str_replace( "[tEx]chapter{", "\n\n\n         [iTaLiC]", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]chapter\*\{", $theline)) {
	    $theline = str_replace( "[tEx]chapter*{", "\n\n\n         [iTaLiC]", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]part\{", $theline)) {
	    $theline = str_replace( "[tEx]part{", "\n\n\n\n          [iTaLiC]", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]end\{document\}", $theline)) {
	    $theline = "";  //may lose something, but it's unlikely
	    $thebreak = "\n";  //end last par
	    $end = true;
	    }
	/* if no matches for [tEx], output in brackets */
        $theline = preg_replace("/\[tEx\]([A-Za-z0-9]*)\{([A-Za-z0-9]*)\}/", "[[$1 $2]]", $theline);
        $theline = preg_replace("/\[tEx\]([A-Za-z0-9]*)/", "[[\${1}]]", $theline);
	$theline = str_replace( "[tEx]", "[[??]]", $theline );
	$theline = str_replace( "[iTaLiC]", $italicchar, $theline );
	$theline = str_replace( "[tHoUgHt]", $thoughtchar, $theline );
	$theline = str_replace( "[bOlD]", $boldchar, $theline );
	$theline = str_replace( "[sMaLlCaP]", $smallcapchar, $theline );
	$theline = str_replace( "}", $italicchar, $theline );
	echo $thebreak . $theline;
	$thebreak = "";
	}
   }

 }
fclose($handle);
unlink($tempdir . $longname);
?>

                             # # # # #


