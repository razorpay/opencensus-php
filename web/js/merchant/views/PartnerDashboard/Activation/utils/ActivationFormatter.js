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

const getAddressUploadData = (data) => {
  const documentsData = data?.partner_activation?.documents;
  const fieldObjects = {};
  if (Object.keys(documentsData).length > 0) {
    const addressProofType = Object.keys(documentsData)[0].split('_', 1)[0]; // 'eg. aadhar_front -> aadhar';
    const addressFrontLabel = `${addressProofType}_front`;
    const addressBackLabel = `${addressProofType}_back`;

    fieldObjects.address_proof_type = addressProofType;
    fieldObjects[addressFrontLabel] = documentsData[addressFrontLabel];
    fieldObjects[addressBackLabel] = documentsData[addressBackLabel];
  }
  return fieldObjects;
};

const activationFormatter = (data) => {
  const contactDetails = getFieldObjects(contactDetailFields, data);
  const businessDetails = getFieldObjects(businessDetailFields, data);
  const addressDetails = {
    ...getFieldObjects(addressDetailFields, data),
    ...getAddressUploadData(data),
  };
  return {
    ...data,
    contact_details: contactDetails,
    business_details: businessDetails,
    address_details: addressDetails,
  };
};

export default activationFormatter;
