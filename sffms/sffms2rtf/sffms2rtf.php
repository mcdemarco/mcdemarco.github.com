<?php 
/* sffms2rtf is a LaTeX to RTF conversion utility.
   This script takes an uploaded file and returns an RTF file.  
   It requires an html form to upload the file and an .htaccess file 
   to serve the results as the correct file type.                       */
$newname="output";
$oldnames=explode(".", $_FILES['userfile']['name']);
if ($oldnames[0] && $oldnames[0] != "") $newname = $oldnames[0];
$mtime = array_sum(explode(" ",microtime()));
$longname=$newname . $mtime;
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=".$newname.".rtf"); ?>
{\rtf1\ansi\deff1\ansicpg10000
{\fonttbl\f0\fmodern\fcharset77 Courier;\f1\froman\fcharset77 Times New Roman;}
{\colortbl;\red255\green255\blue255;\red255\green0\blue0;}
\margl1440\margr1440\vieww12240\viewh15840\viewkind1\viewscale100
{\header\pard\qr\plain\f0\chpgn\par}
<?php

function cleanline($line) {
   $newline = $line;
   /* clean out slashes - need to handle escaped characters */
     /* this line just matches the slashes - modify */
     //$newline = preg_replace("/\\\\([A-Za-z0-9 ]*)/", "[tEx]\${1}", $newline);
   $newline = str_replace("\\", "[tEx]", $newline);
   //eat space at start of line
   $newline = preg_replace("/^( *)(.*)/", "\${2}", $newline);
   //handle LaTeX comments and \% here
   $newline = str_replace("[tEx]%", "[tEx:percent]", $newline);
   //   $newline = str_replace("[tEx]~", "[tEx:tilde]", $newline);
   $newline = str_replace("~", " ", $newline);
   $newline = preg_replace("/(.*)\%(.*)/", "\${1}", $newline);
   $newline = str_replace("[tEx:percent]", "%", $newline);
   //   $newline = str_replace("[tEx:tilde]", "~", $newline);
   // escape sequences: \{, \}, \\, literal openbrace, closebrace, or backslash
   $newline = str_replace("[tEx]{", "[tEx:obr]", $newline);
   $newline = str_replace("[tEx]}", "[tEx:cbr]", $newline);
   $newline = str_replace("[tEx]#", "#", $newline);
   $newline = preg_replace("/\[tEx\]\[tEx\]([ ]+)/", "\line ", $newline);
   $newline = str_replace("[tEx][tEx]", "\line ", $newline);
   //handle some accents in a bad way
   $newline = str_replace( "[tEx]v{", "{\cf2 ^", $newline );
   $newline = str_replace( "[tEx]'{", "{\cf2 '", $newline );
   //handle known LaTeX font commands
   $newline = str_replace( "[tEx]emph{", "{\ul ", $newline );
   $newline = str_replace( "[tEx]em ", "\ul ", $newline );
   $newline = str_replace( "[tEx]thought{", "{\ul ", $newline );
   $newline = str_replace( "[tEx]textit{", "{\ul ", $newline );
   $newline = str_replace( "[tEx]it ", "\ul ", $newline );
   $newline = str_replace( "[tEx]textsl{", "{\ul ", $newline );
   $newline = str_replace( "[tEx]sl ", "\ul ", $newline );
   $newline = str_replace( "[tEx]slshape ", "\ul ", $newline );
   // for double underline, use \uldb, for actual sc use \scaps
   $newline = str_replace( "[tEx]textsc{", "{\uldb ", $newline );
   $newline = str_replace( "[tEx]sc ", "\uldb ", $newline );
   $newline = str_replace( "[tEx]scshape ", "\uldb ", $newline );
   $newline = str_replace( "[tEx]textbf{", "{\b ", $newline );
   $newline = str_replace( "[tEx]bf ", "\b ", $newline );
   $newline = str_replace( "[tEx]bfseries ", "\b ", $newline );
   //handle some introduced commands
   $newline = str_replace( "[tEx]dots[tEx]", ". . .", $newline );   
   $newline = str_replace( "[tEx]dots", ". . .", $newline );   
   //$newline = str_replace( "[tEx]chapter{", "\pard\sl510 \qc\f0\fs24 {\b ", $newline );
   //$newline = str_replace( "[tEx]chapter*{", "\pard\sl510 \qc\f0\fs24 {\b ", $newline );
   //$newline = str_replace( "[tEx]part{", "\pard\sl510 \qc\f0\fs24 {\b ", $newline );
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
$wordcount = "{\cf2 ??}";


move_uploaded_file($_FILES['userfile']['tmp_name'], $tempdir . $longname);

$handle = fopen($tempdir . $longname, "r");
if (!$handle) {
        echo "\pard{\cf2 ERROR--unable to open file}\par";
    } else {

  /* convert slashes in the header, just for practice */
while (!ereg("\\begin\{document\}", $theline)) {
    $theline = fgets($handle, 4096);
    $theline = cleanline($theline);
    $thehead .= str_replace( "\r\n", " ", $theline );
    $thehead .= str_replace( "\n", " ", $theline );
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
//$address=str_replace( "[tEx][tEx] ", "\par ", $address ); //remove extra space
//$address=str_replace( "[tEx][tEx]", "\par ", $address );
preg_match("/(\[tEx\]wordcount\{)(.*?)(\})/", $thehead, $matches);
$wordcount=$matches[2];

?>
\deftab360
\pard\tx7500\pardeftab360\ql\qnatural
\f0\fs24 \cf0 
<?php echo $authorname; ?>\tab <?php echo $wordcount; ?> words\par
<?php echo $address; ?>\par
\
\pard\pardeftab720\ql\qnatural\sb4000\par
\pard\sl510 \qc\f0\fs24 
<?php echo $title; ?> \
\ 
by <?php echo $author; ?>\par
\pard\sl510\f0\fs24
\cf0 \
<?php

  /* take in text by lines (switch to pars later) */
while (!ereg("\\end\{document\}", $theline)&&!$end) {
    $theline = fgets($handle, 4096);
    $theline = cleanline($theline);
    if ($theline=="\n" || ereg("^ *$", $theline)) {
	// paragraph break
	if (!$breaking) {
	   $breaking = true;
	   $thebreak = "\par\n\pard\sl510\\f0\\fs24\cf0      ";}
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
            $theline = str_replace( "[tEx]scenebreak", "\par\pard\sl510\qc\cf0 #", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]newscene", $theline)) {
            $theline = str_replace( "[tEx]newscene", "\par\pard\sl510\qc\cf0 #", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]chapter\{", $theline)) {
	    $theline = str_replace( "[tEx]chapter{", "\par\pard\sl510\qc\cf0 \par\pard\sl510\qc\cf0 {\b ", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]chapter\{", $theline)) {
	    $theline = str_replace( "[tEx]chapter{", "\par\pard\sl510\qc\cf0 {\b ", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]chapter\*\{", $theline)) {
	    $theline = str_replace( "[tEx]chapter*{", "\par\pard\sl510\qc\cf0 {\b ", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]part\{", $theline)) {
	    $theline = str_replace( "[tEx]part{", "\par\pard\sl510\qc\cf0 {\b ", $theline );
	    $thebreak = "";
	    }
	if (ereg("\[tEx\]end\{document\}", $theline)) {
	    $theline = "";  //may lose something, but it's unlikely
	    $thebreak = "\par\n";  //end last par
	    $end = true;
	    }
	/* if no matches for [tEx], output in red */
        $theline = preg_replace("/\[tEx\]([A-Za-z0-9]*)\{/", "{\cf2 ??\${1} ", $theline);
	$theline = str_replace( "[tEx]", "{\cf2 ??}", $theline );
	echo $thebreak . $theline;
	$thebreak = "";
	}
   }

 }
fclose($handle);
unlink($tempdir . $longname);
?>
\pard\sl510 \qc\f0\fs24 # # # # #\par
}
