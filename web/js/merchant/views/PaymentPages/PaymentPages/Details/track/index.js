import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentPages().interaction(`pp.details.${event}`, {
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'details payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    pageOpen: () => {
      sendToLumberjack('page_open');
      sendToSegment(`page`, 'open');
    },
    pageStatus: (type) => {
      sendToLumberjack(`status.${type}`);
      sendToSegment(`page status ${type}`, 'click');
    },
    cancelExpiry: () => {
      sendToLumberjack('expiry_cancel');
      sendToSegment('cancel expiry', 'click');
    },
    tickExpiry: () => {
      sendToLumberjack('expiry_tick');
      sendToSegment('tick expiry', 'click');
    },
    duplicatePage: () => {
      sendToLumberjack('duplicate_page');
      sendToSegment('duplicate page', 'click');
    },
    saveNotes: (modified) => {
      sendToLumberjack('notes', { modified });
      sendToSegment('save notes', 'clicked', { modified });
    },
    confirmDeleteNotes: () => {
      sendToLumberjack('notes_closed');
      sendToSegment('confirm delete notes', 'clicked');
    },
    cancelDeleteNotes: () => {
      sendToLumberjack('notes.close');
      sendToSegment('cancel delete notes', 'clicked');
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
