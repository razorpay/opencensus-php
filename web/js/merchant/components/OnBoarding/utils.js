import moment from 'moment';

import LocalStorageService from 'rzp/utils/localStorage';

import { getUser, getMode } from 'merchant/store';

export const getOnBoardingKey = feature => {
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

export const getOnBoardingDataFromLocalState = feature => {
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

export const getIsAllowedResetBoarding = feature => {
  const { lastVisitedTime } = getOnBoardingDataFromLocalState(feature);

  const momentLastVisitedTime = moment(lastVisitedTime),
    currentTime = moment(Date.now());

  return currentTime.diff(momentLastVisitedTime, 'days') >= 15;
};
