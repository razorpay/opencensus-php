import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import { Button } from '@razorpay/blade/components';
import { FeatureTiles } from './FeatureTile';
import { getUser } from 'merchant/store';
import { analyticsTrack } from 'common/utils/analytics';
import { sendDataToSalesForce } from 'common/utils/common-api';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  getKycAnalyticsProperties,
  getSfBusinessTypeOfUser,
} from 'merchant/views/RazorpayXWidget/helpers';
import {
  updateUtmParams,
  utmCampaignMap,
  utmMediumMap,
  utmSourceMap,
} from 'merchant/helpers/x/updateUtmCookie';

const user = getUser();

const handleGetStartedButton = (get_started_cta) => {
  const businessType = getSfBusinessTypeOfUser();
  updateUtmParams({
    utm_campaign: utmCampaignMap.BANKING_WIDGET,
    utm_source: utmSourceMap.PG,
    utm_medium: utmMediumMap.DASHBOARD,
  });

  analyticsTrack({
    objectName: 'RazorpayX Get Started',
    actionName: 'Clicked',
    screen: 'RazorpayX Onboarding',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...getKycAnalyticsProperties(),
    },
  });

  sendDataToSalesForce(
    {
      Campaign_ID: 'PG_X_Banking_Widget',
      product_name: 'Current_Account',
      Business_Type: businessType,
    },
    user,
  );
  window.open(get_started_cta.url, '_blank', 'noopener noreferrer');
};

const Onboarding = ({ x_banking_widget }) => {
  const widgetData = x_banking_widget?.slide[0];

  return (
    <OnBoardingWrapper class="RazorpayX">
      <div
        className="OnBoarding--Slide OnBoarding--ImageSlide OnBoarding--Landing Slider"
        key="LandingSlide"
        style={{ backgroundImage: `url(${widgetData?.background_illustration?.url})` }}
      >
        <div className="Landing--Image">
          <img src={widgetData?.left_illustration?.url} alt="landing-image" />
        </div>
        <div className="Product--Details">
          {widgetData ? <div className="Details-heading">{widgetData?.headline}</div> : null}
          <div className="Details-desc">
            <img
              className="razorpayx-logo"
              src={widgetData?.logo?.url}
              alt={widgetData?.logo?.alt_text}
            />
            <p>{widgetData?.headline}</p>
          </div>
          <div className="callout">
            <p className="caption">{widgetData?.sub_headline}</p>
            <FeatureTiles cards={widgetData?.cards} />
          </div>
          <div className="Button-Container">
            <Button
              type="button"
              className="Forward-Button"
              iconAfter="arrow-forward"
              onClick={() => handleGetStartedButton(widgetData?.get_started_cta)}
            >
              {widgetData?.get_started_cta?.label}
            </Button>
          </div>
        </div>
      </div>
    </OnBoardingWrapper>
  );
};

export default Onboarding;
