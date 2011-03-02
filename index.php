<?PHP

include("s3-php5-curl/S3.php");
S3::$useSSL = false;

$settings = parse_ini_file("aws_keys.ini");

$s3 = new S3($settings['public_key'], $settings['secret_key']);


if(!isset($_GET['bucket'])){
	include("frontpage.html");
	die();
}

$bucket = $_GET['bucket'];
trim($bucket);

if(!$bucket){
        include("frontpage.html");
        die();
}

function error_handler($errno, $errstr, $errfile, $errline, $errcontext){

header('HTTP/1.0 400 Bad request');

$match = preg_match("#^(\S*): \[(\w*)\] (.*)$#", $errstr, $matches);

if($match){
	$error = $matches[3];
} else {
	$error = $errstr;
}

echo <<<EOW
<html>
<head><title>Error</title></head>
<body>
	<h1>$error</h1>
</body>
</html>
EOW;
die();
}

set_error_handler("error_handler");

$description = "A podcast made out of the s3 bucket $bucket";
$name   = "bucketcast";
$email  = "bucketcast@istic.net";
$author = "$email ($name)";
$title = "Podcast of $bucket";

$contents = $s3->getBucket($bucket);

header("Content-Type: text/xml");

$image = "http://altru.istic.net/static/bucket.png";

if(isset($contents["icon.png"])){
	$image = "http://$bucket.s3.amazonaws.com/icon.png";
}



echo <<<EOW
<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"> 
    <channel> 
        <!-- begin RSS 2.0 tags --> 
        <title>$title</title> 
        <link>http://altru.istic.net/bucketcast/$bucket</link> 
        <language>en-us</language> 
 
        <description>$description</description> 
        <ttl>720</ttl> 
        <itunes:author>$author</itunes:author> 
        
         <itunes:subtitle></itunes:subtitle> 
    <itunes:summary>$description</itunes:summary> 
    <itunes:owner> 
           <itunes:name>$name</itunes:name> 
           <itunes:email>$email</itunes:email> 
    </itunes:owner> 
    
    <itunes:image href="$image"/> 
    
 
        <image> 
            <url>$image</url> 
            <title>$title</title> 
 
            <link>http://altru.istic.net/bucketcast/$bucket</link> 
            <width>100</width> 
            <height>100</height> 
        </image>
EOW;

foreach($contents as $filename => $item){

	$ext = strtolower(substr($filename, -4, 4));

	switch ($ext){
		case ".mp3":
			$mime = "audio/mpeg";
			break;

		case ".ogg":
			$mime = "audio/ogg";
			break;

		case ".mp4":
			$mime = "audio/mp4a-latm";
			break;

		case ".m4b":
			$mime = "audio/mp4a-latm";
			break;

		case ".m4p":
			$mime = "audio/mp4a-latm";
			break;

		case ".aac":
			$mime = "audio/x-aac";
			break;
		
		default:
			continue 2;

	}

	$date = date("r", $item['time']);
	$url = "http://$bucket.s3.amazonaws.com/".urlencode($filename);

        $title = htmlentities($item['name']);

echo <<<EOW
<item> 
      <title>$title</title> 
      <guid isPermaLink="false">{$item['hash']}</guid> 
      <pubDate>$date</pubDate> 
      <author>$author</author> 
      <link>$url</link> 
      <enclosure url="$url" length="{$item['size']}" type="$mime" />  
</item>

EOW;
}

echo "</channel>
</rss>";
