import Input from 'common/new-ui/Input';

export default class Notify extends React.Component {
  handleEmailNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=email]').focus();
  };

  handleSmsNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=contact]').focus();
  };

  render() {
    const { props } = this;
    return (
      <Input.Group
        class="InputGroup--inline InputGroup--near customer-notify"
        disabled={props.disabled}
      >
        <div class="Input-content">
          <Input.Check
            autoRender
            name="email_notify"
            fieldLabel="Notify via Email"
            onClick={this.handleEmailNotify}
            defaultValue={props.defaultEmailValue}
          />
          <Input.Check
            autoRender
            name="sms_notify"
            fieldLabel="Notify via SMS"
            onClick={this.handleSmsNotify}
            defaultValue={props.defaultContactValue}
          />
        </div>
      </Input.Group>
    );
  }
}
