import * as Yup from 'yup';

import { BUSINESS_SUBCATEGORIES } from 'common/typings/User';
import { humanize } from 'common/utils/rzp-utils';

import BusinessDetails from './BusinessDetails';
import SubmitForm from './SubmitForm';
import SupportingDetails from './SupportingDetails';
import SupportingDocuments from './SupportingDocuments';
import {
  ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE,
  FIRC_DOCUMENT,
  SETTLEMENT_RECORD,
  EMPTY_OPTION,
  DOCUMENTS_SCHEMA,
} from './constants';

export const formInitialValues = {
  products: [],
  goods_type: '',
  business_use_case: '',
  business_txn_size: '',
  about_us_link: '',
  existing_risk_checks: [],
  accepts_intl_txns: 'false',
  import_export_code: '',
  documents: {},
  submit: [],
};

// Map for field to tabs used in calculating tab validity
export const fieldToTabMap = {
  products: 0,
  goods_type: 0,
  business_use_case: 0,
  business_txn_size: 0,
  about_us_link: 0,
  existing_risk_checks: 1,
  accepts_intl_txns: 2,
  import_export_code: 2,
  documents: 2,
  submit: 3,
};

export const tabsData = [
  {
    name: 'Business Details',
    component: <BusinessDetails />,
  },
  {
    name: 'Supporting Details',
    component: <SupportingDetails />,
  },
  {
    name: 'Supporting Documents',
    component: <SupportingDocuments />,
  },
  {
    name: 'Submit Form',
    component: <SubmitForm />,
  },
];

export const revampTabs = [
  {
    name: 'Business Details',
    component: <BusinessDetails />,
  },
  {
    name: 'Supporting Details',
    component: <SupportingDocuments />,
  },
];

export const getWebsiteDetailsInfo = ({ business_website, additional_websites = [] }) => {
  return {
    isWebsiteDetails: !!business_website,
    websitesData: [business_website, ...additional_websites].filter(Boolean),
  };
};

export const getProductOptions = (isRevampFlow, isWebsiteDetailsAvailable) => {
  if (isRevampFlow) {
    return [
      {
        value: ['payment_links', 'payment_pages', 'invoices'],
        label: 'Payment Pages, Links & invoices',
      },
      {
        value: ['payment_gateway'],
        label: 'Payment Gateway',
        disabled: !isWebsiteDetailsAvailable,
      },
    ];
  }
  return [
    { value: ['payment_gateway'], label: 'Payment Gateway' },
    {
      value: ['payment_links,payment_pages,invoices'],
      label: 'Payment Pages, Links & invoices',
    },
    { value: ['payment_gateway,payment_links,payment_pages,invoices'], label: 'Both' },
  ];
};

export const riskChecksOptions = [
  'None',
  'We differentiate between domestic and international customers',
  'We have set up an upper threshold on transactions / cart value',
  'We maintain a blacklist for the suspicious /  confirmed fraud orders',
];

export const riskChecksOptionsV2 = [
  'We differentiate between the customers who pay in INR or any other currency',
  'We maintain a blacklist for the suspicious /  confirmed fraud orders',
  'None',
];

export const modelFormData = (data, user) => {
  if (data.business_txn_size_min != undefined && data.business_txn_size_max != undefined) {
    data.business_txn_size = `${data.business_txn_size_min}=${data.business_txn_size_max}`;
  }

  if (!data.documents) {
    data.documents = {};
  }
  data.accepts_intl_txns = String(data.accepts_intl_txns);

  if (user) {
    let hasNoneSelected = false;
    if (Array.isArray(data.existing_risk_checks)) {
      data.existing_risk_checks =
        data.existing_risk_checks?.filter((check) => {
          if (!hasNoneSelected) {
            hasNoneSelected = check === riskChecksOptionsV2[2];
          }
          return riskChecksOptionsV2.includes(check);
        }) || [];
      if (hasNoneSelected && data.existing_risk_checks.length > 1) {
        // select only none if its selected along with other options
        data.existing_risk_checks = riskChecksOptionsV2.slice(2);
      }
    } else {
      // select none by default
      data.existing_risk_checks = riskChecksOptionsV2.slice(2);
    }

    // user exists - IERevamp flow
    const websiteDetails = getWebsiteDetailsInfo(user);

    if (!websiteDetails.isWebsiteDetails) {
      // preselect PPLI if user doesn't have website details
      data.products = getProductOptions(true, websiteDetails)[0].value;
    }
  }

  // remove unnecessary fields
  delete data.created_at;
  delete data.submitted_at;
  delete data.updated_at;
  delete data.risk_checks;

  delete data.business_txn_size_min;
  delete data.business_txn_size_max;

  return data;
};

