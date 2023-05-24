export const PROPRIETORSHIP = 1;
export const INDIVIDUAL = 2;
export const PARTNERSHIP = 3;
export const PRIVATE = 4; // 'Private Limited',
export const PUBLIC = 5; // 'Public Limited',
export const LLP = 6; // 'LLP'
export const NGO = 7; // 'NGO'
export const EDUCATIONAL_INSTITUTE = 8;
export const TRUST = 9; // 'Trust'
export const SOCIETY = 10; // 'Society'
export const NOT_REGISTERED = 11; // 'Unregistered Businesses
export const OTHER = 12;
export const HUF = 13;

export const CIN_BusinessTypes = [PRIVATE, PUBLIC];
export const LLPIN_BusinessTypes = [LLP];
export const ORG_BusinessTypes = [NGO, TRUST, SOCIETY];

export const UNREGISTERED_TYPES = {
  11: true,
  2: true,
};

export const BusinessTypes = {
  [PROPRIETORSHIP]: 'Proprietorship',
  [INDIVIDUAL]: 'Individual',
  [PARTNERSHIP]: 'Partnership',
  [PRIVATE]: 'Private Limited',
  [PUBLIC]: 'Public Limited',
  [LLP]: 'LLP',
  [NGO]: 'NGO',
  [EDUCATIONAL_INSTITUTE]: 'Educational Institutes',
  [TRUST]: 'Trust',
  [SOCIETY]: 'Society',
  [NOT_REGISTERED]: 'Not Registered',
  [OTHER]: 'Other',
  [HUF]: 'HUF',
};

export const BUSINESS_PROOF_CERTIFICATE_TYPES = {
  GST_CERTIFICATE: 'gst_certificate',
  SHOP_ESTABLISHMENT_CERTIFICATE: 'shop_establishment_certificate',
  MSME_CERTIFICATE: 'msme_certificate',
};

export const BUSINESS_PROOF_TYPE_DOCS = {
  [BUSINESS_PROOF_CERTIFICATE_TYPES.GST_CERTIFICATE]: 'GST Certificate',
  [BUSINESS_PROOF_CERTIFICATE_TYPES.MSME_CERTIFICATE]: 'Udyam certificate',
  [BUSINESS_PROOF_CERTIFICATE_TYPES.SHOP_ESTABLISHMENT_CERTIFICATE]:
    'Shop Establishment Act Certificate',
};

export const states = {
  AN: 'Andaman And Nicobar',
  AP: 'Andhra Pradesh',
  AR: 'Arunachal Pradesh',
  AS: 'Assam',
  BI: 'Bihar',
  CH: 'Chandigarh (UT)',
  CT: 'Chattisgarh',
  DN: 'Dadra And Nagar Haveli',
  DD: 'Daman And Diu (UT)',
  DL: 'Delhi',
  GO: 'Goa',
  GJ: 'Gujarat',
  HA: 'Haryana',
  HP: 'Himachal Pradesh',
  JK: 'Jammu And Kashmir',
  JH: 'Jharkhand',
  KA: 'Karnataka',
  KE: 'Kerala',
  LD: 'Lakshadweep',
  MP: 'Madhya Pradesh',
  MH: 'Maharashtra',
  MA: 'Manipur',
  ME: 'Meghalaya',
  MI: 'Mizoram',
  NA: 'Nagaland',
  OR: 'Orissa',
  PO: 'Pondicherry(UT)',
  PB: 'Punjab',
  RJ: 'Rajasthan',
  SK: 'Sikkim',
  TG: 'Telangana',
  TN: 'Tamilnadu',
  TR: 'Tripura',
  UP: 'Uttar Pradesh',
  UT: 'Uttranchal',
  WB: 'West Bengal',
};

export const ADDRESS_PROOF_TYPES = {
  aadhar: {
    value: 'aadhar',
    label: 'Aadhaar',
  },
  passport: {
    value: 'passport',
    label: 'Passport',
  },
  voter_id: {
    value: 'voter_id',
    label: 'Voter Id',
  },
};

export const BANK_PROOF_TYPE_DOC = {
  cancelled_cheque: {
    label: 'Canceled Cheque',
    value: 'cancelled_cheque',
  },
  bank_statement: {
    label: 'Bank Statement',
    value: 'bank_statement',
  },
};

export const ACCEPTED_DOCUMENT = ['pdf', 'image'];

// Business categories
const FINANCIAL_SERVICES = 'financial_services';
const TOURS_AND_TRAVELS = 'tours_and_travel';
const EDUCATION = 'education';

// Business subcategories Financial Services
const MUTUAL_FUND = 'mutual_fund';
const LENDING = 'lending';
const INSURANCE = 'insurance';
const NBFC = 'nbfc';
const FOREX = 'forex';
const SECURITIES = 'securities';
const COMMODITIES = 'commodities';
const FINANCIAL_ADVISOR = 'financial_advisor';
const TRADING = 'trading';

