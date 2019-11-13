import { connect } from 'react-redux';

import { findBy, filterBy } from 'common/utils/rzp-utils';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import {
  fetchReminders,
  editRemindersMerchantConfigs,
  disableEnableReminders,
  createReminders,
} from 'merchant/reducers/reminders';

import { fetchInvoiceCount } from 'merchant/reducers/invoices/details';
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
      ele.channels.forEach(ele => channels.add(ele));

      if (ele.reminder_config.config_template.attr_key === 'expire_by') {
        withExpireByMerchantConfigs.push(serializeMerchantConfig(ele));

        return;
      }

      withOutExpireByMerchantConfigs.push(serializeMerchantConfig(ele));
    });

    return {
      paymentLinkReminder:
        findBy(state.reminders.reminders.items, 'namespace', 'payment_link') ||
        {},
      withExpireByConfigs,
      withOutExpireByConfigs,
      withExpireByMerchantConfigs,
      withOutExpireByMerchantConfigs,
      channels: Array.from(channels),
      user: state.session.user,
    };
  },
  {
    fetchReminders,
    editRemindersMerchantConfigs,
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

  disableEnableReminders = active => {
    if (!this.props.paymentLinkReminder.id) {
      return createReminders('payment_link').then(this.props.fetchReminders);
    }

    return disableEnableReminders(this.props.paymentLinkReminder.id, {
      active,
    }).then(this.props.fetchReminders);
  };

  render() {
    return (
      <div
        class="RemindersSettings--PaymentLinks"
        key="RemindersSettings--PaymentLinks"
      >
        <Setting
          type="Payment Links"
          isEnabled={this.props.paymentLinkReminder.active}
          channels={this.props.channels}
          onSaveClick={this.saveSettings}
          maxReminderCount={this.props.paymentLinkReminder.max_reminder_count}
          disableEnableReminders={this.disableEnableReminders}
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
