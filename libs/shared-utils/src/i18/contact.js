import { formatPhoneNumber } from '@razorpay/i18nify-js';
import { ANALYTICS } from '../constants/analytics';
import { analyticsTrack } from '@dashboard/shared-utils/analytics';

export function getI18FormattedPhoneNumber(contact) {
  try {
    const formattedContact = formatPhoneNumber(contact);
    return formattedContact ? formattedContact : contact;
  } catch (e) {
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.PHONE_NUMBER,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `${contact}`,
        error: `${e}`,
      },
    });
    return contact;
  }
}
