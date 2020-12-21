import React, { useState } from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import EnableSettlements from './EnableSettlements';

export default {
  title: 'Onboarding/Enable Settlements',
  component: EnableSettlements,
};

export const Modal = () => {
  const [isOpen, setIsOpen] = useState(false);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>Open Modal</Button>
      <EnableSettlements isOpen={isOpen} />
    </>
  );
};
