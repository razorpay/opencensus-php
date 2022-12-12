import React, { useEffect } from 'react';
import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import ShowWhen from 'merchant/components/ShowWhen';
import { FeatureTiles } from './FeatureTile';
import ComingSoon from './comingSoonCallout';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const title = 'Affordability Widget';
const imageUrl = 'https://cdn.razorpay.com/static/assets/affordability-widget/dashboard_banner.svg';

const desc = (
  <p className="title-desc">
    This smart widget helps reduce drop-offs by displaying affordable payment options like EMI,
    Paylater and Offers to your customers before they reach checkout
  </p>
);

const callout = (
  <>
    <p className="caption">Benefits of the widget</p>
    <FeatureTiles />
  </>
);

function AffordabilityWidgetOnboarding() {
  useEffect(() => {
    analyticsTrack({
      objectName: 'Affordability Widget Banner',
      actionName: 'appear',
      screen: 'Affordability Widget',
      properties: {
        location: 'onboarding',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  }, []);
  return (
    <OnBoardingWrapper class="AffordabilityWidget">
      <div className="Slider">
        <div
          className="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing"
          key="LandingSlide"
        >
          {imageUrl && (
            <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
              <div className="Landing--Image">
                <img src={imageUrl} alt="landing-image" />
              </div>
            </ShowWhen>
          )}

          <div className="Product--Details">
            <div className="Details-heading">
              <span className="dash" /> Razorpay
            </div>

            <div className="Details-title">{title}</div>

            <div className="Details-desc">{desc}</div>

            <div className="callout">{callout}</div>

            <div className="Button-Container">
              <ComingSoon />
            </div>
          </div>
        </div>
      </div>
    </OnBoardingWrapper>
  );
}

export default AffordabilityWidgetOnboarding;
