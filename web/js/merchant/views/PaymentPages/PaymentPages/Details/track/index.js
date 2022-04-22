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
    changeExpiry: () => {
      sendToLumberjack('expiry_date_change');
      sendToSegment('change expiry', 'click');
    },
    noExpiry: (value) => {
      sendToLumberjack('no_expiry_checkbox_click', { value });
      sendToSegment('no expire', 'click', { value });
    },
    updateDate: () => {
      sendToLumberjack('expiry_date_select');
      sendToSegment('update date', 'click');
    },
    cancelExpiry: () => {
      sendToLumberjack('expiry_cancel');
      sendToSegment('cancel expiry', 'click');
    },
    saveExpiry: () => {
      sendToLumberjack('expiry_date_save');
      sendToSegment('save expiry', 'click');
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
      sendToLumberjack('update_stock_click');
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
      sendToLumberjack('payment_id_enter', { value: event.target.value });
      sendToSegment('search with payment id', 'input', { value: event.target.value });
    },
    searchStatus: (event) => {
      sendToLumberjack('status_click', { value: event.target.value || 'all' });
      sendToSegment('search with status', 'click', { value: event.target.value || 'all' });
    },
    searchEmail: (event) => {
      sendToLumberjack('email_id_enter', { value: event.target.value });
      sendToSegment('search with email', 'input', { value: event.target.value });
    },
    searchCount: (event) => {
      sendToLumberjack('count_enter', { value: event.target.value });
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
      sendToSegment(`share ${titleCase(eventName)}`, 'click', data);
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
