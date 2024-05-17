import { isValidPhoneNumber } from '@razorpay/i18nify-js/phoneNumber';
import Input from 'common/new-ui/Input';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';

const ContactDetails = (props) => {
  const validateContact = (val) => {
    if (!val || isValidPhoneNumber(val)) return undefined;
    return 'Invalid contact number.';
  };

  return (
    <Input.Group
      class="InputGroup--inline InputGroup--vTop customer-details hidden-xs"
      label="Customer Details"
      disabled={props.disabled}
    >
      <div class="Input-content">
        <Input
          autoRender
          name="email"
          placeholder="john@example.com"
          type="email"
          addonBefore={<i class="i i-email-outline" />}
          defaultValue={props.defaultEmailAddress}
          onBlur={track.lj.fields.email}
        />

        <Input
          autoRender
          name="contact"
          type="tel"
          placeholder={props.contactPlaceholder}
          addonBefore={<i class="i i-phone-outline" />}
          defaultValue={props.defaultContactNumber}
          onBlur={track.lj.fields.contact}
          validator={(val) => validateContact(val)}
        />
      </div>
    </Input.Group>
  );
};

export default ContactDetails;
