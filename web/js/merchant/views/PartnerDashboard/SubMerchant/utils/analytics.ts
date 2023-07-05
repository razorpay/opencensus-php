import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

export const trackSubmerchantReferViaEmail = ({
  contact_mobile,
  email,
}: {
  contact_mobile: string;
  email: string;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Submerchant Refer Via Email',
    actionName: 'Clicked',
    screen: 'Add Merchant modal',
    properties: {
      contactEmail: email,
      contactMobile: contact_mobile,
    },
  });
};

export const trackSubmerchantReferViaBulkUpload = ({
  bulkContactsCount,
}: {
  bulkContactsCount: number;
}): void => {
  return analyticsTrackWithUserInfo({
    objectName: 'Partner Submerchant Refer Via Bulk Upload',
    actionName: 'Clicked',
    screen: 'Add Merchant modal',
    properties: {
      contactsCount: bulkContactsCount,
    },
  });
};
