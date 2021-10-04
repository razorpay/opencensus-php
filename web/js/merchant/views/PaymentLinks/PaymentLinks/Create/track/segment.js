import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _segmentTrack() {
  function send(objectName, actionName, properties = {}) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'Create Payment Link',
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
      return send(objectName, actionName, { duplicate });
    },

    // Form
    form: {
      close: (actionName) => send('payment link', 'closed', { actionName }),
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
        return send('payment link', 'issued', properties);
      },
      fail: (errors, duplicate) => {
        const properties = {
          duplicateLink: duplicate,
          status: 'Failure',
          failureReason: errors[0],
        };
        return send('payment link', 'issued', properties);
      },
    },

    paymentLinkCreate: () => {
      const properties = {
        origin: 'dashboard',
      };
      return send('create paymentlink', 'click', properties);
    },

    paymentLinkIssue: (clone) => {
      const properties = {
        origin: 'dashboard',
        clone,
      };
      return send('create payment link again', 'click', properties);
    },

    paymentLinkCancel: (clone) => {
      const properties = {
        origin: 'dashboard',
        clone,
      };
      return send('payment link cancel', 'click', properties);
    },

    paymentLinkFail: (clone, response) => {
      const properties = {
        origin: 'dashboard',
        clone,
        response,
      };
      return send('payment link fail', 'click', properties);
    },
    successToast: (clone, close) => {
      const properties = {
        origin: 'dashboard',
        clone,
        close,
      };
      return send('payment link success toast close', 'click', properties);
    },
    cloneStart: () => {
      const properties = {
        origin: 'dashboard',
      };
      return send('payment link clone start', 'click', properties);
    },
    cloneClose: () => {
      const properties = {
        origin: 'dashboard',
      };
      return send('payment link clone close', 'click', properties);
    },
    cloneComplete: () => {
      const properties = {
        origin: 'dashboard',
      };
      return send('payment link clone complete', 'click', properties);
    },
  };
}

export default _segmentTrack();
