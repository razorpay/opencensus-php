import { Field } from 'redux-form';

import CheckBoxField from 'common/ui/Forms/CheckboxField';

export default () => (
  <div>
    <h5 class="send-link-head">
      <strong>SEND REGISTRATION LINKS</strong>
    </h5>
    <div class="form-group send-links-form">
      <div class="checkbox rzpCheckbox next m-r">
        <Field name="config.sms_notify" id="sms_notify" component={CheckBoxField} />
        <label for="sms_notify" class="icon i-check">
          Send SMS
        </label>
      </div>
      <div class="checkbox rzpCheckbox next m-r">
        <Field name="config.email_notify" id="email_notify" component={CheckBoxField} />
        <label for="email_notify" class="icon i-check">
          Send Email
        </label>
      </div>
    </div>
    <p>
      <i class="i i-info-circle m-r" /> Registration Links with SMS and Email will be sent once the
      batch is created.
    </p>
  </div>
);
