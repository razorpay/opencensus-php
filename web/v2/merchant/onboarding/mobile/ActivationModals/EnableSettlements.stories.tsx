import React, { useState } from 'react';
import Button from '@razorpay/blade-old/src/atoms/Button';
import EnableSettlements from './EnableSettlements';

export default {
  title: 'Onboarding/ActivationModals/Enable Settlements',
  component: EnableSettlements,
};

export const Modal: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>Open Modal</Button>
      <EnableSettlements isOpen={isOpen} />
    </>
  );
};
