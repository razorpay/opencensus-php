import FIELD_TYPES_MAP from './fieldTypes';

// Note: mapFieldToIndex is prone to error if the position of items is changed in FIELD_TYPES
export function getAmountFieldTypes(hideDynamicPriceField = false, countryCode = 'IN') {
  const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];
  const fieldTypes = [FIELD_TYPES.fixed_price, FIELD_TYPES.multiple_purchase];
  if (!hideDynamicPriceField) {
    // Add dynamic price at 1st index to make the other user expeiriance same as before.
    fieldTypes.splice(1, 0, FIELD_TYPES.dynamic_price);
  }
  return fieldTypes;
}

// Note: If definition of amount types is changed, then this logic would break
export function mapFieldToAmountFieldType(amountField, countryCode = 'IN') {
  let amountFieldType = null;

  if (amountField && amountField.item) {
    if (!amountField.item.amount) {
      amountFieldType = getAmountFieldTypes(false, countryCode)[1]; // FIELD_TYPES.dynamic_price
    } else if (
      amountField.hasOwnProperty('min_purchase') &&
      amountField.min_purchase !== null // Counter type field will have min_purchase defined as 0 or 0+ integer
    ) {
      amountFieldType = getAmountFieldTypes(false, countryCode)[2]; // FIELD_TYPES.multiple_purchase
    } else {
      amountFieldType = getAmountFieldTypes(false, countryCode)[0]; // FIELD_TYPES.fixed_price,
    }
  }

  return amountFieldType && amountFieldType.key;
}

export function getBaseFieldForAmountFieldType(amountFieldType, countryCode = 'IN') {
  const FIELD_TYPES = FIELD_TYPES_MAP[countryCode];

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
    default:
      // fallback to fixed_price_key
      return {
        item: {},
        mandatory: true, // fixed_price is always mandatory
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

export const isFormItemOfTypeAmount = (formItem) => formItem.hasOwnProperty('item');

export function convertSinglePriceFieldToMandatory(paymentPageItems) {
  // if only 1 price field exists in array, ensure its marked as mandatory (before saving in DB)
  if (paymentPageItems.length !== 1) return paymentPageItems;

  const newPaymentPageItems = [...paymentPageItems];
  toggleMandatoryForPriceField(newPaymentPageItems[0], true);
  return newPaymentPageItems;
}

export function toggleMandatoryForPriceField(priceField, forceMandatory) {
  if (!priceField.mandatory || forceMandatory) {
    // convert to mandatory
    priceField.mandatory = true;
    // if dynamic amount field
    if (priceField.item.amount === null) {
      // not setting any min_amount for now. Current UX is fine. Placeholder for future
    }
    // if quantity based price field
    if (priceField.hasOwnProperty('min_purchase') && typeof priceField.min_purchase === 'number') {
      // if min_purchase exists, retain it. If it doesnt exist, keep it at 1;
      if (priceField.min_purchase === 0) {
        priceField.min_purchase = 1;
      }
    }
  } else {
    // convert to optional
    priceField.mandatory = false;
    if (priceField.item.amount === null) {
      // not setting any min_amount for now. Current UX is fine. Placeholder for future
    }
    // if quantity based price field
    if (priceField.hasOwnProperty('min_purchase') && typeof priceField.min_purchase === 'number') {
      // if min_purchase exists, retain it. If it doesnt exist, keep it at 1;
      if (priceField.min_purchase === 1) {
        priceField.min_purchase = 0;
      }
    }
  }
}
