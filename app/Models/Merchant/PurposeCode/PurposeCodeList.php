<?php

namespace RZP\Models\Merchant\PurposeCode;

class PurposeCodeList
{
    const CODES = "codes";
    const PURPOSECODE = "purposeCode";
    const DESCRIPTION = "description";
    const PURPOSEGROUP = "purposeGroup";

    //purpose code categories
    const TRAVEL = "Travel";
    const OTHERS = "Others";
    const TRANSPORT = "Transport";
    const PRIMARY_INCOME = "Primary Income";
    const SHORT_TERM = "Short term credits";
    const CAPITAL_ACCOUNT = "Capital Account";
    const BANKING_CAPITAL = "Banking Capital";
    const SECONDARY_INCOME = 'Secondary Income';
    const EXPORT_OF_GOODS = "Exports (of Goods)";
    const FINANCIAL_SERVICES = "Financial Services";
    const OTHER_SERVICES = "Other Business Services";
    const EXTERNAL_ASSISTANCE = "External Assistance";
    const INSURANCE = "Insurance and Pension Services";
    const FOREIGN_DIRECT = "Foreign Direct Investment ";
    const CONSTRUCTION_SERVICES = "Construction Services";
    const GNIE = "Govt. not included elsewhere (G.n.i.e.)";
    const MANUFACTURING_SERVICES = "Manufacturing services";
    const FOREIGN_PORTFOLIO = "Foreign Portfolio Investment";
    const MAINTENANCE = "Maintenance and repair services n.i.e";
    const PERSONAL = "Personal, Cultural & Recreational services";
    const COMMERCIAL_BORROWINGS = "External Commercial Borrowings";
    const FINANCIAL_DERIVATIVES = "Financial Derivatives and Others ";
    const INTELLECTUAL = "Charges for the use of intellectual property n.i.e ";
    const TELECOMMUNICATION = "Telecommunication, Computer & Information Services";

    //purpose code
    const P0103 = 'P0103';
    const P0802 = 'P0802';
    const P0807 = 'P0807';
    const P1004 = 'P1004';
    const P1005 = 'P1005';
    const P1006 = 'P1006';
    const P1007 = 'P1007';
    const P1008 = 'P1008';
    const P1009 = 'P1009';
    const P1002 = 'P1002';
    const P1019 = 'P1019';
    const P0301 = 'P0301';
    const P0801 = 'P0801';
    const P0803 = 'P0803';
    const P0804 = 'P0804';
    const P0805 = 'P0805';
    const P0806 = 'P0806';
    const P1013 = 'P1013';
    const P1101 = 'P1101';
    const P1020 = 'P1020';
    const P1104 = 'P1104';
    const P1099 = 'P1099';
    const P1015 = 'P1015';
    const P1016 = 'P1016';
    const P1107 = 'P1107';
    const P1109 = 'P1109';
    const P1701 = 'P1701';
    const P0302 = 'P0302';
    const P0304 = 'P0304';
    const P0305 = 'P0305';
    const P0306 = 'P0306';
    const P0808 = 'P0808';
    const P1014 = 'P1014';
    const P1105 = 'P1105';
    const P1106 = 'P1106';
    const P1108 = 'P1108';
    const P0017 = 'P0017';
    const P0019 = 'P0019';
    const P0028 = 'P0028';
    const P0029 = 'P0029';
    const P0099 = 'P0099';
    const P1601 = 'P1601';
    const P1602 = 'P1602';
    const P1501 = 'P1501';
    const P1502 = 'P1502';
    const P1503 = 'P1503';
    const P1505 = 'P1505';
    const P1103 = 'P1103';
    const P1401 = 'P1401';
    const P1403 = 'P1403';
    const P1405 = 'P1405';
    const P1408 = 'P1408';
    const P1409 = 'P1409';
    const P1410 = 'P1410';
    const P1411 = 'P1411';
    const P1412 = 'P1412';
    const P1499 = 'P1499';
    const P1301 = 'P1301';
    const P1302 = 'P1302';
    const P1303 = 'P1303';
    const P1304 = 'P1304';
    const P1306 = 'P1306';
    const P1307 = 'P1307';
    const P1201 = 'P1201';
    const P1203 = 'P1203';
    const P1003 = 'P1003';
    const P1010 = 'P1010';
    const P1011 = 'P1011';
    const P1017 = 'P1017';
    const P1018 = 'P1018';
    const P1021 = 'P1021';
    const P1022 = 'P1022';
    const P0901 = 'P0901';
    const P0902 = 'P0902';
    const P0809 = 'P0809';
    const P0701 = 'P0701';
    const P0702 = 'P0702';
    const P0703 = 'P0703';
    const P0601 = 'P0601';
    const P0602 = 'P0602';
    const P0603 = 'P0603';
    const P0605 = 'P0605';
    const P0607 = 'P0607';
    const P0608 = 'P0608';
    const P0609 = 'P0609';
    const P0610 = 'P0610';
    const P0611 = 'P0611';
    const P0612 = 'P0612';
    const P0501 = 'P0501';
    const P0502 = 'P0502';
    const P0308 = 'P0308';
    const P0201 = 'P0201';
    const P0202 = 'P0202';
    const P0205 = 'P0205';
    const P0207 = 'P0207';
    const P0208 = 'P0208';
    const P0211 = 'P0211';
    const P0214 = 'P0214';
    const P0215 = 'P0215';
    const P0216 = 'P0216';
    const P0217 = 'P0217';
    const P0218 = 'P0218';
    const P0219 = 'P0219';
    const P0220 = 'P0220';
    const P0221 = 'P0221';
    const P0222 = 'P0222';
    const P0223 = 'P0223';
    const P0224 = 'P0224';
    const P0225 = 'P0225';
    const P0226 = 'P0226';
    const P0101 = 'P0101';
    const P0102 = 'P0102';
    const P0104 = 'P0104';
    const P0105 = 'P0105';
    const P0107 = 'P0107';
    const P0108 = 'P0108';
    const P0109 = 'P0109';
    const P0014 = 'P0014';
    const P0015 = 'P0015';
    const P0016 = 'P0016';
    const P0020 = 'P0020';
    const P0021 = 'P0021';
    const P0022 = 'P0022';
    const P0024 = 'P0024';
    const P0025 = 'P0025';
    const P0013 = 'P0013';
    const P0011 = 'P0011';
    const P0012 = 'P0012';
    const P0001 = 'P0001';
    const P0002 = 'P0002';
    const P0009 = 'P0009';
    const P0010 = 'P0010';
    const P0003 = 'P0003';
    const P0004 = 'P0004';
    const P0005 = 'P0005';
    const P0006 = 'P0006';
    const P0007 = 'P0007';
    const P0008 = 'P0008';
    const S1023 = 'S1023';


