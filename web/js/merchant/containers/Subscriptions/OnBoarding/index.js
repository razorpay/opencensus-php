import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import Landing from 'merchant/components/OnBoarding/Screens/Landing';
import Features from 'merchant/components/OnBoarding/Screens/Features';
import OnBoarding, {
  OnBoardingWrapper,
  FeatureEnableSliderButton,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { PROS, FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(state => ({ user: state.session.user }))
@OnBoarding({
  feature: RZPFeatures.SUBSCRIPTIONS,
})
export default class SubscriptionOnBoarding extends React.Component {
  closeOnboarding = () => {
    setQuickGuideIsClosedInLocalStorage(RZPFeatures.SUBSCRIPTIONS, false);
    this.props.closeOnboarding();
  };

  getNextButton = sliderProps => () => {
    const props = {
      feature: RZPFeatures.SUBSCRIPTIONS,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
    };

    if (this.props.user.isSubscriptionsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  renderSkipButton = sliderProps => {
    if (this.props.user.isSubscriptionsEnabled) {
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

export const getIsAllowedResetSubscriptionBoarding = ({
  plans,
  subscriptions,
}) => {
  // if (
  //   plans.loading ||
  //   plans.items.length ||
  //   subscriptions.loading ||
  //   subscriptions.items.length
  // ) {
  //   return false;
  // }

  return getIsAllowedResetBoarding(RZPFeatures.SUBSCRIPTIONS);
};
