import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import FAQs from '../FAQs';
import { useActivationFormState } from '../../context/store';
import { render, fireEvent, screen } from 'test-utils';

test('FAQs component rendering', () => {
  const buttonText = 'billing-label';

  const App = () => {
    const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
    const setFAQSection = useActivationFormState((state) => state.setFAQSection);

    const handleBillingLabelFaqClick = () => {
      setFAQSection('Q1');
      setIsOpen(true);
    };
    return (
      <>
        <button onClick={handleBillingLabelFaqClick}>billing-label</button>
        <FAQs />
      </>
    );
  };
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(buttonText));
  expect(screen.getByText('What is Billing label?')).toBeInTheDocument();
  expect(screen.getByText('How can I add api keys to my website?')).toBeInTheDocument();
  expect(
    screen.getByText(
      "Billing label is your brand's identity, it will be displayed on your invoices and bills. Please ensure billing label is as close to your business name/website as possible.",
    ),
  ).toBeInTheDocument();
});
