<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

require_once __DIR__ . '/../parsedown/Parsedown.php';

class Markdown extends IPSModule
{
    use Markdown\StubsCommonLib;
    use MarkdownLocalLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterAttributeString('UpdateInfo', '');

        $this->InstallVarProfiles(false);

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($timestamp, $senderID, $message, $data)
    {
        parent::MessageSink($timestamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

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

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Markdown');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

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

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
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
        if (isset($opts['SafeMode'])) {
            $Parsedown->setSafeMode((bool) $opts['SafeMode']);
        }
        if (isset($opts['MarkupEscaped'])) {
            $Parsedown->setMarkupEscaped((bool) $opts['MarkupEscaped']);
        }
        if (isset($opts['BreaksEnabled'])) {
            $Parsedown->setBreaksEnabled((bool) $opts['BreaksEnabled']);
        }
        if (isset($opts['UrlsLinked'])) {
            $Parsedown->setUrlsLinked((bool) $opts['UrlsLinked']);
        }

        if (isset($opts['Inline'])) {
            $isInline = (bool) $opts['Inline'];
        } else {
            $isInline = false;
        }

        $html = '';
        if (isset($opts['WithWrapper']) && (bool) $opts['WithWrapper']) {
            $html .= '<html>' . PHP_EOL;
        }

        if ($isInline) {
            $html .= $Parsedown->line($markdown) . PHP_EOL;
        } else {
            $html .= $Parsedown->text($markdown) . PHP_EOL;
        }

        if (isset($opts['WithWrapper']) && (bool) $opts['WithWrapper']) {
            $html .= '</html>' . PHP_EOL;
        }

        $this->SendDebug(__FUNCTION__, 'html=' . $html, 0);
        return $html;
    }
}
