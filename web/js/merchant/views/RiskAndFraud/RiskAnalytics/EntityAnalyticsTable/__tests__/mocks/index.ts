export const mock_data = [
  {
    start_date: '1706466600',
    end_date: '1706553000',
    payment: {
      count: '28',
      amount: '39126783',
    },
    entity_data: {
      count: '5',
      amount: '10000',
    },
    entity: 'fraud',
    card_iin: '5399 10** ****',
    card_country: 'US',
  },
  {
    start_date: '1706553000',
    end_date: '1706639400',
    payment: {
      count: '37',
      amount: '103277777',
    },
    entity_data: {
      count: '10',
      amount: '20000',
    },
    entity: 'fraud',
    card_iin: '5399 10** ****',
    card_country: 'IN',
  },
  {
    start_date: '1706639400',
    end_date: '1706725800',
    payment: {
      count: '20',
      amount: '74390860',
    },
    entity_data: {
      count: '3',
      amount: '0',
    },
    entity: 'fraud',
    card_iin: '5399 10** ****',
    card_country: 'GBP',
  },
];

export const expectedData = [
  {
    card_iin: '5399 10** ****',
    txns: '₹391,267.00',
    frauds: '₹100.00',
    fraudRate: '0.03%',
  },
  {
    card_iin: '5399 10** ****',
    txns: '₹1,032,777.00',
    frauds: '₹200.00',
    fraudRate: '0.02%',
  },
  {
    card_iin: '5399 10** ****',
    txns: '₹743,908.00',
    frauds: '₹0.00',
    fraudRate: '0.00%',
  },
];