    //purpose code descriptions
    const P0103_DESC = 'Advance receipts against export contracts, which will be covered later by GR/PP/SOFTEX/SDF';
    const P0802_DESC = 'Software consultancy/implementation (other than those covered in SOFTEX form)';
    const P0807_DESC = 'Off site Software Exports';
    const P1004_DESC = 'Legal services';
    const P1005_DESC = 'Accounting, auditing, book keeping and tax consulting services';
    const P1006_DESC = 'Business and management consultancy and public relations services';
    const P1007_DESC = 'Advertising, trade fair, market research and public opinion polling services';
    const P1008_DESC = 'Research & Development services';
    const P1009_DESC = 'Architectural, engineering and other technical services';
    const P1002_DESC = 'Trade related services – Commission on exports/imports.';
    const P1019_DESC = 'Other services not included elsewhere';
    const P0301_DESC = 'Purchases towards travel (Includes purchases of foreign TCs, currency notes etc over the counter, by hotels, hospitals, Emporiums, Educational institutions etc. as well as amount received by TT/SWIFT transfers or debit to Non-Resident account).';
    const P0801_DESC = 'Hardware consultancy/implementation';
    const P0803_DESC = 'Data base, data processing charges';
    const P0804_DESC = 'Repair and maintenance of computer and software';
    const P0805_DESC = 'News agency services';
    const P0806_DESC = 'Other information services- Subscription to newspapers, periodicals, etc.';
    const P1013_DESC = 'Environmental Services';
    const P1101_DESC = 'Audio-visual and related services – services and associated fees related to production of motion pictures, rentals, fees received by actors, directors, producers and fees for distribution rights.';
    const P1020_DESC = 'Wholesale and retailing trade services.';
    const P1104_DESC = 'Entertainment services';
    const P1099_DESC = 'Other services not included elsewhere';
    const P1015_DESC = 'Tax consulting services';
    const P1016_DESC = 'Market research and public opinion polling service';
    const P1107_DESC = 'Educational services (e.g. fees received for correspondence courses offered to non-resident by Indian institutions)';
    const P1109_DESC = 'Other Personal, Cultural & Recreational services';
    const P1701_DESC = 'Receipts on account of processing of goods';
    const P0302_DESC = 'Business travel';
    const P0304_DESC = 'Travel for medical treatment including TCs purchased by hospitals';
    const P0305_DESC = 'Travel for education including TCs purchased by educational institutions';
    const P0306_DESC = 'Other travel receipts';
    const P0808_DESC = 'Telecommunication services including electronic mail services and voice mail services';
    const P1014_DESC = 'Engineering Services';
    const P1105_DESC = 'Museums, library and archival services';
    const P1106_DESC = 'Recreation and sporting activity services';
    const P1108_DESC = 'Health Service (Receipts on account of services provided by Indian hospitals, doctors, nurses, paramedical and similar services etc.rendered remotely or on-site) ';
    const P0017_DESC = 'Receipts on account of Sale of non-produced non-financial assets (Sale of intangible assets like patents, copyrights, trademarks etc.,land acquired by government, use of natural resources) –Government ';
    const P0019_DESC = 'Receipts on account of Sale of non-produced non-financial assets(Sale of intangible assets like patents, copyrights, trademarks etc.,use of natural resources) – Non-Government ';
    const P0028_DESC = 'Capital transfer receipts (Guarantee payments, Investment Grant given by the government/international organisation, exceptionally large Non-life insurance claims including claims arising out of natural calamity) - Government';
    const P0029_DESC = 'Capital transfer receipts ( Guarantee payments, Investment Grant given by the Non-government, exceptionally large Non-life insurance claims including claims arising out of natural calamity) – Non-Government';
    const P0099_DESC = 'Other capital receipts not included elsewhere ';
    const P1601_DESC = 'Receipts on account of maintenance and repair services rendered for Vessels, Ships, Boats, Warships, etc.';
    const P1602_DESC = 'Receipts of maintenance and repair services rendered for aircrafts, Space shuttles, Rockets, military aircrafts, etc';
    const P1501_DESC = 'Refunds / rebates on account of imports';
    const P1502_DESC = 'Reversal of wrong entries, refunds of amount remitted for nonimports';
    const P1503_DESC = 'Remittances (receipts) by residents under international bidding process.';
    const P1505_DESC = 'Deemed Exports ( exports between SEZ, EPZs and Domestic Tariff Areas)';
    const P1103_DESC = 'Radio and television production, distribution and transmission services';
    const P1401_DESC = 'Compensation of employees';
    const P1403_DESC = 'Inward remittance towards interest on loans extended to nonresidents (ST/MT/LT loans)';
    const P1405_DESC = 'Inward remittance towards interest receipts of ADs on their own account (on investments.)';
    const P1408_DESC = 'Inward remittance of profit by branches of Indian FDI Enterprises(including bank branches) operating abroad. ';
    const P1409_DESC = 'Inward remittance of dividends (on equity and investment fund shares) by Indian FDI Enterprises, other than branches, operating abroad ';
    const P1410_DESC = 'Inward remittance on account of interest payment by Indian FDI enterprises operating abroad to their Parent company in India. ';
    const P1411_DESC = 'Inward remittance of interest income on account of Portfolio Investment made abroad by India ';
    const P1412_DESC = 'Inward remittance of dividends on account of Portfolio Investment made abroad by India on equity and investment fund shares ';
    const P1499_DESC = 'Other income receipts';
    const P1301_DESC = 'Inward remittance from Indian non-residents towards family maintenance and savings ';
    const P1302_DESC = 'Personal gifts and donations ';
    const P1303_DESC = 'Donations to religious and charitable institutions in India';
    const P1304_DESC = 'Grants and donations to governments and charitable institutions established by the governments ';
    const P1306_DESC = 'Receipts / Refund of taxes';
    const P1307_DESC = 'Receipts on account of migrant transfers including Personal Effects';
    const P1201_DESC = 'Maintenance of foreign embassies in India';
    const P1203_DESC = 'Maintenance of international institutions such as offices of IMF mission, World Bank, UNICEF etc. in India';
    const P1003_DESC = 'Operational leasing services (other than financial leasing) without operating crew, including charter hire- Airlines companies ';
    const P1010_DESC = 'Agricultural services like protection against insects & disease,increasing of harvest yields, forestry services.';
    const P1011_DESC = 'Inward remittance for maintenance of offices in India';
    const P1017_DESC = 'Publishing and printing services';
    const P1018_DESC = 'Mining services like on–site processing services analysis of ores etc.';
    const P1021_DESC = 'Operational leasing services (other than financial leasing) without operating crew, including charter hire- Shipping companies ';
    const P1022_DESC = 'Other Technical Services including scientific/space services.';
    const P0901_DESC = 'Franchises services';
    const P0902_DESC = 'Receipts for use, through licensing arrangements, of produced originals or prototypes (such as manuscripts and films), patents,copyrights, trademarks, industrial processes, franchises etc. ';
    const P0809_DESC = 'Satellite services including space shuttle and rockets, etc.';
    const P0701_DESC = 'Financial intermediation except investment banking – Bank charges,collection charges, LC charges, etc. ';
    const P0702_DESC = 'Investment banking – brokerage, under writing commission etc.';
    const P0703_DESC = 'Auxiliary services – charges on operation & regulatory fees, custodial services, depository services etc.';
    const P0601_DESC = 'Life Insurance premium except term insurance';
    const P0602_DESC = 'Freight insurance – relating to import & export of goods ';
    const P0603_DESC = 'Other general insurance premium including reinsurance premium;and term life insurance premium';
    const P0605_DESC = 'Auxiliary services including commission on insurance ';
    const P0607_DESC = 'Insurance claim Settlement of non-life insurance; and life insurance (only term insurance)';
    const P0608_DESC = 'Life insurance claim settlements (excluding term insurance) received by residents in India';
    const P0609_DESC = 'Standardised guarantee services ';
    const P0610_DESC = 'Premium for pension funds';
    const P0611_DESC = 'Periodic pension entitlements e.g. monthly quarterly or yearly payments of pension amounts by Indian Pension Fund Companies.';
    const P0612_DESC = 'Invoking of standardised guarantees';
    const P0501_DESC = 'Receipts on account of services relating to cost of construction of projects in India ';
    const P0502_DESC = 'Receipts on account of construction works carried out abroad by Indian Companies';
    const P0308_DESC = 'Foreign Currencies/TCs surrendered by returning Indian tourists';
    const P0201_DESC = 'Receipts of surplus freight/passenger fare by Indian shipping companies operating abroad';
    const P0202_DESC = 'Receipts on account of operating expenses of Foreign shipping companies operating in India';
    const P0205_DESC = 'Receipts on account of operational leasing (with crew) – Shipping companies';
    const P0207_DESC = 'Receipts of surplus freight/passenger fare by Indian Airlines companies operating abroad. ';
    const P0208_DESC = 'Receipt on account of operating expenses of Foreign Airlines companies operating in India ';
    const P0211_DESC = 'Receipt on account of operational leasing (with crew) – Airlines companies ';
    const P0214_DESC = 'Receipts on account of other transportation services (stevedoring,demurrage, port handling charges etc).(Shipping Companies)  ';
    const P0215_DESC = 'Receipts on account of other transportation services (stevedoring, demurrage, port handling charges etc).( Airlines companies) ';
    const P0216_DESC = 'Receipts of freight fare -Shipping companies operating abroad ';
    const P0217_DESC = 'Receipts of passenger fare by Indian Shipping companies operating abroad ';
    const P0218_DESC = 'Other receipts by Shipping companies';
    const P0219_DESC = 'Receipts of freight fare by Indian Airlines companies operating abroad ';
    const P0220_DESC = 'Receipts of passenger fare –Airlines ';
    const P0221_DESC = 'Other receipts by Airlines companies ';
    const P0222_DESC = 'Receipts on account of freights under other modes of transport(Internal Waterways, Roadways, Railways, Pipeline transports and Others) ';
    const P0223_DESC = 'Receipts on account of passenger fare under other modes of transport (Internal Waterways, Roadways, Railways, Pipeline transports and Others) ';
    const P0224_DESC = 'Postal & Courier services by Air';
    const P0225_DESC = 'Postal & Courier services by Sea ';
    const P0226_DESC = 'Postal & Courier services by others';
    const P0101_DESC = 'Value of export bills negotiated / purchased/discounted etc.(covered under GR/PP/SOFTEX/EC copy of shipping bills etc.) –Other than Nepal and Bhutan ';
    const P0102_DESC = 'Realisation of export bills (in respect of goods) sent on collection(full invoice value) – Other than Nepal and Bhutan ';
    const P0104_DESC = 'Receipts against export of goods not covered by the GR /PP/SOFTEX /EC copy of shipping bill etc. (under Intermediary/transit trade, i.e., third country export passing through India';
    const P0105_DESC = 'Export bills (in respect of goods) sent on collection – other than Nepal and Bhutan ';
    const P0107_DESC = 'Realisation of NPD export bills (full value of bill to be reported) – other than Nepal and Bhutan';
    const P0108_DESC = 'Goods sold under merchanting / Receipt against export leg of merchanting trade';
    const P0109_DESC = 'Export realisation on account of exports to Nepal and Bhutan, if any';
    const P0014_DESC = 'Receipts o/a Non-Resident deposits (FCNR(B)/NR(E)RA, etc.){ADs should report these even if funds are not “swapped” into Rupees}';
    const P0015_DESC = 'Loans & overdrafts taken by ADs on their own account. (Any amount of loan credited to the NOSTRO account which may not be swapped into Rupees should also be reported)';
    const P0016_DESC = 'Purchase of a foreign currency against another currency.';
    const P0020_DESC = 'Receipts on account of margin payments, premium payment and settlement amount etc. under Financial derivative transactions ';
    const P0021_DESC = 'Receipts on account of sale of share under Employee stock option';
    const P0022_DESC = 'Receipts on account of other investment in ADRs/GDRs ';
    const P0024_DESC = 'External Assistance received by India e.g. Multilateral and bilateral loans received by Govt. of India under agreements with other govt. / international institutions.';
    const P0025_DESC = 'Repayments received on account of External Assistance extended by India ';
    const P0013_DESC = 'Short term loans with original maturity upto one year from NonResidents to India (Short-term Trade Credit) ';
    const P0011_DESC = 'Repayment of loans extended to Non-Residents';
    const P0012_DESC = 'Long & medium term loans, with original maturity of above one year, from Non-Residents to India (External Commercial Borrowings) ';
    const P0001_DESC = "Repatriation of Indian Portfolio investment abroad in equity capital (shares)";
    const P0002_DESC = "Repatriation of Indian Portfolio investment abroad in debt instruments. ";
    const P0009_DESC = "Foreign Portfolio Investment made by overseas Investors in India in equity shares ";
    const P0010_DESC = "Foreign Portfolio Investment made by overseas Investors in India in debt Instruments.";
    const P0003_DESC = "Repatriation of Indian Direct investment abroad (by branches & wholly owned subsidiaries and associates) in equity shares ";
    const P0004_DESC = "Repatriation Indian Direct investment abroad (by branches & wholly owned subsidiaries and associates) in debt instruments";
    const P0005_DESC = "Repatriation of Indian investment abroad in real estate";
    const P0006_DESC = "Foreign Direct Investment made by overseas Investors in India in equity shares";
    const P0007_DESC = "Foreign Direct Investment made by overseas Investors in India in debt instruments.";
    const P0008_DESC = "Foreign Direct Investment made by overseas Investors in India in real estate";
    const S1023_DESC = "Other Technical Services including scientific/space services";

