<?php

namespace RZP\Models\Merchant\PurposeCode;

class PurposeCodeList
{
    const CODES = "codes";
    const PURPOSECODE = "purposeCode";
    const DESCRIPTION = "description";
    const PURPOSEGROUP = "purposeGroup";

    //purpose code categories
    const CAPITAL_ACCOUNT = "Capital Account";
    const EXPORT_OF_GOODS = "Exports (of Goods)";
    const TRANSPORTATION = "Transportation";
    const TRAVEL = "Travel";
    const COMMUNICATION_SERVICE = "Communication Service";
    const CONSTRUCTION_SERVICE = "Construction Service";
    const INSURANCE_AND_PENSION_SERVICE = "Insurance and Pension Service";
    const FINANCIAL_SERVICES = "Financial Services";
    const COMPUTER_AND_IT_SERVICES ="Computer & Information Services";
    const ROYALTIES_LICENSE_FEES = "Royalties & License Fees";
    const OTHER_BUSINESS_SERVICES = "Other Business Services";
    const PERSONAL_CULTURAL_RECREATIONAL_SERVICES = "Personal, Cultural & Recreational services";
    const GNIE = "Government, not included elsewhere (G.n.i.e.)";
    const TRANSFERS = "Transfers";
    const INCOME ="Income";
    const OTHERS = "Others";
    const IMPORTS = "Imports";

    //purpose code

    //capital account
    const P0001 = 'P0001';
    const P0002 = 'P0002';
    const P0003 = 'P0003';
    const P0004 = 'P0004';
    const P0005 = 'P0005';
    const P0006 = 'P0006';
    const P0007 = 'P0007';
    const P0008 = 'P0008';
    const P0009 = 'P0009';
    const P0010 = 'P0010';
    const P0011 = 'P0011';
    const P0012 = 'P0012';
    const P0013 = 'P0013';//old
    const P0014 = 'P0014';
    const P0015 = 'P0015';
    const P0016 = 'P0016';
    const P0017 = 'P0017';
    const P0018 = 'P0018';
    const P0019 = 'P0019';//old
    const P0020 = 'P0020';//old
    const P0021 = 'P0021';//old
    const P0022 = 'P0022';//old
    const P0024 = 'P0024';//old
    const P0025 = 'P0025';//old
    const P0028 = 'P0028';//old
    const P0029 = 'P0029';//old
    const P0099 = 'P0099';//old
    const S0001 = 'S0001';
    const S0002 = 'S0002';
    const S0003 = 'S0003';
    const S0004 = 'S0004';
    const S0005 = 'S0005';
    const S0006 = 'S0006';
    const S0007 = 'S0007';
    const S0008 = 'S0008';
    const S0009 = 'S0009';
    const S0010 = 'S0010';
    const S0011 = 'S0011';
    const S0012 = 'S0012';
    const S0013 = 'S0013';
    const S0014 = 'S0014';
    const S0015 = 'S0015';
    const S0016 = 'S0016';
    const S0017 = 'S0017';
    const S0018 = 'S0018';

    //export of goods
    const P0101 = 'P0101';
    const P0102 = 'P0102';
    const P0103 = 'P0103';
    const P0104 = 'P0104';
    const P0105 = 'P0105';
    const P0106 = 'P0106';
    const P0107 = 'P0107';
    const P0108 = 'P0108';//old
    const P0109 = 'P0109';//old

    //Transportation
    const P0201 = 'P0201';
    const P0202 = 'P0202';
    const P0205 = 'P0205';
    const P0207 = 'P0207';
    const P0208 = 'P0208';
    const P0211 = 'P0211';
    const P0213 = 'P0213';
    const P0214 = 'P0214';//old
    const P0215 = 'P0215';//old
    const P0216 = 'P0216';//old
    const P0217 = 'P0217';//old
    const P0218 = 'P0218';//old
    const P0219 = 'P0219';//old
    const P0220 = 'P0220';//old
    const P0221 = 'P0221';//old
    const P0222 = 'P0222';//old
    const P0223 = 'P0223';//old
    const P0224 = 'P0224';//old
    const P0225 = 'P0225';//old
    const P0226 = 'P0226';//old
    const S0201 = 'S0201';
    const S0202 = 'S0202';
    const S0203 = 'S0203';
    const S0204 = 'S0204';
    const S0205 = 'S0205';
    const S0206 = 'S0206';
    const S0207 = 'S0207';
    const S0208 = 'S0208';
    const S0209 = 'S0209';
    const S0210 = 'S0210';
    const S0211 = 'S0211';
    const S0212 = 'S0212';
    const S0213 = 'S0213';

    //travel
    const P0301 = 'P0301';
    const P0302 = 'P0302';//old
    const P0304 = 'P0304';//old
    const P0305 = 'P0305';//old
    const P0306 = 'P0306';//old
    const P0308 = 'P0308';
    const S0301 = 'S0301';
    const S0302 = 'S0302';
    const S0303 = 'S0303';
    const S0304 = 'S0304';
    const S0305 = 'S0305';
    const S0306 = 'S0306';

    //Communication Service
    const P0401 = 'P0401';
    const P0402 = 'P0402';
    const P0403 = 'P0403';
    const P0404 = 'P0404';
    const S0401 = 'S0401';
    const S0402 = 'S0402';
    const S0403 = 'S0403';
    const S0404 = 'S0404';

    //Construction Service
    const P0501 = 'P0501';
    const P0502 = 'P0502';//old
    const S0501 = 'S0501';
    const S0502 = 'S0502';

    //Insurance Service
    const P0601 = 'P0601';
    const P0602 = 'P0602';
    const P0603 = 'P0603';
    const P0604 = 'P0604';
    const P0605 = 'P0605';
    const P0606 = 'P0606';
    const P0607 = 'P0607';//old
    const P0608 = 'P0608';//old
    const P0609 = 'P0609';//old
    const P0610 = 'P0610';//old
    const P0611 = 'P0611';//old
    const P0612 = 'P0612';//old
    const S0601 = 'S0601';
    const S0602 = 'S0602';
    const S0603 = 'S0603';
    const S0604 = 'S0604';
    const S0605 = 'S0605';
    const S0606 = 'S0606';

    //Financial Services
    const P0701 = 'P0701';
    const P0702 = 'P0702';
    const P0703 = 'P0703';
    const S0701 = 'S0701';
    const S0702 = 'S0702';
    const S0703 = 'S0703';

    //Computer & Information Services
    const P0801 = 'P0801';
    const P0802 = 'P0802';
    const P0803 = 'P0803';
    const P0804 = 'P0804';
    const P0805 = 'P0805';
    const P0806 = 'P0806';
    const P0807 = 'P0807';//old - NOT TO BE REMOVED NOW
    const P0808 = 'P0808';//old
    const P0809 = 'P0809';//old
    const S0801 = 'S0801';
    const S0802 = 'S0802';
    const S0803 = 'S0803';
    const S0804 = 'S0804';
    const S0805 = 'S0805';
    const S0806 = 'S0806';

