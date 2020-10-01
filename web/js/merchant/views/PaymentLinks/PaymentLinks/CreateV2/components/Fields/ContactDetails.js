import Input from 'common/new-ui/Input';

const ContactDetails = (props) => {
  return (
    <Input.Group
      class="InputGroup--inline InputGroup--vTop customer-details"
      label="Customer Details"
      disabled={props.disabled}
    >
      <div class="Input-content">
        <Input
          autoRender
          name="email"
          placeholder="john@example.com"
          type="email"
          addonBefore={<i class="i i-email-outline" />} // TODO: Update icon
          defaultValue={props.defaultEmailAddress}
        />

        <Input
          autoRender
          name="contact"
          type="tel"
          placeholder="+91 9876543210"
          addonBefore={<i class="i i-phone-outline" />}
          defaultValue={props.defaultContactNumber}
        />
      </div>
    </Input.Group>
  );
};

export default ContactDetails;
