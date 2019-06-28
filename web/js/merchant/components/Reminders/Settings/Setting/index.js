import { connect } from 'react-redux';

import { findBy } from 'rzp/utils/rzp-utils';

import * as ModalActions from 'rzp/modules/modals';

import AdvancedSettings from './AdvancedSettings';
import EmailPreviewModal from './EmailPreviewModal';
import Footer from './Footer';
import Header from './Header';
import ReminderOptionSetting from './ReminderOptionSetting';

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
export default class PaymentLinksSettings extends React.Component {
  constructor(props) {
    super();

    this.state = {
      checked: true,
      settings: {
        channels: {
          sms: false,
          email: false,
        },
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

  handleToggle = () => {
    this.setState({
      checked: !this.state.checked,
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
        reminder.disabled = findBy(list, 'value', reminder.value);

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
