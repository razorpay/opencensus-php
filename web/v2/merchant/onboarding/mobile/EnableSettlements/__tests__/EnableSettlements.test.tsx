import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SaveAndExitModal from '../EnableSettlements';
import { render, screen } from 'test-utils';

test('save and exit modal', () => {
  const buttonText = 'Enable settlements';
  const linkText = 'Explore products to accept payments';
  const App = () => (
    <SaveAndExitModal
      isOpen={true}
      onClose={() => {}}
      onEnableSettlementsClick={() => {}}
      exploreToAcceptPaymentsLink={''}
    />
  );
  render(<App />, {});
  expect(screen.getByText(buttonText)).toBeInTheDocument();
  expect(screen.getByText(linkText)).toBeInTheDocument();
  expect(screen.getByText('Live Payments Enabled!')).toBeInTheDocument();
  expect(
    screen.getByText(
      'Congratulations! You can start accepting payments from your customers now but you would need to enable settlements for the payments to be settled to your account',
    ),
  ).toBeInTheDocument();
});