// Business subcategories Tours and Travels
const AVIATION = 'aviation';
const OTA = 'ota';
const TRAVEL_AGENCY = 'travel_agency';

// Business subcategories Education
const COLLEGE = 'college';
const SCHOOLS = 'schools';
const UNIVERSITY = 'university';

export const ADDITIONAL_DOCS = {
  AMFI_CERT: 'amfi_certificate',
  SLA_AMFI: 'sla_amfi_certificate',
  NBFC_CERT: 'nbfc_registration_certificate',
  SLA_NBFC: 'sla_nbfc_registration_certificate',
  IRDAI_CERT: 'irdai_registration_certificate',
  SLA_IRDAI: 'sla_irdai_registration_certificate',
  FFMC_LICENSE: 'ffmc_license',
  SLA_FFMC: 'sla_ffmc_license',
  SEBI_CERT: 'sebi_registration_certificate',
  SLA_SEBI: 'sla_sebi_registration_certificate',
  IATA_CERT: 'iata_certificate',
  SLA_IATA: 'sla_iata_certificate',
  AFFILIATION_CERT: 'affiliation_certificate',
};

// Map for businesses which has additional docs filed
export const ADDITIONAL_DOCS_REQUIRED_REG_BIZ = {
  [`${FINANCIAL_SERVICES}-${MUTUAL_FUND}`]: true,
  [`${FINANCIAL_SERVICES}-${LENDING}`]: true,
  [`${FINANCIAL_SERVICES}-${INSURANCE}`]: true,
  [`${FINANCIAL_SERVICES}-${NBFC}`]: true,
  [`${FINANCIAL_SERVICES}-${FOREX}`]: true,
  [`${FINANCIAL_SERVICES}-${SECURITIES}`]: true,
  [`${FINANCIAL_SERVICES}-${COMMODITIES}`]: true,
  [`${FINANCIAL_SERVICES}-${FINANCIAL_ADVISOR}`]: true,
  [`${FINANCIAL_SERVICES}-${TRADING}`]: true,
  [`${TOURS_AND_TRAVELS}-${AVIATION}`]: true,
  [`${TOURS_AND_TRAVELS}-${OTA}`]: true,
  [`${TOURS_AND_TRAVELS}-${TRAVEL_AGENCY}`]: true,
  [`${EDUCATION}-${COLLEGE}`]: true,
  [`${EDUCATION}-${UNIVERSITY}`]: true,
  [`${EDUCATION}-${SCHOOLS}`]: true,
};

