<?php

function WriteHTMLForm()
{
  echo  <<<_PHP
  <form name="github_form" method="post" onsubmit="return validateForm()" action="
_PHP;
echo $_SERVER['PHP_SELF'];
echo <<<_PHP
">
    <p>
    Name: <input type="text" name="name" value="ceneblock">
    <input type="submit" name="submit" value="Submit">
  </p>
  </form>
 
_PHP;
}
function WriteHTMLHeader()
{
  echo <<<_PHP
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
<title>GitHub Follower Grabber</title>
</head>
<body>
<script type="text/javascript">
function validateForm() {
  var name = document.forms["github_form"]["name"].value;
  if (name == "") {
    alert("Name must be filled out");
    return false;
  }
}
</script>


_PHP;
}

function WriteHTMLFooter()
{
  echo <<<_PHP
  <p>
    <a href="http://validator.w3.org/check?uri=referer"><img
      src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01 Strict" height="31" width="88"></a>
  </p>
</body>
</html>
_PHP;
}

/**
 * @breif calls the github API.
 * @param method what type of method do we want to do?
 * @param user who are we interested in?
 * @param url what website do we want to peek into? (the example is meant for
 * github, but ideally it should work for any website with a similar API)
 * @param call what call number on we on? The requirements are to break on 3
 * deep.
 */
function CallAPI($method, $user = "ceneblock", $url = "https://api.github.com/users/", $call = 0)
{
  if($call < 3)
  {
    $curl = curl_init();

    $final_url = $url . $user;
    switch($method)
    {
      case "repos":
      case "starred":
      case "followers":
        $final_url = $final_url . "/" . $method;
        break;
      case "user":
      default:
        break;
    }
    curl_setopt($curl, CURLOPT_URL, $final_url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'php/7.3.6');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    $return_code = curl_getinfo($curl,  CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if($return_code == 200)
    {
      $rv = array();
      for($x = 0; $x < 5; $x++)
      {
        if(!empty(json_decode($result, true)[$x]["login"]))
        {
          if($call == 2)
          {
            //save these values
            $rv = array_merge($rv, array(json_decode($result, true)[$x]["login"]));
          }
          else
          {
            //go ahead and call it again
            $rv = array_merge($rv, array( json_decode($result, true)[$x]["login"] => CallAPI($method, json_decode($result, true)[$x]["login"], $url, $call+1)));
          }
        }
      }
      return $rv;
    }
    else if($return_code == 404)
    {
      print <<<_PHP
<p> Error: Unknown Username
<button onclick="goBack()">Go Back</button>
</p> 
<script type="text/javascript">
function goBack() {
  window.history.back();
}
</script>         
_PHP;
    }
    else
    {
     $messages = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
				);

			print "<p>" . "Unimplemented Return Code: " . $return_code . " " . $messages[$return_code] . "</p>";
		  return null;	
		} 
  }
}

/**
 * @brief Writes a string (json in our case) to an output file
 * @param data the data to write to a file
 * @param output_file where to write the data to.
 */
function WriteString($data, $output_file = "out.json")
{
  $myfile = fopen($output_file, "w") or die("Unable to open file!");
  fwrite($myfile, $data);
  fclose($myfile);
}

function main()
{

  if (!empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "GET")
  {
    WriteHTMLHeader();
    WriteHTMLForm();
    WriteHTMLFooter();
  }
  else if (!empty($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["name"])) {
            print <<<_PHP
<p> Error: Please Enter A Username
<button onclick="goBack()">Go Back</button>
</p>
<script type="text/javascript">
function goBack() {
  window.history.back();
}
</script>
_PHP;

        $nameErr = "Name is required";
    } else {
      $json = json_encode(array($_POST["name"] => CallAPI("followers", $_POST["name"])), JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
      if($json != null)
      {
        $filename = uniqid($_POST["name"] . "_").'.json';
        WriteString($json, $filename);
        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename="' . $_POST["name"] . '.json"');
        readfile($filename);

        //comment out this line to keep the data. Useful for debugging
        unlink($filename);
      }
    }
  }
}

main();
?>
