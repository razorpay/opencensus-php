import React from 'react';
import { action } from '@storybook/addon-actions';
import Step from './index';

export default {
  title: 'Step',
  component: Step,
};

export const StepDefault: React.FC = () => (
  <Step id="contact_details" name="Contact Details" onClick={action('clicked')} />
);

export const StepCompleted: React.FC = () => (
  <Step id="business_details" name="Business details" onClick={action('clicked')} isComplete />
);

export const StepError: React.FC = () => (
  <Step
    id="business_details"
    name="Business details"
    onClick={action('clicked')}
    hasErrorText="Unable to verify your pan. Please update."
  />
);

export const StepLocked: React.FC = () => (
  <Step id="business_details" name="Business details" onClick={action('clicked')} isLocked />
);
