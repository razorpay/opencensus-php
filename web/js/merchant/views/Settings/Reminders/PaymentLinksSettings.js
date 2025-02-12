import React from 'react';
import { connect } from 'react-redux';

import { findBy, filterBy } from 'common/utils/rzp-utils';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import {
  fetchReminders,
  editRemindersMerchantConfigs,
  disableEnableReminders,
  createReminders,
} from 'merchant/reducers/reminders';

import { fetchPLCount } from 'merchant/reducers/paymentlinks/details';
import ReminderSettings from 'merchant/views/Settings/Reminders/components/Settings';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

class PaymentLinksSettings extends React.Component {
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
    fetchPLCount({
      type: 'link',
      status: 'issued',
    }).then((resp) => {
      this.setState({
        totalUnpaidLinks: {
          loading: false,
          count: resp.data.count,
        },
      });
    });
  }

  saveSettings = (props) => {
    const channels = [];
    if (props.advancedSettings.channels.email) {
      channels.push('email');
    }

    if (props.advancedSettings.channels.sms) {
      channels.push('sms');
    }

    const data = {
      enabled_configs: [...props.withExpiry, ...props.withOutExpiry].map((config) => {
        return {
          channels,
          config_id: config.value,
          status: 'enabled',
          merchant_id: this.props.user.current,
        };
      }),
    };

    return this.props
      .editRemindersMerchantConfigs(this.props.paymentLinkReminder.id, data)
      .then(() => {
        selfServeTrackSuccess({
          selfServeAction: 'PL Reminder Created',
          page: 'Reminders',
          screen: 'Settings',
        });
        this.props.showNotification({
          type: 'success',
          message: 'Reminders are updated successfully',
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  disableEnableReminders = (active) => {
    if (!this.props.paymentLinkReminder.id) {
      const namespace = this.props.user.isPaymentlinksV2Enabled
        ? 'payment_link_v2'
        : 'payment_link';

      return createReminders(namespace).then(this.props.fetchReminders);
    }

    return disableEnableReminders(this.props.paymentLinkReminder.id, {
      active,
    }).then(this.props.fetchReminders);
  };

  render() {
    return (
      <div className="RemindersSettings--PaymentLinks" key="RemindersSettings--PaymentLinks">
        <ReminderSettings
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

function serializeConfig(item, disabled = false) {
  return {
    label: item.title,
    value: item.id,
    disabled,
  };
}

export default connect(
  (state) => {
    const namespace = state.session.user.isPaymentlinksV2Enabled
      ? 'payment_link_v2'
      : 'payment_link';

    const configs = filterBy(state.reminders.configs.items, 'namespace', namespace);

    const merchantConfig = state.reminders.merchant_config.items.filter(
      (config) => config.reminder_config.namespace === namespace,
    );

    let withExpireByConfigs = [];
    let withOutExpireByConfigs = [];
    const withExpireByMerchantConfigs = [];
    const withOutExpireByMerchantConfigs = [];
    const channels = new Set([]);

    configs.forEach((ele) => {
      if (ele.config_template.attr_key === 'expire_by') {
        withExpireByConfigs.push(serializeConfig(ele));

        return;
      }

      withOutExpireByConfigs.push(serializeConfig(ele));
    });
    merchantConfig.forEach((ele) => {
      ele.channels.forEach((item) => channels.add(item));

      const reminderOption = serializeConfig(ele.reminder_config, true);

      if (ele.reminder_config.config_template.attr_key === 'expire_by') {
        withExpireByMerchantConfigs.push(reminderOption);

        withExpireByConfigs = withExpireByConfigs.map((item) => {
          if (item.value === reminderOption.value) return reminderOption;

          return item;
        });
        return;
      }

      withOutExpireByMerchantConfigs.push(reminderOption);

      withOutExpireByConfigs = withOutExpireByConfigs.map((item) => {
        if (item.value === reminderOption.value) return reminderOption;

        return item;
      });
    });

    return {
      paymentLinkReminder: findBy(state.reminders.reminders.items, 'namespace', namespace) || {},
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
  },
)(PaymentLinksSettings);