    //Royalties & License Fees
    const P0901 = 'P0901';
    const P0902 = 'P0902';
    const S0901 = 'S0901';
    const S0902 = 'S0902';

    //Other Business Services
    const P1001 = 'P1001';
    const P1002 = 'P1002';
    const P1003 = 'P1003';
    const P1004 = 'P1004';
    const P1005 = 'P1005';
    const P1006 = 'P1006';
    const P1007 = 'P1007';
    const P1008 = 'P1008';
    const P1009 = 'P1009';
    const P1010 = 'P1010';
    const P1011 = 'P1011';
    const P1012 = 'P1012';
    const P1013 = 'P1013';
    const P1014 = 'P1014';//old
    const P1015 = 'P1015';//old
    const P1016 = 'P1016';//old
    const P1017 = 'P1017';//old
    const P1018 = 'P1018';//old
    const P1019 = 'P1019';
    const P1020 = 'P1020';//old
    const P1021 = 'P1021';//old
    const P1022 = 'P1022';//old
    const P1099 = 'P1099';//old
    const S1001 = 'S1001';
    const S1002 = 'S1002';
    const S1003 = 'S1003';
    const S1004 = 'S1004';
    const S1005 = 'S1005';
    const S1006 = 'S1006';
    const S1007 = 'S1007';
    const S1008 = 'S1008';
    const S1009 = 'S1009';
    const S1010 = 'S1010';
    const S1011 = 'S1011';
    const S1012 = 'S1012';
    const S1013 = 'S1013';
    const S1014 = 'S1014';//old
    const S1015 = 'S1015';//old
    const S1016 = 'S1016';//old
    const S1017 = 'S1017';//old
    const S1019 = 'S1019';
    const S1023 = 'S1023';//old

    // Personal, Cultural & Recreational services.
    const P1101 = 'P1101';
    const P1102 = 'P1102';
    const P1103 = 'P1103';//old
    const P1104 = 'P1104';//old
    const P1105 = 'P1105';//old
    const P1106 = 'P1106';//old
    const P1107 = 'P1107';
    const P1108 = 'P1108';//old
    const P1109 = 'P1109';
    const S1101 = 'S1101';
    const S1102 = 'S1102';

    // Government, not included elsewhere (G.n.i.e.)
    const P1201 = 'P1201';
    const P1203 = 'P1203';
    const S1201 = 'S1201';
    const SI202 = 'SI202';

    // Transfers
    const P1301 = 'P1301';
    const P1302 = 'P1302';
    const P1303 = 'P1303';
    const P1304 = 'P1304';
    const P1306 = 'P1306';
    const P1307 = 'P1307';//old
    const S1301 = 'S1301';
    const S1302 = 'S1302';
    const S1303 = 'S1303';
    const S1304 = 'S1304';
    const S1305 = 'S1305';
    const S1306 = 'S1306';

    // Income
    const P1401 = 'P1401';
    const P1403 = 'P1403';
    const P1404 = 'P1404';
    const P1405 = 'P1405';
    const P1406 = 'P1406';
    const P1407 = 'P1407';
    const P1408 = 'P1408';//old
    const P1409 = 'P1409';//old
    const P1410 = 'P1410';//old
    const P1411 = 'P1411';//old
    const P1412 = 'P1412';//old
    const P1499 = 'P1499';//old
    const S1401 = 'S1401';
    const S1402 = 'S1402';
    const S1403 = 'S1403';
    const S1404 = 'S1404';
    const S1405 = 'S1405';
    const S1406 = 'S1406';
    const S1407 = 'S1407';

    //Others
    const P1501 = 'P1501';
    const P1502 = 'P1502';
    const P1503 = 'P1503';
    const P1505 = 'P1505';//old
    const P1590 = 'P1590';
    const S1501 = 'S1501';
    const S1502 = 'S1502';
    const S1503 = 'S1503';
    const S1504 = 'S1504';

    //imports
    const S0101 = 'S0101';
    const S0102 = 'S0102';
    const S0103 = 'S0103';
    const S0104 = 'S0104';
    const S0190 = 'S0190';

    const P1601 = 'P1601';//old
    const P1602 = 'P1602';//old
    const P1701 = 'P1701';//old

    //purpose code descriptions

    //capital account
    const P0001_DESC = "Repatriation of Indian investment abroad in equity capital (shares)";
    const P0002_DESC = "Repatriation of Indian investment abroad in debt securities.";
    const P0003_DESC = "Repatriation of Indian investment abroad in branches";
    const P0004_DESC = "Repatriation of Indian investment abroad in subsidiaries and associates";
    const P0005_DESC = "Repatriation of Indian investment abroad in real estate";
    const P0006_DESC = "Foreign direct investment in India in equity";
    const P0007_DESC = "Foreign direct investment in India in debt securities";
    const P0008_DESC = "Foreign direct investment in India in real estate";
    const P0009_DESC = "Foreign portfolio investment in India in equity shares";
    const P0010_DESC = "Foreign portfolio investment in India in debt securities including debt funds";
    const P0011_DESC = "Repayment of loans extended to Non Residents";
    const P0012_DESC = "Loans from Non-Residents to India";
    const P0014_DESC = "Receipts o/a Non-Resident deposits (FCNRB/NRERA etc.) ADs should report these even if funds are not \"swapped\" into Rupees";
    const P0015_DESC = "Loans & overdrafts taken by ADs on their own account. (Any amount of loan credited to the NOSTRO account which may not be swapped into Rupees should also be reported)";
    const P0016_DESC = "Purchase of a foreign currency against another currency.";
    const P0017_DESC = "Sale of intangible assets like patents, copyrights, trade marks etc. by Indian companies";
    const P0018_DESC = "Other capital receipts not included elsewhere";
    const S0001_DESC = "Indian investment abroad - in equity capital (shares)";
    const S0002_DESC = "Indian investment abroad - in debt securities";
    const S0003_DESC = "Indian investment abroad - in branches";
    const S0004_DESC = "Indian investment abroad - in subsidiaries and associates";
    const S0005_DESC = "Indian investment abroad - in real estate";
    const S0006_DESC = "Repatriation of Foreign Direct Investment in India - in equity shares";
    const S0007_DESC = "Repatriation of Foreign Direct Investment in India - in debt securities";
    const S0008_DESC = "Repatriation of Foreign Direct Investment in India - in real estate";
    const S0009_DESC = "Repatriation of Foreign Portfolio Investment in India - in equity shares";
    const S0010_DESC = "Repatriation of Foreign Portfolio Investment in India - in debt securities";
    const S0011_DESC = "Loans extended to Non-Residents";
    const S0012_DESC = "Repayment of loans received from Non Residents (Long & medium term loans)";
    const S0013_DESC = "Repayment of short term loans received from Non-Residents";
    const S0014_DESC = "Repatriation of Non-Resident Deposits (FCNRB/N.RERA etc)";
    const S0015_DESC = "Repayment of loans & overdrafts taken by ADs on their own account.";
    const S0016_DESC = "Sale of a foreign currency against another foreiqn currency";
    const S0017_DESC = "Purchase of intangible assets like patents, copyrights, trade marks etc.";
    const S0018_DESC = "Other capital payments not included Elsewhere";

