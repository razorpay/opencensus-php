import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import FAQs from '../FAQs';
import { render, fireEvent, screen } from 'test-utils';

test('FAQs component rendering', () => {
  const buttonText = 'billing-label';

  const App = () => {
    const [isOpen, setIsOpen] = React.useState(false);
    const [expanded, setExpanded] = React.useState<React.ReactText[]>(['']);
    const handleBillingLabelFaqClick = () => {
      setExpanded(['Q1']);
      setIsOpen(true);
    };
    return (
      <>
        <button onClick={handleBillingLabelFaqClick}>billing-label</button>
        <FAQs
          isOpen={isOpen}
          onClose={() => setIsOpen(false)}
          expanded={expanded}
          onChange={(_key, exp) => setExpanded(exp)}
          sectionToDisplay="billing-label"
        />
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
