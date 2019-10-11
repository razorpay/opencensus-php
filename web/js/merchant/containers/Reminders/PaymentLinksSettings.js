import { connect } from 'react-redux';

import { findBy, filterBy } from 'rzp/utils/rzp-utils';
import * as NotificationActions from 'rzp/modules/notifications';

import Spinner from 'rzp/ui/Spinner';

import {
  editRemindersMerchantConfigs,
  disableReminders,
} from 'merchant/modules/reminders';

import { fetchInvoiceCount } from 'merchant/modules/invoices/details';
import Setting from 'merchant/components/Reminders/Settings';

@connect(
  state => {
    const configs = filterBy(
      state.reminders.configs.items,
      'namespace',
      'payment_link'
    );

    const merchantConfig = state.reminders.merchant_config.items.filter(
      config => config.reminder_config.namespace === 'payment_link'
    );

    const withExpireByConfigs = [],
      withOutExpireByConfigs = [],
      withExpireByMerchantConfigs = [],
      withOutExpireByMerchantConfigs = [],
      channels = new Set([]);

    configs.forEach(ele => {
      if (ele.config_template.attr_key === 'expire_by') {
        withExpireByConfigs.push(serializeConfig(ele));

        return;
      }

      withOutExpireByConfigs.push(serializeConfig(ele));
    });

    merchantConfig.forEach(ele => {
      if (ele.reminder_config.config_template.attr_key === 'expire_by') {
        withExpireByMerchantConfigs.push(serializeMerchantConfig(ele));

        return;
      }

      ele.channels.forEach(ele => channels.add(ele));

      withOutExpireByMerchantConfigs.push(serializeMerchantConfig(ele));
    });

    return {
      paymentLinkReminder: findBy(
        state.reminders.reminders.items,
        'namespace',
        'payment_link'
      ),
      withExpireByConfigs,
      withOutExpireByConfigs,
      withExpireByMerchantConfigs,
      withOutExpireByMerchantConfigs,
      channels: Array.from(channels),
      user: state.session.user,
    };
  },
  {
    editRemindersMerchantConfigs,
    disableReminders,
    ...NotificationActions,
  }
)
export default class PaymentLinksSettings extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      totalUnpaidLinks: {
        loading: true,
        count: 0,
      },
    };
  }

  componentDidMount() {
    fetchInvoiceCount({
      type: 'link',
      status: 'issued',
    }).then(resp => {
      this.setState({
        totalUnpaidLinks: {
          loading: false,
          count: resp.data.count,
        },
      });
    });
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
            merchant_id: this.props.user.current,
          };
        }
      ),
    };

    return this.props
      .editRemindersMerchantConfigs(this.props.paymentLinkReminder.id, data)
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
    if (this.state.totalUnpaidLinks.loading) {
      return (
        <div
          class="page-spinner-container"
          key="RemindersSettings--PaymentLinks"
        >
          <Spinner />
        </div>
      );
    }

    return (
      <div
        class="Reminders-settings--payment_links"
        key="RemindersSettings--PaymentLinks"
      >
        <Setting
          type="Payment Links"
          emailDetails={emailDetails}
          channels={this.props.channels}
          onSaveClick={this.saveSettings}
          disableReminderSetting={this.disableReminderSetting}
          totalUnpaidLinks={this.state.totalUnpaidLinks}
          withExpiry={this.props.withExpireByMerchantConfigs}
          withOutExpiry={this.props.withOutExpireByMerchantConfigs}
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

function serializeMerchantConfig(item) {
  return serializeConfig(item.reminder_config);
}
