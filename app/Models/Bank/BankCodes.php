<?php

namespace RZP\Models\Bank;

use App;

use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount\Provider;

class BankCodes
{
    //
    // In order to facilitate a refund, we need a valid IFSC belonging
    // to the source bank. These are the ones we're using for now.
    //
    const IFSC_ABHY = 'ABHY0065024';
    const IFSC_AIRP = 'AIRP0000001';
    const IFSC_AKJB = 'AKJB0000028';
    const IFSC_ALLA = 'ALLA0211950';
    const IFSC_ANDB = 'ANDB0001334';
    const IFSC_APBL = 'APBL0000001';
    const IFSC_APGB = 'APGB0000001';
    const IFSC_APMC = 'APMC0000013';
    const IFSC_ASBL = 'ASBL0000058';
    const IFSC_AUBL = 'AUBL0002366';
    const IFSC_AXIS = 'AXIS0000004';    // for Axis UAT
    const IFSC_BACB = 'BACB0000102';
    const IFSC_BARB = 'BARB0MAINOF';
    const IFSC_BARC = 'BARC0INBBIR';
    const IFSC_BCBM = 'BCBM0000012';
    const IFSC_BDBL = 'BDBL0001379';
    const IFSC_BKDN = 'BKDN0611871';
    const IFSC_BKID = 'BKID0000023';
    // const IFSC_BMBL = '';
    const IFSC_BNPA = 'BNPA0009009';
    const IFSC_CBIN = 'CBIN0280663';
    const IFSC_CCBL = 'CCBL0209003';
    const IFSC_CITI = 'CITI0000004';
    const IFSC_CIUB = 'CIUB0000032';
    const IFSC_CLBL = 'CLBL0000001';
    const IFSC_CNRB = 'CNRB0000002';
    const IFSC_CORP = 'CORP0000141';
    const IFSC_COSB = 'COSB0000001';
    const IFSC_CSBK = 'CSBK0000095';
    const IFSC_DBSS = 'DBSS0IN0811';
    const IFSC_DCBL = 'DCBL0000001';
    const IFSC_DEUT = 'DEUT0784PBC';
    const IFSC_DLXB = 'DLXB0000003';
    const IFSC_DNSB = 'DNSB00000CO';
    const IFSC_ESFB = 'ESFB0000002';
    const IFSC_ESMF = 'ESMF0000001';
    const IFSC_FDRL = 'FDRL0000121';
    const IFSC_FINO = 'FINO0009001';
    const IFSC_GBCB = 'GBCB0000024';
    const IFSC_GSCB = 'GSCB0000001';
    const IFSC_HDFC = 'HDFC0000001';
    const IFSC_HSBC = 'HSBC0560002';
    const IFSC_IBKL = 'IBKL0000001';
    const IFSC_ICIC = 'ICIC0002445';
    const IFSC_IDFB = 'IDFB0010201';
    const IFSC_IDIB = 'IDIB0NEFTMU';
    const IFSC_INDB = 'INDB0000006';
    const IFSC_IOBA = 'IOBA0002897';
    const IFSC_IPOS = 'IPOS0000001';
    const IFSC_JAKA = 'JAKA0FRABAD';
    const IFSC_JANA = 'JANA0000001';
    const IFSC_JPCB = 'JPCB0000001';
    const IFSC_JSBL = 'JSBL0000018';
    const IFSC_JSBP = 'JSBP0000043';
    const IFSC_KAIJ = 'KAIJ0000043';
    const IFSC_KARB = 'KARB0000513';
    const IFSC_KCCB = 'KCCB0RTGS4C';
    const IFSC_KJSB = 'KJSB0000007';
    const IFSC_KKBK = 'KKBK0000958';
    const IFSC_KUCB = 'KUCB0488023';
    const IFSC_KVBL = 'KVBL0001101';
    const IFSC_KVGB = 'KVGB0000001';
    const IFSC_LAVB = 'LAVB0000175';
    const IFSC_MAHB = 'MAHB0001150';
    const IFSC_MCBL = 'MCBL0960007';
    const IFSC_MSNU = 'MSNU0000001';
    const IFSC_MUBL = 'MUBL0000001';
    const IFSC_NKGS = 'NKGS0000096';
    const IFSC_NNSB = 'NNSB0128012';
    const IFSC_NSPB = 'NSPB0000001';
    const IFSC_NTBL = 'NTBL0BAR075';
    const IFSC_ORBC = 'ORBC0100001';
    // const IFSC_ORCB = '';
    const IFSC_PJSB = 'PJSB0000055';
    const IFSC_PMCB = 'PMCB0000002';
    const IFSC_PRTH = 'PRTH0000001';
    const IFSC_PSIB = 'PSIB0020994';
    const IFSC_PUNB = 'PUNB0000100';
    const IFSC_PYTM = 'PYTM0000001';
    const IFSC_RATN = 'RATN0000999';
    const IFSC_RNSB = 'RNSB0000001';
    const IFSC_RSBL = 'RSBL0000002';
    const IFSC_SBHY = 'SBIN0010411';
    const IFSC_SBIN = 'SBIN0010411';
    const IFSC_SBMY = 'SBIN0010411';
    const IFSC_SBTR = 'SBIN0010411';
    const IFSC_SCBL = 'SCBL0036001';
    const IFSC_SIBL = 'SIBL0000084';
    const IFSC_SPCB = 'SPCB0251022';
    const IFSC_SRCB = 'SRCB0000024';
    const IFSC_SURY = 'SURY0HE0001';
    const IFSC_SVCB = 'SVCB0000128';
    const IFSC_SYNB = 'SYNB0000005';
    const IFSC_TBSB = 'TBSB0000001';
    const IFSC_TJSB = 'TJSB0000002';
    const IFSC_TMBL = 'TMBL0000001';
    const IFSC_TNSC = 'TNSC0000001';
    const IFSC_TSAB = 'TSAB0000121';
    const IFSC_UBIN = 'UBIN0538167';
    const IFSC_UCBA = 'UCBA0000002';
    const IFSC_UJVN = 'UJVN0000001';
    const IFSC_UTBI = 'UTBI0XCNA10';
    const IFSC_UTIB = 'UTIB0001918';
    const IFSC_UTKS = 'UTKS0001001';
    const IFSC_VIJB = 'VIJB0001398';
    const IFSC_VVSB = 'VVSB0000012';
    const IFSC_YESB = 'YESB0000001';

