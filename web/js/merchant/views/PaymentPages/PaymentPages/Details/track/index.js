import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, titleCase } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

function _track() {
  let lumberjackTrack = () => {};

  const default_screen = 'details payment page';
  const section_name = 'Details Payment Page';

  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentPages().interaction(`pp.details.${event}`, {
        data,
      }),
    );
  }

  function sendToSegment(
    objectName,
    actionName,
    properties,
    screen = default_screen,
    toCleverTap = false,
  ) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { isStorefrontPage: true }),
        ...properties,
        section: section_name,
      },
    });
  }

  function trackSelfServeInitiate() {
    selfServeTrackInitiate({
      selfServeAction: 'Create Payment Page',
      page: 'Paymentpage',
      screen: 'Payment Page',
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
    duplicatePage: (extraProperties = {}) => {
      sendToSegment(
        'duplicate page',
        'click',
        {
          ...extraProperties,
        },
        'Create storefront page',
      );
      sendToLumberjack('duplicate_page');
      trackSelfServeInitiate();
    },
    editPage: (extraProperties = {}) => {
      sendToSegment(
        'edit page',
        'click',
        {
          ...extraProperties,
        },
        'Edit storefront page',
      );
      sendToLumberjack('edit_page');
      trackSelfServeInitiate();
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
    showMore: (extraProperties = {}) => {
      sendToLumberjack('show_more');
      sendToSegment(
        'show more',
        'clicked',
        {
          ...extraProperties,
        },
        'Details Payment Page',
      );
    },
    updateStock: (extraProperties = {}) => {
      sendToLumberjack('update_stock_click');
      sendToSegment(
        'Update Stock',
        'click',
        {
          ...extraProperties,
        },
        'Details Payment Page',
      );
    },
    downloadReport: (extension) => {
      sendToLumberjack('download_report', { extension });
      sendToSegment('download report', 'clicked', { extension });
    },
    settingsDropdown: (extraProperties = {}) => {
      sendToSegment(
        'Settings Dropdown',
        'click',
        {
          ...extraProperties,
        },
        'Details Payment Page',
      );
      sendToLumberjack('settings_dropdown');
    },
    receiptSettings: () => {
      sendToLumberjack('receipt_settings');
      sendToSegment('receipt settings', 'clicked');
      trackSelfServeInitiate();
    },
    pageSettings: () => {
      sendToLumberjack('page_settings');
      sendToSegment('page settings', 'clicked');
      trackSelfServeInitiate();
    },
    searchPaymentId: (event) => {
      sendToLumberjack('payment_id_enter', { value: event.target.value });
      sendToSegment('search with payment id', 'input', {
        value: event.target.value,
      });
    },
    searchStatus: (event) => {
      sendToLumberjack('status_click', { value: event.target.value || 'all' });
      sendToSegment('search with status', 'click', {
        value: event.target.value || 'all',
      });
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
    deactivatePageClicked: (extraProperties = {}) => {
      sendToSegment(
        'Deactivate Page',
        'clicked',
        {
          ...extraProperties,
        },
        'Open deactivate dialog',
      );
    },
  };
}

export default _track();
