function _track() {
  let track;
  let config = {
    template: '',
    is_intent_duplicate: '',
    is_intent_edit: '',
    is_new: '',
    subscription_button_id: '',
  };

  function send(event, data) {
    track(
      window.rzpQ.subscriptionButtons().interaction(`button.create.${event}`, {
        data,
        config,
      }),
    );
  }

  return {
    // 1. Template
    trackTemplateHover(template) {
      send('template.hover', { template });
    },

    trackTemplateSelect(template) {
      this.setConfig({ template });

      send('template.select');
    },

    // 2. Button Type Change
    trackButtonTypeChange() {
      send('setup.type_change');
    },

    trackButtonTypeDiscardYes(template) {
      this.setConfig({ template });

      send('setup.type_discard_yes');
    },

    trackButtonTypeDiscardNo() {
      send('setup.type_discard_no');
    },

    trackButtonTheme(option) {
      send('setup.theme_button', { theme: option.value });
    },

    trackButtonScreenNextSuccess() {
      send('setup.next_button.success');
    },

    trackPlanFieldSaveSuccess() {
      send('input.save_plan_field_success');
    },

    trackPlanFormDeleteField() {
      send('plan.delete_field');
    },

    trackAddNewPlanField() {
      send('plan.add_new');
    },

    trackAmountFormDeleteField() {
      send('amount.delete_field');
    },

    trackOpenAdvanceOptions() {
      send('amount.advanced_options');
    },

    trackAmountFieldCancel() {
      send('amount.cancel');
    },

    trackAmountFieldDescription(status) {
      send('amount.description', {
        status: status ? 'Added' : 'Removed',
      });
    },

    trackAmountFieldSaveSuccess() {
      send('amount.save_success');
    },

    trackAmountScreenNextSuccess() {
      send('amount.next_success');
    },

    trackAmountScreenBackSuccess() {
      send('amount.back_success');
    },

    // 3. Customer Screen Events
    trackCustomerScreenInputField() {
      send('input.add');
    },

    trackCustomerScreenFieldType(option) {
      send('input.choose_type', { label: option.label });
    },

    trackCustomerScreenInputFieldMoreOptions() {
      send('input.more_options');
    },

    trackCustomerScreenToggleMakeOptional(isOptional) {
      send('input.optional', {
        isOptional,
      });
    },

    trackCustomerScreenDescriptionField(status) {
      send('input.description', {
        status: status ? 'Added' : 'Removed',
      });
    },

    trackCustomerScreenDeleteField() {
      send('input.delete_field');
    },

    trackCustomerScreenCancelFieldChanges() {
      send('input.cancel');
    },

    trackCustomerScreenFieldSaveSuccess() {
      send('input.save_success');
    },

    trackCustomerScreenNextSuccess() {
      send('input.next_success');
    },

    trackCustomerScreenBackSuccess() {
      send('input.back_success');
    },

    // Review Screen
    trackReviewScreenBackSuccess() {
      send('review.back_success');
    },

    trackCreateOrEditSuccess() {
      send('review.complete_button.success');
    },

    trackCreateOrEditFail(error) {
      send('review.complete_button.fail', error);
    },

    trackOpenDocs(button_id) {
      send('review.open_docs', {
        button_id,
      });
    },

    trackCodeCopy(button_id) {
      send('review.copy_code', {
        button_id,
      });
    },

    trackShowCode(button_id) {
      send('review.show_code', { button_id });
    },

    trackOnClickButtonSettings(button_id) {
      send('review.navigate_settings', { button_id });
    },

    // Pseudo event
    trackOnClickProgressBar() {
      send('button_progress_button');
    },

    trackOnClickProgressStep(type) {
      send('progress_button_step', type);
    },

    init(_track, config) {
      track = _track;

      this.setConfig(config);
    },

    setConfig(newConfig) {
      config = {
        ...config,
        ...newConfig,
      };
    },
  };
}

export default _track();
