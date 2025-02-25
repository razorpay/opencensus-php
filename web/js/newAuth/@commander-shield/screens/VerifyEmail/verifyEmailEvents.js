import trackEvents from '../../js/analytics';

const verifyEmailEvents = {
  trackVerifyEmailInitiate: (user) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'email_verification',
      data: {
        mid: user.mid,
        userid: user.id,
      },
      toCleverTap: true,
    });
    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'cta clicked',
      screen: 'verify email page',
    });
    trackEvents.segment({
      objectName: 'Verify Email',
      actionName: 'Clicked',
      screen: 'verify email page',
      toCleverTap: true,
    });
  },

  trackVerifyEmailRequest: () => {
    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'request sent',
      screen: 'verify email page',
      properties: {
        status: 'success',
      },
      toCleverTap: true,
    });

    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'request',
      screen: 'verify email page',
      properties: {
        status: 'success',
        errorMessage: 'null',
      },
      toCleverTap: true,
    });
  },

  trackInputError: (error, field) => {
    trackEvents.segment({
      objectName: 'Form Field',
      actionName: 'Validation Failed',
      screen: 'home page',
      properties: {
        error,
        fieldLabel: field,
        tab: 'Verify Email',
      },
    });
  },

  trackResendOtpInitiate: (user) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'resend_verification_email',
      data: {
        mid: user.mid,
        userid: user.id,
      },
    });

    trackEvents.ga('Signup - Steps', 'Click - Resend Verification Email');
  },

  trackResendOtpError: (error) => {
    trackEvents.ga('Signup - Steps', 'Click - Resend Verification Email', error);
  },

  trackVerifyEmailSuccess: (user) => {
    trackEvents.prometheus({ type: 'signup', label: 'verify_otp_success' });

    trackEvents.hubspot({
      name: 'update_property',
      data: {
        email: user.email,
        email_verified: true,
      },
    });

    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'request response',
      screen: 'verify email page',
      properties: {
        status: 'success',
      },
      toCleverTap: true,
    });

    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'result',
      screen: 'verify email page',
      properties: {
        status: 'success',
        errorMessage: 'null',
      },
      toCleverTap: true,
    });
  },

  trackVerifyEmailError: ({ user, error }) => {
    trackEvents.dataLake({
      type: 'failed',
      eventName: 'email_verification',
      data: {
        mid: user.mid,
        userid: user.id,
        error,
      },
      toCleverTap: true,
    });
    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'request response',
      screen: 'verify email page',
      properties: {
        status: 'failure',
        errorMessage: error,
      },
      toCleverTap: true,
    });

    trackEvents.segment({
      objectName: 'Verify email',
      actionName: 'result',
      screen: 'verify email page',
      properties: {
        status: 'failure',
        errorMessage: error,
      },
      toCleverTap: true,
    });
  },
};
export default verifyEmailEvents;
