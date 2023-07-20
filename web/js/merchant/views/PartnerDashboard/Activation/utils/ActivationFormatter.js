const contactDetailFields = ['contact_name', 'contact_mobile', 'contact_email'];
const businessDetailFields = [
  'gstin',
  'business_type',
  'company_pan',
  'business_name',
  'promoter_pan',
  'promoter_pan_name',
  'bank_account_number',
  'bank_account_name',
  'bank_branch_ifsc',
];
const addressDetailFields = [
  'business_registered_address',
  'business_registered_pin',
  'business_registered_city',
  'business_registered_state',
  'business_operation_address',
  'business_operation_pin',
  'business_operation_city',
  'business_operation_state',
];

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
  const businessDetails = getFieldObjects(businessDetailFields, data);
  const addressDetails = getFieldObjects(addressDetailFields, data);
  return {
    ...data,
    contact_details: contactDetails,
    business_details: businessDetails,
    address_details: addressDetails,
  };
};

export default activationFormatter;
