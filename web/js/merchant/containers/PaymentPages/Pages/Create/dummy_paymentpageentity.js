// TODO: "quantity_available": 3 wherver the amount and minimum / maximum is there

const paymentpage_data = {
  id: 'pl_CxsGo6esgKIDt1',
  currency: 'INR',
  currency_symbol: '₹',
  title: 'PP Title',
  description: null,
  expire_by: null,
  status: 'active',
  terms: 'No TC',
  receipt: null,
  support_email: 'prem.kumar@razorpay.com',
  support_contact: '7502233314',
  short_url: 'http://dwarf.razorpay.in/78fodih',
  settings: {
    pay_btn_label: 'Pay', // TODO: Label to customize payment button text before amount
    allow_social_share: '1',
    udf_schema:
      '[{"title":"Email","name":"email","type":"string","pattern":"email","required":true,"options":{"keydown_restrictive":false}, "settings": {"position":1}},{"title":"Phone","name":"phone","type":"number","pattern":"phone","required":true,"minLength":8,"options":{"keydown_restrictive":false}, "settings": {"position":0}},{"name":"asdasd123","title":"asdasd123","required":false,"description":"","type":"string","enum":["random","checking","asdasda"], "settings": {"position":2},"options":{"cmp":"select","enum_labels":[]}}]',
    payment_success_message: 'Payment is successfull',
    payment_success_redirect_url: 'https://razorpay.com/index.html',
    theme: 'light',
  },
  payment_page_items: [
    {
      id: 'ppi_D2D7GpbxDsPIbA',
      item: {
        title: 'Simple Item',
        name: 'amount_field_0', // TODO: Amount fields won't have name
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 3000,
      },
      settings: {
        position: 4,
      },
      image_url: null,
      min_purchase: null,
      max_purchase: null,
      mandatory: true,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbB',
      item: {
        title: 'Simple Item optional',
        name: 'amount_field_0_1',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 4000,
      },
      settings: {
        position: 5,
      },
      image_url: null,
      min_purchase: null,
      max_purchase: null,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbB',
      item: {
        title: 'Simple Item + Stock',
        name: 'amount_field_0_1',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 2500,
      },
      settings: {
        position: 3,
      },
      image_url: null,
      quantity_available: 100, // 0 for item message for soldout
      min_purchase: null,
      max_purchase: null,
      mandatory: true,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbB',
      item: {
        title: 'Simple Item optional+ Stock',
        name: 'amount_field_0_1',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 4500,
      },
      settings: {
        position: 7,
      },
      image_url: null,
      quantity_available: 100, // 0 for page message for soldout, mandatory = true => avoid payments
      min_purchase: null,
      max_purchase: null,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbC',
      item: {
        title: 'Min purchase with Limited Stock',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 5000,
      },
      settings: {
        position: 9,
      },
      image_url: null,
      quantity_available: 2,
      min_purchase: 3,
      max_purchase: null,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbE',
      item: {
        title: 'Min-Max purchase with Limited Stock',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 7000,
      },
      settings: {
        position: 6,
      },
      image_url: null,
      quantity_available: 100,
      min_purchase: 1,
      max_purchase: 8,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbK',
      item: {
        title: 'Min purchase + Unlimited Stock',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 13000,
      },
      settings: {
        position: 8,
      },
      image_url: null,
      quantity_available: null,
      min_purchase: 3,
      max_purchase: null,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbM',
      item: {
        title: 'Min-Max purchase + Unlimited Stock',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: 15000,
      },
      settings: {
        position: 11,
      },
      image_url: null,
      quantity_available: null,
      min_purchase: 1,
      max_purchase: 8,
    },

    {
      id: 'ppi_D2D7GpbxDsPIbO',
      item: {
        title: 'Item with user defined amount (Min + Max)',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: null,
      },
      settings: {
        position: 10,
      },
      image_url: null,
      min_purchase: null,
      max_purchase: null,
      min_amount: 40,
      max_amount: 1000,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbO',
      item: {
        title: 'Item with user defined amount (No Min + Max)',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: null,
      },
      settings: {
        position: 15,
      },
      image_url: null,
      min_purchase: null,
      max_purchase: null,
      min_amount: null,
      max_amount: 1000,
    },
    {
      id: 'ppi_D2D7GpbxDsPIbO',
      item: {
        title: 'Item with user defined amount (Min + No Max)',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: null,
      },
      settings: {
        position: 14,
      },
      image_url: null,
      min_purchase: null,
      max_purchase: null,
      min_amount: 40,
      max_amount: null, // Default max limit would be 5lac
    },
    {
      id: 'ppi_D2D7GpbxDsPIbO',
      item: {
        title: 'Item with user defined amount (No Min + No Max)',
        currency: 'INR',
        description: 'Some description of the field',
        type: 'payment_page',
        amount: null,
      },
      settings: {
        position: 13,
      },
      image_url: null,
      min_purchase: null,
      max_purchase: null,
      min_amount: null,
      max_amount: null,
    },
  ],
};

export default paymentpage_data;
