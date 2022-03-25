import Slider, { SliderDots } from 'common/new-ui/Slider';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import OffersPage from './OffersPage';
import { FeatureTiles } from './FeatureTile';
import { RZPFeatures } from 'merchant/helpers/data';

const getOnBoardingSliderDots = () => {
  return (sliderProps) => <SliderDots {...sliderProps} />;
};

const RazorpayXOnboarding = () => {
  const cdnDashboardUrl = window.cdnDashboardUrl || '';
  const desc = (
    <>
      <img
        className="razorpayx-logo"
        src={`${cdnDashboardUrl}/dist/css/assets/razorpay-x-logo-white.svg`}
        alt="Razorpay X"
      />
      <p>
        Gear up to scale faster with credit cards, payments, taxes, and <br />
        accounting — automated with a RazorpayX Account.
      </p>
    </>
  );

  const calloutElement = (
    <>
      <p className="caption">Banking that powers finances for fast-growing businesses</p>
      <FeatureTiles />
    </>
  );

  const cdnBaseUrl = window.cdnBaseUrl || 'https://cdn.razorpay.com';

  return (
    <OnBoardingWrapper class="RazorpayX">
      <Slider active={0} afterSlide={getOnBoardingSliderDots({ showSkip: false })}>
        {(sliderProps) => (
          <Landing
            {...sliderProps}
            className=""
            imageUrl={`${cdnBaseUrl}/static/assets/razorpayx/widget/razorpayx-landing-image.png`}
            desc={desc}
            callout={calloutElement}
            ctaText="Next"
            feature={RZPFeatures.RAZORPAYX}
            includeKycProperties={true}
          />
        )}

        {(sliderProps) => <OffersPage {...sliderProps} />}
      </Slider>
    </OnBoardingWrapper>
  );
};

export default RazorpayXOnboarding;
