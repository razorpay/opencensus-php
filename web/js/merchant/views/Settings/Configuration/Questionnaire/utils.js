import * as Yup from 'yup';
import BusinessDetails from './BusinessDetails';
import QuickLinks from './QuickLinks';
import SupportingDetails from './SupportingDetails';
import SupportingDocuments from './SupportingDocuments';
import SubmitForm from './SubmitForm';
import { humanize } from 'common/utils/rzp-utils';

export const schema = Yup.object().shape({
  products: Yup.string().nullable().required('Please select an option'),
  goods_type: Yup.string().nullable().required('Goods Type is a required field'),
  business_use_case: Yup.string()
    .nullable()
    .trim()
    .min(50, 'Min 50 characters required')
    .required('Business use case is a required field'),
  allowed_currencies: Yup.string().nullable().required('This is a required field'),
  monthly_sales_intl_cards: Yup.string().nullable().required('This is a required field'),
  business_txn_size: Yup.string().nullable().required('This is a required field'),
  logistic_partners: Yup.string().nullable(),
  about_us_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .required('This is a required field'),
  contact_us_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .required('This is a required field'),
  terms_and_conditions_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .required('Terms & conditions link is required'),
  privacy_policy_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .required('Privacy policy link is required'),
  refund_and_cancellation_policy_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .required('Refund and cancellation link is required'),
  shipping_policy_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .when('goods_type', {
      is: 'digital_services',
      then: Yup.string().notRequired(),
      otherwise: Yup.string().required('Shipping policy link is required'),
    }),
  social_media_page_link: Yup.string().nullable().url('Not a valid url'),
  existing_risk_checks: Yup.string().nullable().required('This is a required field'),
  customer_info_collected: Yup.string().nullable().required('This is a required field'),
  partner_details_plugins: Yup.string().nullable().required('This is a required field'),
  accepts_intl_txns: Yup.string().nullable().required('This is a required field'),
  import_export_code: Yup.string().nullable(),
  submit: Yup.array().required('Please accept the terms and condition'),
});

export const formInitialValues = {
  products: 'payment_gateway',
  goods_type: '',
  business_use_case: '',
  allowed_currencies: [],
  monthly_sales_intl_cards: '',
  business_txn_size: '',
  logistic_partners: '',
  about_us_link: '',
  contact_us_link: '',
  terms_and_conditions_link: '',
  privacy_policy_link: '',
  refund_and_cancellation_policy_link: '',
  shipping_policy_link: '',
  social_media_page_link: '',
  existing_risk_checks: [],
  customer_info_collected: [],
  partner_details_plugins: [],
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
  allowed_currencies: 0,
  monthly_sales_intl_cards: 0,
  business_txn_size: 0,
  logistic_partners: 0,
  about_us_link: 1,
  contact_us_link: 1,
  terms_and_conditions_link: 1,
  privacy_policy_link: 1,
  refund_and_cancellation_policy_link: 1,
  shipping_policy_link: 1,
  social_media_page_link: 1,
  existing_risk_checks: 2,
  customer_info_collected: 2,
  partner_details_plugins: 2,
  accepts_intl_txns: 3,
  import_export_code: 3,
  submit: 4,
};

export const tabsData = [
  {
    name: 'Business Details',
    component: <BusinessDetails />,
  },
  {
    name: 'Quick links',
    component: <QuickLinks />,
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

export const modelFormData = (data) => {
  // handle min max values
  if (data.monthly_sales_intl_cards_min && data.monthly_sales_intl_cards_max) {
    data.monthly_sales_intl_cards =
      data.monthly_sales_intl_cards_min + '=' + data.monthly_sales_intl_cards_max;
  }

  if (data.business_txn_size_min && data.business_txn_size_max) {
    data.business_txn_size = data.business_txn_size_min + '=' + data.business_txn_size_max;
  }

  if (!data.documents) {
    data.documents = {};
  }
  data.accepts_intl_txns = String(data.accepts_intl_txns);

  // remove unnecessary fields
  delete data.created_at;
  delete data.submitted_at;
  delete data.updated_at;

  delete data.monthly_sales_intl_cards_min;
  delete data.monthly_sales_intl_cards_max;

  delete data.business_txn_size_min;
  delete data.business_txn_size_max;

  return data;
};

export const modelFormDataBeforeSave = (formData) => {
  // min max field
  if (formData.monthly_sales_intl_cards) {
    const [
      monthly_sales_intl_cards_min,
      monthly_sales_intl_cards_max,
    ] = formData.monthly_sales_intl_cards.split('=');
    formData.monthly_sales_intl_cards_min = monthly_sales_intl_cards_min;
    formData.monthly_sales_intl_cards_max = monthly_sales_intl_cards_max;
    delete formData.monthly_sales_intl_cards;
  }

  if (formData.business_txn_size) {
    const [business_txn_size_min, business_txn_size_max] = formData.business_txn_size.split('=');
    formData.business_txn_size_min = business_txn_size_min;
    formData.business_txn_size_max = business_txn_size_max;
    delete formData.business_txn_size;
  }

  if (formData.products && typeof formData.products === 'string')
    formData.products = formData.products.split(',');

  formData.accepts_intl_txns =
    formData.accepts_intl_txns === 'true' || formData.accepts_intl_txns === true ? 1 : 0; // converting string value to boolean

  delete formData.submit;

  return formData;
};

export const defaultFileTypes = [
  { label: 'Bank Statement for Inward Remitance', name: 'bank_statement_inward_remittance' },
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
    } else {
      if (
        accepts_intl_txns === 'true' &&
        (it.name === 'bank_statement_inward_remittance' ||
          it.name === 'current_payment_partner_settlement_record')
      ) {
        // do nothing
      } else {
        availableFileTypes.push(it);
      }
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
