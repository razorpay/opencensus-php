import { Field } from 'redux-form';

import CheckBoxField from 'common/ui/Forms/CheckboxField';

export default function RegistrationLinkBatchCreationForm() {
  return (
    <div>
      <h5 className="send-link-head">
        <strong>SEND REGISTRATION LINKS</strong>
      </h5>
      <div className="form-group send-links-form">
        <div className="checkbox rzpCheckbox next m-r">
          <Field name="config.sms_notify" id="sms_notify" component={CheckBoxField} />
          <label htmlFor="sms_notify" className="icon i-check">
            Send SMS
          </label>
        </div>
        <div className="checkbox rzpCheckbox next m-r">
          <Field name="config.email_notify" id="email_notify" component={CheckBoxField} />
          <label htmlFor="email_notify" className="icon i-check">
            Send Email
          </label>
        </div>
      </div>
      <p>
        <i className="i i-info-circle m-r" /> Registration Links with SMS and Email will be sent once
        the batch is created.
      </p>
    </div>
  );
}
