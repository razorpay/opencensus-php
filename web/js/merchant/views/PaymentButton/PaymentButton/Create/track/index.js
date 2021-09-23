import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  let lumberjackTrack = () => {};
  let config = {
    template: '',
    is_intent_duplicate: '',
    is_intent_edit: '',
    is_new: '',
    payment_button_id: '',
  };

  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentPages().interaction(`button.create.${event}`, {
        data,
        config,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'button create',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    createOrEditSuccess(payment_button_id) {
      this.setConfig({ payment_button_id });

      sendToLumberjack('review.complete_button.success');
      sendToSegment('create button', 'success');
    },
    createOrEditFail: (error) => {
      sendToLumberjack('review.complete_button.fail', error);
      sendToSegment('create button', 'fail', { error });
    },
    templateSelect(template) {
      this.setConfig({ template });

      sendToLumberjack('template.select', { template });
      sendToSegment('template select', 'click', { template });
    },
    onClickButtonSettings: () => {
      sendToLumberjack('review.navigate_settings');
      sendToSegment('button settings', 'click');
    },
    templateHover: (template) => {
      sendToLumberjack('template.hover', { template });
      sendToSegment('template', 'hover', { template });
    },
    buttonTypeChange: () => {
      sendToLumberjack('setup.type_change');
      sendToSegment('button type change', 'click');
    },
    buttonTypeDiscardYes(template) {
      this.setConfig({ template });

      sendToLumberjack('setup.type_discard_yes');
      sendToSegment('button type discard yes', 'click');
    },
    buttonTypeDiscardNo: () => {
      sendToLumberjack('setup.type_discard_no');
      sendToSegment('button type discard no', 'click');
    },
    buttonLabel: (event) => {
      const value = event?.target?.value;

      sendToLumberjack('setup.text_button', { value });
      sendToSegment('text in button', 'input', { value });
    },
    buttonAmount: (options) => {
      sendToLumberjack('setup.amount_button', options);
      sendToSegment('amount', 'input', { options });
    },
    buttonTheme: (theme) => {
      sendToLumberjack('setup.amount_button', { theme });
      sendToSegment('theme', 'click', { theme });
    },
    buttonScreenNextSuccess: () => {
      sendToLumberjack('setup.next_button.success');
      sendToSegment('next button', 'click');
    },

    // 2. amount details
    onClickAmountField: () => {
      sendToLumberjack('amount.item_add');
      sendToSegment('amount add', 'click');
    },
    changeCurrency: () => {
      sendToLumberjack('amount.change_currency');
      sendToSegment('amount change currency', 'click');
    },
    amountScreenOpenMoreOptions: () => {
      sendToLumberjack('amount.more_options');
      sendToSegment('amount more options', 'click');
    },
    toggleMakeMandatory: (isOptional) => {
      sendToLumberjack('amount.optional', { isOptional });
      sendToSegment('amount optional', 'toggle');
    },
    amountFormDeleteField: () => {
      sendToLumberjack('amount.delete_field');
      sendToSegment('amount delete field', 'click');
    },
    openAdvanceOptions: () => {
      sendToLumberjack('amount.advanced_options');
      sendToSegment('amount advanced options', 'click');
    },
    amountFieldCancel: () => {
      sendToLumberjack('amount.cancel');
      sendToSegment('amount cancel', 'click');
    },
    amountFieldDescription: (_status) => {
      const status = _status ? 'Added' : 'Removed';

      sendToLumberjack('amount.description', { status });
      sendToSegment('amount description', 'click', { status });
    },
    amountFieldSaveSuccess: () => {
      sendToLumberjack('amount.save_success');
      sendToSegment('amount save', 'success');
    },
    amountScreenNextSuccess: () => {
      sendToLumberjack('amount.next_success');
      sendToSegment('amount next button', 'click');
    },
    amountScreenBackSuccess: () => {
      sendToLumberjack('amount.back_success');
      sendToSegment('amount back button', 'click');
    },

    // 3. customer details
    customerScreenInputField: () => {
      sendToLumberjack('input.add');
      sendToSegment('input add', 'click');
    },
    customerScreenFieldType: (option) => {
      const label = option.label;

      sendToLumberjack('input.choose_type', { label });
      sendToSegment('input choose type', 'click', { label });
    },
    customerScreenInputFieldMoreOptions: () => {
      sendToLumberjack('input.more_options');
      sendToSegment('input more options', 'click');
    },
    customerScreenToggleMakeOptional: (isOptional) => {
      sendToLumberjack('input.optional', { isOptional });
      sendToSegment('input optional', 'toggle', { isOptional });
    },
    customerScreenDescriptionField: (_status) => {
      const status = _status ? 'Added' : 'Removed';

      sendToLumberjack('input.description', { status });
      sendToSegment('input description', 'input', { status });
    },
    customerScreenDeleteField: () => {
      sendToLumberjack('input.delete_field');
      sendToSegment('input delete field', 'click');
    },
    customerScreenCancelFieldChanges: () => {
      sendToLumberjack('input.cancel');
      sendToSegment('input cancel', 'click');
    },
    customerScreenFieldSaveSuccess: () => {
      sendToLumberjack('input.save_success');
      sendToSegment('input save', 'click');
    },
    customerScreenNextSuccess: () => {
      sendToLumberjack('input.next_success');
      sendToSegment('input next button', 'click');
    },
    customerScreenBackSuccess: () => {
      sendToLumberjack('input.back_success');
      sendToSegment('input back button', 'click');
    },

    // 4. review screen
    reviewScreenBackSuccess: () => {
      sendToLumberjack('review.back_success');
      sendToSegment('review back button', 'click');
    },

    // pseudo events
    onClickProgressBar: () => {
      sendToLumberjack('button_progress_button');
      sendToSegment('button progress button', 'click');
    },
    onClickProgressStep: (type) => {
      sendToLumberjack('progress_button_step', type);
      sendToSegment('progress button step', 'click', { type });
    },

    init(_lumberjackTrack, _config) {
      lumberjackTrack = _lumberjackTrack;

      this.setConfig(_config);
    },
    setConfig: (newConfig) => {
      config = {
        ...config,
        ...newConfig,
      };
    },
  };
}

export default _track();
