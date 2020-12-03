import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import useActivation from '../hooks/useActivation';
import ContactDetails from './index';

export default {
  title: 'Onboarding/ContactDetails',
  component: ContactDetails,
  decorators: [
    (StoryFn) => {
      const { status } = useActivation();
      if (status === 'loading') {
        return <div>Loading...</div>;
      }
      if (status === 'error') {
        return <div>Something went wrong.</div>;
      }
      return <StoryFn />;
    },
  ],
} as Meta;

const Template: Story = () => <ContactDetails />;

export const Default = Template.bind({});
