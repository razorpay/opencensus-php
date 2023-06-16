export const INIT_STATE = {
  magicPrepayCODOrders: {
    id: '',
    receipt: '',
    riskTier: '',
    from: '',
    to: '',
    count: 5,
    skip: 0,
    items: [],
    loading: false,
    error: null,
    selectedPresetFromParent: null,
    hasMoreOrders: true,
    reviewMode: '',
    paymentLinkStatus: '',
  },
  magicPrepayCODOrderInfo: {
    id: '',
    receipt: '',
    riskTier: '',
    from: '',
    to: '',
    count: 5,
    skip: 0,
    items: [],
    loading: false,
    error: null,
    selectedPresetFromParent: null,
    hasMoreOrders: true,
    reviewMode: '',
    paymentLinkStatus: '',
  },
  magicCheckout: {
    cod_order_control: true,
  },
};

export const FETCH_RESPONSE = {
  success: true,
  status_code: 200,
  data: {
    items: JSON.parse(`[
      {
        "id": "order_JfP6IFRXAaXv9W",
        "entity": "order",
        "amount": 100200,
        "amount_due": 100200,
        "receipt": "1234",
        "offer_id": null,
        "created_at": 1654779386,
        "line_items_total": 100000,
        "magic_payment_link": {
            "id": "1234",
            "status": "sent",
            "discount": "100"
        },
        "risk_tier": null
      }
    ]`),
  },
};

export const ORDER_INFO_RES = {
  status_code: 200,
  success: true,
  data: JSON.parse(`{
      "id": "order_LkkNLQ97CQMh0l",
      "entity": "order",
      "amount": 50000,
      "amount_due": 50000,
      "receipt": "1234",
      "offer_id": null,
      "created_at": 1683020182,
      "promotions": [],
      "cod_fee": 0,
      "shipping_fee": 0,
      "customer_details": {
          "contact": "+918951468289",
          "email": "sangeeta.ng@razorpay.com",
          "shipping_address": {
              "name": "Dev tester",
              "type": "shipping_address",
              "line1": "123",
              "line2": "Whitefield",
              "zipcode": "560001",
              "city": "Bengaluru",
              "state": "Karnataka",
              "country": "in"
          },
          "billing_address": {
              "name": "Dev tester",
              "type": "billing_address",
              "line1": "123",
              "line2": "Whitefield",
              "zipcode": "560001",
              "city": "Bengaluru",
              "state": "Karnataka",
              "country": "in"
          }
      },
      "line_items_total": 50000,
      "risk_tier": "high",
      "rto_reasons": ["Zipcode not matching state", "Invalid zipcode"],
      "rto_category": "address",
      "review_status": null,
      "reviewed_by": null,
      "reviewed_at": null,
      "magic_payment_link": {
          "id": "1234",
          "status": "sent",
          "discount": 100
      }
  }`),
};

export const ORDER_DATA = JSON.parse(`{
  "id": "order_LHSrCgSTceIQ88",
  "entity": "order",
  "amount": 50000,
  "amount_due": 50000,
  "receipt": "1234",
  "offer_id": null,
  "created_at": 1676626626,
  "promotions": [],
  "cod_fee": 0,
  "shipping_fee": 0,
  "customer_details": {
      "contact": "+918951468289",
      "email": "sangeeta.ng@razorpay.com",
      "shipping_address": {
          "name": "Dev tester",
          "type": "shipping_address",
          "line1": "123",
          "line2": "Whitefield",
          "zipcode": "560001",
          "city": "Bengaluru",
          "state": "Karnataka",
          "country": "in"
      },
      "billing_address": {
          "name": "Dev tester",
          "type": "billing_address",
          "line1": "123",
          "line2": "Whitefield",
          "zipcode": "560001",
          "city": "Bengaluru",
          "state": "Karnataka",
          "country": "in"
      }
  },
  "line_items_total": 50000,
  "risk_tier": "high",
  "rto_reasons": [
      "Dummy name detected",
      "Address does not contain commas",
      "Address contains too many symbols",
      "Address doesn't contain standard fields"
  ],
  "rto_category": "address",
  "review_status": null,
  "reviewed_by": null,
  "reviewed_at": null,
  "magic_payment_link": {
      "id": "plink_Ln400rYlJkjj2S",
      "status": "failed",
      "discount": 100
  }
}`);
