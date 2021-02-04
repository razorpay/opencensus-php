import moment from 'moment';

import LocalStorageService from 'common/utils/localStorage';

import { getUser, getMode } from 'merchant/store';

export const getOnBoardingKey = (feature) => {
  const mode = getMode(),
    user = getUser();

  return `rzp_onboarding_${user.current}_${mode}_${feature}`;
};

export const setOnBoardingDataInLocalState = ({ feature, data }) => {
  const KEY = getOnBoardingKey(feature);

  const dataFromState = getOnBoardingDataFromLocalState(feature);

  const state = JSON.stringify({
    ...dataFromState,
    ...data,
  });

  LocalStorageService.setItem(KEY, state);
};

export const getOnBoardingDataFromLocalState = (feature) => {
  const KEY = getOnBoardingKey(feature);

  const state = LocalStorageService.getItem(KEY);

  return state
    ? JSON.parse(state)
    : {
        isEnabled: undefined,
        lastVisitedScreen: 0,
        lastVisitedTime: null,
      };
};

export const getIsAllowedResetBoarding = (feature) => {
  const { lastVisitedTime } = getOnBoardingDataFromLocalState(feature);

  const momentLastVisitedTime = moment(lastVisitedTime),
    currentTime = moment(Date.now());

  return currentTime.diff(momentLastVisitedTime, 'days') >= 15;
};

export const GTAG_KEYS = {
  activeAccountSuccessRegWhitelist: 'AW-928471290/odzRCLiL8vABEPqx3boD',
  activeAccountSuccessRegGreylist: 'AW-928471290/kYYECKeP8vABEPqx3boD',
  activeAccountSuccessUnReg: 'AW-928471290/8__rCJC_0vABEPqx3boD',
  kycSubmitSuccessRegWhitelist: 'AW-928471290/4xsICMLB0vABEPqx3boD',
  kycSubmitSuccessRegGreylist: 'AW-928471290/0jknCP3f5PABEPqx3boD',
  kycSubmitSuccessUnReg: 'AW-928471290/O7-6CMiY8vABEPqx3boD',
  liveMTUReg: 'AW-928471290/c8-4CL3X0vABEPqx3boD',
  liveMTUUnReg: 'AW-928471290/x8bGCLig8vABEPqx3boD',
};

/**
 * Invokes GTAG for conversion tracking.
 */
export const invokeGtag = (GTAG_KEY) => {
  if (window.location.hostname !== 'dashboard.razorpay.com') {
    return;
  }
  gtag('event', 'conversion', {
    send_to: GTAG_KEY,
  });
};
