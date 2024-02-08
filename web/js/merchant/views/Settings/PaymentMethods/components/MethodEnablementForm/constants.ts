import * as Yup from 'yup';

import {
  PROPRIETORSHIP,
  PUBLIC,
  PRIVATE,
  NGO,
  PARTNERSHIP,
  TRUST,
  SOCIETY,
  LLP,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

import AdditionalDocuments from './components/AdditionalDocuments';
import PreRequisite from './components/PreRequisite';
import VideoKyc from './components/VideoKyc';

export const PRODUCT = 'products_pa_cb';

const SALES_AUTHORITIES_TOOLTIP =
  'This must be issued by sales authorities in the name of the business';

export const EMPTY_OPTION = { label: 'Select', name: '' };
export const FIRC_DOCUMENT = { label: 'Forward inward remittance statement', name: 'firc' };
export const SETTLEMENT_RECORD = {
  label: 'Settlement record from current payment partner',
  name: 'settlement_record',
};
const UDYAM_CERTIFICATE = {
  label: 'Udyam certificate',
  name: 'msme_certificate',
  tooltip:
    'An Udyam certificate is a valid proof of registration for small and medium-sized business. This is issued post a successful registration for each business. To download or Register for your Udyam certificate, go to http://udyamregistration.gov.in and login/create account with your details.',
};
const SHOPS_ESTABLISHMENT_CERTIFICATE = {
  label: 'Shop establishment certificate',
  name: 'shop_establishment_certificate',
  tooltip:
    "The Shop establishment certificate (SEC) is required for all businesses registered under the Shop Establishment Act or Labour department (depending on each state). To download it, follow the steps below: 1. Visit your state's shop establishment website. 2. Register with username and password. 3. Check the application status and click on 'download certificate'",
};
const SALES_TAX_RETURNS = {
  label: 'Sales tax returns',
  name: 'sales_tax_returns',
  tooltip: SALES_AUTHORITIES_TOOLTIP,
};
const INCOME_TAX_RETURNS = {
  label: 'Income tax return',
  name: 'income_tax_returns',
  tooltip: SALES_AUTHORITIES_TOOLTIP,
};
const TRADE_LICENSE = {
  label: 'Trade license',
  name: 'trade_license',
  tooltip: SALES_AUTHORITIES_TOOLTIP,
};
const GST_CERTIFICATE = {
  label: 'GST certificate',
  name: 'gst_certificate',
  tooltip:
    "The GST (Goods and Services Tax) Certificate contains your GST registration details with the Government of India. To get your GSTIN and GST certificate, follow these steps: 1. Go to the GST India portal: https://www.gst.gov.in. 2. Login using your username and password. 3. Download the GST certificate under the 'Services' section",
};
const CERTIFICATION_REGISTRATION_BY_TAX_AUTH = {
  label:
    'Certificate/Registration documents issued by Sales Tax/Service Tax/Professional Tax Authorities',
  subLabel: 'Certificate/Registration documents issued by Tax Authorities',
  name: 'certificate_registration_by_tax_auth',
  tooltip: SALES_AUTHORITIES_TOOLTIP,
};
const IMPORTER_EXPORTER_CODE = {
  label: 'Importer/exporter certificate',
  name: 'iec_license',
  tooltip:
    "An Importer-Exporter Code (IEC) is a key business identification number which is mandatory for export from India or Import to India. How to apply: 1. Register/Login on the https://www.dgft.gov.in/ website. 2. Click on the 'Apply for IEC' option on the DGFT website. Fill out the application form (ANF 2A format), upload the required documents. 3. Pay the required fees. 4. Click on the 'Submit and Generate IEC Certificate' button.",
};
const UTILITY_BILLS = {
  label: 'Utility Bills',
  name: 'utility_bills',
  tooltip:
    'Utility bills must be issued in the name of the business. Only gas, water, electricity, landline telephone bills are allowed. Mobile bills are not allowed.',
};
const PROOF_OF_PROFESSION = {
  label: 'License or Certificate issued by any incorporated professional body',
  name: 'proof_of_profession',
  tooltip:
    'Such certificates are issued by authorized professional bodies such as CA institutes, Bar councils, Pharma licenses etc.',
};
const MOA = {
  label: 'Memorandom of Association',
  name: 'moa',
};
const AOA = {
  label: 'Articles of Association',
  name: 'aoa',
};
const DBO = {
  label: 'Ultimate Beneficial Ownership̦',
  name: 'ubo',
};
const DARPAN_PORTAL = {
  label: 'Darpan Portal',
  name: 'darpan_portal',
};

const REGISTRATION_CERTIFICATE = {
  label:
    'Registration certificate (Trust deed /Society Regn certificate / Section 8 company certificate)',
  name: 'business_proof_url',
};

const PARTNERSHIP_DEED = {
  label: 'Partnership deed',
  name: 'business_proof_url',
};

const TRUST_DEED = {
  label: 'Trust deed',
  name: 'business_proof_url',
};

const SOCIETY_REGISTRATION_CERTIFICATE = {
  label: 'Society registration certificate',
  name: 'business_proof_url',
};

export const PROPRIETORSHIP_DOCUMENT = {
  label: 'Additional KYC document',
  info: "These are additional documents required as per RBI's KYC guidelines",
  type: 'select',
  options: [
    EMPTY_OPTION,
    UDYAM_CERTIFICATE,
    SHOPS_ESTABLISHMENT_CERTIFICATE,
    GST_CERTIFICATE,
    TRADE_LICENSE,
    CERTIFICATION_REGISTRATION_BY_TAX_AUTH,
    INCOME_TAX_RETURNS,
    SALES_TAX_RETURNS,
    IMPORTER_EXPORTER_CODE,
    UTILITY_BILLS,
    PROOF_OF_PROFESSION,
  ],
};

export const ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE = {
  [PROPRIETORSHIP]: [PROPRIETORSHIP_DOCUMENT, PROPRIETORSHIP_DOCUMENT],
  [PUBLIC]: [AOA, MOA, DBO],
  [PRIVATE]: [AOA, MOA, DBO],
  [LLP]: [AOA, MOA, DBO],
  [NGO]: [DARPAN_PORTAL, REGISTRATION_CERTIFICATE],
  [PARTNERSHIP]: [PARTNERSHIP_DEED],
  [TRUST]: [TRUST_DEED],
  [SOCIETY]: [SOCIETY_REGISTRATION_CERTIFICATE],
};

const DOCUMENT_VALIDATE = Yup.array().of(
  Yup.object().shape({
    display_name: Yup.string().nullable(),
    id: Yup.string().nullable(),
  }),
);

const DOCUMENT_SCHEMA = DOCUMENT_VALIDATE.required('This field is required');

export const FORMIK_FORM_KEYS = {
  KYC_TNC_ACCEPTED: 'kyc_tnc_accepted',
  DOCUMENTS: 'documents',
  SIGNATORY: 'signatory',
  VKYC_TNC_ACCEPTED: 'vkyc_tnc_accepted',
};

export const FORM_INITIAL_VALUES = {
  [FORMIK_FORM_KEYS.DOCUMENTS]: {},
  [FORMIK_FORM_KEYS.KYC_TNC_ACCEPTED]: false,
  [FORMIK_FORM_KEYS.SIGNATORY]: null,
  [FORMIK_FORM_KEYS.VKYC_TNC_ACCEPTED]: false,
};

export const FORM_SCHEMA = Yup.object().shape({
  [FORMIK_FORM_KEYS.KYC_TNC_ACCEPTED]: Yup.boolean().test(
    'is-true',
    'Please accept Terms and Condition to continue',
    (value) => value === true,
  ),
  [FORMIK_FORM_KEYS.SIGNATORY]: Yup.mixed().oneOf(['0', '1']),
  [FORMIK_FORM_KEYS.VKYC_TNC_ACCEPTED]: Yup.boolean().when([FORMIK_FORM_KEYS.SIGNATORY], {
    is: '1',
    then: Yup.boolean().test(
      'is-true',
      'Please accept Terms and Condition to continue',
      (value) => value === true,
    ),
    otherwise: Yup.boolean().notRequired(),
  }),
});

export const DOCUMENTS_SCHEMA = {
  [PROPRIETORSHIP]: Yup.object()
    .shape({
      [UDYAM_CERTIFICATE.name]: DOCUMENT_VALIDATE,
      [SHOPS_ESTABLISHMENT_CERTIFICATE.name]: DOCUMENT_VALIDATE,
      [SALES_TAX_RETURNS.name]: DOCUMENT_VALIDATE,
      [GST_CERTIFICATE.name]: DOCUMENT_VALIDATE,
      [CERTIFICATION_REGISTRATION_BY_TAX_AUTH.name]: DOCUMENT_VALIDATE,
      [IMPORTER_EXPORTER_CODE.name]: DOCUMENT_VALIDATE,
      [UTILITY_BILLS.name]: DOCUMENT_VALIDATE,
      [TRADE_LICENSE.name]: DOCUMENT_VALIDATE,
      [INCOME_TAX_RETURNS.name]: DOCUMENT_VALIDATE,
      [PROOF_OF_PROFESSION.name]: DOCUMENT_VALIDATE,
    })
    .test('at-least-two-documents', 'At least two documents are required', (values) => {
      const documentCount = PROPRIETORSHIP_DOCUMENT.options.filter(
        (docType) => values?.[docType.name],
      ).length;
      return documentCount >= 2;
    }),
  [PUBLIC]: Yup.object().shape({
    [AOA.name]: DOCUMENT_SCHEMA,
    [MOA.name]: DOCUMENT_SCHEMA,
    [DBO.name]: DOCUMENT_SCHEMA,
  }),
  [PRIVATE]: Yup.object().shape({
    [AOA.name]: DOCUMENT_SCHEMA,
    [MOA.name]: DOCUMENT_SCHEMA,
    [DBO.name]: DOCUMENT_SCHEMA,
  }),
  [LLP]: Yup.object().shape({
    [AOA.name]: DOCUMENT_SCHEMA,
    [MOA.name]: DOCUMENT_SCHEMA,
    [DBO.name]: DOCUMENT_SCHEMA,
  }),
  [NGO]: Yup.object().shape({
    [DARPAN_PORTAL.name]: DOCUMENT_SCHEMA,
    [REGISTRATION_CERTIFICATE.name]: DOCUMENT_SCHEMA,
  }),
  [PARTNERSHIP]: Yup.object().shape({
    [PARTNERSHIP_DEED.name]: DOCUMENT_SCHEMA,
  }),
  [TRUST]: Yup.object().shape({
    [TRUST_DEED.name]: DOCUMENT_SCHEMA,
  }),
  [SOCIETY]: Yup.object().shape({
    [SOCIETY_REGISTRATION_CERTIFICATE.name]: DOCUMENT_SCHEMA,
  }),
};

//side navigation tabs
export const TABS = [
  {
    name: 'Pre-requisite information',
    component: PreRequisite,
    buttonText: 'Next',
  },
  {
    name: 'Additional Documents',
    errorMessage: ['Please enter all the required and valid details to continue or switch tabs'],
    component: AdditionalDocuments,
    buttonText: 'Submit for verification',
    validateKeys: [FORMIK_FORM_KEYS.DOCUMENTS, FORMIK_FORM_KEYS.KYC_TNC_ACCEPTED],
  },
  {
    name: 'Video KYC',
    errorMessage: ['Please enter all the required and valid details to continue or switch tabs'],
    component: VideoKyc,
    buttonText: 'Complete Video KYC',
    validateKeys: [FORMIK_FORM_KEYS.SIGNATORY, FORMIK_FORM_KEYS.VKYC_TNC_ACCEPTED],
  },
];

export const KYC_BUSSINESS_TYPES = [
  PROPRIETORSHIP,
  PUBLIC,
  PRIVATE,
  LLP,
  NGO,
  PARTNERSHIP,
  TRUST,
  SOCIETY,
];

export const SAMPLE_UBO_FILE =
  'https://cdn.razorpay.com/static/assets/international/UBO-Declaration-sample.docx';
