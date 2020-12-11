import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import ProgressBar, { ProgressBarPropsT } from './ProgressBar';

export default {
  title: 'ProgressBar',
  component: ProgressBar,
} as Meta;

export const ProgressBarSteps: React.FC = () => {
  return <ProgressBar currentStep={3} totalSteps={5} />;
};

const Template: Story<ProgressBarPropsT> = (args) => <ProgressBar {...args} />;

export const ProgressBarWithControls = Template.bind({});

ProgressBarWithControls.args = {
  totalSteps: 5,
  currentStep: 2,
};
