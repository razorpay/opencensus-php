import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import FAQs from 'merchant/views/onboarding/mobile/FAQs/FAQs';
import { useActivationFormState } from 'merchant/views/onboarding/mobile/context/store';
import { render, fireEvent, screen, waitFor } from 'test-utils';

const FAQText =
  "Billing label is your brand's identity, it will be displayed on your invoices and bills. Please ensure billing label is as close to your business name/website as possible.";
test('FAQs component rendering', () => {
  const buttonText = 'billing-label';

  const App = () => {
    const setIsOpen = useActivationFormState((state) => state.setIsFAQOpen);
    const setFAQSection = useActivationFormState((state) => state.setFAQSection);

    const handleBillingLabelFaqClick = () => {
      setFAQSection('Q3');
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
  fireEvent.click(screen.getByText('What is Billing label?'));
  expect(screen.getByText(FAQText)).toBeInTheDocument();
  fireEvent.click(screen.getByTestId('modalCloseButton'));
  waitFor(() => {
    expect(screen.queryByText(FAQText)).not.toBeInTheDocument();
  });
});
