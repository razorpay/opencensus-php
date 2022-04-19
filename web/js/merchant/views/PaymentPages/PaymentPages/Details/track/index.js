import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';

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
    updateDate: () => {
      sendToLumberjack('update_date');
      sendToSegment('update date', 'click');
    },
    noExpiry: () => {
      sendToLumberjack('no_expire');
      sendToSegment('no expire', 'click');
    },
    duplicatePage: () => {
      sendToLumberjack('duplicate_page');
      sendToSegment('duplicate page', 'click');
    },
    addNewNote: () => {
      sendToLumberjack('add_new_note');
      sendToSegment('add new note', 'clicked');
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
    copyUrl: () => {
      sendToLumberjack('copy_url');
      sendToSegment('copy url', 'clicked');
    },
    share: () => {
      sendToLumberjack('share');
      sendToSegment('share', 'clicked');
    },
    showMore: () => {
      sendToLumberjack('show_more');
      sendToSegment('show more', 'clicked');
    },
    updateStock: () => {
      sendToLumberjack('update_stock');
      sendToSegment('update stock', 'clicked');
    },
    downloadReport: (extension) => {
      sendToLumberjack('download_report', { extension });
      sendToSegment('download report', 'clicked', { extension });
    },
    settingsDropdown: () => {
      sendToLumberjack('settings_dropdown');
      sendToSegment('settings dropdown', 'clicked');
    },
    receiptSettings: () => {
      sendToLumberjack('receipt_settings');
      sendToSegment('receipt settings', 'clicked');
    },
    pageSettings: () => {
      sendToLumberjack('page_settings');
      sendToSegment('page settings', 'clicked');
    },
    searchPaymentId: (event) => {
      sendToLumberjack('search.payment_id', { value: event.target.value });
      sendToSegment('search with payment id', 'input', { value: event.target.value });
    },
    searchStatus: (event) => {
      sendToLumberjack('search.status', { value: event.target.value || 'all' });
      sendToSegment('search with status', 'click', { value: event.target.value || 'all' });
    },
    searchEmail: (event) => {
      sendToLumberjack('search.email', { value: event.target.value });
      sendToSegment('search with email', 'input', { value: event.target.value });
    },
    searchCount: (event) => {
      sendToLumberjack('search.count', { value: event.target.value });
      sendToSegment('search with count', 'input', { value: event.target.value });
    },
    search: (params) => {
      sendToLumberjack('search', { params });
      sendToSegment('search', 'click', { params });
    },
    paymentIdClick: () => {
      sendToLumberjack('payment_id_click');
      sendToSegment('payment id', 'click');
    },
    shareModalEvents: (eventName, data) => {
      sendToLumberjack(`share_${eventName}`, data);
      sendToSegment(`share ${titleCase(eventName)}`, data);
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