    //export of goods
    const P0101_DESC = "Value of export bills negotiated/purchased/discounted etc. (covered under GR/PP/SOFTEX/EC copy of shipping bills etc.)";
    const P0102_DESC = "Realisation of export bills (in respect of goods) sent on collection (full invoice value)";
    const P0103_DESC = "Advance receipts against export contracts (export of goods only)";
    const P0104_DESC = "Receipts against export of goods not covered by the GR/PP/SOFTEX/EC copy of shipping bill etc.";
    const P0105_DESC = "Export bills (in respect of goods) sent on collection.";
    const P0106_DESC = "Conversion of overdue export bills from NPD to collection mode";
    const P0107_DESC = "Realisation of NPD export bills (full value of bill to be reported)";

    //Transportation
    const P0201_DESC = "Receipts of surplus freight/passenger fare by Indian shipping companies operating abroad";
    const P0202_DESC = "Purchases, on account of operating expenses of Foreign shipping companies operating in India";
    const P0205_DESC = "Purchases on account of operational leasing (with crew) - Shipping companies";
    const P0207_DESC = "Receipts of surplus freight/passenger fare by Indian Airlines companies operating abroad.";
    const P0208_DESC = "Receipt on account of operating expenses of Foreign Airlines companies operating in India";
    const P0211_DESC = "Purchases on account of operational leasing (with crew) - Airlines companies";
    const P0213_DESC = "Receipts on account of other transportation services (stevedoring, demurrage, port handling charges etc).";
    const S0201_DESC = "Payments for surplus freight/passenger fare by foreign shipping companies operating in India.";
    const S0202_DESC = "Payment for operating expenses of Indian shipping companies operating abroad.";
    const S0203_DESC = "Freight on imports - Shipping companies";
    const S0204_DESC = "Freight on exports - Shipping companies";
    const S0205_DESC = "Operational leasing (with crew) -Shipping companies";
    const S0206_DESC = "Booking of passages abroad -Shipping companies";
    const S0207_DESC = "Payments for surplus freight/passenger fare by foreign Airlines companies operating in India.";
    const S0208_DESC = "Operating expenses of Indian Airlines companies operating abroad";
    const S0209_DESC = "Freight on imports - Airlines companies";
    const S0210_DESC = "Freight on exports - Airlines companies";
    const S0211_DESC = "Operational leasing (with crew) - Airlines companies";
    const S0212_DESC = "Booking of passages abroad - Airlines companies";
    const S0213_DESC = "Payments on account of stevedoring, demurrage, port handling charges etc.";

    //travel
    const P0301_DESC = "Purchases towards travel (Includes purchases of foreign TCs, currency : notes etc over the counter, by hotels, hospitals, Emporiums, Educational institutions etc. as well as amount received bv TT/SWIFT transfers or debit.";
    const P0308_DESC = "FC surrendered by returning Indian tourists.";
    const S0301_DESC = "Remittance towards Business travel.";
    const S0302_DESC = "Travel under basic travel quota (BTQ)";
    const S0303_DESC = "Travel for pilgrimage";
    const S0304_DESC = "Travel for medical treatment";
    const S0305_DESC = "Travel for education (including fees, lostel expenses etc.)";
    const S0306_DESC = "Other travel (international credit cards)";

    //Communication Service
    const P0401_DESC = 'Postal services';
    const P0402_DESC = 'Courier services';
    const P0403_DESC = 'Telecommunication services';
    const P0404_DESC = 'Satellite services';
    const S0401_DESC = 'Postal services';
    const S0402_DESC = 'Courier services';
    const S0403_DESC = 'Telecommunication services';
    const S0404_DESC = 'Satellite services';

    //Construction Service
    const P0501_DESC = "Receipts for cost of construction of services projects in India";
    const S0501_DESC = "Construction of projects abroad by Indian companies including import of goods at project site";
    const S0502_DESC = "Payments for cost of construction etc. of projects executed by foreign companies in India.";

    //Insurance Service
    const P0601_DESC = "Receipts of life insurance premium";
    const P0602_DESC = "Receipts of freight insurance - relating to import & export of goods";
    const P0603_DESC = "Receipts on account of other general insurance premium";
    const P0604_DESC = "Receipts of Reinsurance premium";
    const P0605_DESC = "Receipts on account of Auxiliary services (commission on Insurance)";
    const P0606_DESC = "Receipts on account of settlement of claims";
    const S0601_DESC = "Payments for Life insurance premium";
    const S0602_DESC = "Freight insurance - relating to import & export of goods";
    const S0603_DESC = "Other general insurance premium";
    const S0604_DESC = "Reinsurance premium";
    const S0605_DESC = "Auxiliary services (commission on insurance)";
    const S0606_DESC = "Settlement of claims";

    //Financial Services
    const P0701_DESC = 'Financial intermediation except investment banking - Bank charges, collection charges, LC charges, cancellation of forward contracts, commission on financial leasing etc.';
    const P0702_DESC = 'Investment banking - brokerage, underwriting commission etc.';
    const P0703_DESC = 'Auxiliary services - charges on operation & regulatory fees, custodial services, depository services etc.';
    const S0701_DESC = 'Financial intermediation except investment banking - Bank charges, collection charges, LC charges, cancellation of forward contracts, commission on financial leasing etc.';
    const S0702_DESC = 'Investment banking - brokerage, underwriting commission etc.';
    const S0703_DESC = 'Auxiliary services - charges on operation & regulatory fees, custodial services, depository services etc.';

    //Computer & Information Services
    const P0801_DESC = 'Hardware consultancy';
    const P0802_DESC = 'Software implementation/consultancy (other than those covered in SOFTEX form)';
    const P0803_DESC = 'Data base, data processing charges';
    const P0804_DESC = 'Repair and maintenance of computer and software';
    const P0805_DESC = 'Mews agency services';
    const P0806_DESC = 'Other information services Subscription to newspapers, periodicals, etc.';
    const S0801_DESC = 'Hardware consultancy';
    const S0802_DESC = 'Software implementation/consultancy';
    const S0803_DESC = 'Data base, data processing charges';
    const S0804_DESC = 'Repair and maintenance of computer And software';
    const S0805_DESC = 'slews agency services';
    const S0806_DESC = 'Other information services-Subscription to newspapers, periodicals';

