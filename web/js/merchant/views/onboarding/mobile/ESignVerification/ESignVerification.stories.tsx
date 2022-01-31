import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import ESignVerification from './index';

export default {
  title: 'Onboarding/ESignVerification',
  component: ESignVerification,
} as Meta;

const Template: Story = () => <ESignVerification disabled={false} showAddressProofDoc={() => {}} />;

export const Default = Template.bind({});
