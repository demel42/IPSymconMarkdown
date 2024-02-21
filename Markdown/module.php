<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

require_once __DIR__ . '/../libs/parsedown/Parsedown.php';

class Markdown extends IPSModule
{
    use Markdown\StubsCommonLib;
    use MarkdownLocalLib;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('ipsIP', '');
        $this->RegisterPropertyInteger('ipsPort', 3777);

        $this->RegisterPropertyString('hook', '/hook/' . __CLASS__);
        $this->RegisterPropertyString('hook_user', '');
        $this->RegisterPropertyString('hook_password', '');

        $this->RegisterPropertyString('items', json_encode([]));

        $this->RegisterPropertyBoolean('opt_SafeMode', false);
        $this->RegisterPropertyBoolean('opt_MarkupEscaped', false);
        $this->RegisterPropertyBoolean('opt_BreaksEnabled', false);
        $this->RegisterPropertyBoolean('opt_UrlsLinked', true);
        $this->RegisterPropertyBoolean('opt_Inline', false);
        $this->RegisterPropertyBoolean('opt_HtmlWrapper', true);

        $this->RegisterPropertyBoolean('opt_spellChecker', false);
        $this->RegisterPropertyBoolean('opt_codeSyntaxHighlighting', false);

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $hook = $this->ReadPropertyString('hook');
        if ($hook != '' && $this->HookIsUsed($hook)) {
            $this->SendDebug(__FUNCTION__, '"hook" is already used', 0);
            $r[] = $this->Translate('Webhook is already used');
        }

        $items = json_decode($this->ReadPropertyString('items'), true);
        for ($idx = 1; $idx <= count($items); $idx++) {
            $item = $items[$idx - 1];

            $markdown_varID = $item['markdown_varID'];
            if (IPS_VariableExists($markdown_varID)) {
                $var = IPS_GetVariable($markdown_varID);
                $varType = $var['VariableType'];
                if ($varType != VARIABLETYPE_STRING) {
                    $this->SendDebug(__FUNCTION__, '"markdown_varID" of item ' . $idx . ' must be type string', 0);
                    $r[] = $this->TranslateFormat('Markdown variable of item {$idx} must be type string', ['{$idx}' => $idx]);
                }
            } else {
                $this->SendDebug(__FUNCTION__, '"markdown_varID" of item ' . $idx . ' is missing', 0);
                $r[] = $this->TranslateFormat('Markdown variable of item {$idx} isn\'t specified', ['{$idx}' => $idx]);
            }

            $html_varID = $item['html_varID'];

            if (IPS_VariableExists($html_varID)) {
                $var = IPS_GetVariable($html_varID);
                $varProfile = $var['VariableCustomProfile'];
                if ($varProfile == false) {
                    $varProfile = $var['VariableProfile'];
                }
                if ($varProfile != '~HTMLBox') {
                    $this->SendDebug(__FUNCTION__, '"html_varID" of item ' . $idx . ' must have variable profile "~HTMLBox"', 0);
                    $r[] = $this->TranslateFormat('HTML variable of item {$idx} must have variable profile "~HTMLBox"', ['{$idx}' => $idx]);
                }
            }
        }

