import React from 'react';
import cloneDeep from 'lodash/cloneDeep';
import lodashset from 'lodash/set';

import { set } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import { REQUESTED } from 'merchant/views/Settings/PaymentMethods/constants';

const SET_LEAF_INSTRUMENT = 'SET_LEAF_INSTRUMENT';
const SET_INTERMEDIATE_INSTRUMENT = 'SET_INTERMEDIATE_INSTRUMENT';
const CLEAR_INTERMEDIATE_INSTRUMENT = 'CLEAR_INTERMEDIATE_INSTRUMENT';
const CLEAR_LEAF_INSTRUMENT = 'CLEAR_LEAF_INSTRUMENT';
const FETCH_ALL_MERCHANT_INSTRUMENTS = 'FETCH_ALL_MERCHANT_INSTRUMENTS';
const FETCH_REQUESTED_MERCHANT_INSTRUMENTS = 'FETCH_REQUESTED_MERCHANT_INSTRUMENTS';
const CREATE_INSTRUMENT_REQUEST = 'CREATE_INSTRUMENT_REQUEST';
const CANCEL_INSTRUMENT_REQUEST = 'CANCEL_INSTRUMENT_REQUEST';
const REINITIATE_INSTRUMENT_REQUEST = 'REINITIATE_INSTRUMENT_REQUEST';
const SET_LOADING = 'SET_LOADING';
const GET_DISCREPANCY_CATEGORIES = 'GET_DISCREPANCY_CATEGORIES';
const GET_IIR_DISCREPANCIES = 'GET_IIR_DISCREPANCIES';

const GBP_BANK_ACCOUNT = {
  name: 'GBP Bank Account',
  description: 'Accept payments via FPS transfer',
  message: 'Share the below details with your UK customers to accept GBP payments via FPS transfer',
  vaCurrency: 'GBP',
  status: 'greyed',
  slug: 'international.gbp',
  icon: 'https://cdn.razorpay.com/static/assets/instrument-request/gbp.svg',
};

const EUR_BANK_ACCOUNT = {
  name: 'EUR Bank Account',
  description: 'Accept payments via SEPA transfer',
  message:
    'Share the below details with your European customers to accept EUR payments via SEPA transfer',
  vaCurrency: 'EUR',
  status: 'greyed',
  slug: 'international.eur',
  icon: 'https://cdn.razorpay.com/static/assets/instrument-request/eur.svg',
};

export const clearIntermediateInstrument = () => {
  return {
    type: CLEAR_INTERMEDIATE_INSTRUMENT,
  };
};

export const clearLeafInstrument = () => {
  return {
    type: CLEAR_LEAF_INSTRUMENT,
  };
};

export const setInstrument = (instrument) => {
  if (instrument.leafList) {
    return {
      type: SET_LEAF_INSTRUMENT,
      payload: instrument,
    };
  } else {
    return {
      type: SET_INTERMEDIATE_INSTRUMENT,
      payload: instrument,
    };
  }
};

export const fetchMerchantInstruments = () => {
  return {
    type: FETCH_ALL_MERCHANT_INSTRUMENTS,
    payload: merchantFetch('merchant_instrument_status'),
  };
};

export const fetchRequestedInstruments = () => {
  return {
    type: FETCH_REQUESTED_MERCHANT_INSTRUMENTS,
    payload: merchantFetch('merchant_instruments'),
  };
};

export const createMerchantInstrumentRequest = (instrument) => {
  return {
    type: CREATE_INSTRUMENT_REQUEST,
    payload: merchantFetch({
      url: 'merchant_instrument_request',
      method: 'post',
      data: { instrument },
    }),
  };
};

export const cancelMerchantInstrumentRequest = (id) => {
  return {
    type: CANCEL_INSTRUMENT_REQUEST,
    payload: merchantFetch({
      url: `merchant_instrument_request/${id}`,
      method: 'patch',
      data: { status: 'cancelled' },
    }),
  };
};

export const reinitiateMerchantInstrumentRequest = (id) => {
  return {
    type: REINITIATE_INSTRUMENT_REQUEST,
    payload: merchantFetch({
      url: `merchant_instrument_request/${id}`,
      method: 'patch',
      data: { status: 'reinitiated' },
    }),
  };
};

export const setLoading = () => {
  return {
    type: SET_LOADING,
  };
};

export const getDiscrepanciesCategories = () => {
  return {
    type: GET_DISCREPANCY_CATEGORIES,
    payload: merchantFetch('terminals/proxy/discrepancy_list_merchant'),
  };
};

export const getIirDiscrepancies = (mirId) => {
  return {
    type: GET_IIR_DISCREPANCIES,
    payload: merchantFetch(
      `terminals/proxy/merchant_instrument_request/${mirId}/iir_discrepancies`,
    ),
  };
};

