import Input from 'common/new-ui/Input';
import { PhoneNumberInput } from '@razorpay/blade/components';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';
import { getUser } from '@apps/shell/src/client/store/commonStore/exposedActions';

const ContactDetails = (props) => {
  const user = getUser();
  return (
    <Input.Group
      className="InputGroup--inline InputGroup--vTop customer-details hidden-xs"
      label="Customer Details"
      disabled={props.disabled}
    >
      <div className="Input-content">
        <Input
          autoRender
          name="email"
          placeholder="john@example.com"
          type="email"
          addonBefore={<i className="i i-email-outline" />}
          defaultValue={props.defaultEmailAddress}
          onBlur={track.lj.fields.email}
        />

        {user.isCountrySingapore ? (
          <PhoneNumberInput
            name="contact"
            label="Contact Number"
            defaultValue={props.defaultContactNumber}
            defaultCountry={user.merchant.country_code}
            showDialCode={true}
            value={props.contactValue}
            onChange={(event) => {
              props.handlePhoneNumberChange(event);
            }}
            onBlur={track.lj.fields.contact}
          />
        ) : (
          <Input
            autoRender
            name="contact"
            type="tel"
            placeholder={props.contactPlaceholder}
            addonBefore={<i className="i i-phone-outline" />}
            defaultValue={props.defaultContactNumber}
            onBlur={track.lj.fields.contact}
          />
        )}
      </div>
    </Input.Group>
  );
};

export default ContactDetails;
