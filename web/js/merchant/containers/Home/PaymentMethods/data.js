import { getDefaultPaymentFilter } from 'rzp/utils/pokedex';

const paymentMethodsColumns = [
  'method',
  'bank',
  'issuer',
  'network',
  'wallet',
  'type',
];

const aggTypes = [
  {
    value: 'sum',
    text: 'By Payment Volume',
    isCurrency: true,
  },
  {
    value: 'count',
    text: 'By Number of Payments',
  },
];

const getQuery = ({ startTime, endTime, aggType = aggTypes[0].value }) => ({
  filters: {
    default: [getDefaultPaymentFilter(startTime, endTime)],
  },
  aggregations: {
    agg: {
      agg_type: aggType,
      details: {
        index: 'payments',
        column: 'base_amount',
        group_by: paymentMethodsColumns,
      },
    },
  },
});

const bankNames = {
  AIRP: 'Airtel Payments Bank',
  ALLA: 'Allahabad Bank',
  ANDB: 'Andhra Bank',
  UTIB: 'Axis Bank',
  UTIB_C: 'Axis Bank - Corporate Banking',
  BBKM: 'Bank of Bahrein and Kuwait',
  BARB: 'Bank of Baroda',
  BARB_C: 'Bank of Baroda - Corporate Banking',
  BARB_R: 'Bank of Baroda - Retail Banking',
  BKID: 'Bank of India',
  MAHB: 'Bank of Maharashtra',
  CITI: 'CITI Bank',
  CNRB: 'Canara Bank',
  CSBK: 'Catholic Syrian Bank',
  CBIN: 'Central Bank of India',
  CIUB: 'City Union Bank',
  CORP: 'Corporation Bank',
  COSB: 'Cosmos Co-operative Bank',
  DCBL: 'DCB Bank',
  BKDN: 'Dena Bank',
  DEUT: 'Deutsche Bank',
  DBSS: 'Development Bank of Singapore',
  DLXB: 'Dhanlaxmi Bank',
  FDRL: 'Federal Bank',
  HDFC: 'HDFC Bank',
  ICIC: 'ICICI Bank',
  ICIC_C: 'ICICI Bank - Corporate Banking',
  IBKL: 'IDBI',
  IDFB: 'IDFC Bank',
  IDIB: 'Indian Bank',
  IOBA: 'Indian Overseas Bank',
  INDB: 'Indusind Bank',
  JAKA: 'Jammu and Kashmir Bank',
  JSBP: 'Janata Sahakari Bank (Pune)',
  KARB: 'Karnataka Bank',
  KVBL: 'Karur Vysya Bank',
  KKBK: 'Kotak Mahindra Bank',
  LAVB_C: 'Lakshmi Vilas Bank - Corporate Banking',
  LAVB_R: 'Lakshmi Vilas Bank - Retail Banking',
  NKGS: 'NKGSB Co-operative Bank',
  ORBC: 'Oriental Bank of Commerce',
  PMCB: 'Punjab & Maharashtra Co-operative Bank',
  PSIB: 'Punjab & Sind Bank',
  PUNB: 'Punjab National Bank',
  PUNB_C: 'Punjab National Bank - Corporate Banking',
  PUNB_R: 'Punjab National Bank - Retail Banking',
  RATN: 'RBL Bank',
  SRCB: 'Saraswat Co-operative Bank',
  SVCB: 'Shamrao Vithal Co-operative Bank',
  SIBL: 'South Indian Bank',
  SCBL: 'Standard Chartered Bank',
  SBBJ: 'State Bank of Bikaner and Jaipur',
  SBHY: 'State Bank of Hyderabad',
  SBIN: 'State Bank of India',
  SBMY: 'State Bank of Mysore',
  STBP: 'State Bank of Patiala',
  SBTR: 'State Bank of Travancore',
  SYNB: 'Syndicate Bank',
  TMBL: 'Tamilnadu Mercantile Bank',
  TNSC: 'Tamilnadu State Apex Co-operative Bank',
  UCBA: 'UCO Bank',
  UBIN: 'Union Bank of India',
  UTBI: 'United Bank of India',
  VIJB: 'Vijaya Bank',
  YESB: 'Yes Bank',
};

const getBankName = bankCode => bankNames[bankCode] || 'Unknown';

export { getQuery, aggTypes, bankNames, getBankName, paymentMethodsColumns };