    //Royalties & License Fees
    const P0901_DESC = 'Franchises services';
    const P0902_DESC = 'Receipts for use, through licensing arrangements, of produced originals or prototypes (such as manuscripts and films), patents,copyrights, trademarks, industrial processes, franchises etc. ';
    const S0901_DESC = "Franchises services - patents, copyrights, trade marks, industrial processes, franchises etc.";
    const S0902_DESC = "Payment for use, through licensing arrangements, of produced originals or prototypes (such as manuscripts and films)";

    //Other Business Services
    const P1001_DESC = 'Merchanting Services – net receipts (from sale and purchase of goods without crossing the border).';
    const P1002_DESC = 'Trade related services - Commission on exports/imports.';
    const P1003_DESC = 'Operational leasing services (other than financial leasing and without operating crew) including charter hire';
    const P1004_DESC = 'Legal services';
    const P1005_DESC = 'Accounting, auditing, book keeping and tax consulting services';
    const P1006_DESC = 'Business and management consultancy and public relations services';
    const P1007_DESC = 'Advertising, trade fair, market research and public opinion polling services';
    const P1008_DESC = 'Research & Development services';
    const P1009_DESC = 'Architectural, engineering and other technical services';
    const P1010_DESC = 'Agricultural, mining and on - site processing services - protection against insects & disease, increasing of harvest yields, forestry services, mining services like analysis of ores etc.';
    const P1011_DESC = 'Inward remittance for maintenance of offices in India';
    const P1012_DESC = 'Distribution services';
    const P1013_DESC = 'Environmental services';
    const P1019_DESC = 'Other services not included elsewhere';
    const S1001_DESC = 'Merchanting services – net payments (from Sale & purchase of goods without crossing the border).';
    const S1002_DESC = 'Trade related services - commission on exports / imports';
    const S1003_DESC = 'Operational leasing services (other than financial leasing) without operating crew, including charter hire';
    const S1004_DESC = 'Legal services';
    const S1005_DESC = 'Accounting, auditing, book keeping and tax consulting services';
    const S1006_DESC = 'Business and management consultancy and public relations Services';
    const S1007_DESC = 'Advertising, trade fair, market research and public opinion polling Service';
    const S1008_DESC = 'Research & Development services';
    const S1009_DESC = 'Architectural, engineering and other technical services';
    const S1010_DESC = 'Agricultural, mining and on-site processing services - protection against insects & disease, increasing of harvest yields, forestry services, mining services like analysis of ores etc.';
    const S1011_DESC = 'Payments for maintenance of offices Abroad';
    const S1012_DESC = 'Distribution services';
    const S1013_DESC = 'Environmental services';
    const S1019_DESC = 'Other services not included elsewhere';

    // Personal, Cultural & Recreational services.
    const P1101_DESC = 'Audio-visual and related services -services and associated fees related to production of motion pictures, rentals, fees received by actors, directors, producers and fees for distribution rights.';
    const P1102_DESC = 'Personal, cultural services such as those related to museums, libraries, archives and sporting activities and fees for correspondence courses of Indian Universities/Institutes';
    const S1101_DESC = 'Audio-visual and related services -services and associated fees related to production of motion pictures, rentals, fees received by actors, directors, producers and fees for distribution rights.';
    const S1102_DESC = 'Personal, cultural services such as those related to museums, libraries, archives and sporting activities; fees for correspondence courses abroad.';

    // Government, not included elsewhere (G.n.i.e.)
    const P1201_DESC = 'Maintenance of foreign embassies in India';
    const P1203_DESC = 'Maintenance of international institutions such as offices of IMF mission, World Bank, UNICEF etc. in India.';
    const S1201_DESC = 'Maintenance of Indian embassies abroad';
    const SI202_DESC = 'Remittances by foreign embassies in India';

    // Transfers
    const P1301_DESC = 'Inward remittance from Indian nonresidents towards family maintenance and savings';
    const P1302_DESC = 'Personal gifts and donations';
    const P1303_DESC = 'Donations to religious and charitable institutions in India';
    const P1304_DESC = 'Grants and donations to governments and charitable institutions established by the governments';
    const P1306_DESC = 'Receipts/Refund of taxes';
    const S1301_DESC = 'Remittance by non-residents towards family maintenance and savings';
    const S1302_DESC = 'Remittance towards personal gifts and donations';
    const S1303_DESC = 'Remittance towards donations to religious and charitable institutions abroad';
    const S1304_DESC = 'Remittance towards grants and donations to other governments and charitable institutions established by the governments.';
    const S1305_DESC = 'Contributions/donations by the Government to international institutions';
    const S1306_DESC = 'Remittance towards payment/refund of taxes.';

    // Income
    const P1401_DESC = 'Compensation of employees';
    const P1403_DESC = 'Inward remittance towards interest on loans extended to non-residents (ST/MT/LT loans)';
    const P1404_DESC = 'Inward remittance of interest on debt securities - debentures/bonds/FRNsetc,';
    const P1405_DESC = 'Inward remittance towards interest receipts of ADs on their own account (on investments.)';
    const P1406_DESC = 'Repatriation of profits to India';
    const P1407_DESC = 'Receipt of dividends by Indians';
    const S1401_DESC = 'Compensation of employees';
    const S1402_DESC = 'Remittance towards interest on Non-Resident deposits (FCNRB/NRERA/ NRNRD/NRSR etc.)';
    const S1403_DESC = 'Remittance towards interest on loans from Non Residents (ST/MT/LT loans)';
    const S1404_DESC = 'Remittance of interest on debt securities - debentures / bonds / FRNs etc.';
    const S1405_DESC = 'Remittance towards interest payment by ADs on their own account (to VOSTRO a/c holders or the OD on NOSTRO a/c.)';
    const S1406_DESC = 'Repatriation of profits';
    const S1407_DESC = 'Payment/repatriation of dividends';

    //Others
    const P1501_DESC = 'Refunds/rebates on account of imports';
    const P1502_DESC = 'Reversal of wrong entries, refunds of amount remitted for non-imports';
    const P1503_DESC = 'Remittances (receipts) by residents under international bidding process.';
    const P1590_DESC = 'Receipts below $10,000 (say Rs 5,00,000)';
    const S1501_DESC = 'Refunds/rebates/reduction in invoice value on account of exports';
    const S1502_DESC = 'Reversal of wrong entries, refunds of amount remitted for non-exports';
    const S1503_DESC = 'Payments by residents for international bidding';
    const S1504_DESC = 'Crystallize/Cancel export bills';

    //imports
    const S0101_DESC = "Advance payment against imports";
    const S0102_DESC = "Payment towards imports - settlement of invoice";
    const S0103_DESC = "Imports by diplomatic missions";
    const S0104_DESC = "Intermediary trade";
    const S0190_DESC = "Imports below Rs. 500.000 - (For use by ECD offices)";

