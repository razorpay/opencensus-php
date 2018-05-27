import { Field } from 'redux-form';

import CheckBoxField from 'rzp/ui/Forms/CheckboxField';

/**
 * Batch Payment Links Form
 * - Send Email/Send SMS
 */

export default ({ batchType, sms_notify, email_notify, onChange }) => {
  const handleChange = propName => (_, value) => {
    onChange(propName, value);
  };

  return (
    <div>
      <h5 class="send-link-head">
        <strong>SEND PAYMENT LINKS</strong>
      </h5>
      <div class="form-group send-links-form">
        <div class="checkbox rzpCheckbox next m-r">
          <Field
            name="config.sms_notify"
            id="sms_notify"
            component={CheckBoxField}
            onChange={handleChange(onChange, 'sms_notify')}
          />
          <label for="sms_notify" class="icon i-check">
            Send SMS
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
            Send Email
          </label>
        </div>
      </div>
      <p>
        <i class="i i-info-circle m-r" />
        Payment Links with SMS and Email will be sent once the batch is created.
      </p>
    </div>
  );
};
