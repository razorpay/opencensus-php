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

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(
  state => ({
    user: state.session.user,
    paymentPageProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.PP
    ),
  }),
  {
    handleProductQuickGuide,
  }
)
@OnBoarding({
  feature: RZPFeatures.PP,
})
export default class PaymentPagesOnBoarding extends React.Component {
  getNextBtnProp = sliderProps => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.PP}
        onClick={this.closeOnboarding}
        page={sliderProps.active}
      />
    );
  };

  closeOnboarding = () => {
    if (
      this.props.user.isPaymentPagesEnabled &&
      !this.props.paymentPageProductOnBoarding.isTour
    ) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PP, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active, onSlideChange, paymentPageProductOnBoarding } = this.props;

    return (
      <OnBoardingWrapper class="PaymentPages">
        <Slider active={active} onSlideChange={onSlideChange}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Payment Pages"
              feature={RZPFeatures.PP}
              imageUrl="https://razorpay.com/assets/paymentpages/hero-main.svg"
              desc="Build a custom, branded payment page for your business in under 10 minutes and start accepting international and domestic payments with zero integration and tech efforts."
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              title="What makes Payment Pages great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              feature={RZPFeatures.PP}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              <SkipAndGetStartedButton
                isLocalEnabler
                feature={RZPFeatures.PP}
                onClick={this.closeOnboarding}
                page={sliderProps.active}
                isTour={paymentPageProductOnBoarding.isTour}
              />
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export function getIsAllowedPaymentPagesResetOnBoarding({
  paymentPages,
  loading,
}) {
  if (paymentPages.length || loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.PP);
}

export function getIsPaymentPagesEnabled({ user, paymentPages, loading }) {
  if (loading) {
    return true;
  }

  if (!user.isPaymentPagesEnabled) {
    setPaymentPageOnboardingData(paymentPages);
  }

  return user.isPaymentLinksEnabled;
}

function setPaymentPageOnboardingData(paymentPages) {
  const isEnabled = Boolean(paymentPages.length);

  setOnBoardingDataInLocalState({
    feature: RZPFeatures.PP,
    data: {
      isEnabled,
      lastVisitedTime: isEnabled && Date.now(),
    },
  });
}
