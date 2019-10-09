import { connect } from 'react-redux';
import { withRouter, Prompt } from 'react-router-dom';

import { findBy, objectDiff, isBlank } from 'rzp/utils/rzp-utils';

import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import AdvancedSettings from './AdvancedSettings';
import EmailPreviewModal from './EmailPreviewModal';
import Footer from './Footer';
import Header from './Header';
import ReminderOptionSetting from './ReminderOptionSetting';

const initState = {
  withExpiry: [],
  withOutExpiry: [],
  advancedSettings: {
    scheduledTime: '10AM - 12PM',
    channels: {
      sms: false,
      email: true,
    },
  },
};

@withRouter
@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  {
    ...ModalActions,
    showNotification,
  }
)
export default class ReminderSetting extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super();

    this.changeRoute = false;
    this.typeInLowerCase = String(props.type).toLowerCase();

    this.state = {
      // TODO: Update when API is ready according API response.
      checked: true,
      __stashed_settings__: {
        ...initState,
      },
      settings: {
        ...initState,
      },
      withExpireByConfigs: props.withExpireByConfigs,
      withOutExpireByConfigs: props.withOutExpireByConfigs,
    };

    if (props.withExpiry) {
      this.state.__stashed_settings__.withExpiry = props.withExpiry;
      this.state.settings.withExpiry = props.withExpiry;
    }

    if (props.withOutExpiry) {
      this.state.__stashed_settings__.withOutExpiry = props.withOutExpiry;
      this.state.settings.withOutExpiry = props.withOutExpiry;
    }
  }

  disableReminderSetting = () => {
    return this.props
      .disableReminderSetting()
      .then(() => {
        this.setState({
          checked: !this.state.checked,
        });

        this.props.showNotification({
          type: 'success',
          message: `Reminders disabled for ${this.typeInLowerCase}`,
        });
      })
      .catch(({ errors }) => {
        this.setState({
          checked: !this.state.checked,
        });

        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  handleToggle = () => {
    if (!this.state.checked) {
      return this.disableReminderSetting();
    }

    this.context.confirm({
      header: `Disable reminders for all ${this.typeInLowerCase} ?`,
      message: `There are ${this.props.totalUnpaidLinks} existing unpaid ${
        this.typeInLowerCase
      } that have reminders scheduled.`,
      affirmativeLabel: 'Yes, disable',
      affirmativePendingLabel: 'Disabling...',
      abortLabel: 'No, don’t!',
      action: this.disableReminderSetting,
    });
  };

  onSaveClick = () => {
    return this.props
      .onSaveClick({
        ...this.state.settings,
      })
      .then(resp => {
        this.setState({
          __stashed_settings__: {
            ...this.state.settings,
          },
        });
      });
  };

  onChange = type => (list, name) => {
    const listType =
      name === 'with_expiry' ? 'withExpireByConfigs' : 'withOutExpireByConfigs';

    this.setState({
      settings: {
        ...this.state.settings,
        [type]: list,
      },
      [listType]: this.state[listType].map(reminder => {
        reminder.disabled = !!findBy(list, 'value', reminder.value);

        return reminder;
      }),
    });
  };

  handleChannelChange = type => e => {
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

  showReminderEMailPreview = () => {
    this.props.openModal({
      size: 'large',
      className: 'reminders-email-preview-modal',
      component: (
        <EmailPreviewModal
          onClose={this.props.closeModal}
          subject={this.props.emailDetails.subject}
          businessName={this.props.user.business_name}
          contentList={this.props.emailDetails.contentList}
        />
      ),
    });
  };

  isChanged = () => {
    return !isBlank(
      objectDiff(this.state.__stashed_settings__, this.state.settings)
    );
  };

  handleRouteChange = location => {
    this.context.confirm({
      header: 'Discard unsaved changes?',
      message:
        'You have made changes to the reminder schedule.  All changes will be lost.',
      affirmativeLabel: 'Discard',
      abortLabel: 'Cancel',
      action: () => {
        this.changeRoute = true;

        this.props.history.push(location.pathname);
      },
    });

    return this.changeRoute;
  };

  render() {
    const {
        settings,
        checked,
        withExpireByConfigs,
        withOutExpireByConfigs,
      } = this.state,
      { type } = this.props;

    return (
      <div class={`setting-item ${checked ? 'enabled' : 'disabled'}`}>
        <div class="panel panel-default">
          <div class="panel-section--theme">
            <div class="panel-heading">
              <Header
                type={type}
                checked={checked}
                onToggle={this.handleToggle}
              />
            </div>

            {checked && (
              <div class="panel-body">
                <Prompt
                  when={this.isChanged()}
                  message={this.handleRouteChange}
                />

                <ReminderOptionSetting
                  isExpiry
                  name="with_expiry"
                  remindersList={withExpireByConfigs}
                  onChange={this.onChange('withExpiry')}
                  selectedReminders={settings.withExpiry}
                />

                <ReminderOptionSetting
                  name="with_out_expiry"
                  onChange={this.onChange('withOutExpiry')}
                  selectedReminders={settings.withOutExpiry}
                  remindersList={withOutExpireByConfigs}
                />

                <AdvancedSettings
                  {...settings.advancedSettings}
                  onChannelChange={this.handleChannelChange}
                />

                <Footer
                  isSaveBtnDisable={!this.isChanged()}
                  onSaveClick={this.onSaveClick}
                  onPreviewClick={this.showReminderEMailPreview}
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
