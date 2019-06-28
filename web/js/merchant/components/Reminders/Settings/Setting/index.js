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
      withOutExpiry: [
        { value: '1', label: 'Remind 1 day after issue date' },
        { value: '2', label: 'Remind 2 day after issue date' },
      ],
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
  { value: '1', label: 'Remind 1 day after issue date' },
  { value: '2', label: 'Remind 2 day after issue date' },
  { value: '3', label: 'Remind 3 day after issue date' },
  { value: '4', label: 'Remind 4 day after issue date' },
  { value: '5', label: 'Remind 5 day after issue date' },
  { value: '6', label: 'Remind 6 day after issue date' },
];

const REMINDERS_LIST = [
  { value: 0, label: 'Remind on due date' },
  ...REMINDERS_LIST_WITHOUT_EXPIRY,
];