    // inconsistent ifsc codes
    const IFSC_BARB_R = 'BARB0MAINOF';
    const IFSC_PUNB_R = 'PUNB0000100';
    const IFSC_LAVB_R = 'LAVB0000175';

    //
    // Kotak sends us 3 digit bank codes for IMPS transactions.
    // These are the ones we've collected till date.
    //
    const CODE_TO_IFSC_MAPPING = [
        'ACB'   => self::IFSC_ABHY,
        'AKO'   => self::IFSC_AKJB,
        'ALB'   => self::IFSC_ALLA,
        'ANB'   => self::IFSC_ANDB,
        'AND'   => self::IFSC_ANDB,
        'AND'   => self::IFSC_APGB,
        'APM'   => self::IFSC_APMC,
        'APN'   => self::IFSC_ASBL,
        'ARL'   => self::IFSC_AIRP,
        'ASB'   => self::IFSC_APBL,
        'AUS'   => self::IFSC_AUBL,
        'AXIS'  => self::IFSC_AXIS,   // for Axis UAT
        'AXB'   => self::IFSC_UTIB,
        'BCC'   => self::IFSC_BACB,
        'BDN'   => self::IFSC_BDBL,
        'BHB'   => self::IFSC_BCBM,
        // 'BMH'   => self::IFSC_BMBL,
        'BNP'   => self::IFSC_BNPA,
        'BOB'   => self::IFSC_BARB,
        'BOI'   => self::IFSC_BKID,
        'BOM'   => self::IFSC_MAHB,
        'BRC'   => self::IFSC_BARC,
        'CBI'   => self::IFSC_CBIN,
        'CCC'   => self::IFSC_CCBL,
        'CNB'   => self::IFSC_CNRB,
        'COB'   => self::IFSC_COSB,
        'CPF'   => self::IFSC_CLBL,
        'CRB'   => self::IFSC_CORP,
        'CSB'   => self::IFSC_CSBK,
        'CTB'   => self::IFSC_CITI,
        'CUB'   => self::IFSC_CIUB,
        'DBS'   => self::IFSC_DBSS,
        'DCB'   => self::IFSC_DCBL,
        'DLB'   => self::IFSC_DLXB,
        'DNB'   => self::IFSC_BKDN,
        'DNS'   => self::IFSC_DNSB,
        'DSB'   => self::IFSC_DNSB,
        'EAF'   => self::IFSC_ESMF,
        'EQT'   => self::IFSC_ESFB,
        'ESF'   => self::IFSC_ESFB,
        'FBL'   => self::IFSC_FDRL,
        'FIN'   => self::IFSC_FINO,
        'GBB'   => self::IFSC_GBCB,
        'GPB'   => self::IFSC_PJSB,
        'GSC'   => self::IFSC_GSCB,
        'HDB'   => self::IFSC_HDFC,
        'HSB'   => self::IFSC_HSBC,
        'ICI'   => self::IFSC_ICIC,
        'IDB'   => self::IFSC_IBKL,
        'IDF'   => self::IFSC_IDFB,
        'IIA'   => self::IFSC_INDB,
        'IIB'   => self::IFSC_INDB,
        'INB'   => self::IFSC_IDIB,
        'IOB'   => self::IFSC_IOBA,
        'IOBN1' => self::IFSC_IOBA,
        'IOBN2' => self::IFSC_IOBA,
        'JAB'   => self::IFSC_JPCB,
        'JKB'   => self::IFSC_JAKA,
        'JKS'   => self::IFSC_JSBL,
        'JNS'   => self::IFSC_JANA,
        'JSB'   => self::IFSC_JSBP,
        'KIB'   => self::IFSC_KAIJ,
        'KJB'   => self::IFSC_KJSB,
        'KLB'   => self::IFSC_KCCB,
        'KMB'   => self::IFSC_KKBK,
        'KTB'   => self::IFSC_KARB,
        'KUC'   => self::IFSC_KUCB,
        'KVB'   => self::IFSC_KVBL,
        'KVBN2' => self::IFSC_KVBL,
        'KVBN3' => self::IFSC_KVBL,
        'KVBN4' => self::IFSC_KVBL,
        'KVG'   => self::IFSC_KVGB,
        'LVB'   => self::IFSC_LAVB,
        'MNB'   => self::IFSC_MCBL,
        'MUC'   => self::IFSC_MSNU,
        'MUN'   => self::IFSC_MUBL,
        'NGB'   => self::IFSC_NKGS,
        'NNI'   => self::IFSC_NNSB,
        'NTB'   => self::IFSC_NTBL,
        'OBC'   => self::IFSC_ORBC,
        // 'OSC'   => self::IFSC_ORCB,
        'PMB'   => self::IFSC_PMCB,
        'PMC'   => self::IFSC_PMCB,
        'PNB'   => self::IFSC_PUNB,
        'PRT'   => self::IFSC_PRTH,
        'PSB'   => self::IFSC_PSIB,
        'PTM'   => self::IFSC_PYTM,
        'RKN'   => self::IFSC_RNSB,
        'RNB'   => self::IFSC_RATN,
        'RNS'   => self::IFSC_RSBL,
        'SBI'   => self::IFSC_SBIN,
        'SCB'   => self::IFSC_SCBL,
        'SIB'   => self::IFSC_SIBL,
        'SPB'   => self::IFSC_SPCB,
        'SRC'   => self::IFSC_SRCB,
        'SSF'   => self::IFSC_SURY,
        'SVC'   => self::IFSC_SVCB,
        'SYB'   => self::IFSC_SYNB,
        'TBS'   => self::IFSC_TBSB,
        'TEL'   => self::IFSC_TSAB,
        'TMB'   => self::IFSC_TMBL,
        'TSA'   => self::IFSC_TNSC,
        'TSB'   => self::IFSC_TJSB,
        'UBI'   => self::IFSC_UTBI,
        'UCO'   => self::IFSC_UCBA,
        'UIJ'   => self::IFSC_UJVN,
        'UKS'   => self::IFSC_UTKS,
        'UOB'   => self::IFSC_UBIN,
        'UOBN1' => self::IFSC_UBIN,
        'UOBN2' => self::IFSC_UBIN,
        'UOBN3' => self::IFSC_UBIN,
        'UOBN4' => self::IFSC_UBIN,
        'UOBN5' => self::IFSC_UBIN,
        'UOBN6' => self::IFSC_UBIN,
        'VJB'   => self::IFSC_VIJB,
        'VJBN1' => self::IFSC_VIJB,
        'VJBN2' => self::IFSC_VIJB,
        'VJBN3' => self::IFSC_VIJB,
        'VJBN4' => self::IFSC_VIJB,
        'VVS'   => self::IFSC_VVSB,
        'YBL'   => self::IFSC_YESB,
    ];