// Map contains the label, value, desc. etc for each additional docs based on biz-cat & sub-cat
export const ADDITIONAL_DOCS_LABEL_VALUE_MAP = {
  [`${FINANCIAL_SERVICES}-${MUTUAL_FUND}`]: {
    [ADDITIONAL_DOCS.AMFI_CERT]: {
      value: ADDITIONAL_DOCS.AMFI_CERT,
      label: 'AMFI Certificate',
    },
    [ADDITIONAL_DOCS.SLA_AMFI]: {
      value: ADDITIONAL_DOCS.SLA_AMFI,
      label: 'Service Level Agreement with an AMFI Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${LENDING}`]: {
    [ADDITIONAL_DOCS.NBFC_CERT]: {
      value: ADDITIONAL_DOCS.NBFC_CERT,
      label: 'NBFC Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_NBFC]: {
      value: ADDITIONAL_DOCS.SLA_NBFC,
      label: 'Service Level Agreement with a NBFC Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${INSURANCE}`]: {
    [ADDITIONAL_DOCS.IRDAI_CERT]: {
      value: ADDITIONAL_DOCS.IRDAI_CERT,
      label: 'IRDAI Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_IRDAI]: {
      value: ADDITIONAL_DOCS.SLA_IRDAI,
      label: 'Service Level Agreement with an IRDA Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${NBFC}`]: {
    [ADDITIONAL_DOCS.NBFC_CERT]: {
      value: ADDITIONAL_DOCS.NBFC_CERT,
      label: 'NBFC Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_NBFC]: {
      value: ADDITIONAL_DOCS.SLA_NBFC,
      label: 'Service Level Agreement with a NBFC Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${FOREX}`]: {
    [ADDITIONAL_DOCS.FFMC_LICENSE]: {
      value: ADDITIONAL_DOCS.FFMC_LICENSE,
      label: 'FFMC License',
    },
    [ADDITIONAL_DOCS.SLA_FFMC]: {
      value: ADDITIONAL_DOCS.SLA_FFMC,
      label: 'Service Level Agreement with a FFMC Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${SECURITIES}`]: {
    [ADDITIONAL_DOCS.SEBI_CERT]: {
      value: ADDITIONAL_DOCS.SEBI_CERT,
      label: 'SEBI Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_SEBI]: {
      value: ADDITIONAL_DOCS.SLA_SEBI,
      label: 'Service Level Agreement with a SEBI Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${COMMODITIES}`]: {
    [ADDITIONAL_DOCS.SEBI_CERT]: {
      value: ADDITIONAL_DOCS.SEBI_CERT,
      label: 'SEBI Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_SEBI]: {
      value: ADDITIONAL_DOCS.SLA_SEBI,
      label: 'Service Level Agreement with a SEBI Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${FINANCIAL_ADVISOR}`]: {
    [ADDITIONAL_DOCS.SEBI_CERT]: {
      value: ADDITIONAL_DOCS.SEBI_CERT,
      label: 'SEBI Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_SEBI]: {
      value: ADDITIONAL_DOCS.SLA_SEBI,
      label: 'Service Level Agreement with a SEBI Certified Company',
    },
  },
  [`${FINANCIAL_SERVICES}-${TRADING}`]: {
    [ADDITIONAL_DOCS.SEBI_CERT]: {
      value: ADDITIONAL_DOCS.SEBI_CERT,
      label: 'SEBI Registration Certificate',
    },
    [ADDITIONAL_DOCS.SLA_SEBI]: {
      value: ADDITIONAL_DOCS.SLA_SEBI,
      label: 'Service Level Agreement with a SEBI Certified Company',
    },
  },
  [`${TOURS_AND_TRAVELS}-${AVIATION}`]: {
    [ADDITIONAL_DOCS.IATA_CERT]: {
      value: ADDITIONAL_DOCS.IATA_CERT,
      label: 'IATA Certificate',
    },
    [ADDITIONAL_DOCS.SLA_IATA]: {
      value: ADDITIONAL_DOCS.SLA_IATA,
      label: 'Service Level Agreement with an IATA Certified Company',
    },
  },
  [`${TOURS_AND_TRAVELS}-${OTA}`]: {
    [ADDITIONAL_DOCS.IATA_CERT]: {
      value: ADDITIONAL_DOCS.IATA_CERT,
      label: 'IATA Certificate',
    },
    [ADDITIONAL_DOCS.SLA_IATA]: {
      value: ADDITIONAL_DOCS.SLA_IATA,
      label: 'Service Level Agreement with an IATA Certified Company',
    },
  },
  [`${TOURS_AND_TRAVELS}-${TRAVEL_AGENCY}`]: {
    [ADDITIONAL_DOCS.IATA_CERT]: {
      value: ADDITIONAL_DOCS.IATA_CERT,
      label: 'IATA Certificate',
    },
    [ADDITIONAL_DOCS.SLA_IATA]: {
      value: ADDITIONAL_DOCS.SLA_IATA,
      label: 'Service Level Agreement with an IATA Certified Company',
    },
  },
  [`${EDUCATION}-${COLLEGE}`]: {
    [ADDITIONAL_DOCS.AFFILIATION_CERT]: {
      value: ADDITIONAL_DOCS.AFFILIATION_CERT,
      label: 'Affiliation Certificate',
    },
  },
  [`${EDUCATION}-${SCHOOLS}`]: {
    [ADDITIONAL_DOCS.AFFILIATION_CERT]: {
      value: ADDITIONAL_DOCS.AFFILIATION_CERT,
      label: 'Affiliation Certificate',
    },
  },
  [`${EDUCATION}-${UNIVERSITY}`]: {
    [ADDITIONAL_DOCS.AFFILIATION_CERT]: {
      value: ADDITIONAL_DOCS.AFFILIATION_CERT,
      label: 'Affiliation Certificate',
    },
  },
};

export const EASY_ONBOARDING = 'easy_onboarding';

export const SAMPLE_TICKET = {
  cc_emails: [],
  fwd_emails: [],
  reply_cc_emails: [],
  ticket_cc_emails: [],
  fr_escalated: false,
  spam: false,
  email_config_id: 11000002563,
  group_id: 11000003820,
  priority: 3,
  requester_id: 11014824425,
  responder_id: null,
  source: 1,
  company_id: null,
  status: 100,
  subject: '',
  association_type: null,
  to_emails: [],
  product_id: null,
  id: 'ID',
  type: null,
  due_by: '2020-07-07T21:18:52Z',
  fr_due_by: '2020-07-07T13:18:52Z',
  is_escalated: false,
  custom_fields: {},
  stats: {
    agent_responded_at: null,
    requester_responded_at: null,
    first_responded_at: null,
    status_updated_at: '2020-07-07T09:18:52Z',
    reopened_at: null,
    resolved_at: null,
    closed_at: null,
    pending_since: null,
  },
  tags: [],
  nr_due_by: null,
  nr_escalated: false,
};
