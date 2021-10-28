import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import {
  getIsAllowedResetBoarding,
  getOnBoardingKey,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
} from './utils';
import track from './track';

import { NextButton, SkipAndGetStartedButton, FeatureEnableSliderButton } from './utilButtons';

export default (params) => {
  const { feature: FEATURE } = params;

  let WrappedComponent;
  @RTracking(() => window.rzpQ.component('OnboardingContainer'))
  @connect(
    (state) => ({
      currentOnboarding: getCurrentProductOnBoardingDetails(state, FEATURE),
    }),
    {
      handleProductQuickGuide,
    },
  )
  class OnBoardingHOC extends React.Component {
    constructor(props) {
      super(props);

      this.state = {
        active: props.active || 0,
      };
    }

    componentDidMount() {
      if (typeof window.hj === 'function') {
        window.hj('trigger', 'product_onboarding_intro');
        window.hj('tagRecording', [`${FEATURE}_onboarding`]);
      }

      track.init(this.props.tracking.trackEvent);
    }

    goTo = (active) => {
      this.setState({ active });
    };

    closeOnboarding = () => {
      setOnBoardingDataInLocalState({
        feature: FEATURE,
        data: {
          isEnabled: true,
          lastVisitedTime: Date.now(),
        },
      });

      this.props.handleProductQuickGuide({
        ...this.props.currentOnboarding,
        showOnboarding: false,
      });
    };

    render() {
      return (
        <WrappedComponent
          active={Number(this.state.active)}
          goTo={this.goTo}
          closeOnboarding={this.closeOnboarding}
          {...this.props}
        />
      );
    }
  }

  return (_WrappedComponent) => {
    WrappedComponent = _WrappedComponent;

    return OnBoardingHOC;
  };
};

export const OnBoardingWrapper = ({ className, children }) => (
  <div class={`OnBoarding OnBoarding--${className}`}>{children}</div>
);

export {
  NextButton,
  SkipAndGetStartedButton,
  FeatureEnableSliderButton,
  getOnBoardingKey,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
  getIsAllowedResetBoarding,
};
