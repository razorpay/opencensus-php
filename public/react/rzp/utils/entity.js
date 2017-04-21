/*
 * Most of the code here is picked by its Angular counter part
 */

const validEntities = [
  'adjustment',
  'amex',
  'atom',
  'axis_genius',
  'axis_migs',
  'balance',
  'bank_account',
  'bank_account',
  'batch_fund_transfer',
  'billdesk',
  'card',
  'credits',
  'customer',
  'ebs',
  'file_store',
  'first_data',
  'fund_transfer_attempt',
  'emi_plan',
  'hdfc',
  'iin',
  'invoice',
  'merchant',
  'methods',
  'mobikwik',
  'netbanking',
  'payment',
  'payment_analytics',
  'pricing',
  'refund',
  'settlement',
  'settlement_details',
  'schedule',
  'terminal',
  'token',
  'transaction',
  'wallet',
  'webhook'
]

function isTimestamp(key) {
  return (key.substr(-3) === '_at' || key === 'next_run')
}

function getEntity(key) {
    return key.substr(0, key.length - 3)
}

function isId(key) {
  const entity = getEntity(key)

  // It needs to be suffixed with _id
  // and be a valid entity name for this to work
  return key.substr(-3) === '_id' && validEntities.indexOf(entity) > -1
}

export function getType(key, value) {
  const entity = getEntity(key)

  // These have their own views
  const specialEntities = [
    'merchant_id',
    'payment_id'
  ]

  // Timestamps could be blank, which is why
  // we consider its value as well
  if (value && isTimestamp(key)) {
    return 'timestamp'
  }
  // Base Amounts are always in INR
  // includes base_amount and base_amount_refunded
  else if (key.substr(0,11) === 'base_amount') {
    return 'amount_inr';
  }
  else if ((key.substr(-6) === 'amount') || (key.substr(0,7) === 'amount_')) {
    return 'amount';
  }
  // All other entity links are considered here
  else if (isId(key)) {
    if (specialEntities.indexOf(key) > -1) {
      return getEntity(key)
    } else {
      return 'id';
    }
  }  // Unknown type is entity specific things, like currency
  else {
    return 'unknown';
  }
}
