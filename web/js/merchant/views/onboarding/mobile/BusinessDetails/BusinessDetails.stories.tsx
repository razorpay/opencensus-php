import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import BusinessDetails from './index';

export default {
  title: 'Onboarding/BusinessDetails',
  component: BusinessDetails,
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

const Template: Story = () => <BusinessDetails />;

export const Default = Template.bind({});
