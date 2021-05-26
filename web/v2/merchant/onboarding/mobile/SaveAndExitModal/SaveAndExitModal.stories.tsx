import React, { useState } from 'react';
import Button from '@razorpay/blade-old/src/atoms/Button';
import SaveAndExitModal from './SaveAndExitModal';

export default {
  title: 'Onboarding/Save and exit',
  component: SaveAndExitModal,
};

export const Popup: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>Open Modal</Button>
      <SaveAndExitModal isOpen={isOpen} onClose={() => setIsOpen(false)} />
    </>
  );
};
