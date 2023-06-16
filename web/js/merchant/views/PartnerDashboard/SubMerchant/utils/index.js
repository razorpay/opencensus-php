import { PRODUCT_TYPE, ADD_MODE } from 'merchant/views/PartnerDashboard/constants';
import moment from 'moment';

export const minLength = (length, message = '') => {
  message = message || `Enter min ${length} characters`;

  return (value = '') => {
    const updatedValue = value.trim().replace(/\s+/g, ' ');
    return updatedValue.length < length ? message : '';
  };
};

export const numberDifferentiation = (value) => {
  let val = Math.abs(value);
  if (val >= 10000000) {
    val = `${(val / 10000000).toFixed(2)} Cr`;
  } else if (val >= 100000) {
    val = `${(val / 100000).toFixed(2)} L`;
  }
  return val;
};

export const getInitialState = ({ user, addType, referralData }) => {
  const { isPartnershipForXEnabled } = user;
  const state = {
    file_id: '',
    addMode: ADD_MODE.single,
    bulkContactsCount: 0,
    step: 1,
    merchantType: PRODUCT_TYPE.PG,
    merchantEmail: '',
    merchantName: '',
    merchantContact: '',
    referralData: referralData || '',
    isFormValid: false,
  };
  switch (addType) {
    case PRODUCT_TYPE.PG: {
      state.step = 1;
      state.merchantType = PRODUCT_TYPE.PG;
      break;
    }
    case PRODUCT_TYPE.X: {
      state.step = 2;
      state.merchantType = PRODUCT_TYPE.X;
      break;
    }
    case PRODUCT_TYPE.CAPITAL: {
      state.step = 2;
      state.merchantType = PRODUCT_TYPE.CAPITAL;
      state.addMode = ADD_MODE.bulk;
      break;
    }
    default: {
      if (!isPartnershipForXEnabled) {
        state.step = 1;
        state.merchantType = PRODUCT_TYPE.PG;
      } else {
        state.step = 1;
        state.merchantType = PRODUCT_TYPE.X;
      }
    }
  }
  return state;
};

export const isInviteRecentlyAccepted = (created_at) => {
  const momentInviteAcceptedOn = moment(created_at * 1000);
  const currentTime = moment(Date.now());
  return currentTime.diff(momentInviteAcceptedOn, 'days') <= 7;
};
