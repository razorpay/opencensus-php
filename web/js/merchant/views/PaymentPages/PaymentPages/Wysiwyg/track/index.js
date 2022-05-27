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

  function sendToSegment(objectName, actionName, properties, toCleverTap = false) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'create payment page',
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        ...config,
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
    publishPaymentPage: (label, is_new_page, clone) => {
      sendToLumberjack('publish_page', { is_new_page, clone });
      sendToSegment(label, 'clicked');
    },
    publishPaymentPageSuccess: (payment_page_id, isNew) => {
      setConfig({ payment_page_id });

      sendToLumberjack('publish_page.success', { isNew });
      sendToSegment('publish page', 'success', { isNew }, true);
    },
    publishPaymentPageFail: (isNew) => {
      sendToLumberjack('publish_page.fail', { isNew });
      sendToSegment('publish page', 'fail', { isNew });
    },

    plugins: {
      save: (fb, ga) => {
        sendToLumberjack('plugins.save', { fb, ga });
        sendToSegment('plugins', 'save', { fb, ga });
      },
    },

    receipt: {
      open: () => {
        sendToSegment('payment receipts', 'clicked');
        sendToLumberjack('receipt.open_settings');
      },
      close: () => {
        sendToLumberjack('receipt.close_settings');
        sendToSegment('receipts', 'close');
      },
      save: (automatedReceipts, input_field, details_80G) => {
        sendToLumberjack('receipt.save', { automatedReceipts, input_field, details_80G });
        sendToSegment('receipts', 'saved', { automatedReceipts, input_field, details_80G });
      },
      clickSendingOption: (label) => {
        sendToLumberjack(`receipt.${label}`);
        sendToSegment(`receipts ${label}`, 'clicked');
      },
      check80GDetails: (label) => {
        sendToSegment('receipts 80-G', 'clicked');
        sendToLumberjack(`receipt.${label}`);
      },
      checkInputFields: (checked) => {
        sendToLumberjack('receipt.show_customer_info', { checked });
        sendToSegment('receipts input field', 'chosen', { checked });
      },
    },

    modal80G: {
      open: () => {
        sendToLumberjack('receipt.80g_details_start');
        sendToSegment('receipts 80-G', 'open');
      },
      close: () => {
        sendToLumberjack('receipt.80g_details_close');
        sendToSegment('receipts 80-G', 'close');
      },
      uploadStart: () => {
        sendToLumberjack('receipt.80g_upload_start');
        sendToSegment('receipts 80-G', 'upload start');
      },
      uploadSave: () => {
        sendToLumberjack('receipt.80g_upload_save');
        sendToSegment('receipts 80-G', 'upload save');
      },
      removeSignature: () => {
        sendToLumberjack('receipt.80g_upload_remove');
        sendToSegment('receipts 80-G', 'upload remove');
      },
      save: () => {
        sendToLumberjack('receipt.80g_details_save');
        sendToSegment('receipts 80-G', 'saved');
      },
      uploadFail: (error) => {
        sendToLumberjack('receipt.80g_upload_fail', { error });
        sendToSegment('receipts 80-G', 'upload fail', { error });
      },
    },

    settings: {
      open: () => {
        sendToLumberjack('settings.open');
        sendToSegment('settings', 'clicked');
      },
      save: (expiry, customMessage, redirect) => {
        sendToLumberjack('settings.save', { expiry, customMessage, redirect });
        sendToSegment('settings', 'saved');
      },
      close: () => sendToSegment('settings', 'closed'),
      enterCustomUrl: (event) => {
        sendToLumberjack('settings.custom_url', { value: event.target.value });
        sendToSegment('settings custom url', 'input', { value: event.target.value });
      },
      checkCustomMessage: (value) => {
        const label = value ? 'checked' : 'unchecked';
        sendToLumberjack(`settings.custom_message.${label}`);
        sendToSegment('settings', `custom message ${label}`);
      },
      checkRedirect: (value) => {
        const label = value ? 'checked' : 'unchecked';
        sendToLumberjack(`settings.redirect.${label}`);
        sendToSegment('settings', `redirect ${label}`);
      },
      clickConfigurePlugins: () => {
        sendToLumberjack(`settings.plugins.configure`);
        sendToSegment('settings', `configure plugins`);
      },
      enterFBPixel: (event) => {
        sendToLumberjack(`settings.plugins.fb_pixel`, { value: event.target.value });
        sendToSegment('settings plugins fb pixel', `input`, { value: event.target.value });
      },
      enterGAPixel: (event) => {
        sendToLumberjack(`settings.plugins.ga_pixel`, { value: event.target.value });
        sendToSegment('settings plugins ga pixel', `input`, { value: event.target.value });
      },
      clickCreateHyperlinkButton: () => {
        sendToLumberjack(`settings.button.create`);
        sendToSegment('settings', `create hyperlink`);
      },
      noExpiry: (value) => {
        sendToLumberjack('settings.no_expiry_checkbox_click', { value });
        sendToSegment('settings expiry', 'added', { value });
      },
      selectExpiryDate: () => {
        sendToLumberjack('settings.expiry_date_select');
        sendToSegment('settings expiry date', 'select');
      },
      clickShiprocketEnable: () => {
        sendToLumberjack('settings.enable_shiprocket');
        sendToSegment('settings', 'shiprocket enable');
      },
      clickShiprocketEnableConfirm: () => {
        sendToSegment('settings', 'shiprocket enable confirm');
      },
      clickShiprocketDisable: () => {
        sendToSegment('settings', 'shiprocket disable');
      },
      clickShiprocketDisableConfirm: () => {
        sendToSegment('settings', 'shiprocket disable confirm');
      },
      clickShiprocketDashboard: () => {
        sendToSegment('settings', 'shiprocket dashboard');
      },
    },

    success: {
      clickCopyUrl: () => {
        sendToLumberjack(`success.copy_url`);
        sendToSegment('success', 'copy url', {}, true);
      },
      clickShareUrlViaEmailorSMS: (trackData) => {
        sendToLumberjack(`success.share.sms_email`, { trackData });
        sendToSegment('success', 'share via email/sms', { modes: trackData });
      },
      clickShareUrlViaSocialMedia: (platform) => {
        sendToLumberjack(`success.share.${platform}`);
        sendToSegment('success', `share via ${platform}`);
      },
      getHyperlinkButton: () => sendToSegment('success', 'get hyperlink'),
    },

    wysiwyg: {
      reorderField: () => {
        sendToLumberjack('reorder_field');
        sendToSegment('form section field', 'reorder');
      },
      addPriceField: () => {
        sendToLumberjack('field', { button_type: 'Price Field' });
        sendToSegment('amount item', 'added');
      },
      chosenPriceField: () => {
        sendToSegment('amount item', 'chosen');
      },
      addInputField: () => {
        sendToLumberjack('field', { button_type: 'Input Field' });
        sendToSegment('input item', 'added');
      },
      chosenInputField: () => {
        sendToSegment('input item', 'chosen');
      },
      inputFieldName: () => {
        sendToLumberjack('edit_field');
        sendToSegment('field name', 'input');
      },
      selectTemplate: (template) => {
        sendToLumberjack('choose_template', { template });
        sendToSegment('template', 'selected', { templateName: template }, true);
      },
      titleEnterSuccess: (title) => {
        sendToLumberjack('title.success', { title });
        sendToSegment('title success', 'input', { title });
      },
      titleEnterError: (title, error) => {
        sendToLumberjack('title.fail', { title, error });
        sendToSegment('title fail', 'input', { title, error });
      },
      supportEmail: (text, is_prefill = false) => {
        sendToLumberjack('support.email', { text, is_prefill });
        sendToSegment('support email', 'input', { text, is_prefill });
      },
      supportPhone: (text, is_prefill = false) => {
        sendToLumberjack('support.phone', { text, is_prefill });
        sendToSegment('support phone', 'input', { text, is_prefill });
      },
      addImageSuccess: (sizeInMB, type, isOptimised) => {
        // sizeInMB is the initial file size without compression
        sendToLumberjack('description.image_upload.success', { sizeInMB, type, isOptimised });
        sendToSegment(' description image upload', 'success', { sizeInMB, type, isOptimised });
      },
      addImageFail: (error) => {
        sendToLumberjack('description.image_upload.fail', { error });
        sendToSegment('description image upload', 'fail', { error });
      },
      addVideoSuccess: () => {
        sendToLumberjack('description.video_upload.success');
        sendToSegment('description video upload', 'success');
      },
      addVideoFail: (video_url) => {
        sendToLumberjack('description.video_upload.fail', { video_url });
        sendToSegment('description video upload', 'fail', { video_url });
      },
      addGoalTrackerBtn: () => {
        sendToLumberjack('goal_tracker.add_button');
        sendToSegment('goal tracker', 'add button');
      },
      addGoalTrackerBtnType: (type) => {
        sendToLumberjack('goal_tracker.add_button_type', { type });
        sendToSegment('goal tracker', 'add button type', { type });
      },
      saveGoalTracker: (isExisting) => {
        sendToLumberjack('goal_tracker.save_button', { is_new: !isExisting ? '1' : '0' });
        sendToSegment('goal tracker', 'save button', { is_new: !isExisting ? '1' : '0' });
      },
      editGoalTrackerBtn: (isExisting) => {
        sendToLumberjack('goal_tracker.edit_button', { is_new: !isExisting ? '1' : '0' });
        sendToSegment('goal tracker', 'edit button', { is_new: !isExisting ? '1' : '0' });
      },
      deleteGoalTracker: (isExisting) => {
        sendToLumberjack('goal_tracker.delete_button', { is_new: !isExisting ? '1' : '0' });
        sendToSegment('goal tracker', 'delete button', { is_new: !isExisting ? '1' : '0' });
      },
      clickPageSettingsViaSRField: () => {
        sendToSegment('form section field', 'page settings'); // for shiprocket fields
      },
      clickShiprocketDocsLink: (via) => {
        sendToSegment('shiprocket docs', 'link', { via }); // for shiprocket fields
      },
      addSocialMediaIcons: () => {
        sendToLumberjack('add_social_icons');
        sendToSegment('add social icons', 'click');
      },
    },

    init(_lumberjackTrack, _config) {
      lumberjackTrack = _lumberjackTrack;

      setConfig(_config);
    },
  };
}

export default _track();
