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
import track from '../track';

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
  getNextButton = (sliderProps) => () => {
    const props = {
      feature: RZPFeatures.QR_CODES,
      onClick: (...args) => {
        return this.props.closeOnboarding(...args);
      },
      page: sliderProps.active,
    };

    if (this.props.user.isQRCodeProductEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <FeatureEnableSliderButton {...props} />;
  };

  componentDidMount() {
    track.tourPageRendered();
  }

  renderSkipButton = (sliderProps) => {
    const props = {
      feature: RZPFeatures.QR_CODES,
      onClick: this.props.closeOnboarding,
      page: sliderProps.active,
      isTour: this.props.qrCodeProductOnBoarding.isTour,
    };

    if (this.props.user.isQRCodeProductEnabled) {
      props.isLocalEnabler = true;
      props.onClick = this.closeOnboarding;
    }

    return <SkipAndGetStartedButton {...props} />;
  };

  closeOnboarding = () => {
    if (!this.props.qrCodeProductOnBoarding.isTour) {
      setQuickGuideIsClosedInLocalStorage(RZPFeatures.QR_CODES, false);
    }

    track.skipClickOnTourPage();
    this.props.closeOnboarding();
  };

  readMoreClicked = () => {
    track.readMoreClickedOnTourPage();
  };

  render() {
    const { active, qrCodeProductOnBoarding } = this.props;

    return (
      <OnBoardingWrapper class="QRCodes">
        <Slider active={active} afterSlide={getOnBoardingSliderDots(this.renderSkipButton)}>
          {(sliderProps) => (
            <Landing
              {...sliderProps}
              feature={RZPFeatures.QR_CODES}
              title="QR Codes"
              imageUrl={require("assets/qr_code/onboarding.svg")}
              desc="Create UPI QR codes in 3 simple steps with no integration efforts. Adopt contactless payments through customized QR codes and track payments easily."
              readMoreClicked={this.readMoreClicked}
            />
          )}

          {(sliderProps) => (
            <Features
              {...sliderProps}
              title="Whats unique about QR Codes?"
              feature={RZPFeatures.QR_CODES}
              nextBtn={this.getNextButton(sliderProps)}
              featureLinks={FEATURES_LINKS}
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
