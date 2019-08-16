import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import Landing from 'merchant/components/OnBoarding/Screens/Landing';
import Features from 'merchant/components/OnBoarding/Screens/Features';
import FeatureRequest from 'merchant/components/OnBoarding/Screens/FeatureRequest';
import OnBoarding, {
  NextButton,
  OnBoardingWrapper,
  FeatureEnableSliderButton,
  SkipAndGetStartedButton,
  isAllowedResetBoarding,
} from 'merchant/components/OnBoarding';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(state => ({
  user: state.session.user,
  mode: state.session.mode,
}))
@OnBoarding({
  feature: RZPFeatures.ROUTE,
})
export default class MarketPlaceOnBoarding extends React.Component {
  closeOnboarding = () => {
    this.props.closeOnboarding({
      isFeatureEnabler: true,
    });
  };

  getNextBtnProp = sliderProps => () => {
    if (this.props.mode === 'live') {
      return (
        <NextButton
          feature={RZPFeatures.ROUTE}
          onClick={sliderProps.next}
          page={sliderProps.active}
        />
      );
    }

    return (
      <FeatureEnableSliderButton
        feature={RZPFeatures.ROUTE}
        page={sliderProps.active}
      />
    );
  };

  onClickSkipButton = () => {
    if (this.props.mode === 'test') return;

    this.props.goTo(2);
  };

  render() {
    const { mode, goTo, active, onSlideChange } = this.props;

    const isTestMode = mode === 'test';

    return (
      <OnBoardingWrapper class="Route">
        <Slider active={active} onSlideChange={onSlideChange}>
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
                  desc="We'd require the following details to enable Razorpay Subscriptions on your account."
                  imageUrl="https://razorpay.com/assets/route/route-landing.svg"
                />
              )
            : null}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              {sliderProps.active !== 2 && (
                <SkipAndGetStartedButton
                  onClick={this.onClickSkipButton}
                  feature={isTestMode && RZPFeatures.ROUTE}
                  isTestMode={isTestMode}
                />
              )}
            </SliderDots>
          )}
        </Slider>
      </OnBoardingWrapper>
    );
  }
}

export function isAllowedResetRouteBoarding({
  merchantId,
  mode,
  transfers,
  accounts,
}) {
  if (
    accounts.loading ||
    transfers.loading ||
    transfers.items.length ||
    accounts.accounts.length
  ) {
    return false;
  }

  return isAllowedResetBoarding({
    merchantId,
    mode,
    feature: RZPFeatures.ROUTE,
  });
}
