import React from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import { useActivationFormState } from '../context/store';
import FAQs from './FAQs';

export default {
  title: 'onboarding/FAQs',
  component: FAQs,
};

export const Faq: React.FC = () => {
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>All FAQs</Button>
      <FAQs />
    </>
  );
};

export const SpecificSectionFaq: React.FC = () => {
  const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
  const setFAQSection = useActivationFormState((state) => state.setFAQSection);

  return (
    <>
      <Button
        onClick={() => {
          setFAQSection('Q1');
          setIsOpen(true);
        }}
      >
        billing - label
      </Button>
      <br />
      <br />
      <Button
        onClick={() => {
          setFAQSection('Q2');
          setIsOpen(true);
        }}
      >
        website details
      </Button>
      <FAQs />
    </>
  );
};
