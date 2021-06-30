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
import ComingSoonCallout from 'merchant/views/CheckoutRewards/components/ComingSoonCallout';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { getItem, setItem } from 'common/utils/localStorage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

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
  constructor() {
    super();
    this.state = {
      isJoinedWaitlist: false,
      isPending: false,
    };
  }

  componentDidMount() {
    // Using the same key as CheckoutRewardsOnBoardingAnnouncement banner
    // because we want to show merchants "Joined the waitlist" who
    // have already clicked "interested" in past.
    const isJoinedWaitlist = !!getItem(`rewards-onboarding-banner-${this.props.user.current}`);

    this.setState({ isJoinedWaitlist });

    // Not doing it inside Callout component because we want to track only
    // once when this onboarding screen loads, to make it consistent with
    // previous "interested" banner used
    if (this.showJoinWaitlistButton) {
      analyticsTrack({
        objectName: 'Onboarding Interest Banner',
        actionName: 'appear',
        screen: 'Checkout Rewards',
        properties: {
          location: 'onboarding',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
        toLumberjack: true,
      });
    }
  }

  get showJoinWaitlistButton() {
    return !this.props.user.isRewardsPageEnabled;
  }

  joinTheWaitlist = () => {
    return new Promise((resolve) => {
      setTimeout(() => {
        setItem(`rewards-onboarding-banner-${this.props.user.current}`, 1);
        analyticsTrack({
          objectName: 'Onboarding Interest Banner',
          actionName: 'clicked',
          screen: 'Checkout Rewards',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
          toLumberjack: true,
        });
        resolve('Success');
      }, 1000);
    });
  };

  handleJoinWaitlistClick = () => {
    this.setState({ isPending: true });
    this.joinTheWaitlist()
      .then((res) => {
        if (res === 'Success') {
          this.setState({ isJoinedWaitlist: true });
        }
      })
      .finally(() => {
        this.setState({ isPending: false });
      });
  };

  getNextBtnProp = (sliderProps) => () => {
    if (this.showJoinWaitlistButton) {
      return (
        <>
          {this.state.isJoinedWaitlist ? (
            <Button className="joined-button">
              Joined the waitlist
              <i className="i i-tick" />
            </Button>
          ) : (
            <AsyncBtn.Primary
              class="Forward-Button"
              onClick={this.handleJoinWaitlistClick}
              showLoader={true}
            >
              Join the waitlist
              {this.state.isPending && <span className="spin-btn white visible" />}
            </AsyncBtn.Primary>
          )}
        </>
      );
    }

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
    const { isJoinedWaitlist, isPending } = this.state;
    const { active, rewardsProductOnBoarding, user } = this.props;

    const isFeatureEnabled = user.isRewardsPageEnabled;
    const calloutElement = !isFeatureEnabled ? (
      <ComingSoonCallout
        isJoinedWaitlist={isJoinedWaitlist}
        isPending={isPending}
        joinTheWaitlist={this.handleJoinWaitlistClick}
      />
    ) : null;

    return (
      <OnBoardingWrapper class={`Rewards ${!isFeatureEnabled ? 'Checkout-Rewards-Callout' : ''}`}>
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            rewardsProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
            showSkip: isFeatureEnabled,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              className={!isFeatureEnabled ? 'with-callout' : ''}
              feature={RZPFeatures.REWARDS}
              title="Checkout Rewards"
              imageUrl="https://cdn.razorpay.com/static/assets/rewards/rewards_checkout_demo.gif"
              desc="Give your customers exciting rewards with every purchase! Watch your sales grow with higher conversion and higher repeat purchase."
              callout={calloutElement}
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
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots({ closeOnboarding, rewardsProductOnBoarding, showSkip }) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      {showSkip && (
        <SkipAndGetStartedButton
          isLocalEnabler
          isTour={rewardsProductOnBoarding.isTour}
          feature={RZPFeatures.REWARDS}
          page={sliderProps.active}
          onClick={closeOnboarding}
        />
      )}
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