    //purpose code mapping
    protected static $purposeCodeDescMappings = [
        self::P0103 => self::P0103_DESC,
        self::P0802 => self::P0802_DESC,
        self::P0807 => self::P0807_DESC,
        self::P1004 => self::P1004_DESC,
        self::P1005 => self::P1005_DESC,
        self::P1006 => self::P1006_DESC,
        self::P1007 => self::P1007_DESC,
        self::P1008 => self::P1008_DESC,
        self::P1009 => self::P1009_DESC,
        self::P1002 => self::P1002_DESC,
        self::P1019 => self::P1019_DESC,
        self::P0301 => self::P0301_DESC,
        self::P0801 => self::P0801_DESC,
        self::P0803 => self::P0803_DESC,
        self::P0804 => self::P0804_DESC,
        self::P0805 => self::P0805_DESC,
        self::P0806 => self::P0806_DESC,
        self::P1013 => self::P1013_DESC,
        self::P1101 => self::P1101_DESC,
        self::P1020 => self::P1020_DESC,
        self::P1104 => self::P1104_DESC,
        self::P1099 => self::P1099_DESC,
        self::P1015 => self::P1015_DESC,
        self::P1016 => self::P1016_DESC,
        self::P1107 => self::P1107_DESC,
        self::P1109 => self::P1109_DESC,
        self::P1701 => self::P1701_DESC,
        self::P0302 => self::P0302_DESC,
        self::P0304 => self::P0304_DESC,
        self::P0305 => self::P0305_DESC,
        self::P0306 => self::P0306_DESC,
        self::P0808 => self::P0808_DESC,
        self::P1014 => self::P1014_DESC,
        self::P1105 => self::P1105_DESC,
        self::P1106 => self::P1106_DESC,
        self::P1108 => self::P1108_DESC,
        self::P0017 => self::P0017_DESC,
        self::P0019 => self::P0019_DESC,
        self::P0028 => self::P0028_DESC,
        self::P0029 => self::P0029_DESC,
        self::P0099 => self::P0099_DESC,
        self::P1601 => self::P1601_DESC,
        self::P1602 => self::P1602_DESC,
        self::P1501 => self::P1501_DESC,
        self::P1502 => self::P1502_DESC,
        self::P1503 => self::P1503_DESC,
        self::P1505 => self::P1505_DESC,
        self::P1103 => self::P1103_DESC,
        self::P1401 => self::P1401_DESC,
        self::P1403 => self::P1403_DESC,
        self::P1405 => self::P1405_DESC,
        self::P1408 => self::P1408_DESC,
        self::P1409 => self::P1409_DESC,
        self::P1410 => self::P1410_DESC,
        self::P1411 => self::P1411_DESC,
        self::P1412 => self::P1412_DESC,
        self::P1499 => self::P1499_DESC,
        self::P1301 => self::P1301_DESC,
        self::P1302 => self::P1302_DESC,
        self::P1303 => self::P1303_DESC,
        self::P1304 => self::P1304_DESC,
        self::P1306 => self::P1306_DESC,
        self::P1307 => self::P1307_DESC,
        self::P1201 => self::P1201_DESC,
        self::P1203 => self::P1203_DESC,
        self::P1003 => self::P1003_DESC,
        self::P1010 => self::P1010_DESC,
        self::P1011 => self::P1011_DESC,
        self::P1017 => self::P1017_DESC,
        self::P1018 => self::P1018_DESC,
        self::P1021 => self::P1021_DESC,
        self::P1022 => self::P1022_DESC,
        self::P0901 => self::P0901_DESC,
        self::P0902 => self::P0902_DESC,
        self::P0809 => self::P0809_DESC,
        self::P0701 => self::P0701_DESC,
        self::P0702 => self::P0702_DESC,
        self::P0703 => self::P0703_DESC,
        self::P0601 => self::P0601_DESC,
        self::P0602 => self::P0602_DESC,
        self::P0603 => self::P0603_DESC,
        self::P0605 => self::P0605_DESC,
        self::P0607 => self::P0607_DESC,
        self::P0608 => self::P0608_DESC,
        self::P0609 => self::P0609_DESC,
        self::P0610 => self::P0610_DESC,
        self::P0611 => self::P0611_DESC,
        self::P0612 => self::P0612_DESC,
        self::P0501 => self::P0501_DESC,
        self::P0502 => self::P0502_DESC,
        self::P0308 => self::P0308_DESC,
        self::P0201 => self::P0201_DESC,
        self::P0202 => self::P0202_DESC,
        self::P0205 => self::P0205_DESC,
        self::P0207 => self::P0207_DESC,
        self::P0208 => self::P0208_DESC,
        self::P0211 => self::P0211_DESC,
        self::P0214 => self::P0214_DESC,
        self::P0215 => self::P0215_DESC,
        self::P0216 => self::P0216_DESC,
        self::P0217 => self::P0217_DESC,
        self::P0218 => self::P0218_DESC,
        self::P0219 => self::P0219_DESC,
        self::P0220 => self::P0220_DESC,
        self::P0221 => self::P0221_DESC,
        self::P0222 => self::P0222_DESC,
        self::P0223 => self::P0223_DESC,
        self::P0224 => self::P0224_DESC,
        self::P0225 => self::P0225_DESC,
        self::P0226 => self::P0226_DESC,
        self::P0101 => self::P0101_DESC,
        self::P0102 => self::P0102_DESC,
        self::P0104 => self::P0104_DESC,
        self::P0105 => self::P0105_DESC,
        self::P0107 => self::P0107_DESC,
        self::P0108 => self::P0108_DESC,
        self::P0109 => self::P0109_DESC,
        self::P0014 => self::P0014_DESC,
        self::P0015 => self::P0015_DESC,
        self::P0016 => self::P0016_DESC,
        self::P0020 => self::P0020_DESC,
        self::P0021 => self::P0021_DESC,
        self::P0022 => self::P0022_DESC,
        self::P0024 => self::P0024_DESC,
        self::P0025 => self::P0025_DESC,
        self::P0013 => self::P0013_DESC,
        self::P0011 => self::P0011_DESC,
        self::P0012 => self::P0012_DESC,
        self::P0001 => self::P0001_DESC,
        self::P0002 => self::P0002_DESC,
        self::P0009 => self::P0009_DESC,
        self::P0010 => self::P0010_DESC,
        self::P0003 => self::P0003_DESC,
        self::P0004 => self::P0004_DESC,
        self::P0005 => self::P0005_DESC,
        self::P0006 => self::P0006_DESC,
        self::P0007 => self::P0007_DESC,
        self::P0008 => self::P0008_DESC,
        self::S1023 => self::S1023_DESC,
    ];

