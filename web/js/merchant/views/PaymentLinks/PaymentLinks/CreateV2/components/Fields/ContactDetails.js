import Input from 'common/new-ui/Input';
import track from '../../track';

const ContactDetails = (props) => {
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
          placeholder="+91 9876543210"
          addonBefore={<i class="i i-phone-outline" />}
          defaultValue={props.defaultContactNumber}
          onBlur={track.lj.fields.contact}
        />
      </div>
    </Input.Group>
  );
};

export default ContactDetails;
