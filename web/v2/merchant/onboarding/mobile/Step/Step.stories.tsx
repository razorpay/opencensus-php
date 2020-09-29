import React from 'react';
import { action } from '@storybook/addon-actions';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import { StepPropsT } from './Step';
import { Step } from './index';

export default {
  title: 'Onboarding/Step',
  component: Step,
} as Meta;

const DEFAULT_STEP: StepPropsT = {
  id: 'contact_details',
  name: 'Contact Details',
  onClick: () => action('clicked'),
};

const Template: Story = (args) => <Step {...args.step} />;

export const Default = Template.bind({});
Default.args = {
  step: DEFAULT_STEP,
};

export const StepCompleted = Template.bind({});
StepCompleted.args = {
  step: { ...DEFAULT_STEP, isComplete: true },
};

export const StepError = Template.bind({});
StepError.args = {
  step: { ...DEFAULT_STEP, hasErrorText: 'Unable to verify your pan. Please update.' },
};

export const StepLocked = Template.bind({});
StepLocked.args = {
  step: { ...DEFAULT_STEP, isLocked: true },
};
