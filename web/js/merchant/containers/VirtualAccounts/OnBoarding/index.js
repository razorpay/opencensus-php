import { connect } from 'react-redux';

import { RZPFeatures } from 'rzp/utils/constants';

import Slider, { SliderDots } from 'component/Slider';

import Landing from 'merchant/components/OnBoarding/Screens/Landing';
import Features from 'merchant/components/OnBoarding/Screens/Features';
import OnBoarding, {
  FeatureEnableSliderButton,
  OnBoardingWrapper,
  SkipAndGetStartedButton,
  getIsAllowedResetBoarding,
} from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS, PROS } from './data';

@connect(state => ({ user: state.session.user }))
@OnBoarding({
  feature: RZPFeatures.VA,
})
export default class InvoicesOnBoarding extends React.Component {
  getNextButton = sliderProps => () => {
    const props = {
      feature: RZPFeatures.VA,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
    };

    if (this.props.user.isVirtualAccountsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  renderSkipButton = sliderProps => {
    const props = {
      feature: RZPFeatures.VA,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
    };

    if (this.props.user.isVirtualAccountsEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <SkipAndGetStartedButton {...props} />;
  };

  closeOnboarding = () => {
    if (this.props.user.isVirtualAccountsEnabled) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.VA, false);
    }

    this.props.closeOnboarding();
  };

  render() {
    const { active, onSlideChange, user } = this.props;

    return (
      <OnBoardingWrapper class="SmartCollect">
        <Slider active={active} onSlideChange={onSlideChange}>
          {sliderProps => (
            <Landing
              {...sliderProps}
              title="Smart Collect"
              imageUrl="https://razorpay.com/assets/smartcollect/banner.svg"
              desc="Create and send GST compliant invoices that your customers can pay online instantly."
              pros={PROS}
            />
          )}

          {sliderProps => (
            <Features
              {...sliderProps}
              title="What makes Smart Collect great?"
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

export function getIsAllowedResetVAOnBoarding({ items, loading }) {
  if (items.length || loading) {
    return false;
  }

  return getIsAllowedResetBoarding(RZPFeatures.VA);
}
