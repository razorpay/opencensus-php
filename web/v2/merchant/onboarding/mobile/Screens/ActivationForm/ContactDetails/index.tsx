import React from 'react';
import TextInput from '@razorpay/blade/src/atoms/TextInput';
import { FormSection, Field } from '../../../Form/';

const ContactDetails: React.FC = () => {
  const onChange = (e) => {
    console.log(e.target.name, e.target.value);
  };

  return (
    <form onChange={onChange}>
      <FormSection title="Contact Details" last>
        <Field>
          <TextInput width="auto" name="contact_name" label="Contact Name" />
        </Field>
        <Field>
          <TextInput
            width="auto"
            name="contact_email"
            label="Contact Email"
            helpText="We will reach out at this email id in case of any account related issue"
          />
        </Field>
        <Field last>
          <TextInput width="auto" name="contact_number" label="Contact Number" />
        </Field>
      </FormSection>
    </form>
  );
};

export default ContactDetails;
