import React from 'react';
import Input from 'common/new-ui/Input';
import DocsLink from 'merchant/components/DocsLink';
import track from '../../track';

export default class Notify extends React.Component {
  handleEmailNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=email]').focus();

    track.lj.fields.notifyEmail();
    track.segment.fields.notifyEmail();
  };

  handleSmsNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=contact]').focus();

    track.lj.fields.notifySms();
    track.segment.fields.notifySms();
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
        <DocsLink
          title="More ways to notify"
          url="https://razorpay.com/app-store/"
          style={{ paddingLeft: '0' }}
        />
      </Input.Group>
    );
  }
}
