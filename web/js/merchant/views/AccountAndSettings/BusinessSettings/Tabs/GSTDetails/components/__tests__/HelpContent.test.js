import React from 'react';
import HelpContent from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/components/HelpContent';
import { render, screen } from 'test-utils';

describe('HelpContent component', () => {
  const App = () => {
    return <HelpContent />;
  };

  test('Should render title correctly', () => {
    render(<App />);

    expect(screen.getByText('Want to add a different GST?')).toBeInTheDocument();
  });

  test('Should render body correctly', () => {
    render(<App />);

    expect(
      screen.getByText(
        `GSTIN linked to your PAN are shown above. To add a different GST not linked to your PAN, create a new Razorpay Account.`,
      ),
    ).toBeInTheDocument();
  });
});
