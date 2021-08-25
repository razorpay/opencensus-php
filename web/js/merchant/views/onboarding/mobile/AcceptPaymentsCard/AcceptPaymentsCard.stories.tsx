import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import AcceptPaymentsCard from './index';

export default {
  title: 'Onboarding/AcceptpaymentsCard',
  component: AcceptPaymentsCard,
  parameters: {
    backgrounds: {
      default: 'bg.600',
      values: [{ name: 'bg.600', value: '#EDF0F5', default: true }],
    },
  },
} as Meta;

const Template: Story = (args) => <AcceptPaymentsCard {...args} />;

export const Default = Template.bind({});
