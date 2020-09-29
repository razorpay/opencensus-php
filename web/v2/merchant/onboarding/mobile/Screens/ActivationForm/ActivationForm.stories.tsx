import React from 'react';
import ActivationForm from './index';

export default {
  title: 'Onboarding/ActivationForm',
  component: ActivationForm,
  parameters: {
    info: { disable: true },
    layout: 'fullscreen',
  },
};

const Template = () => <ActivationForm />;

export const Default = Template.bind({});
