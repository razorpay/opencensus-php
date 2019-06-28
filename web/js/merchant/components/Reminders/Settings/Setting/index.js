import { findBy } from 'rzp/utils/rzp-utils';

import Header from './Header';
import Footer from './Footer';
import AdvancedSettings from './AdvancedSettings';
import ReminderOptionSetting from './ReminderOptionSetting';

export default class PaymentLinksSettings extends React.Component {
  state = {
    checked: true,
    settings: {
      channels: {
        sms: false,
        email: false,
      },
      maxNoReminders: 5,
      withExpiry: [],
      withOutExpiry: [3, 5],
      advancedSettings: {
        scheduledTime: '10AM - 12PM',
        channels: {
          sms: true,
          email: false,
        },
      },
    },
  };

  componentDidMount() {}

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

  onChange = type => list => {
    this.setState({
      settings: {
        ...this.state.settings,
        [type]: list,
      },
    });
  };

  handleRemove = value => () => {
    this.setState({
      [this.name]: this.state[this.name].filter(val => val !== value),
    });
  };

  render() {
    const { settings, checked } = this.state,
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
                    remindersList={REMINDERS_LIST}
                    onChange={this.onChange('withExpiry')}
                    maxSelections={settings.maxNoReminders}
                    selectedReminders={settings.withExpiry}
                  />

                  <ReminderOptionSetting
                    name="with_out_expiry"
                    maxSelections={settings.maxNoReminders}
                    onChange={this.onChange('withOutExpiry')}
                    selectedReminders={settings.withOutExpiry}
                    remindersList={REMINDERS_LIST_WITHOUT_EXPIRY}
                  />
                </div>

                <AdvancedSettings {...settings.advancedSettings} />

                <Footer
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

const REMINDERS_LIST_WITHOUT_EXPIRY = [
  { id: 1, value: 'Remind 1 day after issue date' },
  { id: 2, value: 'Remind 2 day after issue date' },
  { id: 3, value: 'Remind 3 day after issue date' },
  { id: 4, value: 'Remind 4 day after issue date' },
  { id: 5, value: 'Remind 5 day after issue date' },
  { id: 6, value: 'Remind 6 day after issue date' },
];

const REMINDERS_LIST = [
  { id: 0, value: 'Remind on due date' },
  ...REMINDERS_LIST_WITHOUT_EXPIRY,
];
