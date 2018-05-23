<?php


namespace Core\Modules\Crm\Business\EmailNotificationHandlers;

class CaseObj extends \Core\Core\Injectable
{
    protected $dependencyList = [
        'entityManager'
    ];

    protected $inboundEmailEntityHash = array();

    public function getSmtpParams($type, $case, $user = null)
    {
        $inboundEmailId = $case->get('inboundEmailId');

        if (!$inboundEmailId) return;

        if (!array_key_exists($inboundEmailId, $this->inboundEmailEntityHash)) {
            $this->inboundEmailEntityHash[$inboundEmailId] = $this->getInjection('entityManager')->getEntity('InboundEmail', $inboundEmailId);
        }

        $inboundEmail = $this->inboundEmailEntityHash[$inboundEmailId];

        if (!$inboundEmail) return;

        $emailAddress = $inboundEmail->get('emailAddress');

        if (!$emailAddress) return;

        return array(
            'replyToAddress' => $emailAddress
        );
    }
}