    // older codes
    const P0013_DESC = "Short term loans with original maturity upto one year from NonResidents to India (Short-term Trade Credit)";
    const P0019_DESC = "Receipts on account of Sale of non-produced non-financial assets (Sale of intangible assets like patents, copyrights, trademarks etc.,use of natural resources) – Non-Government";
    const P0020_DESC = "Receipts on account of margin payments, premium payment and settlement amount etc. under Financial derivative transactions";
    const P0021_DESC = "Receipts on account of sale of share under Employee stock option";
    const P0022_DESC = "Receipts on account of other investment in ADRs/GDRs";
    const P0024_DESC = "External Assistance received by India e.g. Multilateral and bilateral loans received by Govt. of India under agreements with other govt. / international institutions.";
    const P0025_DESC = "Repayments received on account of External Assistance extended by India";
    const P0028_DESC = "Capital transfer receipts (Guarantee payments, Investment Grant given by the government/international organisation, exceptionally large Non-life insurance claims including claims arising out of natural calamity) - Government";
    const P0029_DESC = "Capital transfer receipts ( Guarantee payments, Investment Grant given by the Non-government, exceptionally large Non-life insurance claims including claims arising out of natural calamity) – Non-Government";
    const P0099_DESC = "Other capital receipts not included elsewhere";
    const P0108_DESC = "Goods sold under merchanting / Receipt against export leg of merchanting trade";
    const P0109_DESC = "Export realisation on account of exports to Nepal and Bhutan, if any";
    const P0214_DESC = "Receipts on account of other transportation services (stevedoring, demurrage, port handling charges etc).(Shipping Companies)";
    const P0215_DESC = "Receipts on account of other transportation services (stevedoring, demurrage, port handling charges etc).(Airlines companies)";
    const P0216_DESC = "Receipts of freight fare -Shipping companies operating abroad";
    const P0217_DESC = "Receipts of passenger fare by Indian Shipping companies operating abroad";
    const P0218_DESC = "Other receipts by Shipping companies";
    const P0219_DESC = "Receipts of freight fare by Indian Airlines companies operating abroad";
    const P0220_DESC = "Receipts of passenger fare - Airlines";
    const P0221_DESC = "Other receipts by Airlines companies";
    const P0222_DESC = "Receipts on account of freights under other modes of transport (Internal Waterways, Roadways, Railways, Pipeline transports and Others)";
    const P0223_DESC = "Receipts on account of passenger fare under other modes of transport (Internal Waterways, Roadways, Railways, Pipeline transports and Others)";
    const P0224_DESC = "Postal & Courier services by Air";
    const P0225_DESC = "Postal & Courier services by Sea";
    const P0226_DESC = "Postal & Courier services by others";
    const P0302_DESC = "Business travel";
    const P0304_DESC = "Travel for medical treatment including TCs purchased by hospitals";
    const P0305_DESC = "Travel for education including TCs purchased by educational institutions";
    const P0306_DESC = "Other travel receipts";
    const P0502_DESC = "Receipts on account of construction works carried out abroad by Indian Companies";
    const P0607_DESC = "Insurance claim Settlement of non-life insurance; and life insurance (only term insurance)";
    const P0608_DESC = "Life insurance claim settlements (excluding term insurance) received by residents in India";
    const P0609_DESC = "Standardised guarantee services";
    const P0610_DESC = "Premium for pension funds";
    const P0611_DESC = "Periodic pension entitlements e.g. monthly quarterly or yearly payments of pension amounts by Indian Pension Fund Companies.";
    const P0612_DESC = "Invoking of standardised guarantees";
    const P0807_DESC = 'Off site Software Exports';
    const P0808_DESC = 'Telecommunication services including electronic mail services and voice mail services';
    const P0809_DESC = 'Satellite services including space shuttle and rockets, etc.';
    const P1014_DESC = 'Engineering Services';
    const P1015_DESC = 'Tax consulting services';
    const P1016_DESC = 'Market research and public opinion polling service';
    const P1017_DESC = 'Publishing and printing services';
    const P1018_DESC = 'Mining services like on–site processing services analysis of ores etc.';
    const P1020_DESC = 'Wholesale and retailing trade services.';
    const P1021_DESC = 'Operational leasing services (other than financial leasing) without operating crew, including charter hire- Shipping companies ';
    const P1022_DESC = 'Other Technical Services including scientific/space services.';
    const P1099_DESC = 'Other services not included elsewhere';
    const S1014_DESC = "Engineering Services";
    const S1015_DESC = "Tax consulting services";
    const S1016_DESC = "Market research and public opinion polling service";
    const S1017_DESC = "Publishing and printing services";
    const S1023_DESC = "Other Technical Services including scientific/space services";
    const P1103_DESC = 'Radio and television production, distribution and transmission services';
    const P1104_DESC = 'Entertainment services';
    const P1105_DESC = 'Museums, library and archival services';
    const P1106_DESC = 'Recreation and sporting activity services';
    const P1107_DESC = 'Educational services (e.g. fees received for correspondence courses offered to non-resident by Indian institutions)';
    const P1108_DESC = 'Health Service (Receipts on account of services provided by Indian hospitals, doctors, nurses, paramedical and similar services etc.rendered remotely or on-site) ';
    const P1109_DESC = 'Other Personal, Cultural & Recreational services';
    const P1307_DESC = 'Receipts on account of migrant transfers including Personal Effects';
    const P1408_DESC = 'Inward remittance of profit by branches of Indian FDI Enterprises(including bank branches) operating abroad.';
    const P1409_DESC = 'Inward remittance of dividends (on equity and investment fund shares) by Indian FDI Enterprises, other than branches, operating abroad';
    const P1410_DESC = 'Inward remittance on account of interest payment by Indian FDI enterprises operating abroad to their Parent company in India.';
    const P1411_DESC = 'Inward remittance of interest income on account of Portfolio Investment made abroad by India';
    const P1412_DESC = 'Inward remittance of dividends on account of Portfolio Investment made abroad by India on equity and investment fund shares';
    const P1499_DESC = 'Other income receipts';
    const P1505_DESC = 'Deemed Exports ( exports between SEZ, EPZs and Domestic Tariff Areas)';
    const P1601_DESC = 'Receipts on account of maintenance and repair services rendered for Vessels, Ships, Boats, Warships, etc.';
    const P1602_DESC = 'Receipts of maintenance and repair services rendered for aircrafts, Space shuttles, Rockets, military aircrafts, etc';
    const P1701_DESC = 'Receipts on account of processing of goods';

