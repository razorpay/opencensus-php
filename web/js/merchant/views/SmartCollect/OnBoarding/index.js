import React from 'react';
import { connect } from 'react-redux';

import { RZPFeatures } from 'merchant/helpers/data';
import RTracking from 'react-tracking';

import Slider, { SliderDots } from 'common/new-ui/Slider';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import OnBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS, PROS } from './data';

import ImgSmartCollect from 'assets/product_onboarding/smart_collect.svg';
import { compose } from 'redux';

class InvoicesOnBoarding extends React.Component {
  track = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.smartCollect().interaction(`smartcollect.va.onboarding.${event}`, options),
    );
  };

  getNextButton = (sliderProps) => () => {
    const props = {
      feature: RZPFeatures.VA,
      onClick: (...args) => {
        this.track('screen-2');

        return this.props.closeOnboarding(...args);
      },
      page: sliderProps.active,
    };

    if (this.props.user.isVirtualAccountsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  renderSkipButton = (sliderProps) => {
    const props = {
      feature: RZPFeatures.VA,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
      isTour: this.props.VAProductOnBoarding.isTour,
    };

    if (this.props.user.isVirtualAccountsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <SkipAndGetStartedButton {...props} />;
  };

  closeOnboarding = () => {
    if (!this.props.VAProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.VA, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active } = this.props;

    return (
      <OnBoardingWrapper className="SmartCollect">
        <Slider active={active} afterSlide={getOnBoardingSliderDots(this.renderSkipButton)}>
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.VA}
              title="Smart Collect"
              imageUrl={ImgSmartCollect}
              desc="Automate reconciliation by generating unlimited Customer Identifiers and Virtual UPI IDs on demand. Accept payments via NEFT, RTGS and IMPS."
              pros={PROS}
              next={(...args) => {
                this.track('screen-1');
                sliderProps.next(...args);
              }}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              feature={RZPFeatures.VA}
              title="What makes Smart Collect great?"
              nextBtn={this.getNextButton(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots(renderSkipButton) {
  return (sliderProps) => <SliderDots {...sliderProps}>{renderSkipButton(sliderProps)}</SliderDots>;
}

export function getIsAllowedResetVAOnBoarding({ items, loading }) {
  if (items.length || loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.VA);
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      VAProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.VA),
    }),
    { handleProductQuickGuide },
  ),
  RTracking(() => window.rzpQ.component('InvoicesOnBoarding')),
  OnBoarding({
    feature: RZPFeatures.VA,
  }),
)(InvoicesOnBoarding);
