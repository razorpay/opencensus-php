import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _segmentTrack() {
  const template = {};

  function send(objectName, actionName, screen, properties = {}) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      properties: {
        ...properties,
        ...template,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }

  return {
    // Payment link type selection
    linkTypeSelection: {
      select(type) {
        template.templateType = type;
        send('template', 'selected', 'Create Payment Link');
      },
    },

    // Fields
    fields: {
      paymentFor: () => send('description', 'added', 'Create Payment Link'),
      partialPayment: () => send('partial payment', 'selected', 'Create Payment Link'),
      notifySms: () => send('sms notify', 'changed', 'Create Payment Link'),
      notifyEmail: () => send('email notify', 'changed', 'Create Payment Link'),
      receipt: () => send('receipt', 'added', 'Create Payment Link'),
      expiryDate: () => send('expiry date', 'selected', 'Create Payment Link'),
      expiryTime: () => send('expiry time', 'selected', 'Create Payment Link'),
      reminders: () => send('reminders', 'selected', 'Create Payment Link'),
    },

    // Form
    form: {
      close: () => send('payment link', 'closed', 'Create Payment Link'),
      cancelConfirm: (options) =>
        send('payment link cancel', 'confirmed', 'Create Payment Link', options),
      success: (resp, duplicate) => {
        const properties = {
          customerDetailsFilled: !!resp.data.customer_details,
          emailFilled: !!(resp.data.customer_details && resp.data.customer_details.customer_email),
          phoneNumberFilled: !!(
            resp.data.customer_details && resp.data.customer_details.customer_contact
          ),
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
      fail: (error, duplicate) => {
        const properties = {
          duplicateLink: duplicate,
          status: 'Failure',
          failureReason: error,
        };
        return send('payment link', 'issued', 'Create Payment Link', properties);
      },
    },
  };
}

export default _segmentTrack();