export const modelFormDataBeforeSave = (formData, isRevampEnabled = false) => {
  formData.allowed_currencies = null;
  formData.monthly_sales_intl_cards_min = null;
  formData.monthly_sales_intl_cards_max = null;
  formData.logistic_partners = null;
  formData.contact_us_link = null;
  formData.terms_and_conditions_link = null;
  formData.privacy_policy_link = null;
  formData.refund_and_cancellation_policy_link = null;
  formData.shipping_policy_link = null;
  formData.social_media_page_link = null;
  formData.customer_info_collected = null;
  formData.partner_details_plugins = null;

  if (formData.business_txn_size) {
    const [business_txn_size_min, business_txn_size_max] = formData.business_txn_size.split('=');
    formData.business_txn_size_min = business_txn_size_min;
    formData.business_txn_size_max = business_txn_size_max;
    delete formData.business_txn_size;
  } else {
    formData.business_txn_size_min = null;
    formData.business_txn_size_max = null;
    delete formData.business_txn_size;
  }

  if (formData.products) {
    if (typeof formData.products === 'string') {
      formData.products = formData.products.split(',');
    }
  } else {
    formData.products = [];
  }

  formData.accepts_intl_txns =
    formData.accepts_intl_txns === 'true' || formData.accepts_intl_txns === true ? 1 : 0; // converting string value to boolean

  delete formData.submit;
  delete formData.risk_checks;

  if (isRevampEnabled) {
    delete formData.business_txn_size_min;
    delete formData.business_txn_size_max;
  }

  return formData;
};

export const defaultFileTypesIERevamp = [
  {
    label: 'Bank Statement (Last 60 days)',
    name: 'bank_statement_inward_remittance',
    isRequired: true,
    tooltipContent:
      'Document to ensure that your bank statement is matching with the invoices shared with us',
  },
  {
    label: 'Invoices',
    name: 'invoices',
    isRequired: true,
    tooltipContent: 'Invoice for purchases made by customers on your website',
  },
  {
    label: 'Forward inward remittance statement',
    name: 'firc',
    isRequired: false,
    tooltipContent:
      'Document to validate if you are already receiving international payments from another payment partner',
  },
];

export const defaultFileTypes = [
  { label: 'Bank Statement for Inward Remittance', name: 'bank_statement_inward_remittance' },
  {
    label: 'Settlement record from current payment partner',
    name: 'current_payment_partner_settlement_record',
  },
  { label: 'FIRC', name: 'firc' },
  { label: 'I/E Code', name: 'ie_code' },
  { label: 'Invoices', name: 'invoices' },
];

export const getAvailableFileTypes = (formikProps) => {
  const availableFileTypes = [];
  const preUploadedDocuments = [];

  defaultFileTypes.forEach((it) => {
    const doc = formikProps.values.documents[it.name];
    const accepts_intl_txns = formikProps.values.accepts_intl_txns;

    if (doc) {
      preUploadedDocuments.push(it);
    } else if (
      accepts_intl_txns === 'true' &&
      (it.name === 'bank_statement_inward_remittance' ||
        it.name === 'current_payment_partner_settlement_record')
    ) {
      // do nothing
    } else {
      availableFileTypes.push(it);
    }
  });

  // To find and set any custom file types
  Object.keys(formikProps.values.documents.others || {}).forEach((it) => {
    preUploadedDocuments.push({ label: humanize(it), name: it });
  });
  return [availableFileTypes, preUploadedDocuments];
};

export const getProductValue = (triggerSource) =>
  triggerSource === 'pg' ? 'payment_gateway' : 'payment_links,payment_pages,invoices';

