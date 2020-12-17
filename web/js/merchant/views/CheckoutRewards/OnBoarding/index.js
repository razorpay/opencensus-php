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
import CheckoutRewardsOnBoardingAnnouncement from 'merchant/components/Announcements/CheckoutRewardsOnBoarding';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(
  (state) => ({
    user: state.session.user,
    rewardsProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.REWARDS),
  }),
  { handleProductQuickGuide },
)
@OnBoarding({
  feature: RZPFeatures.REWARDS,
})
export default class RewardsOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        isLocalEnabler
        feature={RZPFeatures.REWARDS}
        page={sliderProps.active}
        onClick={this.closeOnboarding}
      />
    );
  };

  closeOnboarding = () => {
    this.props.closeOnboarding();
  };

  render() {
    const { active, rewardsProductOnBoarding, user } = this.props;

    const isFeatureEnabled = user.isRewardsPageEnabled;

    return (
      <OnBoardingWrapper class={`Rewards ${!isFeatureEnabled ? 'Checkout-Rewards-Disabled' : ''}`}>
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            rewardsProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.REWARDS}
              title="Checkout Rewards"
              imageUrl="/dist/css/assets/product_onboarding/rewards_checkout.gif"
              desc="Give your customers exciting rewards with every purchase! Watch your sales grow with higher conversion and higher repeat purchase."
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="What makes Checkout Rewards great?"
              feature={RZPFeatures.REWARDS}
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
        {!isFeatureEnabled && <CheckoutRewardsOnBoardingAnnouncement userId={user.current} />}
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({ closeOnboarding, rewardsProductOnBoarding }) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isLocalEnabler
        isTour={rewardsProductOnBoarding.isTour}
        feature={RZPFeatures.REWARDS}
        page={sliderProps.active}
        onClick={closeOnboarding}
      />
    </SliderDots>
  );
}

export function getIsAllowedResetRewardsOnBoarding(rewards, loading) {
  if (rewards.length || loading) {
    return false;
  }
  return getIsAllowedResetBoarding(RZPFeatures.REWARDS);
}

function setRewardsOnboardingData() {
  setOnBoardingDataInLocalState({
    feature: RZPFeatures.REWARDS,
    data: {
      isEnabled: true,
      lastVisitedTime: Date.now(),
    },
  });
}

export function getIsRewardsEnabled({ user, rewards }) {
  if (user.isRewardsEnabled || rewards.loading) {
    return true;
  }

  if (rewards.rewards.length) {
    setRewardsOnboardingData();
  }

  return user.isRewardsEnabled;
}