    //purpose code mapping
    //TODO:- remove old purpose codes after merchants have updated to new purpose codes
    protected static $purposeCodeDescMappings = [
        self::P0001 => self::P0001_DESC,
        self::P0002 => self::P0002_DESC,
        self::P0003 => self::P0003_DESC,
        self::P0004 => self::P0004_DESC,
        self::P0005 => self::P0005_DESC,
        self::P0006 => self::P0006_DESC,
        self::P0007 => self::P0007_DESC,
        self::P0008 => self::P0008_DESC,
        self::P0009 => self::P0009_DESC,
        self::P0010 => self::P0010_DESC,
        self::P0011 => self::P0011_DESC,
        self::P0012 => self::P0012_DESC,
        self::P0013 => self::P0013_DESC,//old
        self::P0014 => self::P0014_DESC,
        self::P0015 => self::P0015_DESC,
        self::P0016 => self::P0016_DESC,
        self::P0017 => self::P0017_DESC,
        self::P0018 => self::P0018_DESC,
        self::P0019 => self::P0019_DESC,//old
        self::P0020 => self::P0020_DESC,//old
        self::P0021 => self::P0021_DESC,//old
        self::P0022 => self::P0022_DESC,//old
        self::P0024 => self::P0024_DESC,//old
        self::P0025 => self::P0025_DESC,//old
        self::P0028 => self::P0028_DESC,//old
        self::P0029 => self::P0029_DESC,//old
        self::P0099 => self::P0099_DESC,//old
        self::S0001 => self::S0001_DESC,
        self::S0002 => self::S0002_DESC,
        self::S0003 => self::S0003_DESC,
        self::S0004 => self::S0004_DESC,
        self::S0005 => self::S0005_DESC,
        self::S0006 => self::S0006_DESC,
        self::S0007 => self::S0007_DESC,
        self::S0008 => self::S0008_DESC,
        self::S0009 => self::S0009_DESC,
        self::S0010 => self::S0010_DESC,
        self::S0011 => self::S0011_DESC,
        self::S0012 => self::S0012_DESC,
        self::S0013 => self::S0013_DESC,
        self::S0014 => self::S0014_DESC,
        self::S0015 => self::S0015_DESC,
        self::S0016 => self::S0016_DESC,
        self::S0017 => self::S0017_DESC,
        self::S0018 => self::S0018_DESC,
        self::P0101 => self::P0101_DESC,
        self::P0102 => self::P0102_DESC,
        self::P0103 => self::P0103_DESC,
        self::P0104 => self::P0104_DESC,
        self::P0105 => self::P0105_DESC,
        self::P0106 => self::P0106_DESC,
        self::P0107 => self::P0107_DESC,
        self::P0108 => self::P0108_DESC,//old
        self::P0109 => self::P0109_DESC,//old
        self::P0201 => self::P0201_DESC,
        self::P0202 => self::P0202_DESC,
        self::P0205 => self::P0205_DESC,
        self::P0207 => self::P0207_DESC,
        self::P0208 => self::P0208_DESC,
        self::P0211 => self::P0211_DESC,
        self::P0213 => self::P0213_DESC,
        self::P0214 => self::P0214_DESC,//old
        self::P0215 => self::P0215_DESC,//old
        self::P0216 => self::P0216_DESC,//old
        self::P0217 => self::P0217_DESC,//old
        self::P0218 => self::P0218_DESC,//old
        self::P0219 => self::P0219_DESC,//old
        self::P0220 => self::P0220_DESC,//old
        self::P0221 => self::P0221_DESC,//old
        self::P0222 => self::P0222_DESC,//old
        self::P0223 => self::P0223_DESC,//old
        self::P0224 => self::P0224_DESC,//old
        self::P0225 => self::P0225_DESC,//old
        self::P0226 => self::P0226_DESC,//old
        self::S0201 => self::S0201_DESC,
        self::S0202 => self::S0202_DESC,
        self::S0203 => self::S0203_DESC,
        self::S0204 => self::S0204_DESC,
        self::S0205 => self::S0205_DESC,
        self::S0206 => self::S0206_DESC,
        self::S0207 => self::S0207_DESC,
        self::S0208 => self::S0208_DESC,
        self::S0209 => self::S0209_DESC,
        self::S0210 => self::S0210_DESC,
        self::S0211 => self::S0211_DESC,
        self::S0212 => self::S0212_DESC,
        self::S0213 => self::S0213_DESC,
        self::P0301 => self::P0301_DESC,
        self::P0302 => self::P0302_DESC,//old
        self::P0304 => self::P0304_DESC,//old
        self::P0305 => self::P0305_DESC,//old
        self::P0306 => self::P0306_DESC,//old
        self::P0308 => self::P0308_DESC,
        self::S0301 => self::S0301_DESC,
        self::S0302 => self::S0302_DESC,
        self::S0303 => self::S0303_DESC,
        self::S0304 => self::S0304_DESC,
        self::S0305 => self::S0305_DESC,
        self::S0306 => self::S0306_DESC,
        self::P0401 => self::P0401_DESC,
        self::P0402 => self::P0402_DESC,
        self::P0403 => self::P0403_DESC,
        self::P0404 => self::P0404_DESC,
        self::S0401 => self::S0401_DESC,
        self::S0402 => self::S0402_DESC,
        self::S0403 => self::S0403_DESC,
        self::S0404 => self::S0404_DESC,
        self::P0501 => self::P0501_DESC,
        self::P0502 => self::P0502_DESC,//old
        self::S0501 => self::S0501_DESC,
        self::S0502 => self::S0502_DESC,
        self::P0601 => self::P0601_DESC,
        self::P0602 => self::P0602_DESC,
        self::P0603 => self::P0603_DESC,
        self::P0604 => self::P0604_DESC,
        self::P0605 => self::P0605_DESC,
        self::P0606 => self::P0606_DESC,
        self::P0607 => self::P0607_DESC,//old
        self::P0608 => self::P0608_DESC,//old
        self::P0609 => self::P0609_DESC,//old
        self::P0610 => self::P0610_DESC,//old
        self::P0611 => self::P0611_DESC,//old
        self::P0612 => self::P0612_DESC,//old
        self::S0601 => self::S0601_DESC,
        self::S0602 => self::S0602_DESC,
        self::S0603 => self::S0603_DESC,
        self::S0604 => self::S0604_DESC,
        self::S0605 => self::S0605_DESC,
        self::S0606 => self::S0606_DESC,
        self::P0701 => self::P0701_DESC,
        self::P0702 => self::P0702_DESC,
        self::P0703 => self::P0703_DESC,
        self::S0701 => self::S0701_DESC,
        self::S0702 => self::S0702_DESC,
        self::S0703 => self::S0703_DESC,
        self::P0801 => self::P0801_DESC,
        self::P0802 => self::P0802_DESC,
        self::P0803 => self::P0803_DESC,
        self::P0804 => self::P0804_DESC,
        self::P0805 => self::P0805_DESC,
        self::P0806 => self::P0806_DESC,
        self::P0807 => self::P0807_DESC,//old
        self::P0808 => self::P0808_DESC,//old
        self::P0809 => self::P0809_DESC,//old
        self::S0801 => self::S0801_DESC,
        self::S0802 => self::S0802_DESC,
        self::S0803 => self::S0803_DESC,
        self::S0804 => self::S0804_DESC,
        self::S0805 => self::S0805_DESC,
        self::S0806 => self::S0806_DESC,
        self::P0901 => self::P0901_DESC,
        self::P0902 => self::P0902_DESC,
        self::S0901 => self::S0901_DESC,
        self::S0902 => self::S0902_DESC,
        self::P1001 => self::P1001_DESC,
        self::P1002 => self::P1002_DESC,
        self::P1003 => self::P1003_DESC,
        self::P1004 => self::P1004_DESC,
        self::P1005 => self::P1005_DESC,
        self::P1006 => self::P1006_DESC,
        self::P1007 => self::P1007_DESC,
        self::P1008 => self::P1008_DESC,
        self::P1009 => self::P1009_DESC,
        self::P1010 => self::P1010_DESC,
        self::P1011 => self::P1011_DESC,
        self::P1012 => self::P1012_DESC,
        self::P1013 => self::P1013_DESC,
        self::P1014 => self::P1014_DESC,//old
        self::P1015 => self::P1015_DESC,//old
        self::P1016 => self::P1016_DESC,//old
        self::P1017 => self::P1017_DESC,//old
        self::P1018 => self::P1018_DESC,//old
        self::P1019 => self::P1019_DESC,
        self::P1020 => self::P1020_DESC,//old
        self::P1021 => self::P1021_DESC,//old
        self::P1022 => self::P1022_DESC,//old
        self::P1099 => self::P1099_DESC,//old
        self::S1001 => self::S1001_DESC,
        self::S1002 => self::S1002_DESC,
        self::S1003 => self::S1003_DESC,
        self::S1004 => self::S1004_DESC,
        self::S1005 => self::S1005_DESC,
        self::S1006 => self::S1006_DESC,
        self::S1007 => self::S1007_DESC,
        self::S1008 => self::S1008_DESC,
        self::S1009 => self::S1009_DESC,
        self::S1010 => self::S1010_DESC,
        self::S1011 => self::S1011_DESC,
        self::S1012 => self::S1012_DESC,
        self::S1013 => self::S1013_DESC,
        self::S1014 => self::S1014_DESC,//old
        self::S1015 => self::S1015_DESC,//old
        self::S1016 => self::S1016_DESC,//old
        self::S1017 => self::S1017_DESC,//old
        self::S1019 => self::S1019_DESC,
        self::S1023 => self::S1023_DESC,//old
        self::P1101 => self::P1101_DESC,
        self::P1102 => self::P1102_DESC,
        self::P1103 => self::P1103_DESC,//old
        self::P1104 => self::P1104_DESC,//old
        self::P1105 => self::P1105_DESC,//old
        self::P1106 => self::P1106_DESC,//old
        self::P1107 => self::P1107_DESC,//old
        self::P1108 => self::P1108_DESC,//old
        self::P1109 => self::P1109_DESC,//old
        self::S1101 => self::S1101_DESC,
        self::S1102 => self::S1102_DESC,
        self::P1201 => self::P1201_DESC,
        self::P1203 => self::P1203_DESC,
        self::S1201 => self::S1201_DESC,
        self::SI202 => self::SI202_DESC,
        self::P1301 => self::P1301_DESC,
        self::P1302 => self::P1302_DESC,
        self::P1303 => self::P1303_DESC,
        self::P1304 => self::P1304_DESC,
        self::P1306 => self::P1306_DESC,
        self::P1307 => self::P1307_DESC,//old
        self::S1301 => self::S1301_DESC,
        self::S1302 => self::S1302_DESC,
        self::S1303 => self::S1303_DESC,
        self::S1304 => self::S1304_DESC,
        self::S1305 => self::S1305_DESC,
        self::S1306 => self::S1306_DESC,
        self::P1401 => self::P1401_DESC,
        self::P1403 => self::P1403_DESC,
        self::P1404 => self::P1404_DESC,
        self::P1405 => self::P1405_DESC,
        self::P1406 => self::P1406_DESC,
        self::P1407 => self::P1407_DESC,
        self::P1408 => self::P1408_DESC,//old
        self::P1409 => self::P1409_DESC,//old
        self::P1410 => self::P1410_DESC,//old
        self::P1411 => self::P1411_DESC,//old
        self::P1412 => self::P1412_DESC,//old
        self::P1499 => self::P1499_DESC,//old
        self::S1401 => self::S1401_DESC,
        self::S1402 => self::S1402_DESC,
        self::S1403 => self::S1403_DESC,
        self::S1404 => self::S1404_DESC,
        self::S1405 => self::S1405_DESC,
        self::S1406 => self::S1406_DESC,
        self::S1407 => self::S1407_DESC,
        self::P1501 => self::P1501_DESC,
        self::P1502 => self::P1502_DESC,
        self::P1503 => self::P1503_DESC,
        self::P1505 => self::P1505_DESC,//old
        self::P1590 => self::P1590_DESC,
        self::S1501 => self::S1501_DESC,
        self::S1502 => self::S1502_DESC,
        self::S1503 => self::S1503_DESC,
        self::S1504 => self::S1504_DESC,
        self::S0101 => self::S0101_DESC,
        self::S0102 => self::S0102_DESC,
        self::S0103 => self::S0103_DESC,
        self::S0104 => self::S0104_DESC,
        self::S0190 => self::S0190_DESC,
        self::P1601 => self::P1601_DESC,//old
        self::P1602 => self::P1602_DESC,//old
        self::P1701 => self::P1701_DESC,//old
    ];

