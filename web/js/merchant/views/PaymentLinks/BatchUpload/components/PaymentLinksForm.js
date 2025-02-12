import React from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Field } from 'redux-form';

import CheckBoxField from 'common/ui/Forms/CheckboxField';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { findBy } from 'common/utils/rzp-utils';
import { fetchReminders } from 'merchant/reducers/reminders';
import track from 'merchant/views/PaymentLinks/BatchUpload/track';
import { BATCH_TYPE, NOTIFY_MESSAGE } from 'merchant/views/PaymentPages/PaymentPages/constants';

/**
 * Batch Payment Links Form
 * - Send Email/Send SMS
 * - Enable Reminders
 */

// TODO: Remove `paymentLinksRemindersSettings` setting to global level.
class PaymentLinksForm extends React.Component {
  componentDidMount() {
    this.props.fetchReminders();
  }

  handleChange = (propName) => (_, value) => {
    this.props.onChange(propName, value);
  };

  renderRemindersFormFields = () => {
    if (this.props.reminders.loading) {
      return <PlaceholderLoader />;
    }

    const type = this.props.user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link';
    const paymentLinksRemindersSettings =
      findBy(this.props.reminders.items, 'namespace', type) || {};

    if (paymentLinksRemindersSettings.active) {
      return (
        <div className="checkbox rzpCheckbox next">
          <Field
            name="config.reminder_enable"
            id="reminder_enable"
            component={CheckBoxField}
            onChange={this.handleChange('reminder_enable')}
          />
          <label htmlFor="reminder_enable">Send auto reminders</label>
        </div>
      );
    }

    return (
      <span>
        Reminders are not set for payment links. Set it up{' '}
        <Link target="_blank" to="/reminders" rel="noreferrer noopener">
          here
        </Link>
      </span>
    );
  };

  render() {
    const { batchType } = this.props;
    const { BATCH_PAYMENT_PAGE, PAYMENT_LINK } = NOTIFY_MESSAGE;
    return (
      <div>
        <div className="form-group send-links-form">
          <label className="m-r">Notify</label>

          <div className="checkbox rzpCheckbox m-r">
            <Field
              name="config.sms_notify"
              id="sms_notify"
              component={CheckBoxField}
              onChange={() => {
                this.handleChange('sms_notify');
                track.onSmSNotify && track.onSmSNotify();
              }}
            />
            <label htmlFor="sms_notify" className="icon i-check">
              via SMS
            </label>
          </div>

          <div className="checkbox rzpCheckbox">
            <Field
              name="config.email_notify"
              id="email_notify"
              component={CheckBoxField}
              onChange={() => {
                this.handleChange('email_notify');
                track.onEmailNotify && track.onEmailNotify();
              }}
            />
            <label htmlFor="email_notify" className="icon i-check">
              via Email
            </label>
          </div>
        </div>

        {batchType !== BATCH_TYPE && (
          <div className="form-group send-links-form">
            <label className="m-r">Reminders</label>

            {this.renderRemindersFormFields()}
          </div>
        )}

        <p className="m-t">
          <i className="i i-info-circle m-r" />
          {batchType === BATCH_TYPE ? BATCH_PAYMENT_PAGE : PAYMENT_LINK}
        </p>
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      user: state.session.user,
      reminders: state.reminders.reminders,
    };
  },
  {
    fetchReminders,
  },
)(PaymentLinksForm);
