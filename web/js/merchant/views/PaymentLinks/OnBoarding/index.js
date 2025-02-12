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
import track from 'merchant/views/PaymentLinks/track';
import { FEATURES_DATA, FEATURES_LINKS } from './data';
import PaymentLinkIcon from 'assets/product_onboarding/payment_link.svg';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { NOT_SKIP } from 'merchant/constants/payments';
import { compose } from 'redux';

// i18
export const LANDING_PAGE_DESC = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]:
    'Create and share a Razorpay Payment Link in under a minute with your customers via email, SMS, messenger, chatbot etc. Get domestic and international payments online directly into your bank account.',
  [ORG_CUSTOM_CODE_MAP.CURLEC]:
    'Create and share a Curlec Payment Link in under a minute with your customers via email, SMS, messenger, chatbot etc. Get payments directly into your bank account.',
};

// i18
const FEATURE_LINKS_MAPS = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: FEATURES_LINKS,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: [],
};

class PaymentPagesOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.PL}
        page={sliderProps.active}
        // eslint-disable-next-line react/no-this-in-sfc
        onClick={() => this.closeOnboarding(NOT_SKIP)}
      />
    );
  };

  componentDidMount() {
    track.onBoardingSuccess();
    track.tourPageRendered();
  }

  closeOnboarding = (val = '') => {
    if (this.props.user.isPaymentLinksEnabled && !this.props.paymentLinksProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PL, false);
    }

    if (val === NOT_SKIP) {
      track.getStartedClickedOnTourPage();
    } else {
      track.skipClickOnTourPage();
    }
    this.props.closeOnboarding();
  };

  readMoreClicked = () => {
    track.readMoreClickedOnTourPage();
  };

  render() {
    const { active, paymentLinksProductOnBoarding, org } = this.props;
    const orgCode = org.custom_code.toLowerCase();
    const featureLinks = FEATURE_LINKS_MAPS[orgCode] || FEATURES_LINKS;
    const description = LANDING_PAGE_DESC[orgCode];

    return (
      <OnBoardingWrapper className="PaymentLinks">
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
              imageUrl={PaymentLinkIcon}
              businessName={org.business_name}
              desc={description}
              readMoreClicked={this.readMoreClicked}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Payment Links great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={featureLinks}
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

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      org: state.session.org,
      paymentLinksProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.PL),
    }),
    { handleProductQuickGuide },
  ),
  OnBoarding({
    feature: RZPFeatures.PL,
  }),
)(PaymentPagesOnBoarding);
