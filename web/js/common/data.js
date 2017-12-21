export const testMerchantId = '10000000000000';

export const entity2Prefix = {
  balance_account: 'ba',
  balance_transfer: 'bt',
  card: 'card',
  customer: 'cust',
  dispute: 'dispute',
  payment: 'pay',
  offer: 'offer',
  order: 'order',
  plan: 'plan',
  refund: 'rfnd',
  reversal: 'rvrsl',
  settlement: 'setl',
  subscription: 'sub',
  token: 'tkn',
  transaction: 'txn',
  transfer: 'trf',
  virtual_account: 'va',
};

export const prefix2Entity = Object.keys(entity2Prefix).reduce((o, entity) => {
  o[entity2Prefix[entity]] = entity;
  return o;
}, {});

export function prefixEntityValue(entityName, value) {
  // TODO: Ideally api should fix this. In some cases, eg- 'pay_' is prepended and in some cases not.
  let prefix = entity2Prefix[entityName];
  if (!value.includes('_')) {
    value = `${prefix}_${value}`;
  }
  if (prefix) {
    return value;
  }
}

// TODO: All below mappings exists in 'entity-resources.js' as well. "Check if they've exactly same data". Merge Accordingly.
export const methods = {
  card: 'Card',
  emi: 'EMI',
  netbanking: 'Netbanking',
  wallet: 'Wallet',
  upi: 'UPI',
};

export const cardTypes = {
  credit: 'Credit',
  debit: 'Debit',
};

export const wallets = {
  payzapp: 'Payzapp',
  mobikwik: 'Mobikwik',
  payumoney: 'Payumoney',
  olamoney: 'Olamoney',
  airtelmoney: 'Airtelmoney',
  freecharge: 'Freecharge',
  jiomoney: 'Jiomoney',
  openwallet: 'Openwallet',
  mpesa: 'Mpesa',
  paytm: 'Paytm',
  sbibuddy: 'SBI Buddy',
};

export const networks = {
  AMEX: 'American Express',
  DICL: 'Diners Club',
  DISC: 'Discover',
  JCB: 'JCB',
  MAES: 'Maestro',
  MC: 'MasterCard',
  RUPAY: 'RuPay',
  VISA: 'Visa',
  UNP: 'Union Pay',
};

export const gatewayAcquirers = {
  axis: 'Axis',
  hdfc: 'HDFC',
  amex: 'Amex',
  icic: 'ICICI',
};

export const gateways = {
  card: {
    first_data: 'First Data',
    hdfc: 'FSS',
    axis_migs: 'Axis Migs',
    cybersource: 'Cybersource',
    amex: 'Amex',
    sharp: 'Sharp',
  },

  netbanking: {
    netbanking_hdfc: 'HDFC Netbanking',
    netbanking_corporation: 'Corporation Netbanking',
    netbanking_kotak: 'Kotak Netbanking',
    netbanking_icici: 'ICICI Netbanking',
    netbanking_axis: 'Axis Netbanking',
    netbanking_federal: 'Federal Netbanking',
    netbanking_airtel: 'Airtel Netbanking',
    netbanking_rbl: 'RBL netbanking',
    netbanking_indusind: 'IndusInd netbanking',
    billdesk: 'Billdesk',
    ebs: 'Ebs',
    sharp: 'Sharp',
  },

  wallet: {
    mobikwik: 'Mobikwik',
    wallet_airtelmoney: 'Airtelmoney',
    wallet_freecharge: 'Freecharge',
    wallet_jiomoney: 'Jiomoney',
    wallet_olamoney: 'Olamoney',
    wallet_payumoney: 'Payumoney',
    wallet_payzapp: 'Payzapp',
    wallet_mpesa: 'Mpesa',
    wallet_sbibuddy: 'SbiBuddy',
    wallet_openwallet: 'Openwallet',
    sharp: 'Sharp',
  },

  emi: {
    amex: 'Amex',
    hdfc: 'FSS',
    first_data: 'First Data',
    sharp: 'Sharp',
  },

  upi: {
    upi_idfc: 'IDFC UPI',
    upi_icici: 'ICICI UPI',
    upi_hulk: 'UPI/HULK',
    upi_mindgate: 'Mindgate/HDFC UPI',
    sharp: 'Sharp',
  },
};

export const categories = {
  lending: 'Lending',
  securities: 'Securities',
  commodities: 'Commodities',
  grocery: 'Grocery',
  gaming: 'Gaming',
  ecommerce: 'Ecommerce',
  govt_education: 'Govt Education',
  pvt_education: 'Pvt Education',
  utilities: 'Utilities',
  corporate: 'Corporate',
  insurance: 'Insurance',
  housing: 'Housing',
  mutual_funds: 'Mutual funds',
  travel_agency: 'Travel Agency',
  pharma: 'Pharma',
  government: 'Government',
  cryptocurrency: 'Cryptocurrency',
  forex: 'Forex',
  hospitality: 'Hospitality',
  logistics: 'Logistics',
};

const statusPillClasses = {
  // Common
  // created: 'label-semi-muted',
  failed: 'label-danger',

  // payment
  authorized: 'label-info',
  captured: 'label-success',
  refunded: 'label-primary',

  initiated: 'label-info',

  // order
  attempted: 'label-info',
  paid: 'label-success',

  // settlement
  processed: 'label-success',

  // billdesk
  cancelled: 'label-danger',
  null: 'label-pending',

  // batch
  processing: 'label-info',

  // refund
  partial: 'label-info', // payment.refund_status

  // invoice
  // draft: 'label-semi-muted',
  issued: 'label-info',
  expired: 'label-danger',

  // dispute
  open: 'label-primary',
  under_review: 'label-pending',
  won: 'label-success',
  lost: 'label-danger',

  closed: 'label-danger',
};

export const statusPill = status => (
  <span class={`pill ${statusPillClasses[status] || 'label-semi-muted'}`}>
    {status}
  </span>
);
