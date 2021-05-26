import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import View from '@razorpay/blade-old/src/atoms/View';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Field from './Field';

export default {
  title: 'Onboarding/Field',
  component: Field,
} as Meta;

const Template: Story = ({ fields }) => {
  return (
    <View>
      {fields.map((field, idx) => (
        <Field {...field} key={idx} />
      ))}
    </View>
  );
};

export const SingleField = Template.bind({});
SingleField.args = {
  fields: [
    {
      last: true,
      children: <TextInput width="auto" name="contact_name" label="Contact Name" />,
    },
  ],
};

export const MultipleField = Template.bind({});
MultipleField.args = {
  fields: [
    {
      children: <TextInput width="auto" name="contact_name" label="Contact Name" />,
    },
    {
      children: (
        <TextInput
          width="auto"
          name="contact_email"
          label="Contact Email"
          helpText="We will reach out at this email id in case of any account related issue"
        />
      ),
    },
    {
      last: true,
      children: <TextInput width="auto" name="contact_number" label="Contact Number" />,
    },
  ],
};