    //purpose category and their code mapping

    const CAPITAL_ACCOUNT_CODES = [
        self::P0001,
        self::P0002,
        self::P0003,
        self::P0004,
        self::P0005,
        self::P0006,
        self::P0007,
        self::P0008,
        self::P0009,
        self::P0010,
        self::P0011,
        self::P0012,
        self::P0014,
        self::P0015,
        self::P0016,
        self::P0017,
        self::P0018,
        self::S0001,
        self::S0002,
        self::S0003,
        self::S0004,
        self::S0005,
        self::S0006,
        self::S0007,
        self::S0008,
        self::S0009,
        self::S0010,
        self::S0011,
        self::S0012,
        self::S0013,
        self::S0014,
        self::S0015,
        self::S0016,
        self::S0017,
        self::S0018
    ];

    const EXPORT_OF_GOODS_CODES = [
        self::P0101,
        self::P0102,
        self::P0103,
        self::P0104,
        self::P0105,
        self::P0106,
        self::P0107
    ];

    const TRANSPORTATION_CODES = [
        self::P0201,
        self::P0202,
        self::P0205,
        self::P0207,
        self::P0208,
        self::P0211,
        self::P0213,
        self::S0201,
        self::S0202,
        self::S0203,
        self::S0204,
        self::S0205,
        self::S0206,
        self::S0207,
        self::S0208,
        self::S0209,
        self::S0210,
        self::S0211,
        self::S0212,
        self::S0213
    ];

