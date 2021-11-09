import * as Yup from 'yup';
import BusinessDetails from './BusinessDetails';
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
  business_txn_size: Yup.string().nullable().required('This is a required field'),
  about_us_link: Yup.string()
    .nullable()
    .url('Not a valid url')
    .required('This is a required field'),
  existing_risk_checks: Yup.string().nullable().required('This is a required field'),
  accepts_intl_txns: Yup.string().nullable().required('This is a required field'),
  import_export_code: Yup.string().nullable(),
  submit: Yup.array().required('Please accept the terms and condition'),
});

export const formInitialValues = {
  products: 'payment_gateway',
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

export const modelFormData = (data) => {
  if (data.business_txn_size_min != undefined && data.business_txn_size_max != undefined) {
    data.business_txn_size = `${data.business_txn_size_min}=${data.business_txn_size_max}`;
  }

  if (!data.documents) {
    data.documents = {};
  }
  data.accepts_intl_txns = String(data.accepts_intl_txns);

  // remove unnecessary fields
  delete data.created_at;
  delete data.submitted_at;
  delete data.updated_at;

  delete data.business_txn_size_min;
  delete data.business_txn_size_max;

  return data;
};

export const modelFormDataBeforeSave = (formData) => {
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
