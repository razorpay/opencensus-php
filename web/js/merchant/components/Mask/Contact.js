import { formatPhoneNumber } from '@razorpay/i18nify-js';
import { connect } from 'react-redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getMaskedContact } from 'merchant/components/Mask/utils/masking';
import { ANALYTICS } from '@libs/shared-utils';

// Formats phone number based on user's locale. In case of error, logs the error and returns the original contact as fallback
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

function MaskedContact({ contact = '', user }) {
  const formattedContact = contact ? getI18FormattedPhoneNumber(contact) : contact;
  return user.isHidePIDetails ? getMaskedContact(formattedContact) : formattedContact;
}

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
  };
};

export default connect(mapStateToProps)(MaskedContact);
