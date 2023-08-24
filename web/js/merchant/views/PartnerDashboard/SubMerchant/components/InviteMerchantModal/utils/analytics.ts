import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
const { SINGLE_INVITE, BULK_UPLOAD, PUBLIC_LINK } = INVITE_TAB_TYPES;

export const trackInviteFlowModalLoaded = ({ activeTabId, productType }) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Flow Modal',
    actionName: 'Loaded',
    screen: 'Invite Merchant Modal',
    properties: {
      inviteFlow: activeTabId,
      productType,
    },
  });

export const trackEmailFlowCTAClicked = ({ ctaClicked, productType, message = '' }) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Email Flow CTA',
    actionName: 'Clicked',
    screen: 'Invite Merchant Modal',
    properties: {
      ctaClicked,
      productType,
      message,
    },
  });

export const trackBulkFlowCTAClicked = ({ ctaClicked, productType, message = '' }) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Bulk Upload Flow CTA',
    actionName: 'Clicked',
    screen: 'Invite Merchant Modal',
    properties: {
      ctaClicked,
      message,
      productType,
    },
  });

export const trackPublicLinkFlowCTAClicked = ({ ctaClicked, productType, message = '' }) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Public Flow CTA',
    actionName: 'Clicked',
    screen: 'Invite Merchant Modal',
    properties: {
      ctaClicked,
      message,
      productType,
    },
  });
export const trackInviteFlowCommonCtaClicked = ({
  inviteFlow,
  ctaClicked,
  productType,
  message = '',
}) => {
  switch (inviteFlow) {
    case SINGLE_INVITE:
    default:
      return trackEmailFlowCTAClicked({ ctaClicked, productType, message });
    case BULK_UPLOAD:
      return trackEmailFlowCTAClicked({ ctaClicked, productType, message });
    case PUBLIC_LINK:
      return trackPublicLinkFlowCTAClicked({ ctaClicked, productType, message });
  }
};
export const trackInviteFlowFieldEditStarted = ({
  inviteFlow,
  productType,
  fieldEdited,
  screen = 'Invite Merchant Modal',
}) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Flow Field Edited',
    actionName: 'Started',
    screen,
    properties: {
      inviteFlow,
      productType,
      fieldEdited,
    },
  });

export const trackInviteFlowValidationError = ({
  inviteFlow,
  fieldEdited,
  errorMessage,
  productType,
  screen = 'Invite Merchant Modal',
}) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Form Field Validation',
    actionName: 'Error',
    screen,
    properties: {
      inviteFlow,
      fieldEdited,
      errorMessage,
      productType,
    },
  });

export const trackInviteFlowGenericError = ({
  inviteFlow,
  errorMessage,
  productType,
  screen = 'Invite Merchant Modal',
}) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Form Field Generic',
    actionName: 'Error',
    screen,
    properties: {
      inviteFlow,
      errorMessage,
      productType,
    },
  });

export const trackInviteFlowSuccessfulInvite = ({
  inviteFlow,
  productType,
  screen = 'Invite Merchant Modal',
  isKycAssistedSelected,
}) =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Flow Successful Invite',
    actionName: 'Sent',
    screen,
    properties: {
      inviteFlow,
      productType,
      isKycAssistedSelected,
    },
  });

export const trackCopyLinkClicked = ({
  inviteFlow,
  productType,
  screen = 'Invite Merchant Modal',
  isKycAssistedSelected,
}) =>
  analyticsTrackWithUserInfo({
    objectName: 'Copy Referal Link',
    actionName: 'Clicked',
    screen,
    properties: {
      inviteFlow,
      productType,
      isKycAssistedSelected,
    },
  });
export const trackSocialShareLinkClicked = ({
  inviteFlow,
  productType,
  socialMedia,
  screen = 'Invite Merchant Modal',
  isKycAssistedSelected,
}) =>
  analyticsTrackWithUserInfo({
    objectName: 'Social Share Referral Link',
    actionName: 'Clicked',
    screen,
    properties: {
      inviteFlow,
      productType,
      isKycAssistedSelected,
      socialMedia,
    },
  });

export const trackInviteFlowOptOutForm = ({ inviteFlow, productType, radioValue, customReason }) =>
  analyticsTrackWithUserInfo({
    objectName: 'KYC Access Opt Out Form',
    actionName: 'Submitted',
    screen: 'Success Screen',
    properties: {
      response: radioValue,
      productType,
      inviteFlow,
      customReason,
    },
  });

export const trackSubmerchantReferViaEmail = ({
  contact_mobile,
  email,
  isKycAssistedSelected = false,
}: {
  contact_mobile: string;
  email: string;
  isKycAssistedSelected: boolean;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Submerchant Refer Via Email',
    actionName: 'Clicked',
    screen: 'Add Merchant modal',
    properties: {
      contactEmail: email,
      contactMobile: contact_mobile,
      isKycAssistedSelected,
    },
  });
};

export const trackSubmerchantReferViaBulkUpload = ({
  bulkContactsCount,
  isKycAssistedSelected = false,
}: {
  bulkContactsCount: number;
  isKycAssistedSelected: boolean;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Submerchant Refer Via Bulk Upload',
    actionName: 'Clicked',
    screen: 'Add Merchant modal',
    properties: {
      contactsCount: bulkContactsCount,
      isKycAssistedSelected,
    },
  });
};
