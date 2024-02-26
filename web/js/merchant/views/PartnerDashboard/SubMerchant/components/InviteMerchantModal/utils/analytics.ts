import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
const { SINGLE_INVITE, BULK_UPLOAD, PUBLIC_LINK } = INVITE_TAB_TYPES;

export const trackInviteFlowModalLoaded = ({
  activeTabId,
  productType,
}: {
  activeTabId: string;
  productType: string;
}): void =>
  analyticsTrackWithUserInfo({
    objectName: 'Partner Invite Flow Modal',
    actionName: 'Loaded',
    screen: 'Invite Merchant Modal',
    properties: {
      inviteFlow: activeTabId,
      productType,
    },
  });

export const trackEmailFlowCTAClicked = ({
  ctaClicked,
  productType,
  message = '',
}: {
  ctaClicked: string;
  productType: string;
  message?: string;
}): void =>
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

export const trackBulkFlowCTAClicked = ({
  ctaClicked,
  productType,
  message = '',
}: {
  ctaClicked: string;
  productType: string;
  message?: string;
}): void =>
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

export const trackPublicLinkFlowCTAClicked = ({
  ctaClicked,
  productType,
  message = '',
}: {
  ctaClicked: string;
  productType: string;
  message?: string;
}): void =>
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
}: {
  inviteFlow: string;
  ctaClicked: string;
  productType: string;
  message?: string;
}): void => {
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
}: {
  inviteFlow: string;
  productType: string;
  fieldEdited: string;
  screen?: string;
}): void =>
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
}: {
  inviteFlow: string;
  productType: string;
  errorMessage: string | TODO_PD;
  fieldEdited: string;
  screen?: string;
}): void =>
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
}: {
  inviteFlow: string;
  productType: string;
  errorMessage: string | TODO_PD;
  screen?: string;
}): void =>
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
}: {
  inviteFlow: string;
  productType: string;
  isKycAssistedSelected: boolean | null;
  screen?: string;
}): void =>
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
}: {
  inviteFlow: string;
  productType: string;
  isKycAssistedSelected: boolean | null;
  screen?: string;
}): void =>
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
}: {
  inviteFlow: string;
  productType: string;
  socialMedia: string;
  screen?: string;
  isKycAssistedSelected: boolean | null;
}): void =>
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

export const trackInviteFlowOptOutForm = ({
  inviteFlow,
  productType,
  radioValue,
  customReason,
}: {
  inviteFlow: string;
  productType: string;
  radioValue: string;
  customReason: string;
}): void =>
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
  productType,
  isKycAssistedSelected = false,
}: {
  contact_mobile: string;
  email: string;
  productType: string;
  isKycAssistedSelected?: boolean;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Submerchant Refer Via Email',
    actionName: 'Clicked',
    screen: 'Add Merchant modal',
    properties: {
      contactEmail: email,
      contactMobile: contact_mobile,
      isKycAssistedSelected,
      productType,
    },
  });
};

export const trackSubmerchantReferViaBulkUpload = ({
  bulkContactsCount,
  isKycAssistedSelected = false,
  productType,
}: {
  bulkContactsCount: number;
  isKycAssistedSelected?: boolean;
  productType: string;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Submerchant Refer Via Bulk Upload',
    actionName: 'Clicked',
    screen: 'Add Merchant modal',
    properties: {
      contactsCount: bulkContactsCount,
      isKycAssistedSelected,
      productType,
    },
  });
};
