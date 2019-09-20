const FIELD_TYPES = {
  fixed_price: {
    label: 'Fixed Amount',
    key: 'fixed_price',
    icon: 'fixed_price',
  },

  dynamic_price: {
    label: 'Customers Decide Amount',
    key: 'dynamic_price',
    icon: 'dynamic_price',
  },

  multiple_purchase: {
    label: 'Item with Quantity',
    key: 'multiple_purchase',
    icon: 'multiple_purchase',
  },
};

export default FIELD_TYPES;

//////////////////////////////////////////////
/*
* Below schemas are just blueprints and not having exact values for their keys.
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