    //purpose category and their code mapping
    const SHORT_TERM_CODES = [
        self::P0013,
    ];

    const COMMERCIAL_BORROWINGS_CODES = [
        self::P0011,
        self::P0012,
    ];

    const FOREIGN_PORTFOLIO_CODES = [
        self::P0001,
        self::P0002,
        self::P0009,
        self::P0010,
    ];

    const FOREIGN_DIRECT_CODES = [
        self::P0003,
        self::P0004,
        self::P0005,
        self::P0006,
        self::P0007,
        self::P0008,
    ];
    const EXPORT_OF_GOODS_CODES = [
        self::P0103,
        self::P0101,
        self::P0102,
        self::P0104,
        self::P0105,
        self::P0107,
        self::P0108,
        self::P0109,
    ];

    const MANUFACTURING_SERVICES_CODES = [
        self::P1701,
    ];

    const TELECOMMUNICATION_CODES = [
        self::P0801,
        self::P0802,
        self::P0803,
        self::P0804,
        self::P0805,
        self::P0806,
        self::P0807,
        self::P0808,
        self::P0809,
    ];

    const TRAVEL_CODES = [
        self::P0301,
        self::P0302,
        self::P0304,
        self::P0305,
        self::P0306,
        self::P0308,
    ];

    const PERSONAL_CODES = [
        self::P1101,
        self::P1103,
        self::P1104,
        self::P1107,
        self::P1109,
        self::P1105,
        self::P1106,
        self::P1108,
    ];

