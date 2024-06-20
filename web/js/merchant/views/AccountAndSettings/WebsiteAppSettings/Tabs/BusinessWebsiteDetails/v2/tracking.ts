import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export interface Track {
  objectName: string;
  actionName?: string;
  screen?: string;
  properties?: Record<string, unknown>;
}
type Properties = Record<string, any>;

const SCREEN = 'Business website details';

export const track = ({
  objectName,
  actionName = 'Clicked',
  screen = SCREEN,
  properties,
}: Track): void => {
  analyticsTrack({
    objectName,
    actionName,
    screen,
    properties: {
      version: 'v2',
      page: SCREEN,
      ...properties,
      session_id: window.session_id,
      currentWebsite: window.rzp_user?.business_website,
      ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
    },
  });
};

export const trackBusinessWebsitePageLoad = () => {
  track({
    objectName: 'Business Website Details Page',
    actionName: 'Displayed',
  });
};

export const trackWebsiteAppDetailsButtonClick = (properties: Properties) => {
  track({
    objectName: 'Add Website App Details Button',
    actionName: 'Clicked',
    properties,
  });
};

export const trackEditWebsiteIconClick = (properties: Properties) => {
  track({
    objectName: 'Edit Website Icon',
    actionName: 'Clicked',
    properties,
  });
};

export const trackWebsitePrivacyPolicyModalLoaded = (properties: Properties) => {
  track({
    objectName: 'Submit Website Privacy Policy Modal',
    actionName: 'Displayed',
    properties,
  });
};

export const trackWebsitePrivacyPolicyModalRequestClicked = (properties: Properties) => {
  track({
    objectName: 'Submit Website Privacy Policy Modal Request',
    actionName: 'Clicked',
    properties,
  });
};

export const trackSubmitWebsiteDetailsVerificationModalLoad = (properties: Properties) => {
  track({
    objectName: 'Submit Website Details Verification Modal',
    actionName: 'Displayed',
    properties,
  });
};

export const trackAcceptPaymentToggleButtonClick = (properties: Properties) => {
  track({
    objectName: 'Accept Payment Toggle Button',
    actionName: 'Clicked',
    properties,
  });
};

export const trackLoginRequiredToggleButtonClick = (properties: Properties) => {
  track({
    objectName: 'Login Required Toggle Button',
    actionName: 'Clicked',
    properties,
  });
};

export const trackSubmitWebsiteDetailsVerificationRequestClick = (properties: Properties) => {
  track({
    objectName: 'Submit Website Details Verification Request',
    actionName: 'Clicked',
    properties,
  });
};

export const trackBasicWebsiteCheckInProgressModalLoad = (properties: Properties) => {
  track({
    objectName: 'Basic Website Check In Progress Modal',
    actionName: 'Displayed',
    properties,
  });
};

export const trackBasicWebsiteCheckCompleteModalLoad = (properties: Properties) => {
  track({
    objectName: 'Basic Website Check Complete Modal',
    actionName: 'Displayed',
    properties,
  });
};

export const trackWebsiteRequestStatusBannerLoad = (properties: Properties) => {
  track({
    objectName: 'Website Request Status Banner',
    actionName: 'Displayed',
    properties,
  });
};

export const trackWebsiteRequestStatusBannerOptionClick = (properties: Properties) => {
  track({
    objectName: 'Website Request Status Banner Option',
    actionName: 'Clicked',
    properties,
  });
};

// export const trackCreatePolicyPagesButtonClick = () => {
//   track({
//     objectName: 'Create Policy Pages Button',
//     actionName: 'Clicked',
//   });
// };

// export const trackPolicyPageDetailsAdditionClick = () => {
//   track({
//     objectName: 'Policy Page Details Addition',
//     actionName: 'Clicked',
//   });
// };
