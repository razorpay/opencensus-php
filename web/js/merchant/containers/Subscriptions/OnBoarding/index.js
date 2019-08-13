import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import Landing from 'merchant/components/OnBoarding/Screens/Landing';
import Features from 'merchant/components/OnBoarding/Screens/Features';
import OnBoarding, {
  NextButton,
  OnBoardingWrapper,
  FeatureEnableSliderButton,
  SkipAndGetStartedButton,
  isAllowedResetBoarding,
} from 'merchant/components/OnBoarding';

import { PROS, FEATURES_DATA, FEATURES_LINKS } from './data';

@OnBoarding({
  feature: RZPFeatures.SUBSCRIPTIONS,
})
export default class SubscriptionOnBoarding extends React.Component {
  closeOnboarding = () => {
    this.props.closeOnboarding({
      isFeatureEnabler: true,
    });
  };

  getNextButton = sliderProps => () => {
    const { isSubscriptionsEnabled, isChargeAtWillEnabled } = this.props.user;

    if (isSubscriptionsEnabled || isChargeAtWillEnabled) {
      return (
        <NextButton
          onClick={this.props.closeOnboarding}
          page={sliderProps.active}
        />
      );
    }

    return (
      <FeatureEnableSliderButton
        page={sliderProps.active}
        feature={RZPFeatures.SUBSCRIPTIONS}
        onClick={this.closeOnboarding}
      />
    );
  };

  renderSkipButton = sliderProps => {
    const { isSubscriptionsEnabled, isChargeAtWillEnabled } = this.props.user;

    if (isSubscriptionsEnabled || isChargeAtWillEnabled) {
      return (
        <SkipAndGetStartedButton
          page={sliderProps.active}
          onClick={this.props.closeOnboarding}
        />
      );
    }

    return (
      <SkipAndGetStartedButton
        page={sliderProps.active}
        feature={RZPFeatures.SUBSCRIPTIONS}
        onClick={this.closeOnboarding}
      />
    );
  };

  render() {
    const { active, onSlideChange } = this.props;

    return (
      <OnBoardingWrapper class="Subscription">
        <Slider active={active} onSlideChange={onSlideChange}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Subscription"
              feature={RZPFeatures.SUBSCRIPTIONS}
              pros={PROS}
              imageUrl="https://razorpay.com/assets/subscriptions/banner.svg"
              desc="Collect recurring payments from customers with Razorpay Subscriptions APIs"
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              feature={RZPFeatures.SUBSCRIPTIONS}
              title="What makes Subscription great?"
              nextBtn={this.getNextButton(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              {this.renderSkipButton(sliderProps)}
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export const isAllowedResetSubscriptionBoarding = ({
  merchantId,
  mode,
  plans,
  subscriptions,
}) => {
  if (
    plans.loading ||
    plans.items.length ||
    subscriptions.loading ||
    subscriptions.items.length
  ) {
    return false;
  }

  return isAllowedResetBoarding({
    merchantId,
    mode,
    feature: RZPFeatures.SUBSCRIPTIONS,
  });
};
