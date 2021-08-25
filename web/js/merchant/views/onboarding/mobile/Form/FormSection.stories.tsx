import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import View from '@razorpay/blade-old/src/atoms/View';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import TextArea from '@razorpay/blade-old/src/atoms/TextArea';
import FormSection from './FormSection';
import Field from './Field';

export default {
  title: 'Onboarding/FormSection',
  component: FormSection,
} as Meta;

const Template: Story = ({ sections }) => {
  return (
    <View>
      {sections.map((section, idx) => (
        <FormSection {...section} key={idx} />
      ))}
    </View>
  );
};

export const SingleFormSection = Template.bind({});
SingleFormSection.args = {
  sections: [
    {
      title: 'Contact Details',
      last: true,
      children: (
        <>
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
        </>
      ),
    },
  ],
};

export const SingleFormSectionError = Template.bind({});
SingleFormSectionError.args = {
  sections: [
    {
      title: 'Contact Details',
      subtitle: 'We were unable to verify contact number.',
      last: true,
      hasError: true,
      children: (
        <>
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
        </>
      ),
    },
  ],
};
export const MultipleFormSections = Template.bind({});

MultipleFormSections.args = {
  sections: [
    {
      title: 'PAN Details',
      subtitle: 'These details will be verified with the government database',
      children: (
        <>
          <Field>
            <TextInput width="auto" name="promoter_pan" label="Business Owner's PAN" />
          </Field>
          <Field last>
            <TextInput
              width="auto"
              name="promoter_pan_name"
              label="Business Owner's Name"
              helpText="As mentioned in the PAN"
            />
          </Field>
        </>
      ),
    },
    {
      title: 'Address Details',
      last: true,
      children: (
        <>
          <Field>
            <TextArea width="auto" name="business_registered_address" label="Enter Address" />
          </Field>
          <Field>
            <TextInput width="auto" name="business_registered_pin" label="Pincode" />
          </Field>
          <Field>
            <TextInput width="auto" name="business_registered_city" label="City" />
          </Field>
          <Field last>
            <TextInput width="auto" name="business_registered_state" label="Select State" />
          </Field>
        </>
      ),
    },
  ],
};
