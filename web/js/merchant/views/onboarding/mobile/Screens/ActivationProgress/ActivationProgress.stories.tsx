import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import ActivationProgress from './index';

export default {
  title: 'Onboarding/ActivationProgress',
  component: ActivationProgress,
} as Meta;

const Template: Story = (args) => <ActivationProgress {...args} />;

export const WhitelistedMerchant = Template.bind({});
WhitelistedMerchant.args = {
  activationFlow: 'whitelist',
};

export const GreylistedMerchant = Template.bind({});
GreylistedMerchant.args = {
  activationFlow: 'greylist',
};
