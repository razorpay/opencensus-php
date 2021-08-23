import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _segmentTrack() {
  function send(objectName, actionName, screen, properties = {}) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...properties,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  return {
    // Fields
    fields: (fieldName, duplicate) => {
      let actionName = 'added';
      let objectName = fieldName;
      if (objectName === 'sms_notify') {
        objectName = 'sms notify';
        actionName = 'changed';
      } else if (objectName === 'email_notify') {
        objectName = 'email notify';
        actionName = 'changed';
      } else if (objectName === 'partial_payment') {
        objectName = 'partial payment';
        actionName = 'changed';
      } else if (objectName === 'reminder_enable') {
        objectName = 'reminder enable';
        actionName = 'changed';
      }
      return send(objectName, actionName, 'Create Payment Link', { duplicate: duplicate });
    },

    // Form
    form: {
      close: (actionName) =>
        send('payment link', 'closed', 'Create Payment Link', { actionName: actionName }),
      success: (resp, duplicate) => {
        const properties = {
          customerDetailsFilled: !!resp.data.customer_details,
          emailFilled:
            resp.data.customer_details && resp.data.customer_details.customer_email ? true : false,
          phoneNumberFilled:
            resp.data.customer_details && resp.data.customer_details.customer_contact
              ? true
              : false,
          paymentLinksNotes: resp.data.notes,
          paymentLinkId: resp.data.id,
          paymentAmount: resp.data.amount,
          currency: resp.data.currency,
          paymentDescriptionFilled: !!resp.data.description,
          partialsPayments: resp.data.partial_payment,
          refrenceId: resp.data.receipt,
          expiryDate: resp.data.expire_by,
          duplicateLink: duplicate,
          status: 'Success',
        };
        return send('payment link', 'issued', 'Create Payment Link', properties);
      },
      fail: (errors, duplicate) => {
        const properties = {
          duplicateLink: duplicate,
          status: 'Failure',
          failureReason: errors[0],
        };
        return send('payment link', 'issued', 'Create Payment Link', properties);
      },
    },
  };
}

export default _segmentTrack();
