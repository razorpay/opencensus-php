import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';
import { fetchUser } from 'merchant/modules/session';

import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import Features from 'merchant/components/OnBoarding/Slides/Features';
import OnBoarding, {
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
  {
    fetchUser,
    handleProductQuickGuide,
  }
)
@OnBoarding({
  feature: RZPFeatures.ROUTE,
})
export default class MarketPlaceOnBoarding extends React.Component {
  closeOnboarding = () => {
    if (!this.props.routeProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.ROUTE, false);
    }

    this.props.closeOnboarding();
  };

  getNextBtnProp = sliderProps => () => {
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

  renderSkipButton = sliderProps => {
    const btnProps = {
      feature: RZPFeatures.ROUTE,
      page: sliderProps.active,
      onClick: this.props.closeOnboarding,
      isTour: this.props.routeProductOnBoarding.isTour,
    };

    if (this.props.user.isMarketplaceEnabled) {
      btnProps.isLocalEnabler = true;
      btnProps.onClick = this.closeOnboarding;
    }

    return <SkipAndGetStartedButton {...btnProps} />;
  };

  render() {
    return (
      <OnBoardingWrapper class="Route">
        <Slider active={this.props.active}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Route"
              feature={RZPFeatures.ROUTE}
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
              feature={RZPFeatures.ROUTE}
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
