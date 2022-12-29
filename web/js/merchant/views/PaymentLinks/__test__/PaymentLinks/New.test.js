import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/New';

describe('New Link', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  const renderApp = (isPaymentLinkCreationV2Enabled) => {
    return render(<App />, {
      initialState: { session: { user: { isPaymentLinkCreationV2Enabled } } },
    });
  };

  test('should render payment link v2', () => {
    renderApp(true);
    expect(screen.getByText('Payment Links V2')).toBeInTheDocument();
  });

  test('should render payment link v1', () => {
    renderApp(false);
    expect(screen.getByText('Payment Links V1')).toBeInTheDocument();
  });
});