        return $r;
    }

    public function MessageSink($timestamp, $senderID, $message, $data)
    {
        parent::MessageSink($timestamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $hook = $this->ReadPropertyString('hook');
            if ($hook != '') {
                $this->RegisterHook($hook);
            }
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();
        $items = json_decode($this->ReadPropertyString('items'), true);
        foreach ($items as $item) {
            $markdown_varID = $item['markdown_varID'];
            if (IPS_VariableExists($markdown_varID)) {
                $this->RegisterReference($markdown_varID);
            }
            $html_varID = $item['html_varID'];
            if (IPS_VariableExists($html_varID)) {
                $this->RegisterReference($html_varID);
            }
        }

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $hook = $this->ReadPropertyString('hook');
            if ($hook != '') {
                $this->RegisterHook($hook);
            }
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Markdown convert to HTML');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'hook',
                    'caption' => 'Name'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'hook_user',
                    'caption' => 'User'
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'hook_password',
                    'caption' => 'Password'
                ],
            ],
            'caption' => 'Access to webhook',
        ];

        $formElements[] = [
            'name'    => 'items',
            'type'    => 'List',
            'add'     => true,
            'delete'  => true,
            'columns' => [
                [
                    'name'               => 'markdown_varID',
                    'add'                => 0,
                    'edit'               => [
                        'type'               => 'SelectVariable',
                        'validVariableTypes' => [VARIABLETYPE_STRING],
                    ],
                    'caption'            => 'Markdown variable',
                    'width'              => '500px',
                ],
                [
                    'name'               => 'html_varID',
                    'add'                => 0,
                    'edit'               => [
                        'type'               => 'SelectVariable',
                        'validVariableTypes' => [VARIABLETYPE_STRING],
                    ],
                    'caption'            => 'HTML variable',
                    'width'              => '500px',
                ],
                [
                    'name'     => 'title',
                    'add'      => '',
                    'edit'     => [
                        'type'               => 'ValidationTextBox',
                    ],
                    'width'    => 'auto',
                    'caption'  => 'Title',
                ],
            ],
            'caption'  => 'Editable items',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_SafeMode',
                    'caption' => 'Parser option "SafeMode"'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_MarkupEscaped',
                    'caption' => 'Parser option "MarkupEscaped"'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_BreaksEnabled',
                    'caption' => 'Parser option "BreaksEnabled"'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_UrlsLinked',
                    'caption' => 'Parser option "UrlsLinked"'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_Inline',
                    'caption' => 'Parser option "Inline"'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_HtmlWrapper',
                    'caption' => 'Add HTML code as wrapper'
                ],
            ],
            'caption' => 'Defaults for the converter options',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_spellChecker',
                    'caption' => 'Editor option "spellChecker"'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'opt_codeSyntaxHighlighting',
                    'caption' => 'Editor option "codeSyntaxHighlighting"'
                ],
            ],
            'caption' => 'Editor configuration',
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $expert_items = [];

        $hook = $this->ReadPropertyString('hook');
        if ($hook != '') {
            $ipsIP = $this->ReadPropertyString('ipsIP');
            $ipsPort = $this->ReadPropertyInteger('ipsPort');
            $url = $this->GetConnectUrl();
            if ($url == false) {
                $url = 'http://' . ($ipsIP != '' ? $ipsIP : gethostbyname(gethostname())) . ':' . $ipsPort . '/';
            }
            $url .= $hook . '/editor';

            $markdown_options = [];
            $items = json_decode($this->ReadPropertyString('items'), true);
            foreach ($items as $item) {
                $markdown_varID = $item['markdown_varID'];
                if (IPS_VariableExists($markdown_varID) == false) {
                    $this->SendDebug(__FUNCTION__, '.... unknown/invalid markdown_varID "' . $markdown_varID . '"', 0);
                    continue;
                }
                $v = '?markdown_varID=' . $markdown_varID;
                $html_varID = $item['html_varID'];
                if ($html_varID) {
                    $v .= '&html_varID=' . $html_varID;
                }

                $c = IPS_GetLocation($markdown_varID);

                $markdown_options[] = [
                    'value'   => $v,
                    'caption' => $c,
                ];
            }

            $expert_items[] = [
                'type'    => 'RowLayout',
                'items'   => [
                    [
                        'type'    => 'Select',
                        'options' => $markdown_options,
                        'name'    => 'ent',
                        'caption' => 'Markdown variable'
                    ],
                    [
                        'type'    => 'Button',
                        'caption' => 'Open editor',
                        'onClick' => 'echo  "' . $url . '$ent";',
                    ],
                ],
            ];
        }

        if ($expert_items != []) {
            $formActions[] = [
                'type'      => 'ExpansionPanel',
                'caption'   => 'Expert area',
                'expanded'  => false,
                'items'     => $expert_items,
            ];
        }

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    protected function ProcessHookData()
    {
        $this->SendDebug(__FUNCTION__, '_SERVER=' . print_r($_SERVER, true), 0);
        $this->SendDebug(__FUNCTION__, '_GET=' . print_r($_GET, true), 0);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            http_response_code(404);
            die('Instance is disabled!');
        }

        $hook_user = $this->ReadPropertyString('hook_user');
        $hook_password = $this->ReadPropertyString('hook_password');
        if ($hook_user != '' || $hook_password != '') {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                $_SERVER['PHP_AUTH_USER'] = '';
            }
            if (!isset($_SERVER['PHP_AUTH_PW'])) {
                $_SERVER['PHP_AUTH_PW'] = '';
            }

            if (($_SERVER['PHP_AUTH_USER'] != $hook_user) || ($_SERVER['PHP_AUTH_PW'] != $hook_password)) {
                header('WWW-Authenticate: Basic Realm="Markdown WebHook"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Authorization required';
                return;
            }
        }

        $root = realpath(__DIR__);
        $uri = $_SERVER['REQUEST_URI'];
        if (substr($uri, -1) == '/') {
            http_response_code(404);
            die('File not found!');
        }
        $hook = $this->ReadPropertyString('hook');
        if ($hook == '') {
            http_response_code(404);
            die('Hook isn\'t configured!');
        }
        if (substr($uri, -1) != '/') {
            $hook .= '/';
        }
        $path = parse_url($uri, PHP_URL_PATH);
        $basename = substr($path, strlen($hook));
        $command = $basename;
        if (substr($command, 0, 1) == '/') {
            $command = substr($command, 1);
        }
        if ($command == '') {
            http_response_code(404);
            die('No command given!');
        }

        $items = json_decode($this->ReadPropertyString('items'), true);

        $ret = '';
        switch ($command) {
            case 'editor':
                $this->SendDebug(__FUNCTION__, 'editor ...', 0);

                $markdown_varID = isset($_GET['markdown_varID']) ? $_GET['markdown_varID'] : 0;
                $html_varID = isset($_GET['html_varID']) ? $_GET['html_varID'] : 0;
                $this->SendDebug(__FUNCTION__, '... markdown_varID=' . $markdown_varID . ', html_varID=' . $html_varID, 0);

                if (IPS_VariableExists($markdown_varID) == false) {
                    $this->SendDebug(__FUNCTION__, '.... unknown/invalid markdown_varID "' . $markdown_varID . '"', 0);
                    http_response_code(404);
                    die('Unknown/invalid markdown variable!');
                }
                for ($i = 0; $i < count($items); $i++) {
                    $item = $items[$i];
                    if ($markdown_varID == $item['markdown_varID']) {
                        break;
                    }
                }
                if ($i == count($items)) {
                    $this->SendDebug(__FUNCTION__, '.... markdown_varID ' . $markdown_varID . ' not found in list', 0);
                    http_response_code(404);
                    die('Markdown variable is not permitted!');
                }
                if ($this->IsValidID($html_varID)) {
                    if (IPS_VariableExists($html_varID) == false) {
                        $this->SendDebug(__FUNCTION__, '.... unknown/invalid html_varID "' . $html_varID . '"', 0);
                        http_response_code(404);
                        die('unknown/invalid HTML variable!');
                    }
                    if ($html_varID != $items[$i]['html_varID']) {
                        $this->SendDebug(__FUNCTION__, '.... html_varID ' . $html_varID . ' matches not markdown_varID', 0);
                        http_response_code(404);
                        die('HTML variable dont\'t macht markdown_varID!');
                    }
                }

                $title = $items[$i]['title'];
                if ($title == '') {
                    $title = 'Variable ' . $markdown_varID . '(' . IPS_GetName($markdown_varID) . ')';
                }
                $header = 'Markdown-Editor - ' . $title;

                $ret = $this->GetEditorPage($header, $markdown_varID, $html_varID);
                break;
            case 'load':
                $this->SendDebug(__FUNCTION__, 'load ...', 0);

                $markdown_varID = isset($_GET['markdown_varID']) ? $_GET['markdown_varID'] : 0;
                $this->SendDebug(__FUNCTION__, '... markdown_varID=' . $markdown_varID, 0);

                if (IPS_VariableExists($markdown_varID) == false) {
                    $this->SendDebug(__FUNCTION__, '.... unknown/invalid markdown_varID "' . $markdown_varID . '"', 0);
                    http_response_code(404);
                    die('Unknown/invalid markdown variable!');
                }
                for ($i = 0; $i < count($items); $i++) {
                    $item = $items[$i];
                    if ($markdown_varID == $item['markdown_varID']) {
                        break;
                    }
                }
                if ($i == count($items)) {
                    $this->SendDebug(__FUNCTION__, '.... markdown_varID ' . $markdown_varID . ' not found in list', 0);
                    http_response_code(404);
                    die('Markdown variable is not permitted!');
                }

                $var = IPS_GetVariable($markdown_varID);
                $timestamp = date('d.m.Y H:i:s', (int) $var['VariableUpdated']);

                $data = [
                    'status'    => 'OK',
                    'text'      => GetValueString($markdown_varID),
                    'timestamp' => $timestamp,
                ];
                $this->SendDebug(__FUNCTION__, '... data=' . print_r($data, true), 0);
                $ret = json_encode($data);
                break;
            case 'save':
                $this->SendDebug(__FUNCTION__, 'save ...', 0);

                $markdown_varID = isset($_GET['markdown_varID']) ? $_GET['markdown_varID'] : 0;
                $html_varID = isset($_GET['html_varID']) ? $_GET['html_varID'] : 0;
                $this->SendDebug(__FUNCTION__, '... markdown_varID=' . $markdown_varID . ', html_varID=' . $html_varID, 0);

                if (IPS_VariableExists($markdown_varID) == false) {
                    $this->SendDebug(__FUNCTION__, '.... unknown/invalid markdown_varID "' . $markdown_varID . '"', 0);
                    http_response_code(404);
                    die('Unknown/invalid markdown variable!');
                }
                for ($i = 0; $i < count($items); $i++) {
                    $item = $items[$i];
                    if ($markdown_varID == $item['markdown_varID']) {
                        break;
                    }
                }
                if ($i == count($items)) {
                    $this->SendDebug(__FUNCTION__, '.... markdown_varID ' . $markdown_varID . ' not found in list', 0);
                    http_response_code(404);
                    die('Markdown variable is not permitted!');
                }
                if ($this->IsValidID($html_varID)) {
                    if (IPS_VariableExists($html_varID) == false) {
                        $this->SendDebug(__FUNCTION__, '.... unknown/invalid html_varID "' . $html_varID . '"', 0);
                        http_response_code(404);
                        die('unknown/invalid HTML variable!');
                    }
                    if ($html_varID != $items[$i]['html_varID']) {
                        $this->SendDebug(__FUNCTION__, '.... html_varID ' . $html_varID . ' matches not markdown_varID', 0);
                        http_response_code(404);
                        die('HTML variable dont\'t macht markdown_varID!');
                    }
                }

                $data = file_get_contents('php://input');
                $this->SendDebug(__FUNCTION__, '... got data="' . $data . '"', 0);

                $jdata = json_decode($data, true);
                $markdown = $jdata['text'];
                $this->SendDebug(__FUNCTION__, '... markdown="' . $markdown . '"', 0);
                SetValueString($markdown_varID, $markdown);

                if ($this->IsValidID($html_varID)) {
                    $html = $this->Convert2HTML($markdown, []);
                    $this->SendDebug(__FUNCTION__, '... html="' . $html . '"', 0);
                    SetValueString($html_varID, $html);
                }

                $data = [
                    'status' => 'OK',
                ];
                $this->SendDebug(__FUNCTION__, '... data=' . print_r($data, true), 0);
                $ret = json_encode($data);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'unknown command "' . $command . '"', 0);
                http_response_code(404);
                die('command not found!');
                break;
        }
        echo $ret . PHP_EOL;
    }

    private function LocalRequestAction($ident, $value)
    {
        $r = true;
        switch ($ident) {
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $this->SendDebug(__FUNCTION__, 'ident=' . $ident . ', value=' . $value, 0);

        $r = false;
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
        if ($r) {
            $this->SetValue($ident, $value);
        }
    }

    public function Convert2HTML(string $markdown, array $opts)
    {
        $this->SendDebug(__FUNCTION__, 'markdown=' . $markdown, 0);
        $this->SendDebug(__FUNCTION__, 'opts=' . print_r($opts, true), 0);

        $Parsedown = new Parsedown();

        $opt_SafeMode = isset($opts['SafeMode']) ? (bool) $opts['SafeMode'] : $this->ReadPropertyBoolean('opt_SafeMode');
        $opt_MarkupEscaped = isset($opts['MarkupEscaped']) ? (bool) $opts['MarkupEscaped'] : $this->ReadPropertyBoolean('opt_MarkupEscaped');
        $opt_BreaksEnabled = isset($opts['BreaksEnabled']) ? (bool) $opts['BreaksEnabled'] : $this->ReadPropertyBoolean('opt_BreaksEnabled');
        $opt_UrlsLinked = isset($opts['UrlsLinked']) ? (bool) $opts['UrlsLinked'] : $this->ReadPropertyBoolean('opt_UrlsLinked');
        $opt_Inline = isset($opts['Inline']) ? (bool) $opts['Inline'] : $this->ReadPropertyBoolean('opt_Inline');
        $opt_HtmlWrapper = isset($opts['HtmlWrapper']) ? (bool) $opts['HtmlWrapper'] : $this->ReadPropertyBoolean('opt_HtmlWrapper');

        $Parsedown->setSafeMode($opt_SafeMode);
        $Parsedown->setMarkupEscaped($opt_MarkupEscaped);
        $Parsedown->setBreaksEnabled($opt_BreaksEnabled);
        $Parsedown->setUrlsLinked($opt_UrlsLinked);

        $html = '';

        if ($opt_HtmlWrapper) {
            $html .= '<html>' . PHP_EOL;
            $html .= '    <style>' . PHP_EOL;
            $html .= '        a:link { color: blue; background-color: transparent; text-decoration: none; }' . PHP_EOL;
            $html .= '        a:visited { color: blue; background-color: transparent; text-decoration: none; }' . PHP_EOL;
            $html .= '        a:hover { color: red; background-color: transparent; text-decoration: underline; }' . PHP_EOL;
            $html .= '        a:active { color: green; background-color: transparent; text-decoration: underline; }' . PHP_EOL;
            $html .= '    </style>' . PHP_EOL;
        }

        if ($opt_Inline) {
            $html .= $Parsedown->line($markdown) . PHP_EOL;
        } else {
            $html .= $Parsedown->text($markdown) . PHP_EOL;
        }

        if ($opt_HtmlWrapper) {
            $html .= '</html>' . PHP_EOL;
        }

        $this->SendDebug(__FUNCTION__, 'html=' . $html, 0);
        return $html;
    }

    private function GetEditorPage($header, $markdown_varID, $html_varID)
    {
        $html = '';

        $opt_codeSyntaxHighlighting = $this->ReadPropertyBoolean('opt_codeSyntaxHighlighting');
        $opt_spellChecker = $this->ReadPropertyBoolean('opt_spellChecker');

        $html .= '<!DOCTYPE html>' . PHP_EOL;
        $html .= '<html>' . PHP_EOL;
        $html .= '    <head>' . PHP_EOL;
        $html .= '        <meta charset="utf-8" />' . PHP_EOL;
        $html .= '        <title>IP-Symcon - Markdown-Editor</title>' . PHP_EOL;

        $html .= '        <link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">' . PHP_EOL;
        $html .= '        <script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>' . PHP_EOL;

        $html .= '        <link rel="stylesheet" href="https://cdn.jsdelivr.net/highlight.js/latest/styles/github.min.css">' . PHP_EOL;
        $html .= '        <script src="https://cdn.jsdelivr.net/highlight.js/latest/highlight.min.js"></script>' . PHP_EOL;

        $html .= '        <style>' . PHP_EOL;
        $html .= '            h3     { font-family: "Open Sans", sans-serif; font-size: 24px; }' . PHP_EOL;
        $html .= '            td     { font-family: "Open Sans", sans-serif; font-size: 16px; }' . PHP_EOL;
        $html .= '            button { font-family: "Open Sans", sans-serif; font-size: 16px; }' . PHP_EOL;
        $html .= '            p      { font-family: "Open Sans", sans-serif; font-size: 12px; }' . PHP_EOL;
        $html .= '        </style>' . PHP_EOL;

        $html .= '        <script>' . PHP_EOL;
        $html .= '            var load_url = "../../hook/Markdown/load?markdown_varID=' . $markdown_varID . '"' . PHP_EOL;
        $html .= '            var save_url = "../../hook/Markdown/save?markdown_varID=' . $markdown_varID . '&html_varID=' . $html_varID . '"' . PHP_EOL;
        $html .= '        </script>' . PHP_EOL;
        $html .= '    </head>' . PHP_EOL;
        $html .= '    <body>' . PHP_EOL;
        $html .= '        <table width=100%>' . PHP_EOL;
        $html .= '            <tr>' . PHP_EOL;
        $html .= '                <td style="text-align: center; vertical-align: top">' . PHP_EOL;
        $html .= '                    <h3>' . $header . '</h3>' . PHP_EOL;
        $html .= '                </td>' . PHP_EOL;
        $html .= '            </tr>' . PHP_EOL;
        $html .= '        </table>' . PHP_EOL;

        $html .= '        <textarea id="text"></textarea>' . PHP_EOL;

        $html .= '        <table width=100%>' . PHP_EOL;
        $html .= '            <tr>' . PHP_EOL;
        $html .= '                <td style="text-align: left; vertical-align: bottom">' . PHP_EOL;
        $html .= '                    <button id="save">Speichern</button>' . PHP_EOL;
        $html .= '                    <button id="load">neu Laden</button>' . PHP_EOL;
        $html .= '                </td>' . PHP_EOL;
        $html .= '                <td style="text-align: right; vertical-align: bottom">' . PHP_EOL;
        $html .= '                    <div id="timestamp">&nbsp</div>' . PHP_EOL;
        $html .= '                </td>' . PHP_EOL;
        $html .= '            </tr>' . PHP_EOL;
        $html .= '        </table>' . PHP_EOL;

        $html .= '        <script>' . PHP_EOL;
        $html .= '            var simplemde = new SimpleMDE({' . PHP_EOL;
        $html .= '                    element: document.getElementById("text"),' . PHP_EOL;
        $html .= '                    spellChecker: ' . $this->bool2str($opt_spellChecker) . ',' . PHP_EOL;
        $html .= '                    renderingConfig: { ' . PHP_EOL;
        $html .= '                        codeSyntaxHighlighting: ' . $this->bool2str($opt_codeSyntaxHighlighting) . ',' . PHP_EOL;
        $html .= '                    },' . PHP_EOL;
        $html .= '                });' . PHP_EOL;

        $html .= '            function load() {' . PHP_EOL;
        $html .= '                var xhr = new XMLHttpRequest();' . PHP_EOL;
        $html .= '                console.log("load: url=" + load_url);' . PHP_EOL;
        $html .= '                xhr.open ("GET", load_url);' . PHP_EOL;
        $html .= '                xhr.onreadystatechange = function () {' . PHP_EOL;
        $html .= '                        if ((xhr.status === 200) && (xhr.readyState === 4)) {' . PHP_EOL;
        $html .= '                            var data = xhr.responseText;' . PHP_EOL;
        $html .= '                            var jdata = JSON.parse(data);' . PHP_EOL;
        $html .= '                            simplemde.value(jdata.text);' . PHP_EOL;
        $html .= '                            if (elem = document.getElementById("timestamp"))' . PHP_EOL;
        $html .= '                                elem.innerHTML = "Stand:&nbsp" + jdata.timestamp;' . PHP_EOL;
        $html .= '                        }' . PHP_EOL;
        $html .= '                    }' . PHP_EOL;
        $html .= '                xhr.send();' . PHP_EOL;
        $html .= '            }' . PHP_EOL;

        $html .= '            load();' . PHP_EOL;

        $html .= '            document.getElementById("save").addEventListener("click",function() {' . PHP_EOL;
        $html .= '                    var jdata = {' . PHP_EOL;
        $html .= '                            "text": simplemde.value(),' . PHP_EOL;
        $html .= '                        };' . PHP_EOL;
        $html .= '                    var data = JSON.stringify(jdata);' . PHP_EOL;
        $html .= '                    console.log("save: url=" +  save_url + ", jdata: ", jdata);' . PHP_EOL;
        $html .= '                    var xhr = new XMLHttpRequest();' . PHP_EOL;
        $html .= '                    xhr.open("POST", save_url, true);' . PHP_EOL;
        $html .= '                    xhr.send(data);' . PHP_EOL;
        $html .= '                    xhr.onreadystatechange = function() {' . PHP_EOL;
        $html .= '                            if (xhr.readyState == 4 && xhr.status == 200) {' . PHP_EOL;
        $html .= '                                var data = xhr.responseText;' . PHP_EOL;
        $html .= '                                var jdata = JSON.parse(data);' . PHP_EOL;
        $html .= '                                if (jdata.status != "OK") {' . PHP_EOL;
        $html .= '                                    console.log("jdata: ", jdata);' . PHP_EOL;
        $html .= '                                    alert("Fehler beim Speichern");' . PHP_EOL;
        $html .= '                                } else {' . PHP_EOL;
        $html .= '                                    load();' . PHP_EOL;
        $html .= '                                }' . PHP_EOL;
        $html .= '                            }' . PHP_EOL;
        $html .= '                        }' . PHP_EOL;
        $html .= '                });' . PHP_EOL;

        $html .= '            document.getElementById("load").addEventListener("click",function() {' . PHP_EOL;
        $html .= '                    load();' . PHP_EOL;
        $html .= '                });' . PHP_EOL;
        $html .= '        </script>' . PHP_EOL;

        $html .= '    </body>' . PHP_EOL;
        $html .= '</html>' . PHP_EOL;

        echo $html;
    }
}
