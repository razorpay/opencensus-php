import trackEvents from '../../js/analytics';

const otpInputEvents = {
  trackAutoOtpReadInitiate: (context) => {
    trackEvents.dataLake({
      type: 'initiated',
      eventName: 'auto_otp_read',
      data: {
        context,
      },
    });
  },
  trackAutoOtpReadSuccess: (context) => {
    trackEvents.dataLake({
      type: 'success',
      eventName: 'auto_otp_read',
      data: {
        context,
      },
    });
  },
  trackAutoOtpReadFailed: (context, err) => {
    trackEvents.dataLake({
      type: 'failed',
      eventName: 'auto_otp_read',
      data: {
        context,
        reason: err?.message,
      },
    });
  },
};

export default otpInputEvents;
