import SwitchField from 'rzp/ui/Forms/SwitchField';

import Footer from './common/Footer';

export default class PaymentLinksSettings extends React.Component {
  state = {
    settings: {
      checked: true,
    },
  };

  componentDidMount() {}

  handleToggle = () => {
    this.setState({
      settings: {
        ...this.state.settings,
        checked: !this.state.settings.checked,
      },
    });
  };

  prepareToSave = () => {};

  saveSettings = () => {};

  render() {
    const { settings } = this.state;

    return (
      <div
        class={`reminders-setting reminders-settings--payment_links ${
          settings.checked ? 'enabled' : 'disabled'
        }`}
      >
        <div class="panel panel-default">
          <div class="panel-section--theme">
            <div class="panel-heading">
              <span className="title">Reminders for payment links</span>

              <SwitchField
                class="m-l"
                type="prime"
                checked={settings.checked}
                onChange={this.handleToggle}
              />
              <span class="status-text">
                {settings.checked ? 'Enabled' : 'Disabled'}
              </span>

              <p class="description">
                Send automated reminders to unpaid payment links and get paid on
                time.
              </p>
            </div>

            {settings.checked && (
              <div class="panel-body">
                <Footer
                  reminderTime="10AM - 12AM"
                  onSaveClick={this.saveSettings}
                />
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }
}
