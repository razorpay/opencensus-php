import React from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import track from './track';
import { NextButton, SkipAndGetStartedButton, FeatureEnableSliderButton } from './utilButtons';
import {
  getIsAllowedResetBoarding,
  getOnBoardingKey,
  setOnBoardingDataInLocalState,
  getOnBoardingDataFromLocalState,
} from './utils';

export default (params) => {
  const { feature: FEATURE } = params;

  let WrappedComponent;

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

  const ConnectedOnBoardingHOC = compose(
    connect(
      (state) => ({
        currentOnboarding: getCurrentProductOnBoardingDetails(state, FEATURE),
      }),
      {
        handleProductQuickGuide,
      },
    ),
    rTracking(() => window.rzpQ.component('OnboardingContainer')),
  )(OnBoardingHOC);

  return (_WrappedComponent) => {
    WrappedComponent = _WrappedComponent;

    return ConnectedOnBoardingHOC;
  };
};

export const OnBoardingWrapper = ({ className, children }) => (
  <div className={`OnBoarding OnBoarding--${className}`}>{children}</div>
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
