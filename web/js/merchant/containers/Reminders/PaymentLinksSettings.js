import { connect } from 'react-redux';

import { findBy, filterBy } from 'rzp/utils/rzp-utils';
import * as NotificationActions from 'rzp/modules/notifications';

import { editReminders, disableReminders } from 'merchant/modules/reminders';
import Setting from 'merchant/components/Reminders/Settings';

@connect(
  state => {
    const configs = filterBy(
      state.reminders.configs.items,
      'namespace',
      'payment_link'
    );

    const withExpireByConfigs = [],
      withOutExpireByConfigs = [];

    configs.forEach(ele => {
      if (ele.config_template.attr_key === 'expire_by') {
        withExpireByConfigs.push(serializeConfig(ele));

        return;
      }

      withOutExpireByConfigs.push(serializeConfig(ele));
    });

    return {
      paymentLinkReminder: findBy(
        state.reminders.reminders.items,
        'namespace',
        'payment_link'
      ),
      withExpireByConfigs,
      withOutExpireByConfigs,
    };
  },
  {
    editReminders,
    disableReminders,
    ...NotificationActions,
  }
)
export default class PaymentLinksSettings extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      totalUnpaidLinks: 12, // TODO: Update when API is ready according API response.
    };
  }

  saveSettings = props => {
    const channels = [];
    if (props.advancedSettings.channels.email) {
      channels.push('email');
    }

    if (props.advancedSettings.channels.sms) {
      channels.push('sms');
    }

    const data = {
      enabled_configs: [...props.withExpiry, ...props.withOutExpiry].map(
        config => {
          return {
            channels,
            config_id: config.value,
            status: 'enabled',
          };
        }
      ),
    };

    return this.props
      .editReminders(this.props.paymentLinkReminder.id, data)
      .then(resp => {
        this.props.showNotification({
          type: 'success',
          message: 'Reminders are updated successfully',
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  disableReminderSetting = () => {
    return this.props.disableReminders(this.props.paymentLinkReminder.id, {
      active: false,
    });
  };

  render() {
    return (
      <div class="Reminders-settings--payment_links">
        <Setting
          type="Payment Links"
          emailDetails={emailDetails}
          onSaveClick={this.saveSettings}
          disableReminderSetting={this.disableReminderSetting}
          totalUnpaidLinks={this.state.totalUnpaidLinks}
          withExpireByConfigs={this.props.withExpireByConfigs}
          withOutExpireByConfigs={this.props.withOutExpireByConfigs}
        />
      </div>
    );
  }
}

const emailDetails = {
  // TODO: Update content
  subject: '"We’ve not received your payment"',
  contentList: [
    'We have not received your payment. It will expire on 21st of April.',
    'You can start accepting payments with Razorpay within 2 minutes. Complete the signup and verify your PAN card details online to start transacting today.',
    <React.Fragment>
      Payment Link:{' '}
      <a href="www.razorpay.com/payment-link/4oejw68wbb9/">
        www.razorpay.com/payment-link/4oejw68wbb9/
      </a>
    </React.Fragment>,
    "Use our products like Payment Links or Invoices without any integration effort. Using these products you can collect payments using SMS, EMail, What'sapp, Chatbots, Messenger etc.",
  ],
};

function serializeConfig(item) {
  return {
    label: item.title,
    value: item.id,
    disabled: false,
  };
}