    const STRIP_LEADING_ZEROES_BANKS_NEFT = [
        IFSC::CNRB,
        IFSC::SIBL,
    ];

    const NBIN_TO_IFSC_MAPPING = [
        '9010' => self::IFSC_ALLA,
        '9011' => self::IFSC_ANDB,
        '9211' => self::IFSC_UTIB,
        '9012' => self::IFSC_BARB,
        '9013' => self::IFSC_BKID,
        '9014' => self::IFSC_MAHB,
        '9238' => self::IFSC_BACB,
        '9034' => self::IFSC_BNPA,
        '9015' => self::IFSC_CNRB,
        '9047' => self::IFSC_CSBK,
        '9016' => self::IFSC_CBIN,
        '9037' => self::IFSC_CITI,
        '9017' => self::IFSC_CORP,
        '9164' => self::IFSC_COSB,
        '9018' => self::IFSC_BKDN,
        '9641' => self::IFSC_DBSS,
        '9072' => self::IFSC_DCBL,
        '9048' => self::IFSC_DLXB,
        '9235' => self::IFSC_DNSB,
        '9049' => self::IFSC_FDRL,
        '9240' => self::IFSC_HDFC,
        '9039' => self::IFSC_HSBC,
        '9229' => self::IFSC_ICIC,
        '9259' => self::IFSC_IBKL,
        '9019' => self::IFSC_IDIB,
        '9020' => self::IFSC_IOBA,
        '9234' => self::IFSC_INDB,
        '9051' => self::IFSC_JAKA,
        '9074' => self::IFSC_JSBP,
        '9052' => self::IFSC_KARB,
        '9053' => self::IFSC_KVBL,
        '9485' => self::IFSC_KKBK,
        '9056' => self::IFSC_LAVB,
        '9313' => self::IFSC_MSNU,
        '9184' => self::IFSC_NTBL,
        '9086' => self::IFSC_NKGS,
        '9022' => self::IFSC_ORBC,
        '9328' => self::IFSC_PMCB,
        '9024' => self::IFSC_PUNB,
        '9088' => self::IFSC_SRCB,
        '9059' => self::IFSC_SIBL,
        '9036' => self::IFSC_SCBL,
        '9002' => self::IFSC_SBIN,
        '9025' => self::IFSC_SYNB,
        '9060' => self::IFSC_TMBL,
        '9109' => self::IFSC_TJSB,
        '9142' => self::IFSC_APMC,
        '9095' => self::IFSC_GBCB,
        '9028' => self::IFSC_UCBA,
        '9026' => self::IFSC_UBIN,
        '9027' => self::IFSC_UTBI,
        '9029' => self::IFSC_VIJB,
        '9532' => self::IFSC_YESB,
        '9176' => self::IFSC_RATN,
        '9023' => self::IFSC_PSIB,
        '9257' => self::IFSC_JANA,
        '9312' => self::IFSC_PJSB,
        '9386' => self::IFSC_KAIJ,
        '9054' => self::IFSC_CIUB,
        '9098' => self::IFSC_KCCB,
        //'9099' => self::IFSC_BMBL,
        '9480' => self::IFSC_ASBL,
        //'8316' => self::IFSC_SMCB,
        //'8352' => self::IFSC_VARA,
        //'8323' => self::IFSC_KLGB,
        //'8301' => self::IFSC_PKGB,
        '9110' => self::IFSC_MUBL,
        '9650' => self::IFSC_ABHY,
        '9124' => self::IFSC_GSCB,
        //'9402' => self::IFSC_HCBL,
        '9367' => self::IFSC_KJSB,
        '9301' => self::IFSC_RSBL,
        //'8103' => self::IFSC_VSBL,
        '9750' => self::IFSC_BDBL,
        '9751' => self::IFSC_IDFB,
        '9128' => self::IFSC_NNSB,
        '8339' => self::IFSC_KVGB,
        '8346' => self::IFSC_APGB,
        '9096' => self::IFSC_MCBL,
        //'8381' => self::IFSC_SUTB,
        '9525' => self::IFSC_TBSB,
        '9658' => self::IFSC_BARC,
        '8348' => self::IFSC_PRTH,
        '9112' => self::IFSC_BCBM,
        '9089' => self::IFSC_SVCB,
        '9740' => self::IFSC_JPCB,
        '9803' => self::IFSC_VVSB,
        '9217' => self::IFSC_RNSB,
        '9753' => self::IFSC_AIRP,
        '9756' => self::IFSC_ESFB,
        '9757' => self::IFSC_UJVN,
        //'9366' => self::IFSC_JASB,
        //'8392' => self::IFSC_HPSC,
        '9364' => self::IFSC_AKJB,
        '9251' => self::IFSC_SPCB,
        '9209' => self::IFSC_CCBL,
        //'8142' => self::IFSC_AMCB,
        '9760' => self::IFSC_ESMF,
        '9759' => self::IFSC_SURY,
        '9143' => self::IFSC_APBL,
        '9874' => self::IFSC_TSAB,
        //'9218' => self::IFSC_ORCB,
        '9001' => self::IFSC_TNSC,
        '9488' => self::IFSC_KUCB,
        '9771' => self::IFSC_FINO,
        '9772' => self::IFSC_NSPB,
        //'8232' => self::IFSC_CRUB,
        //'8313' => self::IFSC_PMEC,
        '9105' => self::IFSC_JSBL,
        '9177' => self::IFSC_CLBL,
        '9765' => self::IFSC_AUBL,
        '9762' => self::IFSC_UTKS,
        '9761' => self::IFSC_PYTM,
        '8302' => 'ICIC00PMCBL',
        '8303' => 'UBIN0RRBKGS',
        '8305' => 'HDFC0CTGCUB',
        '8304' => 'MAHB0RRBMGB',
        '8309' => 'HDFC0CDACUB',
        '8315' => 'SDCB0000001',
        '8312' => 'HDFC0CSUCOB',
        '8307' => 'YESB0NBL002',
        '8306' => 'HDFC0CPCUBL',
        '8359' => 'ICIC00ARIHT',
        '8366' => 'IBKL0548PPC',
        '8367' => 'IBKL0269TDC',
        '8351' => 'HDFC0CJALOR',
        '8350' => 'HDFC0CJBMLG',
        '8355' => 'ICIC00SBSBN',
        '8319' => 'BARB0BUPGBX',
        '8363' => 'KKBK0SPCB01',
        '8384' => 'ICIC00HSBLW',
        '8358' => 'ICIC00ADRSH',
        '8374' => 'ICIC00TMUCB',
        '8310' => 'ICIC00PUCCB',
        '8382' => 'ICIC00RUCBL',
        '8395' => 'GSCB0ADC001',
        '8318' => 'BARB0BRGBXX',
        '8375' => 'HDFC0CTRUMC',
        '8349' => 'SBIN0RRUKGB',
        '8327' => 'SBIN0RRSRGB',
        '8331' => 'SBIN0RRMRGB',
        '8326' => 'SBIN0RRPUGB',
        '8317' => 'BARB0BGGBXX',
        '8377' => 'IBKL0478LOK',
        '8105' => 'HDFC0CSUVRN',
        '8345' => 'SBIN0RRCKGB',
        '8104' => 'HDFC0CCUB01',
        '8325' => 'SBIN0RRAPGB',
        '8386' => 'HDFC0CITC01',
        '8379' => 'HDFC0CPCSBL',
        '8311' => 'HDFC0CSBB01',
        '8347' => 'SBIN0RRUTGB',
        '8109' => 'IBKL0101MCB',
        '8340' => 'PSIB0SGB002',
        '8177' => 'JAKA0GRAMEN',
        '8176' => 'SBIN0RRCHGB',
        '8185' => 'SBIN0RRMEGB',
        '8178' => 'SBIN0RRVCGB',
        '8186' => 'SBIN0RRMBGB',
        '8179' => 'SBIN0RRDCGB',
        '8329' => 'IOBA0ROGB01',
        '8184' => 'SBIN0RRLDGB',
        '8182' => 'SBIN0RRARGB',
        '8183' => 'SBIN0RRMIGB',
        '9755' => 'ESFB0004003',
        '8113' => 'HDFC0CMBANK',
        '8130' => 'IBKL0087PSB',
        '8106' => 'HDFC0CPNSBL',
        '8387' => 'IBKL0116MCO',
        '8117' => 'HDFC0CJMCBL',
        '8102' => 'IBKL0027K01',
        '8119' => 'HDFC0CIUCBL',
        '8141' => 'HDFC0CSUCUB',
        '8118' => 'ICIC00AJHCB',
        '8140' => 'HDFC0CSBL02',
        '8370' => 'KKBK0PNSB01',
        '8125' => 'YESB0UUCB07',
        '8180' => 'SBIN0RRELGB',
        '8132' => 'HDFC0CAACOB',
        '8144' => 'YESB0ACCB01',
        '8167' => 'UTIB0SASKAC',
        '8137' => 'YESB0BBCB00',
        '8136' => 'YESB0BNKCCB',
        '8224' => 'IBKL0216BCB',
        '8170' => 'ICIC00BHCCB',
        '8171' => 'YESB0BDCB00',
        '8147' => 'YESB0BCCB00',
        '8138' => 'IBKL0217C01',
        '8168' => 'UTIB0SKCCBL',
        '8139' => 'YESB0KHCB01',
        '8169' => 'UTIB0SKOCBL',
        '8135' => 'YESB0MCCBHO',
        '8145' => 'YESB0NDB001',
        '8146' => 'YESB0SBPBHO',
        '8162' => 'IBKL0041SCB',
        '8150' => 'YESB0SHBK01',
        '8354' => 'HDFC0CSINDC',
        '8134' => 'YESB0SNGB13',
        '8202' => 'GSCB0BVN001',
        '8157' => 'GSCB0SKB001',
        '8166' => 'YESB0UPNC01',
        '8181' => 'SBIN0RRNLGB',
        '8344' => 'ALLA0AU1002',
        '8332' => 'PUNB0HGB001',
        '8124' => 'IBKL0548PMC',
        '8230' => 'HDFC0CKUCBL',
        '8154' => 'HDFC0CMAN01',
        '8225' => 'IBKL0497LUC',
        '8165' => 'IBKL0068GP1',
        '8255' => 'IBKL0101SBC',
        '8353' => 'ICIC00VSCBL',
        '8373' => 'ICIC00ETAWH',
        '8155' => 'HDFC0CBHLUB',
        '8156' => 'HDFC0CS1812',
        '8231' => 'HDFC0CEENAD',
        '8333' => 'PUNB0HPGB04',
        '8334' => 'PUNB0MBGB06',
        '8335' => 'PUNB0PGB003',
        '8336' => 'PUNB0SUPGB5',
        '8172' => 'IBKL0116RBS',
        '9758' => 'PUNB0IPOS01',
        '8378' => 'SRCB0CNS001',
        '8273' => 'IBKL0116AUC',
        '8261' => 'YESBOBCBL02',
        '8275' => 'IBKL0553MSC',
        '8259' => 'HDFC0CSTUCB',
        '8164' => 'HDFC0CVVCCB',
        '8161' => 'IBKL0768PJS',
        '8246' => 'GSCB0USAURA',
        '8390' => 'YESB0GUCB01',
        '8337' => 'UCBA0RRBBKG',
        '8338' => 'UCBA0RRBPBG',
        '8279' => 'YESB0BSCB01',
        '8247' => 'GSCB0BKD001',
        '8343' => 'SBIN0RRMLGB',
        '9775' => 'PYTM0123456',
        // Below NBINs have multiple IFSCs. We have taken first for all these from the list.
        '8357' => 'HDFC0CPCB01',
        '8314' => 'YESB0YLNS01',
        '8393' => 'HDFC0CSVCBL',
        '8120' => 'YESB0MSB002',
        '8129' => 'UTIB0SBPP02',
    ];

