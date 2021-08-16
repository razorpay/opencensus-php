import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
    let lumberjackTrack = () => {};
    let config = {
      payment_page_id: '',
    };
  
    function sendToLumberjack(event, data) {
      lumberjackTrack(
        window.rzpQ.paymentPages().interaction(`pp.create.${event}`, {            
          data,
          config,
        }),
      );
    }

    function sendToSegment(objectName, actionName, properties) {
      analyticsTrack({
        objectName,
        actionName,
        screen: 'create payment page',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...properties,
        },
      });
    }

    function setConfig(newConfig) {
        config = {
          ...config,
          ...newConfig,
        };
    }
  
    return {
      publishPaymentPage: (label) => sendToSegment(label, 'clicked'),

      plugins: {
        save: (fb, ga) => sendToLumberjack('plugins.save', { fb, ga }), 
      },

      receipt: {
        open: () => {
          sendToSegment('payment receipts', 'clicked');
          sendToLumberjack('receipt.open_settings');
        },
        close: () => sendToLumberjack('receipt.close_settings'), 
        save: (automatedReceipts, input_field, details_80G) => {
          sendToLumberjack('receipt.save', { automatedReceipts, input_field, details_80G }); 
          sendToSegment('receipts', 'saved');
        },
        clickSendingOption: (label) => {
          sendToLumberjack(`receipt.${label}`);
          sendToSegment(`receipts ${label}`, 'clicked');
        },
        check80GDetails: (label) => {
          sendToSegment('receipts 80-G', 'clicked');
          sendToLumberjack(`receipt.${label}`);
        },
        checkInputFields: () => sendToSegment('receipts input field', 'chosen'),
      },

      modal80G: {
        open: () => sendToLumberjack('receipt.80g_details_start'), 
        close: () => sendToLumberjack('receipt.80g_details_close'),
        uploadStart: () => sendToLumberjack('receipt.80g_upload_start'), 
        uploadSave: () => sendToLumberjack('receipt.80g_upload_save'), 
        removeSignature: () => sendToLumberjack('receipt.80g_upload_remove'), 
        save: () => {
          sendToLumberjack('receipt.80g_details_save');
          sendToSegment('receipts 80-G', 'saved');
        },
        uploadFail: (error) => sendToLumberjack('receipt.80g_upload_fail', { error }), 
      },

      settings: {
        open: () => {
          sendToLumberjack('settings.open');
          sendToSegment('settings', 'clicked');
        },
        save: (expiry, customMessage, redirect) => {
          sendToLumberjack('settings.save', { expiry, customMessage, redirect});
          sendToSegment('settings', 'saved');
        },
        close: () => sendToSegment('settings', 'closed'), 
        checkCustomMessage: (value) => {
          let label = value ? 'checked' : 'unchecked';
          sendToLumberjack(`settings.custom_message.${label}`);
        },
        checkRedirect: (value) => {
          let label = value ? 'checked' : 'unchecked';
          sendToLumberjack(`settings.redirect.${label}`);
        },
        clickConfigurePlugins: () => sendToLumberjack(`settings.plugins.configure`), 
        clickCreateHyperlinkButton: () => sendToLumberjack(`settings.button.create`), 
        clickExpiryDate: () => sendToSegment('settings expiry', 'added'), 
      },

      success: {
        clickCopyUrl: () => {
          sendToLumberjack(`success.copy_url`);
          sendToSegment('copy hyperlink', 'button');
        },
        clickShareUrlViaEmailorSMS: (trackData) => sendToLumberjack(`success.share.sms_email`, {trackData}), 
        clickShareUrlViaSocialMedia: (platform) => sendToLumberjack(`success.share.${platform}`),
        getHyperlinkButton: () => sendToSegment('get hyperlink', 'button'), 
      },

      init(_lumberjackTrack, config) {
        lumberjackTrack = _lumberjackTrack;
  
        setConfig(config);
      },
    };
  }
  
  export default _track();
  