export const initialState = {
  pg: [
    {
      name: 'Cards',
      description: 'Visa, Master, Amex',
      actionItems: {},
      slug: 'cards',
      icon: 'card',
      additionalCondition: (user) => !user.isOrgCurlec,
      leafList: [
        {
          header: 'Domestic Cards',
          list: [
            {
              name: 'Visa Cards',
              status: 'greyed',
              slug: 'domestic.visa',
              icon: 'visa',
            },
            {
              name: 'MasterCard',
              status: 'greyed',
              slug: 'domestic.mastercard',
              icon: 'masterCard',
            },
            {
              name: 'Rupay Cards',
              status: 'greyed',
              slug: 'domestic.rupay',
              icon: 'rupay',
            },
            {
              name: 'Amex Cards',
              status: 'greyed',
              slug: 'domestic.amex',
              icon: 'amex',
            },
            {
              name: 'Diners Club',
              status: 'greyed',
              slug: 'domestic.dicl',
              icon: 'diners',
            },
            {
              name: 'Maestro',
              status: 'greyed',
              slug: 'domestic.maestro',
              icon: 'maestro',
            },
          ],
        },
        {
          header: 'Cards Recurring',
          list: [
            {
              name: 'Cards Recurring',
              description: 'Allow customers to create mandates via cards.',
              status: 'greyed',
              slug: 'recurring',
              icon: '',
            },
          ],
        },
      ],
    },
    {
      name: 'UPI/QR',
      description: 'GooglePay, PhonePe, BHIM & more',
      slug: 'upi',
      icon: 'upi',
      additionalCondition: (user) => !user.isOrgCurlec,
      actionItems: {},
      leafList: [
        {
          header: 'UPI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/upi',
          list: [
            {
              name: 'UPI',
              status: 'greyed',
              slug: 'upi',
              description: (
                <p>
                  Gpay, Phonepe, Paytm, and{' '}
                  <a
                    href="https://www.npci.org.in/what-we-do/upi/3rd-party-apps"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    more...
                  </a>
                </p>
              ),
            },
            // {
            //   name: 'Google Pay Omnichannel',
            //   status: 'greyed',
            //   slug: 'google_pay',
            //   description: (
            //     <p>
            //       An enhancement that allows customers to pay via google pay by entering their{' '}
            //       <b>phone number instead of UPI ID.</b>{' '}
            //       <a
            //         href="https://razorpay.com/docs/payment-gateway/payment-methods/upi/google-pay/omnichannel/"
            //         target="_blank"
            //         rel="noopener noreferrer"
            //       >
            //         Know more...
            //       </a>
            //     </p>
            //   ),
            // },
          ],
        },
        {
          header: 'UPI Autopay',
          list: [
            {
              name: 'UPI Autopay',
              status: 'greyed',
              slug: 'recurring.autopay',
              description: 'Allow customers to create mandates via UPI.',
            },
          ],
        },
      ],
    },
    {
      name: 'Netbanking',
      description: 'All Indian Banks',
      slug: 'netbanking',
      icon: 'netbanking',
      additionalCondition: (user) => !user.isOrgCurlec,
      actionItems: {},
      intermediateList: [
        {
          name: 'Retail Netbanking',
          description: 'Direct Netbanking with customers',
          slug: 'retail',
          leafList: [
            {
              header: 'Available Banks',
              list: [
                { name: 'HDFC Bank', status: 'greyed', slug: 'hdfc' },
                {
                  name: 'State Bank of India',
                  status: 'greyed',
                  slug: 'sbin',
                },
                { name: 'Axis Bank', status: 'greyed', slug: 'utib' },
                { name: 'ICICI Bank', status: 'greyed', slug: 'icic' },
                { name: 'City Union Bank', status: 'greyed', slug: 'ciub' },
                {
                  name: 'Standard Chartered Bank',
                  status: 'greyed',
                  slug: 'scbl',
                },
                {
                  name: 'AU Small Finance Bank',
                  status: 'greyed',
                  slug: 'aubl',
                },
                {
                  name: 'Airtel Payments Bank',
                  status: 'greyed',
                  slug: 'airp',
                },
                {
                  name: 'Allahabad Bank',
                  status: 'greyed',
                  slug: 'alla',
                },
                {
                  name: 'Andhra Bank',
                  status: 'greyed',
                  slug: 'andb',
                },
                {
                  name: 'Bandhan Bank',
                  status: 'greyed',
                  slug: 'bdbl',
                },
                {
                  name: 'Bank of Bahrain and Kuwait',
                  status: 'greyed',
                  slug: 'bbkm',
                },
                {
                  name: 'Bank of Baroda',
                  status: 'greyed',
                  slug: 'barb_r',
                },
                {
                  name: 'Bank of India',
                  status: 'greyed',
                  slug: 'bkid',
                },
                {
                  name: 'Bank of Maharashtra',
                  status: 'greyed',
                  slug: 'mahb',
                },
                {
                  name: 'Bassein Catholic Co-operative Bank',
                  status: 'greyed',
                  slug: 'bacb',
                },
                {
                  name: 'Canara Bank',
                  status: 'greyed',
                  slug: 'cnrb',
                },
                {
                  name: 'Catholic Syrian Bank',
                  status: 'greyed',
                  slug: 'csbk',
                },
                {
                  name: 'Central Bank of India',
                  status: 'greyed',
                  slug: 'cbin',
                },
                {
                  name: 'Cosmos Co-operative Bank',
                  status: 'greyed',
                  slug: 'cosb',
                },
                {
                  name: 'DCB Bank',
                  status: 'greyed',
                  slug: 'dcbl',
                },
                {
                  name: 'Dena Bank',
                  status: 'greyed',
                  slug: 'bkdn',
                },
                {
                  name: 'Deutsche Bank',
                  status: 'greyed',
                  slug: 'deut',
                },
                {
                  name: 'Development Bank of Singapore',
                  status: 'greyed',
                  slug: 'dbss',
                },
                {
                  name: 'Dhanlaxmi Bank',
                  status: 'greyed',
                  slug: 'dlxb',
                },
                {
                  name: 'ESAF Small Finance Bank',
                  status: 'greyed',
                  slug: 'esaf',
                },
                {
                  name: 'Equitas Small Finance Bank',
                  status: 'greyed',
                  slug: 'esfb',
                },
                {
                  name: 'Federal Bank',
                  status: 'greyed',
                  slug: 'fdrl',
                },
                {
                  name: 'IDBI',
                  status: 'greyed',
                  slug: 'ibkl',
                },
                {
                  name: 'IDFC FIRST Bank',
                  status: 'greyed',
                  slug: 'idfb',
                },
                {
                  name: 'Indian Bank',
                  status: 'greyed',
                  slug: 'idib',
                },
                {
                  name: 'Indian Overseas Bank',
                  status: 'greyed',
                  slug: 'ioba',
                },
                {
                  name: 'Indusind Bank',
                  status: 'greyed',
                  slug: 'indb',
                },
                {
                  name: 'Jammu and Kashmir Bank',
                  status: 'greyed',
                  slug: 'jaka',
                },
                {
                  name: 'Jana Small Finance Bank',
                  status: 'greyed',
                  slug: 'jsfb',
                },
                {
                  name: 'Janata Sahakari Bank (Pune)',
                  status: 'greyed',
                  slug: 'jsbp',
                },
                {
                  name: 'Kalupur Commercial Co-operative Bank',
                  status: 'greyed',
                  slug: 'kccb',
                },
                {
                  name: 'Kalyan Janata Sahakari Bank',
                  status: 'greyed',
                  slug: 'kjsb',
                },
                {
                  name: 'Karnataka Bank',
                  status: 'greyed',
                  slug: 'karb',
                },
                {
                  name: 'Karur Vysya Bank',
                  status: 'greyed',
                  slug: 'kvbl',
                },
                {
                  name: 'Kotak Mahindra Bank',
                  status: 'greyed',
                  slug: 'kkbk',
                },
                {
                  name: 'Mehsana Urban Co-operative Bank',
                  status: 'greyed',
                  slug: 'msnu',
                },
                {
                  name: 'NKGSB Co-operative Bank',
                  status: 'greyed',
                  slug: 'nkgs',
                },
                {
                  name: 'North East Small Finance Bank',
                  status: 'greyed',
                  slug: 'nesf',
                },
                {
                  name: 'Oriental Bank of Commerce',
                  status: 'greyed',
                  slug: 'orbc',
                },
                {
                  name: 'United Bank Of India',
                  status: 'greyed',
                  slug: 'utbi',
                },
                {
                  name: 'Punjab & Sind Bank',
                  status: 'greyed',
                  slug: 'psib',
                },
                {
                  name: 'Punjab National Bank',
                  status: 'greyed',
                  slug: 'punb_r',
                },
                {
                  name: 'Ratnakar Bank',
                  status: 'greyed',
                  slug: 'ratn',
                },
                {
                  name: 'Saraswat Cooperative Bank',
                  status: 'greyed',
                  slug: 'srcb',
                },
                // {
                //   name: 'SVCB Co-operative Bank',
                //   status: 'greyed',
                //   slug: 'svcb',
                // },
                {
                  name: 'South Indian Bank',
                  status: 'greyed',
                  slug: 'sibl',
                },
                {
                  name: 'State Bank of Bikaner and Jaipur',
                  status: 'greyed',
                  slug: 'sbbj',
                },
                {
                  name: 'State Bank of Hyderabad',
                  status: 'greyed',
                  slug: 'sbhy',
                },
                {
                  name: 'State Bank of Mysore',
                  status: 'greyed',
                  slug: 'sbmy',
                },
                {
                  name: 'State Bank of Patiala',
                  status: 'greyed',
                  slug: 'stbp',
                },
                {
                  name: 'State Bank of Travancore',
                  status: 'greyed',
                  slug: 'sbtr',
                },
                {
                  name: 'Suryoday Small Finance Bank',
                  status: 'greyed',
                  slug: 'sury',
                },
                {
                  name: 'Syndicate Bank',
                  status: 'greyed',
                  slug: 'synb',
                },
                {
                  name: 'Tamilnadu Mercantile Bank',
                  status: 'greyed',
                  slug: 'tmbl',
                },
                {
                  name: 'Tamilnadu State Apex Co-operative Bank ',
                  status: 'greyed',
                  slug: 'tnsc',
                },
                {
                  name: 'Thane Bharat Sahakari Bank',
                  status: 'greyed',
                  slug: 'tbsb',
                },
                {
                  name: 'Thane Janata Sahakari Bank',
                  status: 'greyed',
                  slug: 'tjsb',
                },
                // {
                //   name: 'UCO Bank',
                //   status: 'greyed',
                //   slug: 'ucba',
                // },
                {
                  name: 'Union Bank of India',
                  status: 'greyed',
                  slug: 'ubin',
                },
                {
                  name: 'Corporation Bank',
                  status: 'greyed',
                  slug: 'corp',
                },
                {
                  name: 'Varachha Co-operative Bank',
                  status: 'greyed',
                  slug: 'vara',
                },
                {
                  name: 'Vijaya Bank',
                  status: 'greyed',
                  slug: 'vijb',
                },
                {
                  name: 'Yes Bank',
                  status: 'greyed',
                  slug: 'yesb',
                },
                {
                  name: 'Zoroastrian Co-operative Bank',
                  status: 'greyed',
                  slug: 'zcbl',
                },
              ],
            },
          ],
        },
        {
          name: 'Corporate Netbanking',
          description: 'Netbanking with businesses and corporates',
          slug: 'corporate',
          leafList: [
            {
              header: 'Available Banks',
              list: [
                { name: 'Andhra Bank', status: 'greyed', slug: 'andb_c' },
                {
                  name: 'Shamrao Vithal Co-operative Bank',
                  status: 'greyed',
                  slug: 'svcb_c',
                },
                {
                  name: 'Bank of Baroda',
                  status: 'greyed',
                  slug: 'barb_c',
                },
                {
                  name: 'Punjab National Bank',
                  status: 'greyed',
                  slug: 'punb_c',
                },
                {
                  name: 'YES Bank',
                  status: 'greyed',
                  slug: 'yesb_c',
                },
                { name: 'ICICI Bank', status: 'greyed', slug: 'icic_c' },
                { name: 'Axis Bank', status: 'greyed', slug: 'utib_c' },
                {
                  name: 'IDBI',
                  status: 'greyed',
                  slug: 'ibkl_c',
                },
                {
                  name: 'Bank of India',
                  status: 'greyed',
                  slug: 'bkid_c',
                },
                {
                  name: 'Ratnakar Bank',
                  status: 'greyed',
                  slug: 'ratn_c',
                },
                {
                  name: 'Dhanlaxmi Bank',
                  status: 'greyed',
                  slug: 'dlxb_c',
                },
                {
                  name: 'Kotak Mahindra Bank',
                  status: 'greyed',
                  slug: 'kkbk_c',
                },
              ],
            },
          ],
        },
        {
          name: 'E-Mandate',
          description:
            'Allow customers to create mandates via netbanking, debit card, eSign or paper NACH',
          slug: 'recurring',
          leafList: [
            {
              header: 'Available Methods',
              list: [
                {
                  name: 'eNACH',
                  description: 'Enable customers to create mandates via netbanking and debit card.',
                  status: 'greyed',
                  slug: 'enach',
                },
                {
                  name: 'eSign',
                  description: 'Enable customers to create mandates via Aadhar based eSign flow.',
                  status: 'greyed',
                  slug: 'esign',
                },
                {
                  name: 'Paper NACH',
                  description: 'Enable customers to create physical mandates via Paper NACH.',
                  status: 'greyed',
                  slug: 'papernach',
                },
              ],
            },
          ],
        },
      ],
    },
    {
      name: 'EMI',
      description: 'Credit/Debit cards, Zest money & more',
      slug: 'emi',
      icon: 'emi',
      additionalCondition: (user) => !user.isOrgCurlec,
      actionItems: {},
      leafList: [
        {
          header: 'Debit Card EMI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/emi/debit-card-emi/',
          list: [
            {
              name: 'HDFC Bank',
              description: '',
              status: 'greyed',
              slug: 'debit.hdfc',
              icon: 'https://cdn.razorpay.com/paylater-sq/hdfc.svg',
            },
            {
              name: 'IndusInd Bank',
              description: '',
              status: 'greyed',
              slug: 'debit.indusind',
              icon: 'https://cdn.razorpay.com/bank/INDB.gif',
            },
            // {
            //   name: 'KOTAK Bank',
            //   description: '',
            //   status: 'greyed',
            //   slug: 'debit.kotak',
            //   icon: 'https://cdn.razorpay.com/paylater-sq/kkbk.svg',
            // },
          ],
        },
        {
          header: 'Credit Card EMI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/emi/',
          list: [
            {
              name: 'HDFC CC EMI',
              description: '',
              status: 'greyed',
              slug: 'credit.hdfc',
              icon: 'https://cdn.razorpay.com/bank/HDFC.gif',
            },
            {
              name: 'SBI CC EMI',
              description: '',
              status: 'greyed',
              slug: 'credit.sbi',
              icon: 'https://cdn.razorpay.com/bank/SBIN.gif',
            },
            {
              name: 'Amex CC EMI',
              description: '',
              status: 'greyed',
              slug: 'credit.amex',
              icon: 'amex',
            },
            {
              name: 'Credit Cards',
              description: 'Axis, ICICI, IDFC, Kotak',
              status: 'greyed',
              slug: 'credit',
              docLink:
                'https://razorpay.com/docs/payments/payment-methods/emi/credit-card-emi/#supported-banks-for-credit-card-emis',
            },
          ],
        },
        {
          header: 'Cardless EMI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/emi/cardless-emi/',
          list: [
            {
              name: 'ZestMoney',
              description: '',
              status: 'greyed',
              slug: 'cardless_emi.zestmoney',
              icon: 'zestmoney',
            },
            {
              name: 'Early Salary',
              description: '',
              status: 'greyed',
              slug: 'cardless_emi.earlysalary',
              icon: 'earlysalary',
            },
            {
              name: 'Instacred',
              description: 'ICICI, Kotak, HDFC, Federal',
              docLink:
                'https://razorpay.com/docs/payments/payment-methods/emi/cardless-emi/#supported-payment-partners',
              status: 'greyed',
              slug: 'cardless_emi.instacred',
              icon: 'instacred',
            },
            {
              name: 'Sezzle',
              description: '',
              status: 'greyed',
              slug: 'cardless_emi.sezzle',
              icon: 'sezzle',
            },
            {
              name: 'Axio',
              description: '',
              status: 'greyed',
              slug: 'cardless_emi.walnut369',
              icon: 'walnut369',
            },
          ],
        },
      ],
    },
    {
      name: 'Wallet',
      description: 'Phonepe, Freecharge etc.',
      slug: 'wallet',
      icon: 'wallet',
      additionalCondition: (user) => !user.isOrgCurlec,
      actionItems: {},
      leafList: [
        {
          header: 'Wallets',
          list: [
            {
              name: 'Amazon Pay',
              description: '',
              status: 'greyed',
              slug: 'amazonpay',
              icon: 'amazonpay',
            },
            {
              name: 'Paytm',
              description: '',
              status: 'account_linkable',
              slug: 'paytm',
              icon: 'paytm',
            },
            {
              name: 'Phonepe',
              description: '',
              status: 'greyed',
              slug: 'phonepe',
              icon: 'phonepe',
            },
            {
              name: 'Airtel Money',
              description: '',
              status: 'greyed',
              slug: 'airtelmoney',
              icon: 'airtelmoney',
            },
            {
              name: 'Freecharge',
              description: '',
              status: 'greyed',
              slug: 'freecharge',
              icon: 'freecharge',
            },
            {
              name: 'Jio Money',
              description: '',
              status: 'greyed',
              slug: 'jiomoney',
              icon: 'jiomoney',
            },
            {
              name: 'Ola Money',
              description: '',
              status: 'greyed',
              slug: 'olamoney',
              icon: 'olamoney',
            },
            // {
            //   name: 'Payzapp',
            //   description: '',
            //   status: 'greyed',
            //   slug: 'payzapp',
            //   icon: 'payzapp',
            // },
            {
              name: 'Mobikwik',
              description: '',
              status: 'greyed',
              slug: 'mobikwik',
              icon: 'mobikwik',
            },
            {
              name: 'Itz Cash',
              description: '',
              status: 'greyed',
              slug: 'itzcash',
              icon: 'itzcash',
            },
            {
              name: 'PayCash',
              description: '',
              status: 'greyed',
              slug: 'paycash',
              icon: 'paycash',
            },
            {
              name: 'Citibank Reward Points',
              description: '',
              status: 'greyed',
              slug: 'citibankrewards',
              icon: 'citibankrewards',
            },
            {
              name: 'Bajaj Pay Wallet',
              description: '',
              status: 'greyed',
              slug: 'bajajpay',
              icon: 'bajajpay',
            },
          ],
        },
      ],
    },
    {
      name: 'Pay Later',
      description: 'Buy now and pay later with ePay Later',
      slug: 'paylater',
      icon: 'paylater',
      additionalCondition: (user) => !user.isOrgCurlec,
      actionItems: {},
      leafList: [
        {
          header: 'PayLater',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/pay-later/',
          list: [
            {
              name: 'Flexipay',
              description: '',
              status: 'greyed',
              slug: 'flexipay',
              icon: 'https://cdn.razorpay.com/paylater-sq/hdfc.svg',
            },
            // {
            //   name: 'ICICI',
            //   description: '',
            //   status: 'greyed',
            //   slug: 'icic',
            //   icon: 'icici',
            // },
            {
              name: 'Simpl',
              description: '',
              status: 'greyed',
              slug: 'getsimpl',
              icon: 'getsimpl',
            },
            // {
            //   name: 'Lazypay',
            //   description: '',
            //   status: 'greyed',
            //   slug: 'lazypay',
            //   icon: 'https://cdn.razorpay.com/paylater-sq/lazypay.svg',
            // },
          ],
        },
      ],
    },
    {
      name: 'International Payments',
      description: 'Cards, PayPal, USD ACH & more',
      slug: 'international',
      icon: 'international',
      actionItems: {},
      leafList: [
        {
          header: 'International Payments',
          docLink: 'https://razorpay.com/docs/payments/payments/international-payments/',
          slug: 'card',
          list: [
            {
              name: 'International Cards',
              description: 'On Payment Gateway, Pages, Links and Invoices',
              status: 'greyed',
              slug: 'internationalcards',
              icon: '',
            },
          ],
        },
        {
          header: 'More international payment methods',
          slug: 'moreinternationalmethods',
          listDescription:
            'Includes international bank transfer, local currency bank transfer and instant bank transfer',
          additionalCondition: (user) => user.international,
          list: [],
        },
        {
          header: 'International bank transfers',
          listHeader: '',
          listDescription: 'Cost effective for high-ticket transactions, supports 30+ countries',
          slug: 'moneysaverexportaccount',
          leafList: [
            {
              header: '',
              listHeader: 'Local Currency Bank Transfer',
              listDescription:
                'Setup a local account in all locations mentioned below to accept international payments',
              slug: 'localcurrencytransfer',
              list: [
                {
                  name: 'USD Bank Account',
                  description: 'Accept payments via ACH transfer',
                  message:
                    'Share the below details with your US customers to accept USD payments via ACH transfer',
                  vaCurrency: 'USD',
                  status: 'greyed',
                  slug: 'international.usd',
                  icon: 'https://cdn.razorpay.com/static/assets/instrument-request/usd.svg',
                },
                GBP_BANK_ACCOUNT,
                EUR_BANK_ACCOUNT,
              ],
            },
            {
              header: '',
              listHeader: 'International Bank Transfer',
              listDescription: 'Set up a SWIFT account to accept payments in various currencies',
              slug: 'swiftbanktransfer',
              list: [
                {
                  name: 'SWIFT Account',
                  description: 'Accept payments in more than 30 currencies',
                  message:
                    'Share the below details with your international customers to receive payments in various currencies',
                  vaCurrency: 'SWIFT',
                  status: 'greyed',
                  slug: 'international.swift',
                  icon: 'https://cdn.razorpay.com/static/assets/instrument-request/swift.svg',
                },
              ],
            },
          ],
        },
        {
          header: 'Apps',
          slug: 'wallet',
          list: [
            {
              name: 'PayPal',
              description: 'Accept International Payments using PayPal on Razorpay Checkout',
              status: 'greyed',
              slug: 'paypal',
              icon: 'paypal',
            },
          ],
        },
        {
          header: 'Bank Transfer Apps (International)',
          listHeader: 'Instant Bank Transfer',
          listDescription: 'Enable local payment methods for different geographies',
          slug: 'instantbanktransfer',
          list: [
            {
              name: 'Trustly',
              description: 'Instant bank transfer for Europe',
              status: 'greyed',
              slug: 'app.trustly',
              icon: 'trustly',
            },
            {
              name: 'POLI',
              description: 'Instant Bank Transfer for Australia',
              status: 'greyed',
              slug: 'app.poli',
              icon: 'poli',
            },
            {
              name: 'Giropay',
              description: 'Instant Bank Transfer for Germany',
              status: 'greyed',
              slug: 'app.giropay',
              icon: 'giropay',
            },
            {
              name: 'Sofort',
              description: 'Instant Bank Transfer for Europe',
              status: 'greyed',
              slug: 'app.sofort',
              icon: 'sofort',
            },
          ],
        },
      ],
    },
    {
      name: 'Meal Card/Pluxee',
      description: 'Cards and Meal Pass',
      actionItems: {},
      slug: 'meal-card',
      icon: 'mealcard',
      additionalCondition: (user) => user.isSodexoInstrumentEnabled && !user.isOrgCurlec,
      leafList: [
        {
          header: 'Pluxee',
          description:
            'Pluxee is supported via PayU. Please make sure it is enabled at downstream gateway too.',
          list: [
            {
              name: 'Pluxee',
              status: 'greyed',
              slug: 'domestic.sodexo',
              icon: 'sodexo',
            },
          ],
        },
      ],
    },
  ],
  intermediateInstrument: null,
  leafInstrument: null,
  loading: true,
};

