<?php

class AnopeXMLRPC
{

    private $Host;

    function __construct($Host)
    {
        $this->Host = $Host;
    }

    function DoCommand($Service, $User, $Command)
    {
        return $this->RunXMLRPC("command", array($Service, $User, $Command));
    }

    function RunXMLRPC($name, $params)
    {
        print("XMLRPC called. Host:" . $this->Host);
        $xmlquery = xmlrpc_encode_request($name, $params);
        $context = stream_context_create(array("http" => array(
            "method" => "POST",
            "header" => "Content-Type: text/xml",
            "content" => $xmlquery)));
        $inbuf = file_get_contents($this->Host, false, $context);
        print("Inbuf: " . $inbuf);

//    if(isset($response[0]));
//      return $response[0];
        return NULL;
    }

}

$request = false;
$request_type = false;
$str = file_get_contents('php://input');
//file_put_contents("mytestdata","Raw:".$str."\n", FILE_APPEND);
if (!empty($str)) {
    $request = json_decode($str);
    $request_type = $request->webhookEvent;
}
$content =
    "Got a hit! Type " . $request_type . ", Project " . $request->issue->fields->project->key . " and summary " .
    $request->issue->fields->summary;
switch ($request_type) {
    case 'jira:issue_created':
        $message =
            "Issue Created: \x02\x0307" . $request->issue->fields->issuetype->name . "\x02\x03 [\x02\x0308" .
            $request->issue->key . "\x03\x02] Priority: " . $request->issue->fields->priority->name . " in \x02" .
            $request->issue->fields->project->name . "\x02(\x02" . $request->issue->fields->project->key .
            "\x02) by \x02\x0314" . $request->issue->fields->reporter->key . ".\x03\x02 \"" .
            $request->issue->fields->summary . "\". (\x02\x0311https://jira.fuelrats.com/projects/" .
            $request->issue->fields->project->key . "/issues/" . $request->issue->key . "/\x03\x02)";
        break;
    case 'jira:issue_updated':
        $message =
            "Issue Updated: \x02\x0307" . $request->issue->fields->issuetype->name . "\x02\x03 \"" .
            $request->issue->fields->summary . "\" [\x02\x0308" . $request->issue->key . "\x03\x02] in \x02" .
            $request->issue->fields->project->name . "\x02(\x02" . $request->issue->fields->project->key .
            "\x02) by \x0314\x02" . $request->user->key . ".\x03\x02 Status: \x02" .
            $request->issue->fields->status->name . "\x02. (\x0311\x02https://jira.fuelrats.com/projects/" .
            $request->issue->fields->project->key . "/issues/" . $request->issue->key . "/\x03\x02)";
        break;
    case 'jira:issue_deleted':
        $message =
            "Issue Deleted: \x02\x0307" . $request->issue->fields->issuetype->name . "\x02\x03 \"" .
            $request->issue->fields->summary . "\" [\x02\x0308" . $request->issue->key . "\x03\x02] in \x02" .
            $request->issue->fields->project->name . "\x02(\x02" . $request->issue->fields->project->key .
            "\x02) by \x0314\x02" . $request->user->key . ".\x03\x02";
        break;
    case
    'user_created':
        $message =
            "User created: \x02\x0307" . $request->user->key . "\x02\x03 (\x02\x0311" .
            $request->user->emailAddress . "\x02\x03)";
        break;
    case 'jira:version_created':
        $message = "Version created: \x02\x0307" . $request->version->name . "\x02\x03 of ";
        break;
    case 'project_created':
        $message =
            "Project created: \x02\x0307" . $request->project->name . "\x02\x03 [\x02x0308" .
            $request->project->key . "\x03\x02] under \x02\x0314" .
            $request->project->projectLead->key . " (" . $request->project->projectLead->displayName . ")";
        break;
    case 'project_deleted':
        $message =
            "Project deleted: \x02\x0307\".$request->project->name.\"\x02\x03 [\x02x0308\".
            $request->project->key.\"\x03\x02] under \x02\x0314\".$request->project->projectLead->key.\" (\".
            $request->project->projectLead->displayName.\")";
        break;
    default:
        $message = "JIRA unhandled event: " . $request_type;
        $temp = file_put_contents("unhandled_events", $str . "\n", FILE_APPEND);
        break;
}

$anopexmlrpc = new AnopeXMLRPC("https://127.0.0.1:6080/xmlrpc");
$anopexmlrpc->DoCommand("botserv", "Absolver", "SAY #rattech \x0315JIRA:\x03 " . $message);
?>