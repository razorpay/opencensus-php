export const LOADING = {
  ERROR: -1, // Error = show error msg
  SUCCESS: 1, // Success = show success msg
  PENDING: 0, // Pending = show spinner
  INITIAL: null, // Initial = hide spinner
  DEFAULT: 2, // Some custom message when form opens
};

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

export const BUSINESS_CATEGORIES = {
  FINANCIAL_SERVICES: [FINANCIAL_SERVICES],
  TOURS_AND_TRAVELS: [TOURS_AND_TRAVELS],
  EDUCATION: [EDUCATION],
};

export const BUSINESS_SUBCATEGORIES = {
  MUTUAL_FUND: [MUTUAL_FUND],
  LENDING: [LENDING],
  INSURANCE: [INSURANCE],
  NBFC: [NBFC],
  FOREX: [FOREX],
  SECURITIES: [SECURITIES],
  COMMODITIES: [COMMODITIES],
  FINANCIAL_ADVISOR: [FINANCIAL_ADVISOR],
  TRADING: [TRADING],
  AVIATION: [AVIATION],
  OTA: [OTA],
  TRAVEL_AGENCY: [TRAVEL_AGENCY],
  COLLEGE: [COLLEGE],
  SCHOOLS: [SCHOOLS],
  UNIVERSITY: [UNIVERSITY],
};

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

// Map for additional docs which are optional depending on biz cat and sub cat
export const BIZ_CAT_SUB_CAT_OPTIONAL_ADDITIONAL_DOCS = {
  [ADDITIONAL_DOCS.IATA_CERT]: {
    [`${TOURS_AND_TRAVELS}-${OTA}`]: true,
    [`${TOURS_AND_TRAVELS}-${TRAVEL_AGENCY}`]: true,
    [`${TOURS_AND_TRAVELS}-${AVIATION}`]: true,
  },
  [ADDITIONAL_DOCS.SLA_IATA]: {
    [`${TOURS_AND_TRAVELS}-${OTA}`]: true,
    [`${TOURS_AND_TRAVELS}-${TRAVEL_AGENCY}`]: true,
    [`${TOURS_AND_TRAVELS}-${AVIATION}`]: true,
  },
  [ADDITIONAL_DOCS.AFFILIATION_CERT]: {
    [`${EDUCATION}-${COLLEGE}`]: true,
    [`${EDUCATION}-${SCHOOLS}`]: true,
    [`${EDUCATION}-${UNIVERSITY}`]: true,
  },
};

// Map for the default additional doc that needs to be shown for uploading
export const DEFAULT_ADDITIONAL_DOC_REG_BIZ = {
  [`${FINANCIAL_SERVICES}-${MUTUAL_FUND}`]: ADDITIONAL_DOCS.AMFI_CERT,
  [`${FINANCIAL_SERVICES}-${LENDING}`]: ADDITIONAL_DOCS.NBFC_CERT,
  [`${FINANCIAL_SERVICES}-${INSURANCE}`]: ADDITIONAL_DOCS.IRDAI_CERT,
  [`${FINANCIAL_SERVICES}-${NBFC}`]: ADDITIONAL_DOCS.NBFC_CERT,
  [`${FINANCIAL_SERVICES}-${FOREX}`]: ADDITIONAL_DOCS.FFMC_LICENSE,
  [`${FINANCIAL_SERVICES}-${SECURITIES}`]: ADDITIONAL_DOCS.SEBI_CERT,
  [`${FINANCIAL_SERVICES}-${COMMODITIES}`]: ADDITIONAL_DOCS.SEBI_CERT,
  [`${FINANCIAL_SERVICES}-${FINANCIAL_ADVISOR}`]: ADDITIONAL_DOCS.SEBI_CERT,
  [`${FINANCIAL_SERVICES}-${TRADING}`]: ADDITIONAL_DOCS.SEBI_CERT,
  [`${TOURS_AND_TRAVELS}-${AVIATION}`]: ADDITIONAL_DOCS.IATA_CERT,
  [`${TOURS_AND_TRAVELS}-${OTA}`]: ADDITIONAL_DOCS.IATA_CERT,
  [`${TOURS_AND_TRAVELS}-${TRAVEL_AGENCY}`]: ADDITIONAL_DOCS.IATA_CERT,
  [`${EDUCATION}-${COLLEGE}`]: ADDITIONAL_DOCS.AFFILIATION_CERT,
  [`${EDUCATION}-${SCHOOLS}`]: ADDITIONAL_DOCS.AFFILIATION_CERT,
  [`${EDUCATION}-${UNIVERSITY}`]: ADDITIONAL_DOCS.AFFILIATION_CERT,
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

// Footer Buttons
const SUBMIT_CLARIFICATIONS = 'submit-clarifications';
const SAVE = 'save';
const SAVE_AND_NEXT = 'save-next';
const SUBMIT_L1_FORM = 'submit-L1-form';
const SUBMIT_KYC_FORM = 'submit-kyc-form';

export const FOOTER_BUTTONS = {
  SUBMIT_CLARIFICATIONS,
  SAVE,
  SAVE_AND_NEXT,
  SUBMIT_L1_FORM,
  SUBMIT_KYC_FORM,
};

export const BUSINESS_PROOF_CERTIFICATE_TYPES = {
  GST_CERTIFICATE: 'gst_certificate',
  MSME_CERTIFICATE: 'msme_certificate',
  SHOP_ESTABLISHMENT_CERTIFICATE: 'shop_establishment_certificate',
};

// Business Proof Docs (Only for Proprietorship business)
export const BUSINESS_PROOF_TYPE_DOCS = {
  [BUSINESS_PROOF_CERTIFICATE_TYPES.GST_CERTIFICATE]: 'GST Certificate',
  [BUSINESS_PROOF_CERTIFICATE_TYPES.MSME_CERTIFICATE]: 'Udyam certificate',
  [BUSINESS_PROOF_CERTIFICATE_TYPES.SHOP_ESTABLISHMENT_CERTIFICATE]:
    'Shop Establishment Act Certificate',
};
