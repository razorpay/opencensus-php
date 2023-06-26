import { OnBoardingWrapper } from 'merchant/components/OnBoarding';
import { ArrowRightIcon, Box, Button } from '@razorpay/blade/components';
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
import { BulletPointsContainer } from './BulletPointsContainer';
import { BankingXHeadingV2, BankingXSubHeadingV2 } from './styles';
import Image from 'common/ui/Image';

const user = getUser();

export const BankingWidgetVariants = {
  V1: 'V1',
  V2: 'V2',
};

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
  const variant = widgetData?.variant || BankingWidgetVariants.V2;

  return variant === BankingWidgetVariants.V1 ? (
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
  ) : (
    variant === BankingWidgetVariants.V2 && (
      <OnBoardingWrapper class="RazorpayX">
        <Box
          backgroundImage={`url(${widgetData?.background_illustration?.url})`}
          backgroundColor="surface.background.level1.highContrast"
          minWidth="90%"
          margin="0 auto"
          height="100%"
          justifyContent="space-between"
          display="flex"
          minHeight="550px"
          key="LandingSlideV2"
          position="relative"
        >
          <Box marginLeft="60px" maxWidth="420px" display="flex" alignItems="center">
            <Image
              width="100%"
              src={widgetData?.left_illustration?.url}
              alt={widgetData?.left_illustration?.alt_text}
            />
          </Box>
          <Box width="60%" alignSelf="flex-start" padding="60px">
            {widgetData ? <BankingXHeadingV2>{widgetData?.headline}</BankingXHeadingV2> : null}
            <BankingXSubHeadingV2>{widgetData?.sub_headline}</BankingXSubHeadingV2>
            <BulletPointsContainer widgetData={widgetData} />
          </Box>
          <Box position="absolute" bottom="spacing.8" right="64px">
            <Button
              type="button"
              className="Forward-Button"
              iconAfter="arrow-forward"
              onClick={() => handleGetStartedButton(widgetData?.get_started_cta)}
              iconPosition="right"
              icon={ArrowRightIcon}
            >
              {widgetData?.get_started_cta?.label}
            </Button>
          </Box>
        </Box>
      </OnBoardingWrapper>
    )
  );
};

export default Onboarding;