    const IEC_REQUIRED_BANKS =  [
        IFSC::ICIC
    ];

    const ACCOUNT_NUMBER_LENGTH = 13;

    public static function getIfscForImpsBankCode(string $impsBankCode)
    {
        return self::CODE_TO_IFSC_MAPPING[$impsBankCode] ?? null;
    }

    public static function getIfscForNbin(string $nbin)
    {
        return self::NBIN_TO_IFSC_MAPPING[$nbin] ?? null;
    }

    public static function getIfscForBankCode(string $bankCode)
    {
        $key = __CLASS__ . '::' . 'IFSC_' . strtoupper($bankCode);

        if ((defined($key) === true))
        {
            return constant($key);
        }

        return null;
    }

    public static function hasIfscMapping(string $impsBankCode)
    {
        return (self::getIfscForImpsBankCode($impsBankCode) !== null);
    }

    /**
     * Canara bank account numbers are received like this:
     * - 00000683101027109
     * - 00002724129002387
     * In the former case, the last leading zero is significant. In
     * the latter case, it is not. Result should be 13 characters.
     *
     * @param  string $account
     * @return string $account
     */
    public static function modifyPayerAccount(string $account)
    {
        $account = ltrim($account, '0');

        $account = str_pad($account, self::ACCOUNT_NUMBER_LENGTH, '0', STR_PAD_LEFT);

        return $account;
    }

    /**
     * Checks if for a given bank ifsc code iec is required
     * @param string|null $ifsc
     * @return bool
     */
    public static function isIecRequiredBank(?string $ifsc): bool
    {
        $ifscBankCode = '';
        if(empty($ifsc) === false)
        {
            $ifscBankCode = substr($ifsc, 0, 4);
        }

        return in_array(strtoupper($ifscBankCode), self::IEC_REQUIRED_BANKS);
    }
}
