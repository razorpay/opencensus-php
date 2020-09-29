import React from 'react';
import { action } from '@storybook/addon-actions';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import { StepListPropsT } from './StepList';
import { StepList } from './index';

export default {
  title: 'Onboarding/StepList',
  component: StepList,
} as Meta;

const DEFAULT_STEP_LIST: StepListPropsT['steps'] = [
  {
    name: 'Contact Details',
    id: 'contact_details',
    isComplete: true,
    onClick: () => action('clicked'),
  },
  {
    name: 'Business Overview',
    id: 'business_overview',
    isComplete: true,
    onClick: () => action('clicked'),
  },
  {
    name: 'Business Details',
    id: 'business_details',
    isComplete: true,
    onClick: () => action('clicked'),
  },
];

const Template: Story = (args) => <StepList steps={args.steps} />;

export const Default = Template.bind({});
Default.args = {
  steps: DEFAULT_STEP_LIST,
};
