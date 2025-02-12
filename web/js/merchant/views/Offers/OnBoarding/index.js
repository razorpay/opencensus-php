import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import Slider, { SliderDots } from 'common/new-ui/Slider';
import onBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import { RZPFeatures } from 'merchant/helpers/data';
import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

class OffersOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.OFFERS}
        page={sliderProps.active}
        onClick={this.closeOnboarding}
      />
    );
  };

  closeOnboarding = () => {
    this.props.closeOnboarding();
  };

  render() {
    const { active, offersProductOnBoarding } = this.props;

    return (
      <OnBoardingWrapper className="Offers">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            offersProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.OFFERS}
              title="Offers"
              imageUrl="https://cdn.razorpay.com/static/assets/offers/dashboard_banner.png"
              desc="Now run customer offers via the Razorpay dashboard. Businesses have seen a 35% increase in sales thanks to Razorpay Offers."
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Offers great?"
              feature={RZPFeatures.OFFERS}
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({ closeOnboarding, offersProductOnBoarding }) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        isTour={offersProductOnBoarding.isTour}
        feature={RZPFeatures.OFFERS}
        page={sliderProps.active}
        onClick={closeOnboarding}
      />
    </SliderDots>
  );
}

export function getIsAllowedResetOffersOnBoarding(offers, loading) {
  if (offers.length || loading) {
    return false;
  }
  return getIsAllowedResetBoarding(RZPFeatures.OFFERS);
}

function setOffersOnboardingData() {
  setOnBoardingDataInLocalState({
    feature: RZPFeatures.OFFERS,
    data: {
      isEnabled: true,
      lastVisitedTime: Date.now(),
    },
  });
}

export function getIsOffersEnabled({ user, offers }) {
  if (user.isOffersEnabled || offers.loading) {
    return true;
  }

  if (offers.items.length) {
    setOffersOnboardingData();
  }

  return user.isOffersEnabled;
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      offersProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.offers),
    }),
    { handleProductQuickGuide },
  ),
  onBoarding({
    feature: RZPFeatures.OFFERS,
  }),
)(OffersOnBoarding);
