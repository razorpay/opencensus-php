import moment from 'moment';

import LocalStorageService from 'rzp/utils/localStorage';

import { getUser, getMode } from 'merchant/store';

import Button from 'component/Button';

import FeatureEnableButton from './FeatureEnableButton';

export const FeatureEnableSliderButton = props => {
  const { isLocalEnabler, feature, onClick, page } = props;

  const extraProps = {
    feature,
  };

  if (isLocalEnabler) {
    extraProps.iconAfter = 'arrow-forward';
    extraProps.isLocalEnabler = true;
  }

  return (
    <FeatureEnableButton.Primary
      {...extraProps}
      class="Forward-Button"
      pendingText="Enabling..."
      onClick={(...args) => {
        window.rzpAnalytics({
          eventCategory: `Product Introduction (${feature})`,
          eventAction: `Page ${page} - Get Started CTA`,
        });

        onClick && onClick(args);
      }}
    >
      Get Started
    </FeatureEnableButton.Primary>
  );
};

export const NextButton = ({ feature, onClick, page }) => (
  <Button.Primary
    feature={feature}
    class="Forward-Button"
    iconAfter="arrow-forward"
    pendingText="Enabling..."
    onClick={(...args) => {
      window.rzpAnalytics({
        eventCategory: `Product Introduction (${feature})`,
        eventAction: `Page ${page} - Next CTA`,
      });

      onClick && onClick(args);
    }}
  >
    Get Started
  </Button.Primary>
);

export const SkipAndGetStartedButton = ({
  feature,
  onClick,
  page,
  isLocalEnabler,
}) => (
  <FeatureEnableButton.Transparent
    onClick={(...args) => {
      window.rzpAnalytics({
        eventCategory: `Product Introduction (${feature})`,
        eventAction: `Page ${page} - Skip and Get Started`,
      });

      onClick && onClick(args);
    }}
    feature={feature}
    isLocalEnabler={isLocalEnabler}
  >
    Skip And Get Started
  </FeatureEnableButton.Transparent>
);

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
        isEnabled: false,
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