/**
 *
 * @param {Array} pathToFind [cards, domestic, visa]
 * @param {Array} pg
 * @returns
 */

function findPath(pathToFind, pg) {
  let str = 'pg';

  // removes the first element from an array and returns it ->  'cards'
  let root = pathToFind.shift();

  if (pathToFind.includes('sodexo')) {
    root = 'meal-card';
  }

  const rootIndex = pg.findIndex((_) => _.slug === root);

  function setIntermediateList(intermediateIndex, index, leafIndex) {
    str = `${str}.intermediateList[${intermediateIndex}]`;
    str = `${str}.leafList[${index}].list[${leafIndex}]`;
  }

  if (rootIndex !== -1) {
    str = `${`${str}[${rootIndex}]`}`;
    if (
      pg[rootIndex].intermediateList &&
      Array.isArray(pg[rootIndex].intermediateList) &&
      pg[rootIndex].intermediateList.some((_) => _.slug)
    ) {
      const intermediate = pathToFind.shift();
      const intermediateIndex = pg[rootIndex].intermediateList.findIndex(
        (_) => _.slug === intermediate,
      );
      str = `${`${str}.intermediateList[${intermediateIndex}]`}`;
      if (pg[rootIndex].intermediateList[intermediateIndex]?.leafList) {
        const leafSlug = pathToFind.shift();
        let leafIndex;
        pg[rootIndex].intermediateList[intermediateIndex].leafList.every((leaf, index) => {
          leafIndex = leaf.list.findIndex((_) => _.slug === leafSlug);
          if (leafIndex !== -1) {
            str = `${`${str}.leafList[${index}].list[${leafIndex}]`}`;
            return false;
          }
          return true;
        });
      }
    } else if (
      pg[rootIndex].intermediateList &&
      Array.isArray(pg[rootIndex].intermediateList) &&
      !pg[rootIndex].intermediateList.some((_) => _.slug)
    ) {
      const leafSlug = pathToFind.shift();
      for (
        let intermediateIndex = 0;
        intermediateIndex < pg[rootIndex].intermediateList.length;
        intermediateIndex++
      ) {
        if (pg[rootIndex].intermediateList[intermediateIndex].leafList) {
          let leafIndex;
          pg[rootIndex].intermediateList[intermediateIndex].leafList.every((leaf, index) => {
            leafIndex = leaf.list.findIndex((_) => _.slug === leafSlug);
            if (leafIndex !== -1) {
              setIntermediateList(intermediateIndex, index, leafIndex);
              return false;
            }
            return true;
          });
        }
      }
    } else if (!pg[rootIndex].intermediateList) {
      const leafSlug = pathToFind.join('.');
      let leafIndex;
      pg[rootIndex].leafList.every((leaf, index) => {
        leafIndex = leaf.list.findIndex((_) => _.slug === leafSlug);
        if (leafIndex !== -1) {
          str = `${str}.leafList[${index}].list[${leafIndex}]`;
          return false;
        }
        return true;
      });
    }
    return str;
  }
  return str;
}

