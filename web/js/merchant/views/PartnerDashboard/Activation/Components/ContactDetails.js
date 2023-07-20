import React from 'react';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import { name, email, mobile } from 'common/utils/validators';

const ContactDetails = ({
  isFormLocked,
  contactDetails = {},
  onFormChange,
  commonLockedFields = [],
}) => {
  return (
    <Form onChange={onFormChange} className="Form Form--tabular">
      <Input
        name="contact_name"
        label="Contact Name"
        defaultValue={contactDetails.contact_name}
        disabled={isFormLocked || commonLockedFields.includes('contact_name')}
        size="small"
        required
        validator={name()}
      />
      <Input
        name="contact_email"
        label="Contact Email"
        info="We will reach out at this email id in case of any account related issue"
        defaultValue={contactDetails.contact_email}
        disabled={isFormLocked || commonLockedFields.includes('contact_email')}
        size="small"
        required
        validator={email()}
      />
      <Input
        name="contact_mobile"
        label="Contact Number"
        info="We will reach out to this phone for any account related issues"
        defaultValue={contactDetails.contact_mobile}
        disabled={isFormLocked || commonLockedFields.includes('contact_mobile')}
        size="small"
        required
        validator={mobile()}
      />
    </Form>
  );
};

export default ContactDetails;