export const getAdditionalDocumentsBasedOnSubCategory = ({ business_subcategory }) => {
  if ([BUSINESS_SUBCATEGORIES.Aviation].includes(business_subcategory)) {
    return {
      name: 'iata',
      label: 'IATA',
      isRequired: true,
    };
  } else if ([BUSINESS_SUBCATEGORIES.Charity].includes(business_subcategory)) {
    return {
      name: 'fcra',
      label: 'FCRA',
      isRequired: true,
    };
  } else if (
    [
      BUSINESS_SUBCATEGORIES.Food_Court,
      BUSINESS_SUBCATEGORIES.Online_Food_Ordering,
      BUSINESS_SUBCATEGORIES.Restaurant,
      BUSINESS_SUBCATEGORIES.Catering,
      BUSINESS_SUBCATEGORIES.Alcohol,
      BUSINESS_SUBCATEGORIES.Restaurant_Search_and_Booking,
    ].includes(business_subcategory)
  ) {
    return {
      name: 'fssai',
      label: 'FSSAI',
      isRequired: true,
    };
  } else if (
    [BUSINESS_SUBCATEGORIES.Nbfc, BUSINESS_SUBCATEGORIES.Lending].includes(business_subcategory)
  ) {
    return {
      name: 'nbfc',
      label: 'NBFC',
      isRequired: true,
    };
  } else if (
    [
      BUSINESS_SUBCATEGORIES.Pharmacy,
      BUSINESS_SUBCATEGORIES.Health_Products,
      BUSINESS_SUBCATEGORIES.Healthcare_Marketplace,
      BUSINESS_SUBCATEGORIES.Medical_Equipment_And_Supply_Stores,
    ].includes(business_subcategory)
  ) {
    return {
      name: 'ayush_certificate',
      label: 'Ayush Certificate',
      isRequired: true,
    };
  } else if (
    [
      BUSINESS_SUBCATEGORIES.Trading,
      BUSINESS_SUBCATEGORIES.Financial_Advisor,
      BUSINESS_SUBCATEGORIES.Securities,
      BUSINESS_SUBCATEGORIES.Commodities,
    ].includes(business_subcategory)
  ) {
    return {
      name: 'sebi_certificate',
      label: 'SEBI Certificate',
      isRequired: true,
    };
  } else if ([BUSINESS_SUBCATEGORIES.Forex].includes(business_subcategory)) {
    return {
      name: 'fema_ffmc_certificate',
      label: 'FEMA/FFMC Certificate',
      isRequired: true,
    };
  } else if ([BUSINESS_SUBCATEGORIES.Mutual_Fund].includes(business_subcategory)) {
    return {
      name: 'amfi',
      label: 'AMFI',
      isRequired: true,
    };
  } else if (
    [BUSINESS_SUBCATEGORIES.Internet_Provider, BUSINESS_SUBCATEGORIES.Broadband].includes(
      business_subcategory,
    )
  ) {
    return {
      name: 'trai',
      label: 'TRAI',
      isRequired: true,
    };
  } else if (
    [
      BUSINESS_SUBCATEGORIES.Facility_Management,
      BUSINESS_SUBCATEGORIES.Coworking,
      BUSINESS_SUBCATEGORIES.Space_Rental,
    ].includes(business_subcategory)
  ) {
    return {
      name: 'rera',
      label: 'RERA',
      isRequired: true,
    };
  } else if (
    [
      BUSINESS_SUBCATEGORIES.Game_Developer,
      BUSINESS_SUBCATEGORIES.Esports,
      BUSINESS_SUBCATEGORIES.Online_Casino,
      BUSINESS_SUBCATEGORIES.Fantasy_Sports,
      BUSINESS_SUBCATEGORIES.Gaming_Marketplace,
    ].includes(business_subcategory)
  ) {
    return {
      name: 'gaming_addendum_certificate',
      label: 'Gaming Addendum Certificate',
      isRequired: true,
    };
  } else {
    return null;
  }
};

export const getFormSchema = (isIERevamp, businessType) => {
  const schema = Yup.object().shape({
    products: Yup.string().nullable().required('Please select an option'),
    goods_type: Yup.string().nullable().required('Goods Type is a required field'),
    business_use_case: Yup.string()
      .nullable()
      .trim()
      .min(50, 'Business use case must be at least 50 characters')
      .required('Business use case is a required field'),
    business_txn_size: isIERevamp
      ? Yup.string().nullable()
      : Yup.string().nullable().required('Business txn size is a required field'),

    existing_risk_checks: Yup.string().nullable().required('This is a required field'),
    accepts_intl_txns: Yup.string().nullable().required('This is a required field'),
    import_export_code: Yup.string().nullable().length(10, 'Must be 10 characters only'),
    submit: Yup.array().required('Please accept the terms and condition'),
  });

  if (isIERevamp) {
    let documents = Yup.object().shape({
      bank_statement_inward_remittance: Yup.array().nullable(),
      invoices: Yup.array().nullable(),
      current_payment_partner_settlement_record: Yup.array().nullable(),
      firc: Yup.array().nullable(),
    });

    if (businessType) {
      const additionalDocSchema = DOCUMENTS_SCHEMA[businessType];

      if (additionalDocSchema) {
        documents = documents.concat(additionalDocSchema);
      }
    }

    return schema.concat(
      Yup.object().shape({
        about_us_link: Yup.string().nullable(),
        documents,
      }),
    );
  } else {
    return schema.concat(
      Yup.object().shape({
        about_us_link: Yup.string().nullable().required('This is a required field'),
      }),
    );
  }
};

export const getIsOtherDocumentInRevampFlow = (docType) => {
  return (
    !defaultFileTypesIERevamp.find((_fileTypes) => _fileTypes.name === docType) &&
    docType !== 'current_payment_partner_settlement_record'
  );
};

export const getAdditionalDocumentsBasedOnBusinessType = ({ businessType, acceptsIntlTxns }) => {
  let config = ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE[businessType];

  if (!config) {
    config = [
      {
        type: 'select',
        options: acceptsIntlTxns
          ? [EMPTY_OPTION, FIRC_DOCUMENT, SETTLEMENT_RECORD]
          : [EMPTY_OPTION, FIRC_DOCUMENT],
      },
    ];
  }

  return config;
};
