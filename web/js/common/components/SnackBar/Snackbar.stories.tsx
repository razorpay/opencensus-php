import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import Snackbar, { SnackbarT } from './Snackbar';

export default {
  title: 'Snackbar',
  component: Snackbar,
} as Meta;

const Template: Story<SnackbarT> = (args) => <Snackbar {...args} />;

export const SnackbarWithSuccess = Template.bind({});

SnackbarWithSuccess.args = {
  message: 'Success message',
  type: 'success',
  color: 'positive.900',
  icon: 'success',
  onClose: () => {},
  shouldAnimateIn: true,
};

export const SnackbarWithError = Template.bind({});

SnackbarWithError.args = {
  message: 'Error message',
  type: 'failure',
  color: 'negative.900',
  icon: 'failure',
  onClose: () => {},
  shouldAnimateIn: true,
};
