import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import FeatureRequest from 'merchant/components/OnBoarding/Slides/FeatureRequest';
import OnBoarding, {
  NextButton,
  OnBoardingWrapper,
  FeatureEnableSliderButton,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(
  state => ({
    user: state.session.user,
    isTestMode: state.session.mode === 'test',
    routeProductOnBoarding: getCurrentProductOnBoardingDetails(
      state,
      RZPFeatures.ROUTE
    ),
  }),
  { handleProductQuickGuide }
)
@OnBoarding({
  feature: RZPFeatures.ROUTE,
})
export default class MarketPlaceOnBoarding extends React.Component {
  closeOnboarding = () => {
    setQuickGuideIsClosedInLocalStorage(RZPFeatures.SUBSCRIPTIONS, false);

    this.props.closeOnboarding();
  };

  getNextBtnProp = sliderProps => () => {
    if (!this.props.isTestMode) {
      return (
        <NextButton
          feature={RZPFeatures.ROUTE}
          onClick={sliderProps.next}
          page={sliderProps.active}
        />
      );
    }

    const props = {
      feature: RZPFeatures.ROUTE,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
    };

    if (this.props.user.isMarketplaceEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  onClickSkipButton = () => {
    if (this.props.isTestMode) {
      if (this.props.user.isMarketplaceEnabled) {
        this.closeOnboarding();
      }

      this.props.closeOnboarding();
      return;
    }

    this.props.goTo(2);
  };

  render() {
    const { isTestMode, active, onSlideChange, user } = this.props;

    return (
      <OnBoardingWrapper class="Route">
        <Slider active={active}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Route"
              imageUrl="https://razorpay.com/assets/route/route-landing.svg"
              desc="Easily split payments, make vendor payouts, manage marketplace money flow or automate routing money with complete control over the business logic."
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              title="What makes Route great?"
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {!isTestMode
            ? sliderProps => (
                <FeatureRequest
                  {...sliderProps}
                  isTestMode={isTestMode}
                  formType={RZPFeatures.ROUTE}
                  heading="Route"
                  title="What makes Route great?"
                  desc="We'd require the following details to enable Razorpay Route on your account."
                  imageUrl="https://razorpay.com/assets/route/route-landing.svg"
                />
              )
            : null}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              {sliderProps.active !== 2 && (
                <SkipAndGetStartedButton
                  feature={RZPFeatures.ROUTE}
                  page={sliderProps.active}
                  onClick={this.onClickSkipButton}
                  isLocalEnabler={user.isMarketplaceEnabled}
                />
              )}
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export function getIsAllowedResetRouteBoarding({ transfers, accounts }) {
  if (
    accounts.loading ||
    transfers.loading ||
    transfers.items.length ||
    accounts.accounts.length
  ) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.ROUTE);
}
