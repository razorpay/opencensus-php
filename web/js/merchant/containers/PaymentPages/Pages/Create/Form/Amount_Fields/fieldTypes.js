const FIELD_TYPES = {
  fixed_price: {
    label: 'Fixed Price',
    key: 'fixed_price',
    icon: 'fixed_price',
  },

  fixed_price_optional: {
    label: 'Fixed Price (Optional Item)',
    key: 'fixed_price_optional',
    icon: 'fixed_price_optional',
  },

  dynamic_price: {
    label: 'Customer decides Price',
    key: 'dynamic_price',
    icon: 'dynamic_price',
  },

  multiple_purchase: {
    label: 'Multiple Purchase',
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
    amount: null
  },
  mandatory: true,
  quantity_available: null
}

//////////////////////////////////////////////

// Fixed Price (Optional Purchase)
{
  item: {
    amount: null
  },
  quantity_available: null
}

//////////////////////////////////////////////

// Customer decides Price
{
  item: {},
  min_amount: null,
  max_amount: null,
  quantity_available: null
}

//////////////////////////////////////////////

// Multiple Quantity Purchase
{
  item: {
    amount: null
  },
  mandatory: true,
  min_purchase: null,
  max_purchase: null,
  quantity_available: null
}

//////////////////////////////////////////////
*/
