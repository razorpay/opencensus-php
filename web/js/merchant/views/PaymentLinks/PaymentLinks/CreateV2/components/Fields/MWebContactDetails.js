import { useI18Service } from 'common/i18';
import Input from 'common/new-ui/Input';
import DocsLink from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

const MWebContactDetails = (props) => {
  const { isConfigTagEnabled } = useI18Service();
  const handleEmailNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=email]').focus();

    track.lj.fields.notifyEmail();
    track.segment.fields.notifyEmail();
  };

  const handleSmsNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=contact]').focus();

    track.lj.fields.notifySms();
    track.segment.fields.notifySms();
  };
  return (
    <Input.Group
      class="InputGroup--inline InputGroup--vTop customer-details hidden-lg"
      label="Customer Details"
      disabled={props.disabled}
    >
      <div class="mob-customer-details">
        <Input
          autoRender
          name="email"
          placeholder="john@example.com"
          type="email"
          addonBefore={<i class="i i-email-outline" />}
          defaultValue={props.defaultEmailAddress}
          onBlur={track.lj.fields.email}
        />

        <Input.Check
          autoRender
          name="email_notify"
          fieldLabel="Notify via Email"
          onClick={handleEmailNotify}
          defaultValue={props.defaultEmailValue}
        />
      </div>
      <div class="mob-customer-details">
        <Input
          autoRender
          name="contact"
          type="tel"
          placeholder={props.contactPlaceholder}
          addonBefore={<i class="i i-phone-outline" />}
          defaultValue={props.defaultContactNumber}
          onBlur={track.lj.fields.contact}
        />

        <Input.Check
          autoRender
          name="sms_notify"
          fieldLabel="Notify via SMS"
          onClick={handleSmsNotify}
          defaultValue={props.defaultContactValue}
        />
      </div>
      <ShowWhen
        additionalCondition={() =>
          !isConfigTagEnabled('app_store.app_store') &&
          !isConfigTagEnabled('documentation.documentation')
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
};

export default MWebContactDetails;
