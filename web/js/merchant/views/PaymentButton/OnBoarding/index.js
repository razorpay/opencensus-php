/* eslint-disable react/no-this-in-sfc */
import React from 'react';
import { connect } from 'react-redux';
import imgPaymentButtonRzp from 'assets/product_onboarding/payment_button.svg';

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
  getOnBoardingDataFromLocalState,
} from 'merchant/components/OnBoarding';

import { FEATURES_DATA_ORG, FEATURES_LINKS } from './data';
import { setIsPaymentButtonCodeUsed } from 'merchant/views/PaymentButton/utils';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

const ORG_ONBOARDING_IMG = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: imgPaymentButtonRzp,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: imgPaymentButtonRzp,
};

@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    org: state.session.org,
    paymentButtonsProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PB),
  }),
  { handleProductQuickGuide },
)
@OnBoarding({
  feature: RZPFeatures.PB,
})
export default class PaymentButtonOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        // eslint-disable-next-line react/no-this-in-sfc
        isLocalEnabler={!!this.props.user.isSubscriptionsEnabled}
        feature={
          // eslint-disable-next-line react/no-this-in-sfc
          this.props.user.isSubscriptionsEnabled ? RZPFeatures.PB : RZPFeatures.SUBSCRIPTIONS
        }
        page={sliderProps.active}
        // eslint-disable-next-line react/no-this-in-sfc
        onClick={this.closeOnboarding}
      />
    );
  };

  closeOnboarding = () => {
    this.props.closeOnboarding();

    setIsPaymentButtonCodeUsed(
      {
        mid: this.props.user.current,
        mode: this.props.mode,
      },
      false,
    );
  };

  render() {
    const { active, paymentButtonsProductOnBoarding, org } = this.props;
    const customCode = org.custom_code;
    const onboardingIMGUrl =
      ORG_ONBOARDING_IMG[customCode] || ORG_ONBOARDING_IMG[ORG_CUSTOM_CODE_MAP.RAZORPAY];
    const businessName = org.business_name;
    const featureData =
      FEATURES_DATA_ORG[customCode] || FEATURES_DATA_ORG[ORG_CUSTOM_CODE_MAP.RAZORPAY];

    return (
      <OnBoardingWrapper class="PaymentButtons">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            paymentButtonsProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              title="Payment Buttons"
              feature={RZPFeatures.PB}
              imageUrl={onboardingIMGUrl}
              desc="Collect payments and donations on your websites and blogs, copy-paste a single line of code to collect payments online. Zero integrations required!"
              businessName={businessName}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Payment Buttons great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              feature={RZPFeatures.PB}
              features={featureData}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({ closeOnboarding, paymentButtonsProductOnBoarding }) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        onClick={closeOnboarding}
        feature={RZPFeatures.PB}
        page={sliderProps.active}
        isTour={paymentButtonsProductOnBoarding.isTour}
      />
    </SliderDots>
  );
}

function setPaymentButtonOnBoardingData(isEnabled) {
  setOnBoardingDataInLocalState({
    feature: RZPFeatures.PB,
    data: {
      isEnabled,
      lastVisitedTime: Date.now(),
    },
  });
}

export function getIsAllowedResetPaymentButtonsOnBoarding({ items, loading }) {
  if (loading) {
    return false;
  }

  if (items.length) {
    setPaymentButtonOnBoardingData(true);
  }

  return getIsAllowedResetBoarding(RZPFeatures.PB);
}

export function getIsPaymentButtonsEnabled({ user, paymentbuttons: { items, loading } }) {
  if (user.isPaymentButtonsEnabled || loading) {
    return true;
  }

  if (items.length) {
    setPaymentButtonOnBoardingData(true);
  }

  return getOnBoardingDataFromLocalState(RZPFeatures.PB).isEnabled;
}
