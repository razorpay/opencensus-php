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

import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import { FEATURES_DATA, FEATURES_LINKS } from './data';

@connect(
  (state) => ({
    user: state.session.user,
    qrCodeProductOnBoarding: getCurrentProductOnBoardingDetails(state, RZPFeatures.QR_CODES),
  }),
  { handleProductQuickGuide },
)
@OnBoarding({
  feature: RZPFeatures.QR_CODES,
})
export default class QRCodesOnBoarding extends React.Component {
  getNextBtnProp = (sliderProps) => () => {
    return (
      <FeatureEnableSliderButton
        feature={RZPFeatures.QR_CODES}
        page={sliderProps.active}
        onClick={this.closeOnboarding}
      />
    );
  };

  closeOnboarding = () => {
    this.props.closeOnboarding();
  };

  render() {
    const { active, qrCodeProductOnBoarding } = this.props;

    return (
      <OnBoardingWrapper class="QRCodes">
        <Slider
          active={active}
          afterSlide={getOnBoardingSliderDots({
            qrCodeProductOnBoarding,
            closeOnboarding: this.closeOnboarding,
          })}
        >
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.QR_CODES}
              title="QR Codes"
              imageUrl="/dist/css/assets/qr_code/onboarding.svg"
              desc="Create UPI QR codes in 3 simple steps with no integration efforts. Adopt contactless payments through customized QR codes and track payments easily."
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="Whats unique about QR Codes?"
              feature={RZPFeatures.QR_CODES}
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

function getOnBoardingSliderDots({ closeOnboarding, qrCodeProductOnBoarding }) {
  return (sliderProps) => (
    <SliderDots {...sliderProps}>
      <SkipAndGetStartedButton
        isTour={qrCodeProductOnBoarding.isTour}
        feature={RZPFeatures.QR_CODES}
        page={sliderProps.active}
        onClick={closeOnboarding}
      />
    </SliderDots>
  );
}

export function getIsAllowedResetQRCodesOnBoarding(qrCodes, loading) {
  if (qrCodes.length || loading) {
    return false;
  }
  return getIsAllowedResetBoarding(RZPFeatures.QR_CODES);
}

export function getIsQRCodesEnabled({ user, qr_codes }) {
  if (user.isQRCodeProductEnabled || qr_codes.loading) {
    return true;
  }

  return user.isOffersEnabled;
}
