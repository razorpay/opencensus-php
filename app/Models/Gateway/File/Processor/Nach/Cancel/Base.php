<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Cancel;

use DOMDocument;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Models\FundTransfer\Holidays;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\Nach;
use RZP\Gateway\Enach\Npci\Netbanking\CancelRequestTags;

abstract class Base extends Nach\Base
{
    public function fetchEntities(): PublicCollection
    {
        if (Holidays::isWorkingDay(Carbon::now(Timezone::IST)) === false)
        {
            return new PublicCollection();
        }

        $begin = $this->gatewayFile->getBegin();

        $begin = $this->getLastWorkingDay($begin);

        $end = $this->gatewayFile->getEnd();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        $tokens = $this->repo->token->fetchDeletedTokensForMethods(
            static::METHODS, static::GATEWAYS, static::ACQUIRER, $begin, $end);

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        $tokenIds = $tokens->pluck('id')->toArray();

        $this->trace->info(TraceCode::NACH_CANCEL_REQUEST, [
            'gateway_file_id' => $this->gatewayFile->getId(),
            'entity_ids'      => $tokenIds,
            'begin'           => $begin,
            'end'             => $end,
        ]);

        return $tokens;
    }

    public function generateData(PublicCollection $entites): array
    {
        $xmls = [];

        foreach ($entites as $token)
        {
            $utilityCode = $token->terminal->getGatewayMerchantId2();

            $sponsorBankIfsc = $token->terminal->getGatewayAccessCode();

            $umrn = $token->getGatewayToken();

            $destinationBankIfsc = $token->getIfsc();

            $createdDate = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s');

            $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?> <Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.011.001.01"/>');

            $mandateRoot = $document->addChild(CancelRequestTags::MANDATE_CANCEL_REQUEST);

            $grp = $mandateRoot->addChild(CancelRequestTags::GROUP_HEADER);

            $grp->addChild(CancelRequestTags::MSG_ID, $token['id']);

            $grp->addChild(CancelRequestTags::CREATION_DATE_TIME, $createdDate);

            $finInstnId1 = $grp->addChild(CancelRequestTags::INSTG_AGT)
                               ->addChild(CancelRequestTags::FINANCIAL_INST_ID);

            $finInstnId1->addChild(CancelRequestTags::CLR_SYS_MEMBER_ID)
                        ->addChild(CancelRequestTags::MEMBER_ID, $sponsorBankIfsc);

            $finInstnId2 = $grp->addChild(CancelRequestTags::INSTD_AGT)
                               ->addChild(CancelRequestTags::FINANCIAL_INST_ID);

            $finInstnId2->addChild(CancelRequestTags::CLR_SYS_MEMBER_ID)
                        ->addChild(CancelRequestTags::MEMBER_ID, $destinationBankIfsc);

            $undrlygCxlDtls = $mandateRoot->addChild(CancelRequestTags::UNDERLYING_CANCEL_DETAILS);

            $undrlygCxlDtls->addChild(CancelRequestTags::CANCEL_RSN)
                           ->addChild(CancelRequestTags::RSN)
                           ->addChild(CancelRequestTags::PRTRY, 'C002');

            $undrlygCxlDtls->addChild(CancelRequestTags::ORIGINAL_MANDATE)
                           ->addChild(CancelRequestTags::ORIGINAL_MANDATE_ID, $umrn);

            $dom = new DOMDocument("1.0");

            $dom->preserveWhiteSpace = false;

            $dom->formatOutput = true;

            $dom->loadXML($document->asXML());

            $xmls[$utilityCode][] = $dom->saveXML();
        }

        return $xmls;
    }
}
