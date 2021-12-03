import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import OnboardingCard from './index';

export default {
  title: 'Onboarding/OnboardingCard',
  component: OnboardingCard,
  parameters: {
    backgrounds: {
      default: 'bg.600',
      values: [{ name: 'bg.600', value: '#EDF0F5', default: true }],
    },
    layout: 'fullscreen',
  },
} as Meta;

const Template: Story = (args) => <OnboardingCard referee={undefined} {...args} />;

export const Default = Template.bind({});
