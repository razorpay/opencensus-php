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

import { FEATURES_DATA, FEATURES_LINKS, PROS } from './data';

@OnBoarding({
  feature: RZPFeatures.VA,
})
export default class InvoicesOnBoarding extends React.Component {
  getNextBtnProp = sliderProps => () => {
    return (
      <FeatureEnableSliderButton
        feature={RZPFeatures.VA}
        page={sliderProps.active}
        onClick={this.props.closeOnboarding}
      />
    );
  };

  render() {
    const { active, onSlideChange } = this.props;

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
              nextBtn={this.getNextBtnProp(sliderProps)}
              featureLinks={FEATURES_LINKS}
              features={FEATURES_DATA}
            />
          )}

          {sliderProps => (
            <SliderDots {...sliderProps}>
              <SkipAndGetStartedButton onClick={this.props.closeOnboarding} />
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