    const OTHER_SERVICES_CODES = [
        self::P1002,
        self::P1004,
        self::P1005,
        self::P1006,
        self::P1007,
        self::P1008,
        self::P1009,
        self::P1013,
        self::P1019,
        self::P1020,
        self::P1099,
        self::P1015,
        self::P1016,
        self::P1014,
        self::P1003,
        self::P1010,
        self::P1011,
        self::P1017,
        self::P1018,
        self::P1021,
        self::P1022,
        self::S1023,
    ];

    const CAPITAL_ACCOUNT_CODES = [
        self::P0017,
        self::P0019,
        self::P0028,
        self::P0029,
        self::P0099,
    ];

    const MAINTENANCE_CODES = [
        self::P1601,
        self::P1602,
    ];

    const OTHERS_CODES = [
        self::P1501,
        self::P1502,
        self::P1503,
        self::P1505,
    ];

    const PRIMARY_INCOME_CODES = [
        self::P1401,
        self::P1403,
        self::P1405,
        self::P1408,
        self::P1409,
        self::P1410,
        self::P1411,
        self::P1412,
        self::P1499,
    ];

    const SECONDARY_INCOME_CODES = [
        self::P1301,
        self::P1302,
        self::P1303,
        self::P1304,
        self::P1306,
        self::P1307,
    ];

