import React from 'react';
import { Meta } from '@storybook/react/types-6-0.d';
import { action } from '@storybook/addon-actions';
import CountryCodeInput from './CountryCodeInput';

export default {
  title: 'CountryCodeInput',
  component: CountryCodeInput,
} as Meta;

export const CountrySelect = (): React.ReactElement => (
  <CountryCodeInput
    dialCode="+91"
    value="9999999999"
    onChange={action('change')}
    onDialCodeChange={action('dialCodeChange')}
    onContactChange={action('contactChange')}
  />
);
