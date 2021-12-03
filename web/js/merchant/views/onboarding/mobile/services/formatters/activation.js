const contactDetailFields = ['contact_name', 'contact_mobile', 'contact_email'];
let businessOverviewFields = [
  'business_type',
  'business_subcategory',
  'business_category',
  'business_dba',
  'business_website',
  'merchant_avg_order_value',
  'business_model',
];
const businessDetailFields = [
  'gstin',
  'company_cin',
  'company_pan',
  'business_name',
  'promoter_pan',
  'promoter_pan_name',
  'business_registered_state',
  'business_registered_address',
  'business_registered_pin',
  'business_registered_city',
  'business_operation_state',
  'business_operation_city',
  'business_operation_pin',
  'business_operation_address',
];
const bankAndCompanyDetailFields = ['bank_account_number', 'bank_account_name', 'bank_branch_ifsc'];
const onboardingCardFields = ['business_type', 'business_subcategory', 'business_model'];
let documentsUploadFields = [
  'aadhar_front',
  'aadhar_back',
  'passport_front',
  'passport_back',
  'voter_id_front',
  'voter_id_back',
  'gst_certificate',
  'msme_certificate',
  'shop_establishment_certificate',
  'cancelled_cheque',
  'bank_statement',
  'business_proof_url',
  'business_pan_url',
  'personal_pan',
  'form_12a_url',
  'form_80g_url',
  'amfi_certificate',
  'sla_amfi_certificate',
  'nbfc_registration_certificate',
  'sla_nbfc_registration_certificate',
  'irdai_registration_certificate',
  'sla_irdai_registration_certificate',
  'ffmc_license',
  'sla_ffmc_license',
  'sebi_registration_certificate',
  'sla_sebi_registration_certificate',
  'iata_certificate',
  'sla_iata_certificate',
  'affiliation_certificate',
  'shop_establishment_number',
];

const getFieldObjects = (fields, data) => {
  let fieldObjects = {};
  fields.forEach((field) => {
    const value = data[field] || data.documents[field];
    const error = null;
    fieldObjects = { ...fieldObjects, [field]: { value, error } };
  });
  return fieldObjects;
};

const unquieArray = (value, index, self) => self.indexOf(value) === index;

const activationFormatter = (data, experiments = {}) => {
  if (experiments.isEmailNonMandatoryOnL2Form) {
    documentsUploadFields = documentsUploadFields.concat('contact_email').filter(unquieArray);
    businessOverviewFields = businessOverviewFields.concat('contact_name').filter(unquieArray);
  }

  const contactDetails = getFieldObjects(contactDetailFields, data);
  const businessOverview = getFieldObjects(businessOverviewFields, data);
  const businessDetails = getFieldObjects(businessDetailFields, data);
  const bankAndCompanyDetails = getFieldObjects(bankAndCompanyDetailFields, data);
  const onboardingCardDetails = getFieldObjects(onboardingCardFields, data);
  const documentsUpload = getFieldObjects(documentsUploadFields, data);
  return {
    ...data,
    contact_details: contactDetails,
    business_overview: businessOverview,
    business_details: businessDetails,
    bank_and_company_details: bankAndCompanyDetails,
    onboarding_card_details: onboardingCardDetails,
    documents: documentsUpload,
  };
};

export default activationFormatter;