    const GNIE_CODES = [
        self::P1201,
        self::P1203,
    ];

    const INTELLECTUAL_CODES = [
        self::P0901,
        self::P0902,
    ];

    const FINANCIAL_SERVICES_CODES = [
        self::P0701,
        self::P0702,
        self::P0703,
    ];

    const INSURANCE_CODES = [
        self::P0601,
        self::P0602,
        self::P0603,
        self::P0605,
        self::P0607,
        self::P0608,
        self::P0609,
        self::P0610,
        self::P0611,
        self::P0612,
    ];

    const CONSTRUCTION_SERVICES_CODES = [
        self::P0501,
        self::P0502,
    ];

    const TRANSPORT_CODES = [
        self::P0201,
        self::P0202,
        self::P0205,
        self::P0207,
        self::P0208,
        self::P0211,
        self::P0214,
        self::P0215,
        self::P0216,
        self::P0217,
        self::P0218,
        self::P0219,
        self::P0220,
        self::P0221,
        self::P0222,
        self::P0223,
        self::P0224,
        self::P0225,
        self::P0226,
    ];

    const BANKING_CAPITAL_CODES = [
        self::P0014,
        self::P0015,
        self::P0016,
    ];

    const FINANCIAL_DERIVATIVES_CODES = [
        self::P0022,
        self::P0021,
        self::P0020,
    ];

