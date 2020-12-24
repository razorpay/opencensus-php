import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import ProgressStepsComponent, { ProgressStepsPropsT } from './ProgressSteps';

export default {
  title: 'ProgressSteps',
  component: ProgressStepsComponent,
} as Meta;

export const ProgressSteps: React.FC = () => {
  return <ProgressStepsComponent currentStep={3} totalSteps={5} />;
};

const Template: Story<ProgressStepsPropsT> = (args) => <ProgressStepsComponent {...args} />;

export const ProgressBarWithControls = Template.bind({});

ProgressBarWithControls.args = {
  totalSteps: 5,
  currentStep: 2,
};
