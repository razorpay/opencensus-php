import { Field } from 'redux-form';

import CheckBoxField from 'common/ui/Forms/CheckboxField';

/**
 * Batch Payment Links Form
 * - Send Email/Send SMS
 * - Enable Reminders
 */

export default ({ batchType, sms_notify, email_notify, onChange }) => {
  const handleChange = propName => (_, value) => {
    onChange(propName, value);
  };

  return (
    <div>
      <div class="form-group send-links-form">
        <label class="m-r">Notify</label>
        <div class="checkbox rzpCheckbox next m-r">
          <Field
            name="config.sms_notify"
            id="sms_notify"
            component={CheckBoxField}
            onChange={handleChange('sms_notify')}
          />
          <label for="sms_notify" class="icon i-check">
            via SMS
          </label>
        </div>
        <div class="checkbox rzpCheckbox next m-r">
          <Field
            name="config.email_notify"
            id="email_notify"
            component={CheckBoxField}
            onChange={handleChange('email_notify')}
          />
          <label for="email_notify" class="icon i-check">
            via Email
          </label>
        </div>
      </div>
      <div class="form-group send-links-form">
        <label class="m-r">Reminders</label>
        <div class="checkbox rzpCheckbox next m-r">
          <Field
            name="config.reminder_enable"
            id="reminder_enable"
            component={CheckBoxField}
            onChange={handleChange('reminder_enable')}
          />
          <label for="reminder_enable">Send auto reminders</label>
        </div>
      </div>
      <p>
        <i class="i i-info-circle m-r" />
        Payment Links with SMS and Email will be sent once the batch is created.
      </p>
    </div>
  );
};
