const contactDetailFields = ['contact_name', 'contact_mobile', 'contact_email'];
const businessOverviewFields = [
  'business_type',
  'business_category',
  'business_dba',
  'business_website',
];
const businessDetailFields = [
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
const bankAndCompanyDetailFields = [
  'company_cin',
  'gstin',
  'bank_account_number',
  'bank_account_name',
  'bank_branch_ifsc',
];
const onboardingCardFields = ['business_type', 'business_subcategory', 'business_model'];

const getFieldObjects = (fields, data) => {
  let fieldObjects = {};
  fields.forEach((field) => {
    const value = data[field];
    const error = null;
    fieldObjects = { ...fieldObjects, [field]: { value, error } };
  });
  return fieldObjects;
};

const activationFormatter = (data) => {
  const contactDetails = getFieldObjects(contactDetailFields, data);
  const businessOverview = getFieldObjects(businessOverviewFields, data);
  const businessDetails = getFieldObjects(businessDetailFields, data);
  const bankAndCompanyDetails = getFieldObjects(bankAndCompanyDetailFields, data);
  const onboardingCardDetails = getFieldObjects(onboardingCardFields, data);
  return {
    contact_details: contactDetails,
    business_overview: businessOverview,
    business_details: businessDetails,
    bank_and_company_details: bankAndCompanyDetails,
    onboarding_card_details: onboardingCardDetails,
    ...data,
  };
};

export default activationFormatter;
