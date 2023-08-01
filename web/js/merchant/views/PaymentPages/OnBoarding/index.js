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
import CommonHeroMain from 'assets/payment_pages/hero_main.svg';
import i18nHeroMain from 'assets/payment_pages/i18n_hero_main.svg';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

export const LANDING_PAGE_DESC = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]:
    'Build a custom, branded payment page for your business in under 10 minutes and start accepting international and domestic payments with zero integration and tech efforts.',
  [ORG_CUSTOM_CODE_MAP.CURLEC]:
    'Build a custom, branded payment page for your business in under 10 minutes and start accepting payments with zero integration and tech efforts.',
};

export const HERO_IMAGE_MAP = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: CommonHeroMain,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: i18nHeroMain,
};

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    org: state.session.org,
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
        // eslint-disable-next-line react/no-this-in-sfc
        onClick={this.closeOnboarding}
        page={sliderProps.active}
        additionalTrackData={{
          is_creation_redirection_enabled:
            // eslint-disable-next-line react/no-this-in-sfc
            this.props.user.isPaymentPageOnboardingRedirectionEnabled,
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
    const { active, paymentPageProductOnBoarding, user, org } = this.props;

    const description =
      LANDING_PAGE_DESC[org.custom_code] || LANDING_PAGE_DESC[ORG_CUSTOM_CODE_MAP.RAZORPAY];
    const heroMainImag =
      HERO_IMAGE_MAP[org.custom_code] || HERO_IMAGE_MAP[ORG_CUSTOM_CODE_MAP.RAZORPAY];
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
              className={org.custom_code}
              feature={RZPFeatures.PP}
              imageUrl={heroMainImag}
              businessName={org.business_name}
              desc={description}
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
