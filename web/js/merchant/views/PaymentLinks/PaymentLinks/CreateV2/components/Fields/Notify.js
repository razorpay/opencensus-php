import React from 'react';
import { withI18Service } from 'common/i18';
import Input from 'common/new-ui/Input';
import DocsLink from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

class Notify extends React.Component {
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
        class="InputGroup--inline InputGroup--near customer-notify hidden-xs"
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

        <ShowWhen
          additionalCondition={() =>
            !this.props.i18.isConfigTagEnabled('app_store.app_store') &&
            !this.props.i18.isConfigTagEnabled('documentation.documentation')
          }
        >
          <DocsLink
            title="More ways to notify"
            url="https://razorpay.com/app-store/"
            style={{ paddingLeft: '0' }}
          />
        </ShowWhen>
      </Input.Group>
    );
  }
}
export default withI18Service(Notify);
