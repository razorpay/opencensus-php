import { connect } from 'react-redux';
import { withRouter, Prompt } from 'react-router-dom';

import { findBy, objectDiff, isBlank } from 'rzp/utils/rzp-utils';

import * as ModalActions from 'rzp/modules/modals';

import AdvancedSettings from './AdvancedSettings';
import EmailPreviewModal from './EmailPreviewModal';
import Footer from './Footer';
import Header from './Header';
import ReminderOptionSetting from './ReminderOptionSetting';

@withRouter
@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  {
    ...ModalActions,
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
      checked: true,
      __stashed_settings__: {
        ...initState,
      },
      settings: {
        ...initState,
      },
      remindersList: [
        {
          value: '0',
          label: 'Remind on due date',
          disabled: false,
        },
        ...REMINDERS_LIST_WITHOUT_EXPIRY,
      ],
      withoutExprityRemindersList: [...REMINDERS_LIST_WITHOUT_EXPIRY],
    };
  }

  saveToggleChange = () => {
    return this.props
      .saveToggleChange(``)
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
      return this.saveToggleChange();
    }

    this.context.confirm({
      header: `Disable reminders for all ${this.typeInLowerCase} ?`,
      message: `There are ${this.props.totalUnpaidLinks} existing unpaid ${
        this.typeInLowerCase
      } that have reminders scheduled.`,
      affirmativeLabel: 'Yes, disable',
      affirmativePendingLabel: 'Disabling...',
      abortLabel: 'No, don’t!',
      action: this.saveToggleChange,
    });
  };

  onSaveClick = () => {
    return this.props.onSaveClick({
      ...this.state.settings,
    });
  };

  onChange = type => (list, name) => {
    const listType =
      name === 'with_expiry' ? 'remindersList' : 'withoutExprityRemindersList';

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
            [type]: e.target.value ? '1' : '0',
          },
        },
      },
    });
  };

  showReminderEMailPreview = () => {
    this.props.openModal({
      size: 'large',
      className: 'reminders-email-preivew-modal',
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
        remindersList,
        withoutExprityRemindersList,
      } = this.state,
      { type } = this.props;

    return (
      <div class={`reminders-setting ${checked ? 'enabled' : 'disabled'}`}>
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

                <div class="reminder-setting__reminder-options-settings">
                  <ReminderOptionSetting
                    isExpiry
                    name="with_expiry"
                    remindersList={remindersList}
                    onChange={this.onChange('withExpiry')}
                    maxSelections={settings.maxNoReminders}
                    selectedReminders={settings.withExpiry}
                  />

                  <ReminderOptionSetting
                    name="with_out_expiry"
                    maxSelections={settings.maxNoReminders}
                    onChange={this.onChange('withOutExpiry')}
                    selectedReminders={settings.withOutExpiry}
                    remindersList={withoutExprityRemindersList}
                  />
                </div>

                <AdvancedSettings
                  {...settings.advancedSettings}
                  onChannelChange={this.handleChannelChange}
                />

                <Footer
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

const REMINDERS_LIST_WITHOUT_EXPIRY = [
  { value: '1', label: 'Remind 1 day after issue date', disabled: false },
  { value: '2', label: 'Remind 2 day after issue date', disabled: false },
  { value: '3', label: 'Remind 3 day after issue date', disabled: false },
  { value: '4', label: 'Remind 4 day after issue date', disabled: false },
  { value: '5', label: 'Remind 5 day after issue date', disabled: false },
  { value: '6', label: 'Remind 6 day after issue date', disabled: false },
];

const initState = {
  maxNoReminders: 5,
  withExpiry: [],
  withOutExpiry: [
    { value: '1', label: 'Remind 1 day after issue date' },
    { value: '2', label: 'Remind 2 day after issue date' },
  ],
  advancedSettings: {
    scheduledTime: '10AM - 12PM',
    channels: {
      sms: 0,
      email: 1,
    },
  },
};
