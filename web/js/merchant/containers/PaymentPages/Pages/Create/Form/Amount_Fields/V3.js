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
export function mapFieldToAmountFieldType(amountField) {
  let amountFieldType = null;

  // Fixed price
  if (!amountField.item.amount) {
    // Note: All other fields are by default dynamic_price, or else can check for min_amount
    amountFieldType = getAmountFieldTypes()[2]; // FIELD_TYPES.dynamic_price
  } else if (
    amountField.hasOwnProperty('min_purchase') &&
    amountField.min_purchase !== null
  ) {
    // Note: Assumed that other kind of fields shouldn't have min_purchase key, or else we'll have to check non-null values
    amountFieldType = getAmountFieldTypes()[3]; // FIELD_TYPES.multiple_purchase
  } else if (amountField.item.amount && !amountField.mandatory) {
    amountFieldType = getAmountFieldTypes()[1]; // FIELD_TYPES.fixed_price_optional
  } else {
    // Note: amountField.mandatory is not valid check for fixed_price field because dynamic_price and multiple_purchase items can also have mandatory = true
    amountFieldType = getAmountFieldTypes()[0]; // FIELD_TYPES.fixed_price,
  }

  return amountFieldType && amountFieldType.key;
}

export function getBaseFieldForAmountFieldType(amountFieldType) {
  switch (amountFieldType) {
    case FIELD_TYPES.fixed_price.key:
      return {
        item: {},
        mandatory: true, // fixed_price is always mandatory
      };

    case FIELD_TYPES.fixed_price_optional.key:
      return {
        item: {},
        mandatory: false, // fixed_price_optional is always non-mandatory
      };

    case FIELD_TYPES.dynamic_price.key:
      return {
        item: {},
        mandatory: false, // By default non-mandatory because customer can enter amount value = 0
        // As per currency
        min_amount: 0, // This is in Rupees (bigger unit) To convert in paisa (small unit) before making api call
      };

    case FIELD_TYPES.multiple_purchase.key:
      return {
        item: {},
        mandatory: false, // By default non-mandatory because min_purchase = 0
        min_purchase: 0,
      };
  }
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
  const { mandatory, ...restProps } = fieldData;
  const amountItem = { ...restProps };

  const prettyTitle = amountItem.item.title.trim().replace('  ', ' ');
  amountItem.title = prettyTitle;

  if (typeof mandatory !== 'undefined') {
    amountItem.mandatory =
      typeof mandatory === 'boolean' ? mandatory : Boolean(Number(mandatory)); // BOOL
  }

  return amountItem;
}