    const EXTERNAL_ASSISTANCE_CODES = [
        self::P0024,
        self::P0025,
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
            array(self::PURPOSEGROUP => self::CAPITAL_ACCOUNT,
                self::CODES => self::CAPITAL_ACCOUNT_CODES
            ),
            array(self::PURPOSEGROUP => self::FOREIGN_DIRECT,
                self::CODES => self::FOREIGN_DIRECT_CODES
            ),
            array(self::PURPOSEGROUP => self::FOREIGN_PORTFOLIO,
                self::CODES => self::FOREIGN_PORTFOLIO_CODES
            ),
            array(self::PURPOSEGROUP => self::COMMERCIAL_BORROWINGS,
                self::CODES => self::COMMERCIAL_BORROWINGS_CODES
            ),
            array(self::PURPOSEGROUP => self::SHORT_TERM,
                self::CODES => self::SHORT_TERM_CODES
            ),
            array(self::PURPOSEGROUP => self::BANKING_CAPITAL,
                self::CODES => self::BANKING_CAPITAL_CODES
            ),
            array(self::PURPOSEGROUP => self::FINANCIAL_DERIVATIVES,
                self::CODES => self::FINANCIAL_DERIVATIVES_CODES
            ),
            array(self::PURPOSEGROUP => self::EXTERNAL_ASSISTANCE,
                self::CODES => self::EXTERNAL_ASSISTANCE_CODES
            ),
            array(self::PURPOSEGROUP => self::EXPORT_OF_GOODS,
                self::CODES => self::EXPORT_OF_GOODS_CODES
            ),
            array(self::PURPOSEGROUP => self::TRANSPORT,
                self::CODES => self::TRANSPORT_CODES
            ),
            array(self::PURPOSEGROUP => self::TRAVEL,
                self::CODES => self::TRAVEL_CODES
            ),
            array(self::PURPOSEGROUP => self::CONSTRUCTION_SERVICES,
                self::CODES => self::CONSTRUCTION_SERVICES_CODES
            ),
            array(self::PURPOSEGROUP => self::INSURANCE,
                self::CODES => self::INSURANCE_CODES
            ),
            array(self::PURPOSEGROUP => self::FINANCIAL_SERVICES,
                self::CODES => self::FINANCIAL_SERVICES_CODES
            ),
            array(self::PURPOSEGROUP => self::TELECOMMUNICATION,
                self::CODES => self::TELECOMMUNICATION_CODES
            ),
            array(self::PURPOSEGROUP => self::INTELLECTUAL,
                self::CODES => self::INTELLECTUAL_CODES
            ),
            array(self::PURPOSEGROUP => self::OTHER_SERVICES,
                self::CODES => self::OTHER_SERVICES_CODES
            ),
            array(self::PURPOSEGROUP => self::PERSONAL,
                self::CODES => self::PERSONAL_CODES
            ),
            array(self::PURPOSEGROUP => self::GNIE,
                self::CODES => self::GNIE_CODES
            ),
            array(self::PURPOSEGROUP => self::SECONDARY_INCOME,
                self::CODES => self::SECONDARY_INCOME_CODES
            ),
            array(self::PURPOSEGROUP => self::PRIMARY_INCOME,
                self::CODES => self::PRIMARY_INCOME_CODES
            ),
            array(self::PURPOSEGROUP => self::OTHERS,
                self::CODES => self::OTHERS_CODES
            ),
            array(self::PURPOSEGROUP => self::MAINTENANCE,
                self::CODES => self::MAINTENANCE_CODES
            ),
            array(self::PURPOSEGROUP => self::MANUFACTURING_SERVICES,
                self::CODES => self::MANUFACTURING_SERVICES_CODES
            ));

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