export default function instrumentRequestsReducer(state = initialState, action) {
  const pg = state.pg;
  switch (action.type) {
    case SET_LEAF_INSTRUMENT:
      return set(state, 'leafInstrument', action.payload);
    case SET_INTERMEDIATE_INSTRUMENT:
      return set(state, 'intermediateInstrument', action.payload);
    case CLEAR_INTERMEDIATE_INSTRUMENT:
      return set(state, 'intermediateInstrument', null);
    case CLEAR_LEAF_INSTRUMENT:
      return set(state, 'leafInstrument', null);
    case SET_LOADING:
      return set(state, 'loading', true);
    case `${GET_DISCREPANCY_CATEGORIES}::SUCCESS`:
      return set(state, 'discrepancyCategories', action.payload.data);
    case `${GET_IIR_DISCREPANCIES}::PENDING`:
      return set(state, 'loading', true);
    case `${GET_IIR_DISCREPANCIES}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      lodashset(stateClone, 'loading', false);
      lodashset(stateClone, 'merchantDiscrepancies', action.payload.data);
      return stateClone;
    }
    case `${FETCH_ALL_MERCHANT_INSTRUMENTS}::ERROR`: {
      return set(state, 'loading', false);
    }
    case `${FETCH_ALL_MERCHANT_INSTRUMENTS}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      action.payload.data.forEach((s) => {
        const pathToFind = s.instrument.replace('pg.', '').split('.'); // pg.cards.domestic.visa -> cards.domestic.visa -> [cards, domestic, visa]
        const path = findPath(pathToFind, pg);
        if (s.comment && ['action_required', 'rejected'].includes(s.status)) {
          const rootPath = path.split('.')[0];
          lodashset(stateClone, `${rootPath}.actionItems["${s.instrument}"]`, s.comment);
        }
        lodashset(
          stateClone,
          `${path}.merchant_instrument_request_id`,
          s.merchant_instrument_request_id,
        );
        lodashset(stateClone, `${path}.path`, s.instrument);
        lodashset(stateClone, `${path}.status`, s.status);
        lodashset(
          stateClone,
          `${path}.created_at`,
          s.status === REQUESTED ? s.updated_at : s.created_at,
        );
        lodashset(
          stateClone,
          `${path}.capture_info_before_mir`,
          s?.capture_info_before_mir?.redirect_to_form?.fields,
        );
        lodashset(
          stateClone,
          `${path}.collect_info`,
          s?.capture_info_before_mir?.collect_info?.fields,
        );
        if (['action_required', 'rejected', 'activated_action_required'].includes(s.status)) {
          lodashset(stateClone, `${path}.comment`, s.comment);
          lodashset(
            stateClone,
            `${path}.should_show_smart_dashboard_flow`,
            s.should_show_smart_dashboard_flow,
          );
          lodashset(
            stateClone,
            `${path}.should_show_reinitiate_button`,
            s.should_show_reinitiate_button,
          );
        }
        if (s.status === 'greyed') {
          lodashset(stateClone, `${path}.fade_comment`, s.fade_comment);
        }
      });
      lodashset(stateClone, 'loading', false);
      return stateClone;
    }
    case `${FETCH_REQUESTED_MERCHANT_INSTRUMENTS}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      const instrumentsTat = {};
      action.payload.data.forEach(({ instrument, tat }) => {
        instrumentsTat[instrument] = tat;
      });
      lodashset(stateClone, 'instrumentsTat', instrumentsTat);
      return stateClone;
    }
    case `${CREATE_INSTRUMENT_REQUEST}::SUCCESS`: {
      let updatedLeafIndex, pathToUpdate;
      const stateClone = cloneDeep(state);
      stateClone.leafInstrument.leafList.every((leaf, index) => {
        updatedLeafIndex = leaf.list.findIndex((_) => {
          return _.slug.includes(action.payload.data.instrument.split('.').pop());
        });
        if (updatedLeafIndex !== -1) {
          pathToUpdate = `leafInstrument.leafList[${index}].list[${updatedLeafIndex}]`;
          return false;
        }
        return true;
      });
      if (updatedLeafIndex !== -1) {
        lodashset(
          stateClone,
          `${pathToUpdate}.merchant_instrument_request_id`,
          action.payload.data.merchant_instrument_request_id,
        );
        lodashset(stateClone, `${pathToUpdate}.status`, action.payload.data.status);
        lodashset(stateClone, `${pathToUpdate}.created_at`, action.payload.data.created_at);
        lodashset(
          stateClone,
          `${pathToUpdate}.capture_info_before_mir`,
          action.payload.data?.capture_info_before_mir?.redirect_to_form?.fields,
        );
        lodashset(
          stateClone,
          `${pathToUpdate}.collect_info`,
          action.payload.data?.capture_info_before_mir?.collect_info?.fields,
        );
        return stateClone;
      }
      return state;
    }

    case `${CANCEL_INSTRUMENT_REQUEST}::SUCCESS`: {
      let cancelLeafIndex, pathToCancel;
      const stateClone = cloneDeep(state);
      stateClone.leafInstrument.leafList.every((leaf, index) => {
        cancelLeafIndex = leaf.list.findIndex((_) => {
          return _.slug.includes(action.payload.data.instrument.split('.').pop());
        });

        if (cancelLeafIndex !== -1) {
          pathToCancel = `leafInstrument.leafList[${index}].list[${cancelLeafIndex}]`;
          return false;
        }
        return true;
      });
      if (cancelLeafIndex !== -1) {
        lodashset(stateClone, `${pathToCancel}.status`, action.payload.data.status);
        return stateClone;
      }
      return state;
    }
    case `${REINITIATE_INSTRUMENT_REQUEST}::SUCCESS`: {
      let reinitiateLeafIndex, pathToReinitiate;
      const stateClone = cloneDeep(state);
      stateClone.leafInstrument.leafList.every((leaf, index) => {
        reinitiateLeafIndex = leaf.list.findIndex((_) => {
          return _.slug.includes(action.payload.data.instrument.split('.').pop());
        });

        if (reinitiateLeafIndex !== -1) {
          pathToReinitiate = `leafInstrument.leafList[${index}].list[${reinitiateLeafIndex}]`;
          return false;
        }
        return true;
      });
      if (reinitiateLeafIndex !== -1) {
        lodashset(stateClone, `${pathToReinitiate}.status`, action.payload.data.status);
        return stateClone;
      }
      return state;
    }
    default:
      return state;
  }
}