    const TRAVEL_CODES = [
        self::P0301,
        self::P0308,
        self::S0301,
        self::S0302,
        self::S0303,
        self::S0304,
        self::S0305,
        self::S0306,
    ];

    const COMMUNICATION_SERVICES_CODES = [
        self::P0401,
        self::P0402,
        self::P0403,
        self::P0404,
        self::S0401,
        self::S0402,
        self::S0403,
        self::S0404,
    ];

    const CONSTRUCTION_SERVICES_CODES = [
        self::P0501,
        self::S0501,
        self::S0502,
    ];

    const INSURANCE_SERVICES_CODES = [
        self::P0601,
        self::P0602,
        self::P0603,
        self::P0604,
        self::P0605,
        self::P0606,
        self::S0601,
        self::S0602,
        self::S0603,
        self::S0604,
        self::S0605,
        self::S0606
    ];

    const FINANCIAL_SERVICES_CODES = [
        self::P0701,
        self::P0702,
        self::P0703,
        self::S0701,
        self::S0702,
        self::S0703,
    ];

    const COMPUTER_AND_IT_CODES = [
        self::P0801,
        self::P0802,
        self::P0803,
        self::P0804,
        self::P0805,
        self::P0806,
        self::P0807,
        self::S0801,
        self::S0802,
        self::S0803,
        self::S0804,
        self::S0805,
        self::S0806,
    ];

    const ROYALTIES_AND_LICENSE_FEES_CODES = [
        self::P0901,
        self::P0902,
        self::S0901,
        self::S0902,
    ];

    const OTHER_BUSINESS_SERVICES_CODES = [
        self::P1001,
        self::P1002,
        self::P1003,
        self::P1004,
        self::P1005,
        self::P1006,
        self::P1007,
        self::P1008,
        self::P1009,
        self::P1010,
        self::P1011,
        self::P1012,
        self::P1013,
        self::P1019,
        self::S1001,
        self::S1002,
        self::S1003,
        self::S1004,
        self::S1005,
        self::S1006,
        self::S1007,
        self::S1008,
        self::S1009,
        self::S1010,
        self::S1011,
        self::S1012,
        self::S1013,
        self::S1019
    ];

    const PERSONAL_CULTURAL_RECREATIONAL_SERVICES_CODES = [
        self::P1101,
        self::P1102,
        self::P1107,
        self::P1109,
        self::S1101,
        self::S1102,
    ];

    const GNIE_CODES = [
        self::P1201,
        self::P1203,
        self::S1201,
        self::SI202,
    ];

    const TRANSFERS_CODES = [
        self::P1301,
        self::P1302,
        self::P1303,
        self::P1304,
        self::P1306,
        self::S1301,
        self::S1302,
        self::S1303,
        self::S1304,
        self::S1305,
        self::S1306,
    ];

    const INCOME_CODES = [
        self::P1401,
        self::P1403,
        self::P1404,
        self::P1405,
        self::P1406,
        self::P1407,
        self::S1401,
        self::S1402,
        self::S1403,
        self::S1404,
        self::S1405,
        self::S1406,
        self::S1407,
    ];

    const OTHER_CODES = [
        self::P1501,
        self::P1502,
        self::P1503,
        self::P1590,
        self::S1501,
        self::S1502,
        self::S1503,
        self::S1504,
    ];

    const IMPORT_CODES = [
        self::S0101,
        self::S0102,
        self::S0103,
        self::S0104,
        self::S0190,
    ];

    const IEC_REQUIRED = [
        self::P0103,
        self::P0807,
    ];

    public static function getPurposeCodeDescDescription($purposeCode): string
    {
        return self::$purposeCodeDescMappings[$purposeCode];
    }

    public static function getPurposeCode(): array
    {
        $data = array();

        $purposeGroupMapping = array(
            array(self::PURPOSEGROUP => self::CAPITAL_ACCOUNT, self::CODES => self::CAPITAL_ACCOUNT_CODES),
            array(self::PURPOSEGROUP => self::EXPORT_OF_GOODS, self::CODES => self::EXPORT_OF_GOODS_CODES),
            array(self::PURPOSEGROUP => self::TRANSPORTATION, self::CODES => self::TRANSPORTATION_CODES),
            array(self::PURPOSEGROUP => self::TRAVEL, self::CODES => self::TRAVEL_CODES),
            array(self::PURPOSEGROUP => self::COMMUNICATION_SERVICE, self::CODES => self::COMMUNICATION_SERVICES_CODES),
            array(self::PURPOSEGROUP => self::CONSTRUCTION_SERVICE, self::CODES => self::CONSTRUCTION_SERVICES_CODES),
            array(self::PURPOSEGROUP => self::INSURANCE_AND_PENSION_SERVICE, self::CODES => self::INSURANCE_SERVICES_CODES),
            array(self::PURPOSEGROUP => self::FINANCIAL_SERVICES, self::CODES => self::FINANCIAL_SERVICES_CODES),
            array(self::PURPOSEGROUP => self::COMPUTER_AND_IT_SERVICES, self::CODES => self::COMPUTER_AND_IT_CODES),
            array(self::PURPOSEGROUP => self::ROYALTIES_LICENSE_FEES, self::CODES => self::ROYALTIES_AND_LICENSE_FEES_CODES),
            array(self::PURPOSEGROUP => self::OTHER_BUSINESS_SERVICES, self::CODES => self::OTHER_BUSINESS_SERVICES_CODES),
            array(self::PURPOSEGROUP => self::PERSONAL_CULTURAL_RECREATIONAL_SERVICES, self::CODES => self::PERSONAL_CULTURAL_RECREATIONAL_SERVICES_CODES),
            array(self::PURPOSEGROUP => self::GNIE, self::CODES => self::GNIE_CODES),
            array(self::PURPOSEGROUP => self::TRANSFERS, self::CODES => self::TRANSFERS_CODES),
            array(self::PURPOSEGROUP => self::INCOME, self::CODES => self::INCOME_CODES),
            array(self::PURPOSEGROUP => self::OTHERS, self::CODES => self::OTHER_CODES),
            array(self::PURPOSEGROUP => self::IMPORTS, self::CODES => self::IMPORT_CODES));

        foreach ($purposeGroupMapping as $purposeCodeDtl) {
            array_push($data, PurposeCodeList::getPurposeGroupDetails($purposeCodeDtl[self::PURPOSEGROUP], $purposeCodeDtl[self::CODES]));
        }

        return $data;
    }

    public static function getPurposeGroupDetails($purposeGroup, $codes): array
    {
        $data = array(
            self::PURPOSEGROUP => $purposeGroup,
            self::CODES => array()
        );

        foreach ($codes as $code) {
            $data[self::CODES][] = array(
                self::PURPOSECODE => $code,
                self::DESCRIPTION => self::$purposeCodeDescMappings[$code],
            );
        }

        return $data;
    }
}
