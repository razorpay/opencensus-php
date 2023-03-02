import { analyticsTrack, getDeviceSource } from 'common/utils/analytics';

function _track() {
  function sendToSegment(objectName, actionName = 'click', properties = {}) {
    return analyticsTrack({
      objectName,
      actionName,
      screen: 'razorpayme',
      toLumberjack: true,
      properties: {
        source: getDeviceSource(),
        ...properties,
      },
    });
  }

  return {
    paymentIdClick: () => {
      sendToSegment('razorpayme payment id', 'click');
    },
    search: (params) => {
      sendToSegment('razorpayme search', 'click', { params });
    },
    searchPaymentId: (event) => {
      sendToSegment('razorpayme search with payment id', 'input', { value: event.target.value });
    },
    searchStatus: (event) => {
      sendToSegment('razorpayme search with status', 'click', {
        value: event.target.value || 'all',
      });
    },
    searchEmail: (event) => {
      sendToSegment('razorpayme search with email', 'input', { value: event.target.value });
    },
    searchCount: (event) => {
      sendToSegment('razorpayme search with count', 'input', { value: event.target.value });
    },
    onboarding: {
      startSuccess: () => {
        sendToSegment('razorpayme onboarding start success', 'click');
      },
      editLinkSuccess: () => {
        sendToSegment('razorpayme edit link success', 'click');
      },
      editLinkSave: () => {
        sendToSegment('razorpayme edit link save', 'click');
      },
      editLinkCancel: () => {
        sendToSegment('razorpayme edit link cancel', 'click');
      },
      getStarted: () => {
        sendToSegment('razorpayme get started success', 'click');
      },
    },
    detail: {
      copyLink: () => {
        sendToSegment('razorpayme copy link', 'click');
      },
      shareLink: () => {
        sendToSegment('razorpayme share link initiate', 'click');
      },
    },
  };
}

export default _track();
