const FIELD_TYPES = {
  fixed_price: {
    label: 'Fixed Price',
    key: 'fixed_price',
    icon: 'alphabet i-fix-alphabet',
  },

  fixed_price_optional: {
    label: 'Fixed Price (Optional Item)',
    key: 'fixed_price_optional',
    icon: 'alphabet i-fix-alphabet',
  },

  dynamic_price: {
    label: 'Customer decides Price',
    key: 'dynamic_price',
    icon: 'alphabet i-fix-alphabet',
  },

  multiple_purchase: {
    label: 'Multiple Purchase',
    key: 'multiple_purchase',
    icon: 'alphabet i-fix-alphabet',
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
