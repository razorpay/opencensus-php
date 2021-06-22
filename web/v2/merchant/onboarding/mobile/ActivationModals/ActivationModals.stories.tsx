import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import { ActivationModal } from './index';

export default {
  title: 'Onboarding/ActivationModals',
  component: ActivationModal,
} as Meta;

const Template: Story<any> = (args) => <ActivationModal {...args} />;

export const ActivationModalWithControls = Template.bind({});

ActivationModalWithControls.args = {
  isOpen: false,
  closeModal: () => {},
  modalType: 'dedupe',
  dedupeStatus: 'blocked',
  activationData: { contact_email: 'test@gmmail.com' },
};
