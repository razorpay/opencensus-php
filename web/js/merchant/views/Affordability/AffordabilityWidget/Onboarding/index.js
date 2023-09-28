import {
  OnBoardingWrapper,
  getIsAllowedResetBoarding,
  setOnBoardingDataInLocalState,
} from 'merchant/components/OnBoarding';
import Landing from 'merchant/components/OnBoarding/Slides/Landing';
import FeatureTiles from './FeatureTile';
import OnboardingPlatforms from './Platforms';
// eslint-disable-next-line import/no-named-as-default
import PlatformSetup from './PlatformSetup';
import { RZPFeatures } from 'merchant/helpers/data';
import { PlatformsTitle, PlatformsList } from './data';
import { Route, Routes } from 'react-router-dom';
import { compose } from 'redux';
import RTracking from 'react-tracking';
import track from './track';
import { withRouter } from 'common/deprecated/withRouter';

const AffordabilityWidgetOnboarding = withRouter((props) => {
  const desc = (
    <p className="title-desc">
      Attract, convert, and retain more customers with early discovery of EMI, Pay Later and Offers
      on product pages of your website
    </p>
  );

  const calloutElement = (
    <>
      <p className="caption">Benefits of the widget</p>
      <FeatureTiles />
    </>
  );

  const handleNextClickHandler = (callback) => {
    callback();
    props.history.push('/affordability/widget/platforms');
    track.continue();
    track.next();
  };
  return (
    <tabbed-container>
      <OnBoardingWrapper class="AffordabilityWidget">
        <div className="Slider">
          <Routes>
            <Route
              index
              element={
                <Landing
                  className=""
                  title="Affordability Widget"
                  imageUrl="https://cdn.razorpay.com/static/assets/affordability-widget/widget_banner.svg"
                  desc={desc}
                  callout={calloutElement}
                  ctaText="Continue"
                  next={handleNextClickHandler}
                  feature={RZPFeatures.AFFORDABILITY_WIDGET}
                  active="0"
                />
              }
            />
            <Route path="setup/:platform" element={<PlatformSetup {...props} />} />
            <Route
              path="platforms"
              element={
                <OnboardingPlatforms {...props} title={PlatformsTitle} platforms={PlatformsList} />
              }
            />
          </Routes>
        </div>
      </OnBoardingWrapper>
    </tabbed-container>
  );
});

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('AffordabilityWidgetOnboarding'))(
    AffordabilityWidgetOnboarding,
  ),
);

export function getIsAllowedResetAffordabilityWidgetOnBoarding(affordabilityWidget, loading) {
  const { affordability } = affordabilityWidget;
  if (affordability?.lastAction?.createdTime || loading) {
    return false;
  }
  return getIsAllowedResetBoarding(RZPFeatures.AFFORDABILITY_WIDGET);
}

function setAffordabilityWidgetOnboardingData() {
  setOnBoardingDataInLocalState({
    feature: RZPFeatures.AFFORDABILITY_WIDGET,
    data: {
      isEnabled: true,
      lastVisitedTime: Date.now(),
    },
  });
}

export function getIsAffordabilityWidgetEnabled({ user, affordabilityWidget }) {
  const { affordability } = affordabilityWidget;
  if (user.isAffordabilityWidgetEnabled || affordabilityWidget.loading) {
    return true;
  }

  if (affordability?.lastAction?.createdTime || affordability.enabled) {
    setAffordabilityWidgetOnboardingData();
  }

  // return user.isAffordabilityWidgetEnabled;
  return affordability.enabled;
}
