import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { RZPFeatures } from 'merchant/helpers/data';

import Slider, { SliderDots } from 'common/new-ui/Slider';

import { getCurrentProductOnBoardingDetails } from 'merchant/reducers/onboarding';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import OnBoarding, {
  OnBoardingWrapper,
  FeatureEnableSliderButton,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { PROS, FEATURES_DATA, FEATURES_LINKS } from './data';
import analytics from '../analytics';

@connect((state) => ({
  user: state.session.user,
  subscriptionProductOnBoarding: getCurrentProductOnBoardingDetails(
    state,
    RZPFeatures.SUBSCRIPTIONS,
  ),
}))
@OnBoarding({
  feature: RZPFeatures.SUBSCRIPTIONS,
})
@RTracking(() => window.rzpQ.component('SubscriptionOnBoarding'))
export default class SubscriptionOnBoarding extends React.Component {
  closeOnboarding = () => {
    if (!this.props.subscriptionProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.SUBSCRIPTIONS, false);
    }

    this.props.closeOnboarding();
  };

  getNextButton = (sliderProps) => () => {
    const props = {
      feature: RZPFeatures.SUBSCRIPTIONS,
      page: sliderProps.active,
    };
    let onClick = this.props.closeOnboarding;

    if (this.props.user.isSubscriptionsEnabled) {
      props.isLocalEnabler = true;
      onClick = this.closeOnboarding;
    }

    return (
      <FeatureEnableSliderButton
        {...props}
        onClick={() => {
          this.props.tracking.trackEvent(
            window.rzpQ.subscription().interaction('subscription.onboarding.get_started', {
              isTour: this.props.user.isSubscriptionsEnabled,
            }),
          );
          analytics.track('subscription.tutorial.get_started');
          onClick();
        }}
      />
    );
  };

  renderSkipButton = (sliderProps) => {
    const btnProps = {
      feature: RZPFeatures.SUBSCRIPTIONS,
      page: sliderProps.active,
      onClick: this.props.closeOnboarding,
      isTour: this.props.subscriptionProductOnBoarding.isTour,
    };
    let onClick = this.props.closeOnboarding;

    if (this.props.user.isSubscriptionsEnabled) {
      btnProps.isLocalEnabler = true;
      onClick = this.closeOnboarding;
    }

    return (
      <SkipAndGetStartedButton
        {...btnProps}
        onClick={() => {
          analytics.track('subscription.tutorial.skip');
          onClick();
        }}
      />
    );
  };

  render() {
    return (
      <OnBoardingWrapper class="Subscription">
        <Slider
          active={this.props.active}
          afterSlide={getOnBoardingSliderDots(this.renderSkipButton)}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              title="Subscription"
              feature={RZPFeatures.SUBSCRIPTIONS}
              pros={PROS}
              imageUrl="https://razorpay.com/assets/subscriptions/banner.svg"
              desc="Collect recurring payments from customers with Razorpay Subscriptions APIs"
              next={(...args) => {
                this.props.tracking.trackEvent(
                  window.rzpQ.subscription().interaction('subscription.onboarding.next_screen_1', {
                    isTour: this.props.user.isSubscriptionsEnabled,
                  }),
                );
                analytics.track(`subscription.tutorial.read_more`);

                return sliderProps.next(...args);
              }}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              feature={RZPFeatures.SUBSCRIPTIONS}
              title="What makes Subscription great?"
              nextBtn={this.getNextButton(sliderProps)}
              featureLinks={FEATURES_LINKS.map((link) => ({
                ...link,
                onClick: () => {
                  this.props.tracking.trackEvent(
                    window.rzpQ.subscription().interaction(`subscription.onboarding.${link.label}`),
                  );
                  analytics.track(
                    `subscription.tutorial.${link.label?.toLowerCase().split(' ').join('_')}`,
                  );
                },
              }))}
              features={FEATURES_DATA}
            />
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

function getOnBoardingSliderDots(renderSkipButton) {
  return (sliderProps) => <SliderDots {...sliderProps}>{renderSkipButton(sliderProps)}</SliderDots>;
}

export const getIsAllowedResetSubscriptionBoarding = ({ plans, subscriptions }) => {
  if (plans.loading || plans.items.length || subscriptions.loading || subscriptions.items.length) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.SUBSCRIPTIONS);
};
