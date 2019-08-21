import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import Landing from 'merchant/components/OnBoarding/Screens/Landing';
import Features from 'merchant/components/OnBoarding/Screens/Features';
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
    if (this.props.user.isPaymentPagesEnabled) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.PP, false);
    }

    this.props.closeOnboarding();

    this.props.handleProductQuickGuide({
      ...this.props.paymentPageProductOnBoarding,
      showOnboarding: false,
    });
  };

  render() {
    const { active, onSlideChange, user } = this.props;

    return (
      <OnBoardingWrapper class="PaymentPages">
        <Slider active={active} onSlideChange={onSlideChange}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Payment Pages"
              imageUrl="https://razorpay.com/assets/paymentpages/hero-main.svg"
              desc="Create custom-branded, hosted Payment Pages in a few clicks to accept payments online. Your business can go online with zero integration and tech efforts."
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              title="What makes Payment Pages great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              <SkipAndGetStartedButton
                feature={RZPFeatures.PP}
                onClick={this.closeOnboarding}
                page={sliderProps.active}
                isLocalEnabler={user.isPaymentPagesEnabled}
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
