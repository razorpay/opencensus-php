import React from 'react';
import { connect } from 'react-redux';

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
import track from '../track';
import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(
  (state) => ({
    user: state.session.user,
    paymentLinksProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PL),
  }),
  { handleProductQuickGuide },
)
@OnBoarding({
  feature: RZPFeatures.PL,
})
export default class PaymentPagesOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.PL}
        page={sliderProps.active}
        onClick={this.closeOnboarding}
      />
    );
  };

  componentDidMount() {
    track.onBoardingSuccess();
  }

  closeOnboarding = () => {
    if (this.props.user.isPaymentLinksEnabled && !this.props.paymentLinksProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PL, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active, paymentLinksProductOnBoarding } = this.props;

    return (
      <OnBoardingWrapper class="PaymentLinks">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            paymentLinksProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              title="Payment Links"
              feature={RZPFeatures.PL}
              imageUrl="/dist/css/assets/product_onboarding/payment_link.svg"
              desc="Create and share a Razorpay Payment Link in under a minute with your customers via email, SMS, messenger, chatbot etc. Get domestic and international payments online directly into your bank account."
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Payment Links great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              feature={RZPFeatures.PL}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({ closeOnboarding, paymentLinksProductOnBoarding }) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        isTour={paymentLinksProductOnBoarding.isTour}
        onClick={closeOnboarding}
        feature={RZPFeatures.PL}
        page={sliderProps.active}
      />
    </SliderDots>
  );
}

export function getIsAllowedResetPaymentLinksOnBoarding(paymentlinks) {
  if (paymentlinks.paymentlinks.length || paymentlinks.loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.PL);
}

export function getIsPaymentLinksEnabled({ user, paymentlinks }) {
  if (user.isPaymentLinksEnabled || paymentlinks.loading) {
    return true;
  }

  if (paymentlinks.paymentlinks.length) {
    setOnBoardingDataInLocalState({
      feature: RZPFeatures.PL,
      data: {
        isEnabled: true,
        lastVisitedTime: Date.now(),
      },
    });

    return true;
  }

  return false;
}
