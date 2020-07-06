import FIELD_TYPES from './fieldTypes';

// Note: mapFieldToIndex is prone to error if the position of items is changed in FIELD_TYPES
export function getAmountFieldTypes() {
  const fieldTypes = [
    FIELD_TYPES.fixed_price,
    FIELD_TYPES.dynamic_price,
    FIELD_TYPES.multiple_purchase,
  ];

  return fieldTypes;
}

// Note: If definition of amount types is changed, then this logic would break
export function mapFieldToAmountFieldType(amountField) {
  let amountFieldType = null;

  if (amountField && amountField.item) {
    if (!amountField.item.hasOwnProperty('amount')) {
      amountFieldType = getAmountFieldTypes()[1]; // FIELD_TYPES.dynamic_price
    } else {
      if (
        amountField.hasOwnProperty('min_purchase') &&
        amountField.min_purchase !== null // Counter type field will have min_purchase defined as 0 or 0+ integer
      ) {
        amountFieldType = getAmountFieldTypes()[2]; // FIELD_TYPES.multiple_purchase
      } else {
        amountFieldType = getAmountFieldTypes()[0]; // FIELD_TYPES.fixed_price,
      }
    }
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

    case FIELD_TYPES.dynamic_price.key:
      return {
        item: {},
        mandatory: false, // By default non-mandatory because customer can enter amount value = 0
        // As per currency
        min_amount: null, // This is in Rupees (bigger unit) To convert in paisa (small unit) before making api call. null is because BE doesn't want 0
      };

    case FIELD_TYPES.multiple_purchase.key:
      return {
        item: {},
        mandatory: false, // By default non-mandatory because min_purchase = 0
        min_purchase: 0, // it cannot be null because field definition of optional counter field will become same as fixed price optional field otherwise.
      };
  }
}

/*
// Exhaustive set of keys for amount item
{
  item: {
    name: '',
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

// Handles both values where iMandatory is string['0'/'1'] or boolean[false/true]
export function isMandatoryToBool(val) {
  return typeof val === 'boolean' ? val : Boolean(Number(val));
}

export function constructAmountField(fieldData) {
  const { mandatory, ...restProps } = fieldData;
  const amountItem = { ...restProps };

  const prettyName = amountItem.item.name.trim().replace('  ', ' ');
  amountItem.item.name = prettyName;

  if (typeof mandatory !== 'undefined') {
    amountItem.mandatory = isMandatoryToBool(mandatory); // BOOL
  }

  if (restProps.min_amount === '') {
    restProps.min_amount = null;
  }

  if (restProps.max_amount === '') {
    restProps.max_amount = null;
  }

  if (restProps.min_purchase === '') {
    restProps.min_purchase = null;
  }

  if (restProps.max_purchase === '') {
    restProps.max_purchase = null;
  }

  return amountItem;
}
