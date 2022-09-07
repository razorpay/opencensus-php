import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

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
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';

import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PP),
  }),
  {
    handleProductQuickGuide,
  },
)
@OnBoarding({
  feature: RZPFeatures.PP,
})
export default class PaymentPagesOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.PP}
        onClick={this.closeOnboarding}
        page={sliderProps.active}
        additionalTrackData={{
          is_creation_redirection_enabled: this.props.user
            .isPaymentPageOnboardingRedirectionEnabled,
        }}
      />
    );
  };

  closeOnboarding = () => {
    const { user, paymentPageProductOnBoarding, closeOnboarding, history } = this.props;

    if (user.isPaymentPagesEnabled && !paymentPageProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PP, false);
    }

    closeOnboarding();

    /*
      For an experiment being run 50% where on the click of skip/get started, rather
      than landing on the list view, the user is redirected to the create flow
    */
    if (user.isPaymentPageOnboardingRedirectionEnabled) {
      history.push('/paymentpages/new');
    }
  };

  render() {
    const { active, paymentPageProductOnBoarding, user } = this.props;

    return (
      <OnBoardingWrapper class="PaymentPages">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            paymentPageProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
            isCreationRedirectionEnabled: user.isPaymentPageOnboardingRedirectionEnabled,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              title="Payment Pages"
              feature={RZPFeatures.PP}
              imageUrl="https://razorpay.com/assets/paymentpages/hero-main.svg"
              desc="Build a custom, branded payment page for your business in under 10 minutes and start accepting international and domestic payments with zero integration and tech efforts."
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Payment Pages great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              feature={RZPFeatures.PP}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({
  closeOnboarding,
  paymentPageProductOnBoarding,
  isCreationRedirectionEnabled,
}) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        feature={RZPFeatures.PP}
        onClick={closeOnboarding}
        page={sliderProps.active}
        isTour={paymentPageProductOnBoarding.isTour}
        additionalTrackData={{ is_creation_redirection_enabled: isCreationRedirectionEnabled }}
      />
    </SliderDots>
  );
}

export function getIsAllowedPaymentPagesResetOnBoarding({ paymentPages, loading }) {
  if (paymentPages.length || loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.PP);
}

export function getIsPaymentPagesEnabled({ user, paymentPages, loading }) {
  if (user.isPaymentPagesEnabled || loading) {
    return true;
  }

  if (paymentPages.length) {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.PP,
      data: {
        isEnabled: true,
      },
    });

    return true;
  }

  return false;
}
