import { connect } from 'react-redux';
import { Field } from 'redux-form';
import { Link } from 'react-router-dom';

import { findBy } from 'common/utils/rzp-utils';

import CheckBoxField from 'common/ui/Forms/CheckboxField';

/**
 * Batch Payment Links Form
 * - Send Email/Send SMS
 * - Enable Reminders
 */

// TODO: Remove `paymentLinksRemindersSettings` setting to global level.
@connect(state => {
  const paymentLinksRemindersSettings =
    findBy(state.reminders.reminders.items, 'namespace', 'payment_link') || {};

  return {
    isRemindersEnabled: state.session.user.isRemindersEnabled,
    isPaymentLinkRemindersEnabled: paymentLinksRemindersSettings.active,
  };
})
export default class extends React.Component {
  handleChange = propName => (_, value) => {
    this.props.onChange(propName, value);
  };

  renderRemindersFormFields = () => {
    if (this.props.isPaymentLinkRemindersEnabled) {
      return (
        <div class="checkbox rzpCheckbox next">
          <Field
            name="config.reminder_enable"
            id="reminder_enable"
            component={CheckBoxField}
            onChange={this.handleChange('reminder_enable')}
          />
          <label for="reminder_enable">Send auto reminders</label>
        </div>
      );
    }

    return (
      <span>
        Reminders are not set for payment links. Set it up{' '}
        <Link target="_blank" to="/reminders">
          here
        </Link>
      </span>
    );
  };

  render() {
    return (
      <div>
        <div class="form-group send-links-form">
          <label class="m-r">Notify</label>

          <div class="checkbox rzpCheckbox m-r">
            <Field
              name="config.sms_notify"
              id="sms_notify"
              component={CheckBoxField}
              onChange={this.handleChange('sms_notify')}
            />
            <label for="sms_notify" class="icon i-check">
              via SMS
            </label>
          </div>

          <div class="checkbox rzpCheckbox">
            <Field
              name="config.email_notify"
              id="email_notify"
              component={CheckBoxField}
              onChange={this.handleChange('email_notify')}
            />
            <label for="email_notify" class="icon i-check">
              via Email
            </label>
          </div>
        </div>

        {this.props.isRemindersEnabled && (
          <div class="form-group send-links-form">
            <label class="m-r">Reminders</label>

            {this.renderRemindersFormFields()}
          </div>
        )}

        <p>
          <i class="i i-info-circle m-r" />
          Payment Links with SMS and Email will be sent once the batch is
          created.
        </p>
      </div>
    );
  }
}
