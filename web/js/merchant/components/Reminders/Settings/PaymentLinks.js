import Setting from './Setting';

export default class PaymentLinksSettings extends React.Component {
  saveSettings = () => {};

  render() {
    return (
      <div class="reminders-settings--payment_links">
        <Setting type="Payment Links" onSaveClick={this.saveSettings} />
      </div>
    );
  }
}
