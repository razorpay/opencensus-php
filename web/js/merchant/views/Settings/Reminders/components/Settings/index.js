import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { withRouter, Prompt } from 'react-router-dom';
import PropTypes from 'prop-types';

import { findBy, objectDiff, isBlank } from 'common/utils/rzp-utils';

import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Footer from './Footer';
import Header from './Header';
import AdvancedSettings from './AdvancedSettings';
import ReminderOptionSetting from './ReminderOptionSetting';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const initState = {
  withExpiry: [],
  withOutExpiry: [],
  advancedSettings: {
    scheduledTime: '11AM - 12PM and 3PM - 5PM',
    channels: {
      sms: false,
      email: false,
    },
  },
};

class ReminderSettings extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);

    this.typeInLowerCase = String(props.type).toLowerCase();

    this.state = {
      isEnabled: props.isEnabled,
      __stashed_settings__: {
        ...initState,
        withExpiry: props.withExpiry,
        withOutExpiry: props.withOutExpiry,
        advancedSettings: {
          scheduledTime: '11AM - 12PM and 3PM - 5PM',
          channels: {
            sms: props.channels.includes('sms'),
            email: props.channels.includes('email'),
          },
        },
      },
      settings: {
        ...initState,
        withExpiry: props.withExpiry,
        withOutExpiry: props.withOutExpiry,
        advancedSettings: {
          scheduledTime: '11AM - 12PM and 3PM - 5PM',
          channels: {
            sms: props.channels.includes('sms'),
            email: props.channels.includes('email'),
          },
        },
      },
      withExpireByConfigs: props.withExpireByConfigs,
      withOutExpireByConfigs: props.withOutExpireByConfigs,
    };
  }

  disableReminderSetting = () => {
    return this.props
      .disableEnableReminders(!this.state.isEnabled)
      .then(() => {
        this.setState((prevState) => {
          return {
            isEnabled: !prevState.isEnabled,
          };
        });

        this.props.showNotification({
          type: 'success',
          message: `Reminders ${this.state.isEnabled ? 'disabled' : 'enabled'} for ${
            this.typeInLowerCase
          }`,
        });
      })
      .catch(({ errors }) => {
        this.setState((prevState) => {
          return {
            isEnabled: !prevState.isEnabled,
          };
        });

        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  handleToggle = () => {
    if (!this.state.isEnabled) {
      return this.disableReminderSetting();
    }

    return this.context
      .confirm({
        header: `Disable reminders for all ${this.typeInLowerCase} ?`,
        message: `There are ${this.props.totalUnpaidLinks.count} existing unpaid ${this.typeInLowerCase} that have reminders scheduled.`,
        affirmativeLabel: 'Yes, disable',
        affirmativePendingLabel: 'Disabling...',
        abortLabel: 'No, don’t!',
        action: this.disableReminderSetting,
      })
      .catch();
  };

  onSaveClick = () => {
    return this.props
      .onSaveClick({
        ...this.state.settings,
      })
      .then(() => {
        this.setState((prevState) => {
          return {
            __stashed_settings__: {
              ...prevState.settings,
            },
          };
        });
      });
  };

  getUpdatedReminderOptionsList = (listType, newList) => {
    return this.state[listType].map((reminder) => {
      const disabled = !!findBy(newList, 'value', reminder.value);

      return {
        ...reminder,
        disabled,
      };
    });
  };

  onChange = (type) => (list) => {
    const listType = type === 'withExpiry' ? 'withExpireByConfigs' : 'withOutExpireByConfigs';

    const newList = list.map((ele) => ({ ...ele, disabled: true }));

    this.setState((prevState) => {
      return {
        settings: {
          ...prevState.settings,
          [type]: newList,
        },
        [listType]: this.getUpdatedReminderOptionsList(listType, newList),
      };
    });
  };

  handleChannelChange = (type) => (e) => {
    selfServeTrackInitiate({
      selfServeAction: 'PL Reminder Created',
      page: 'Reminders',
      screen: 'Settings',
    });
    const { settings } = this.state;
    this.setState({
      settings: {
        ...settings,
        advancedSettings: {
          ...settings.advancedSettings,
          channels: {
            ...settings.advancedSettings.channels,
            [type]: e.target.checked,
          },
        },
      },
    });
  };

  isChanged = () => {
    return !isBlank(objectDiff(this.state.__stashed_settings__, this.state.settings));
  };

  isValid = () => {
    const { settings } = this.state;

    return (
      (settings.withExpiry.length || settings.withOutExpiry.length) &&
      (settings.advancedSettings.channels.email || settings.advancedSettings.channels.sms)
    );
  };

  handleRouteChange = (location) => {
    this.context.confirm({
      header: 'Discard unsaved changes?',
      message: 'You have made changes to the reminder schedule.  All changes will be lost.',
      affirmativeLabel: 'Discard',
      abortLabel: 'Cancel',
      action: () => {
        this.setState(
          (prevState) => {
            return {
              settings: {
                ...prevState.__stashed_settings__,
              },
            };
          },
          () => {
            this.props.history.push(location.pathname);
          },
        );
      },
    });

    return false;
  };

  render() {
    const { settings, isEnabled, withExpireByConfigs, withOutExpireByConfigs } = this.state;
    const { type, totalUnpaidLinks, maxReminderCount } = this.props;

    return (
      <div class={`setting-item ${isEnabled ? 'enabled' : 'disabled'}`}>
        <div class="panel panel-default">
          <div class="panel-section--theme">
            <div class="panel-heading">
              <Header
                type={type}
                isEnabled={isEnabled}
                disabled={totalUnpaidLinks.loading}
                onToggle={this.handleToggle}
              />
            </div>

            {isEnabled && (
              <div class="panel-body">
                <Prompt when={this.isChanged()} message={this.handleRouteChange} />

                <ReminderOptionSetting
                  isExpiry
                  name="with_expiry"
                  maxReminderCount={maxReminderCount}
                  remindersList={withExpireByConfigs}
                  onChange={this.onChange('withExpiry')}
                  selectedReminders={settings.withExpiry}
                />

                <ReminderOptionSetting
                  name="with_out_expiry"
                  onChange={this.onChange('withOutExpiry')}
                  maxReminderCount={maxReminderCount}
                  selectedReminders={settings.withOutExpiry}
                  remindersList={withOutExpireByConfigs}
                />

                <AdvancedSettings
                  {...settings.advancedSettings}
                  onChannelChange={this.handleChannelChange}
                />

                <Footer
                  isSaveBtnDisable={!(this.isChanged() && this.isValid())}
                  onSaveClick={this.onSaveClick}
                  scheduledTime={settings.advancedSettings.scheduledTime}
                />
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    {
      ...ModalActions,
      showNotification,
    },
  ),
)(ReminderSettings);
