import AddressFields from 'merchant/containers/Activation/AddressFieldsMap';

const L1_BUSINESS_FIELD_NAMES = [
  'business_dba',
  'business_category',
  'business_model',
  'business_subcategory',
  'promoter_pan',
  'promoter_pan_name',
  'business_type',
  'business_name',
  'business_website',
  'company_pan',
];
const ADDRESS_FIELD_NAMES = AddressFields.map(
  field =>
    Array.isArray(field)
      ? field.map(nestedField => nestedField.name)
      : field.name
)
  .reduce((prevField, curField) => prevField.concat(curField), [])
  .filter(field => !!field);
const L1_FORM_FIELD_NAMES = L1_BUSINESS_FIELD_NAMES.concat(ADDRESS_FIELD_NAMES);

export default L1_FORM_FIELD_NAMES;
