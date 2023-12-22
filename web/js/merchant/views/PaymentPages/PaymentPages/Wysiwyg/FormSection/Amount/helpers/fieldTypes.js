const BASE_FIELD_TYPES = {
  fixed_price: {
    label: 'Fixed Amount',
    key: 'fixed_price',
    icon: 'fixed_price',
    info: {
      img: '/img/payment_pages/fixed_price.png',
      title: 'Fixed Amount',
      description: 'Add a field which contains the price value which customer should pay.',
    },
  },

  dynamic_price: {
    label: 'Customers Decide Amount',
    key: 'dynamic_price',
    icon: 'dynamic_price',
    info: {
      img: '/img/payment_pages/dynamic_price.png',
      title: 'Customers Decide Amount',
      description: 'Add a free field which helps customer to fill a amount which they wish to pay.',
    },
  },

  multiple_purchase: {
    label: 'Item with Quantity',
    key: 'multiple_purchase',
    icon: 'multiple_purchase',
    info: {
      img: '/img/payment_pages/multiple_purchase.png',
      title: 'Item with Quantity',
      description:
        'Add a price field with quantity selection widget to facilitate to purchase multiple quantities.',
    },
  },
};

export const MY_FIELD_TYPES = {
  fixed_price: {
    ...BASE_FIELD_TYPES.fixed_price,
    info: {
      ...BASE_FIELD_TYPES.fixed_price.info,
      img: '/img/payment_pages/i18n/fixed-price.png',
    },
  },

  dynamic_price: {
    ...BASE_FIELD_TYPES.dynamic_price,
    info: {
      ...BASE_FIELD_TYPES.dynamic_price.info,
      img: '/img/payment_pages/dynamic-price.png',
    },
  },

  multiple_purchase: {
    ...BASE_FIELD_TYPES.multiple_purchase,
    info: {
      ...BASE_FIELD_TYPES.multiple_purchase.info,
      img: '/img/payment_pages/multiple-purchase.png',
    },
  },
};

const FIELD_TYPES_MAP = {
  IN: BASE_FIELD_TYPES,
  MY: MY_FIELD_TYPES,
};

export default FIELD_TYPES_MAP;

export const LATE_FEE_FIELD_TYPES = {
  flat_fee: {
    label: 'Flat Fee',
    key: 'flat_fee',
    icon: 'fixed_price',
    type: 'flat_late_fee',
  },
  per_day_fee: {
    label: 'Per Day Fee',
    key: 'per_day_fee',
    icon: 'fixed_price',
    type: 'per_day_late_fee',
  },
};

export const LATE_FEE_TYPES_MAP = {
  [LATE_FEE_FIELD_TYPES.flat_fee.type]: 'Total fee',
  [LATE_FEE_FIELD_TYPES.per_day_fee.type]: 'Per day fee',
};

//////////////////////////////////////////////
/*
 * Below schemas are just blueprints and not having exact values for their keys. Check fn. getBaseFieldForAmountFieldType.
 * Existence of keys defines the definition of that field type
 * */
/*

// Fixed Price
{
  item: {
    amount: 23 // non-null amount value
  },
  mandatory: true/false,
  quantity_available: null
}

//////////////////////////////////////////////

// Customer decides Price
{
  item: {}, // amount is null
  mandatory: true/false,
  min_amount: null,
  max_amount: null,
  quantity_available: null
}

//////////////////////////////////////////////

// Multiple Quantity Purchase
{
  item: {
    amount: 23 // non-null amount value
  },
  mandatory: true/false,
  min_purchase: 0/1, // non-null value (differentiates from fixed-price field)
  max_purchase: null,
  quantity_available: null
}

//////////////////////////////////////////////
*/
