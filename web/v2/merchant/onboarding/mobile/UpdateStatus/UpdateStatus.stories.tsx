import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import { StatusUpdate } from './index';

export default {
  title: 'Onboarding/StatusUpdate',
  component: StatusUpdate,
} as Meta;

const Template: Story = (args) => <StatusUpdate {...args} />;

export const StatusUpdates = Template.bind({});
