import fDefs from './field-definitions';

/*
* A. Type: text
*    Validation: single line text(string), alphabets, alphanumeric, number, email, phone, url, large text area, pan, pincode
*
* B. Type: Select
*     Validation: string
*
* */

// Note: mapFieldToIndex is prone to error if the position of items is changed in FIELD_TYPES
export function getFieldTypes() {
  const FIELD_TYPES = [
    fDefs.fixed_price,
    fDefs.fixed_price_optional,
    fDefs.dynamic_price,
    fDefs.multiple_purchase,
  ];

  return FIELD_TYPES;
}

// Note: If definition of amount types is changed, then this logic would break
export function mapFieldToIndex(field) {
  let selectedIndexInOptions = null;

  // Fixed price
  if (field.mandatory) {
    selectedIndexInOptions = 0; // fDefs.fixed_price,

    if (field.min_purchase || field.max_purchase) {
      selectedIndexInOptions = 3; // fDefs.multiple_purchase
    }
  } else if (field.amount) {
    selectedIndexInOptions = 1; // fDefs.fixed_price_optional
  } else {
    selectedIndexInOptions = 2; // fDefs.dynamic_price
  }

  return selectedIndexInOptions;
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

export function constructAmountItem(fieldData) {
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
