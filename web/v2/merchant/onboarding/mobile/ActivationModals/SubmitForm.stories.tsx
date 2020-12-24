import React, { useState } from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import SubmitForm from './SubmitForm';

export default {
  title: 'Onboarding/ActivationModals/SubmitForm',
  component: SubmitForm,
};

export const Modal: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>Open Modal</Button>
      <SubmitForm isOpen={isOpen} />
    </>
  );
};
