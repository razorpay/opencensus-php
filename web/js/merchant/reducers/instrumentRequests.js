import React from 'react';
import { merchantFetch } from 'merchant/utils/ajax';
import { set } from 'common/utils/immutable';
import lodashset from 'lodash/set';
import cloneDeep from 'lodash/cloneDeep';
import { REQUESTED } from '../views/Settings/PaymentMethods/constants';

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

const initialState = {
  pg: [
    {
      name: 'Cards',
      description: 'Visa, Master, Amex',
      actionItems: {},
      slug: 'cards',
      icon: 'card',

      leafList: [
        {
          header: 'Domestic Cards',
          list: [
            {
              name: 'Visa Cards',
              status: 'Request',
              slug: 'domestic.visa',
              icon: 'visa',
            },
            {
              name: 'MasterCard',
              status: 'Request',
              slug: 'domestic.mastercard',
              icon: 'masterCard',
            },
            {
              name: 'Rupay Cards',
              status: 'Request',
              slug: 'domestic.rupay',
              icon: 'rupay',
            },
            {
              name: 'Maestro',
              status: 'Request',
              slug: 'domestic.maestro',
              icon: 'maestro',
            },
            {
              name: 'Amex Cards',
              status: 'Request',
              slug: 'domestic.amex',
              icon: 'amex',
            },
            {
              name: 'Diners Club',
              status: 'Request',
              slug: 'domestic.dicl',
              icon: 'diners',
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
      actionItems: {},
      leafList: [
        {
          header: 'UPI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/upi',
          list: [
            {
              name: 'UPI',
              status: 'Request',
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
            //   status: 'Request',
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
      ],
    },
    {
      name: 'Netbanking',
      description: 'All Indian Banks',
      slug: 'netbanking',
      icon: 'netbanking',
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
                { name: 'HDFC Bank', status: 'Request', slug: 'hdfc' },
                {
                  name: 'State Bank of India',
                  status: 'Request',
                  slug: 'sbin',
                },
                { name: 'Axis Bank', status: 'Request', slug: 'utib' },
                { name: 'ICICI Bank', status: 'Request', slug: 'icic' },
                { name: 'City Union Bank', status: 'Request', slug: 'ciub' },
                {
                  name: 'Standard Chartered Bank',
                  status: 'Request',
                  slug: 'scbl',
                },
                {
                  name: 'AU Small Finance Bank',
                  status: 'Request',
                  slug: 'aubl',
                },
                {
                  name: 'Airtel Payments Bank',
                  status: 'Request',
                  slug: 'airp',
                },
                {
                  name: 'Allahabad Bank',
                  status: 'Request',
                  slug: 'alla',
                },
                {
                  name: 'Andhra Bank',
                  status: 'Request',
                  slug: 'andb',
                },
                {
                  name: 'Bandhan Bank',
                  status: 'Request',
                  slug: 'bdbl',
                },
                {
                  name: 'Bank of Bahrain and Kuwait',
                  status: 'Request',
                  slug: 'bbkm',
                },
                {
                  name: 'Bank of Baroda',
                  status: 'Request',
                  slug: 'barb_r',
                },
                {
                  name: 'Bank of India',
                  status: 'Request',
                  slug: 'bkid',
                },
                {
                  name: 'Bank of Maharashtra',
                  status: 'Request',
                  slug: 'mahb',
                },
                {
                  name: 'Bassein Catholic Co-operative Bank',
                  status: 'Request',
                  slug: 'bacb',
                },
                {
                  name: 'Canara Bank',
                  status: 'Request',
                  slug: 'cnrb',
                },
                {
                  name: 'Catholic Syrian Bank',
                  status: 'Request',
                  slug: 'csbk',
                },
                {
                  name: 'Central Bank of India',
                  status: 'Request',
                  slug: 'cbin',
                },
                {
                  name: 'Cosmos Co-operative Bank',
                  status: 'Request',
                  slug: 'cosb',
                },
                {
                  name: 'DCB Bank',
                  status: 'Request',
                  slug: 'dcbl',
                },
                {
                  name: 'Dena Bank',
                  status: 'Request',
                  slug: 'bkdn',
                },
                {
                  name: 'Deutsche Bank',
                  status: 'Request',
                  slug: 'deut',
                },
                {
                  name: 'Development Bank of Singapore',
                  status: 'Request',
                  slug: 'dbss',
                },
                {
                  name: 'Dhanlaxmi Bank',
                  status: 'Request',
                  slug: 'dlxb',
                },
                {
                  name: 'ESAF Small Finance Bank',
                  status: 'Request',
                  slug: 'esaf',
                },
                {
                  name: 'Equitas Small Finance Bank',
                  status: 'Request',
                  slug: 'esfb',
                },
                {
                  name: 'Federal Bank',
                  status: 'Request',
                  slug: 'fdrl',
                },
                {
                  name: 'IDBI',
                  status: 'Request',
                  slug: 'ibkl',
                },
                {
                  name: 'IDFC FIRST Bank',
                  status: 'Request',
                  slug: 'idfb',
                },
                {
                  name: 'Indian Bank',
                  status: 'Request',
                  slug: 'idib',
                },
                {
                  name: 'Indian Overseas Bank',
                  status: 'Request',
                  slug: 'ioba',
                },
                {
                  name: 'Indusind Bank',
                  status: 'Request',
                  slug: 'indb',
                },
                {
                  name: 'Jammu and Kashmir Bank',
                  status: 'Request',
                  slug: 'jaka',
                },
                {
                  name: 'Jana Small Finance Bank',
                  status: 'Request',
                  slug: 'jsfb',
                },
                {
                  name: 'Janata Sahakari Bank (Pune)',
                  status: 'Request',
                  slug: 'jsbp',
                },
                {
                  name: 'Kalupur Commercial Co-operative Bank',
                  status: 'Request',
                  slug: 'kccb',
                },
                {
                  name: 'Kalyan Janata Sahakari Bank',
                  status: 'Request',
                  slug: 'kjsb',
                },
                {
                  name: 'Karnataka Bank',
                  status: 'Request',
                  slug: 'karb',
                },
                {
                  name: 'Karur Vysya Bank',
                  status: 'Request',
                  slug: 'kvbl',
                },
                {
                  name: 'Kotak Mahindra Bank',
                  status: 'Request',
                  slug: 'kkbk',
                },
                {
                  name: 'Mehsana Urban Co-operative Bank',
                  status: 'Request',
                  slug: 'msnu',
                },
                {
                  name: 'NKGSB Co-operative Bank',
                  status: 'Request',
                  slug: 'nkgs',
                },
                {
                  name: 'North East Small Finance Bank',
                  status: 'Request',
                  slug: 'nesf',
                },
                {
                  name: 'Oriental Bank of Commerce',
                  status: 'Request',
                  slug: 'orbc',
                },
                {
                  name: 'United Bank Of India',
                  status: 'Request',
                  slug: 'utbi',
                },
                {
                  name: 'Punjab & Sind Bank',
                  status: 'Request',
                  slug: 'psib',
                },
                {
                  name: 'Punjab National Bank',
                  status: 'Request',
                  slug: 'punb_r',
                },
                {
                  name: 'Ratnakar Bank',
                  status: 'Request',
                  slug: 'ratn',
                },
                {
                  name: 'Saraswat Cooperative Bank',
                  status: 'Request',
                  slug: 'srcb',
                },
                // {
                //   name: 'SVCB Co-operative Bank',
                //   status: 'Request',
                //   slug: 'svcb',
                // },
                {
                  name: 'South Indian Bank',
                  status: 'Request',
                  slug: 'sibl',
                },
                {
                  name: 'State Bank of Bikaner and Jaipur',
                  status: 'Request',
                  slug: 'sbbj',
                },
                {
                  name: 'State Bank of Hyderabad',
                  status: 'Request',
                  slug: 'sbhy',
                },
                {
                  name: 'State Bank of Mysore',
                  status: 'Request',
                  slug: 'sbmy',
                },
                {
                  name: 'State Bank of Patiala',
                  status: 'Request',
                  slug: 'stbp',
                },
                {
                  name: 'State Bank of Travancore',
                  status: 'Request',
                  slug: 'sbtr',
                },
                {
                  name: 'Suryoday Small Finance Bank',
                  status: 'Request',
                  slug: 'sury',
                },
                {
                  name: 'Syndicate Bank',
                  status: 'Request',
                  slug: 'synb',
                },
                {
                  name: 'Tamilnadu Mercantile Bank',
                  status: 'Request',
                  slug: 'tmbl',
                },
                {
                  name: 'Tamilnadu State Apex Co-operative Bank ',
                  status: 'Request',
                  slug: 'tnsc',
                },
                {
                  name: 'Thane Bharat Sahakari Bank',
                  status: 'Request',
                  slug: 'tbsb',
                },
                {
                  name: 'Thane Janata Sahakari Bank',
                  status: 'Request',
                  slug: 'tjsb',
                },
                // {
                //   name: 'UCO Bank',
                //   status: 'Request',
                //   slug: 'ucba',
                // },
                {
                  name: 'Union Bank of India',
                  status: 'Request',
                  slug: 'ubin',
                },
                {
                  name: 'Corporation Bank',
                  status: 'Request',
                  slug: 'corp',
                },
                {
                  name: 'Varachha Co-operative Bank',
                  status: 'Request',
                  slug: 'vara',
                },
                {
                  name: 'Vijaya Bank',
                  status: 'Request',
                  slug: 'vijb',
                },
                {
                  name: 'Yes Bank',
                  status: 'Request',
                  slug: 'yesb',
                },
                {
                  name: 'Zoroastrian Co-operative Bank',
                  status: 'Request',
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
                { name: 'Andhra Bank', status: 'Request', slug: 'andb_c' },
                {
                  name: 'Shamrao Vithal Co-operative Bank',
                  status: 'Request',
                  slug: 'svcb_c',
                },
                {
                  name: 'Bank of Baroda',
                  status: 'Request',
                  slug: 'barb_c',
                },
                {
                  name: 'Punjab National Bank',
                  status: 'Request',
                  slug: 'punb_c',
                },
                {
                  name: 'YES Bank',
                  status: 'Request',
                  slug: 'yesb_c',
                },
                { name: 'ICICI Bank', status: 'Request', slug: 'icic_c' },
                { name: 'Axis Bank', status: 'Request', slug: 'utib_c' },
                {
                  name: 'IDBI',
                  status: 'Request',
                  slug: 'ibkl_c',
                },
                {
                  name: 'Bank of India',
                  status: 'Request',
                  slug: 'bkid_c',
                },
                {
                  name: 'Ratnakar Bank',
                  status: 'Request',
                  slug: 'ratn_c',
                },
                {
                  name: 'Dhanlaxmi Bank',
                  status: 'Request',
                  slug: 'dlxb_c',
                },
                {
                  name: 'Kotak Mahindra Bank',
                  status: 'Request',
                  slug: 'kkbk_c',
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
      actionItems: {},
      leafList: [
        {
          header: 'Debit Card EMI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/emi/debit-card-emi/',
          list: [
            {
              name: 'HDFC Bank',
              description: '',
              status: 'Request',
              slug: 'debit.hdfc',
              icon: 'https://cdn.razorpay.com/paylater-sq/hdfc.svg',
            },
          ],
        },
        {
          header: 'Credit Card EMI',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/emi/',
          list: [
            {
              name: 'Credit Cards',
              description: '',
              status: 'Request',
              slug: 'credit',
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
              status: 'Request',
              slug: 'cardless_emi.zestmoney',
              icon: 'zestmoney',
            },
            {
              name: 'Early Salary',
              description: '',
              status: 'Request',
              slug: 'cardless_emi.earlysalary',
              icon: 'earlysalary',
            },
            {
              name: 'Instacred',
              description: '',
              status: 'Request',
              slug: 'cardless_emi.instacred',
              icon: 'instacred',
            },
            {
              name: 'Sezzle',
              description: '',
              status: 'Request',
              slug: 'cardless_emi.sezzle',
              icon: 'sezzle',
            },
            {
              name: 'Walnut369',
              description: '',
              status: 'Request',
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
      actionItems: {},
      leafList: [
        {
          header: 'Wallets',
          list: [
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
              status: 'Request',
              slug: 'phonepe',
              icon: 'phonepe',
            },
            {
              name: 'Airtel Money',
              description: '',
              status: 'Request',
              slug: 'airtelmoney',
              icon: 'airtelmoney',
            },
            {
              name: 'Freecharge',
              description: '',
              status: 'Request',
              slug: 'freecharge',
              icon: 'freecharge',
            },
            {
              name: 'Jio Money',
              description: '',
              status: 'Request',
              slug: 'jiomoney',
              icon: 'jiomoney',
            },
            {
              name: 'Ola Money',
              description: '',
              status: 'Request',
              slug: 'olamoney',
              icon: 'olamoney',
            },
            {
              name: 'Payzapp',
              description: '',
              status: 'Request',
              slug: 'payzapp',
              icon: 'payzapp',
            },
            {
              name: 'Mobikwik',
              description: '',
              status: 'Request',
              slug: 'mobikwik',
              icon: 'mobikwik',
            },
            {
              name: 'Itz Cash',
              description: '',
              status: 'Request',
              slug: 'itzcash',
              icon: 'itzcash',
            },
            {
              name: 'PayCash',
              description: '',
              status: 'Request',
              slug: 'paycash',
              icon: 'paycash',
            },
            {
              name: 'Citibank Reward Points',
              description: '',
              status: 'Request',
              slug: 'citibankrewards',
              icon: 'citibankrewards',
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
      actionItems: {},
      leafList: [
        {
          header: 'PayLater',
          docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/pay-later/',
          list: [
            {
              name: 'Flexipay',
              description: '',
              status: 'Request',
              slug: 'flexipay',
              icon: 'https://cdn.razorpay.com/paylater-sq/hdfc.svg',
            },
            {
              name: 'ICICI',
              description: '',
              status: 'Request',
              slug: 'icic',
              icon: 'icici',
            },
            {
              name: 'Simpl',
              description: '',
              status: 'Request',
              slug: 'getsimpl',
              icon: 'getsimpl',
            },
            // {
            //   name: 'ePayLater',
            //   description: '',
            //   status: 'Request',
            //   slug: 'epaylater',
            //   icon: 'epaylater',
            // },
          ],
        },
      ],
    },
    {
      name: 'International Payments',
      description: 'Cards, Paypal',
      slug: 'international',
      icon: 'international',
      actionItems: {},
      leafList: [
        {
          header: 'International Payments',
          docLink: 'https://razorpay.com/accept-international-payments/',
          list: [
            {
              name: 'International Cards',
              description: 'On Payment Gateway, Pages, Links and Invoices',
              status: 'Request',
              slug: 'internationalcards',
              icon: '',
            },
            {
              name: 'Paypal',
              description: 'Accept International Payments using PayPal on Razorpay Checkout',
              status: 'Request',
              slug: 'paypal',
              icon: 'paypal',
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

function findPath(pathToFind, pg) {
  let str = 'pg';
  const root = pathToFind.shift();
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
      if (pg[rootIndex].intermediateList[intermediateIndex].leafList) {
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
    case `${FETCH_ALL_MERCHANT_INSTRUMENTS}::ERROR`:
      return set(state, 'loading', false);
    case `${FETCH_ALL_MERCHANT_INSTRUMENTS}::SUCCESS`: {
      const stateClone = cloneDeep(state);
      action.payload.data.forEach((s) => {
        const pathToFind = s.instrument.replace('pg.', '').split('.');
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
      lodashset(stateClone, 'intermediateInstrument', null);
      lodashset(stateClone, 'leafInstrument', null);
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
