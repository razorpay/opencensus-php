import FIELD_TYPES from './fieldTypes';

// Note: mapFieldToIndex is prone to error if the position of items is changed in FIELD_TYPES
export function getAmountFieldTypes() {
  const fieldTypes = [
    FIELD_TYPES.fixed_price,
    FIELD_TYPES.fixed_price_optional,
    FIELD_TYPES.dynamic_price,
    FIELD_TYPES.multiple_purchase,
  ];

  return fieldTypes;
}

// Note: If definition of amount types is changed, then this logic would break
export function mapFieldToAmountType(amountField) {
  let amountFieldType = null;

  // Fixed price
  if (amountField.mandatory) {
    amountFieldType = getFieldTypes[0]; // FIELD_TYPES.fixed_price,

    if (amountField.min_purchase || field.max_purchase) {
      amountFieldType = getFieldTypes[3]; // FIELD_TYPES.multiple_purchase
    }
  } else if (amountField.amount) {
    amountFieldType = getFieldTypes[1]; // FIELD_TYPES.fixed_price_optional
  } else {
    amountFieldType = getFieldTypes[2]; // FIELD_TYPES.dynamic_price
  }

  return amountFieldType;
}

/*
// Exhaustive set of keys for amount item
{
  item: {
    title: '',
    description: '',
    amount: null
  },
  image_url: null,
  min_amount: null,
  max_amount: null,
  min_purchase: null,
  max_purchase: null,
  mandatory: null,
  quantity_available: null
};
*/

export function constructAmountField(fieldData) {
  const { title, description, amount, mandatory, ...restProps } = fieldData;
  const prettyTitle = title.trim().replace('  ', ' ');

  const amountItem = {
    item: {
      title: prettyTitle,
      description,
      amount,
    },
    ...restProps,
  };

  if (mandatory) {
    amountItem.mandatory = mandatory; // BOOL
  }

  return amountItem;
}
