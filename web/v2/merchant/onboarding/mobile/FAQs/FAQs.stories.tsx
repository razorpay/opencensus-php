import React from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import FAQs from './FAQs';

export default {
  title: 'FAQs',
  component: FAQs,
};

export const Faq = () => {
  const [isOpen, setIsOpen] = React.useState(false);
  const [expanded, setExpanded] = React.useState<React.ReactText[]>(['']);
  return (
    <>
      <Button onClick={() => setIsOpen(true)}>All FAQs</Button>
      <FAQs
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        expanded={expanded}
        onChange={(_key, exp) => setExpanded(exp)}
      />
    </>
  );
};

export const SpecificSectionFaq = () => {
  const [isOpen, setIsOpen] = React.useState(false);
  const [expanded, setExpanded] = React.useState<React.ReactText[]>(['']);
  const [sectionToDisplay, setSectionToDisplay] = React.useState('');
  const handleBillingLabelFaqClick = () => {
    setExpanded(['Q1']);
    setIsOpen(true);
    setSectionToDisplay('billing-label');
  };
  const hanldeWebsiteDetailsClick = () => {
    setExpanded(['Q2']);
    setIsOpen(true);
    setSectionToDisplay('website-details');
  };
  return (
    <>
      <Button onClick={handleBillingLabelFaqClick}>billing - label</Button>
      <br />
      <br />
      <Button onClick={hanldeWebsiteDetailsClick}>website details</Button>
      <FAQs
        isOpen={isOpen}
        onClose={() => setIsOpen(false)}
        expanded={expanded}
        onChange={(_key, exp) => setExpanded(exp)}
        sectionToDisplay={sectionToDisplay}
      />
    </>
  );
};
