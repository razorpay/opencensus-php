import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor } from 'test-utils';
import SettlementsList from 'merchant/views/Settlements/Settlements/components/List';
import { props } from 'merchant/views/Settlements/Settlements/components/__test__/mocks/fixtures/List';
import { fireEvent } from '@testing-library/react';

describe('List.js', () => {
  const renderApp = (newProps = {}) =>
    render(<SettlementsList {...props} {...newProps} />, { showModal: true });

  test('should render table columns', () => {
    renderApp();
    expect(screen.queryByText('Settlement Id')).toBeInTheDocument();
    expect(screen.queryByText('Amount')).toBeInTheDocument();
    expect(screen.queryByText('Tax')).toBeInTheDocument();
    expect(screen.queryByText('Fees')).toBeInTheDocument();
    expect(screen.queryByText('Status')).toBeInTheDocument();
  });

  test('should open breakup modal on breakup click', async () => {
    renderApp();
    const breakupBtn = screen.getByRole('button', { name: 'Breakup' });
    expect(breakupBtn).toBeInTheDocument();
    fireEvent.click(breakupBtn, {});

    await waitFor(() => {
      expect(screen.queryByTestId('spinner')).not.toBeInTheDocument();
    });
  });

  test('should render settlements item row', () => {
    renderApp();
    const stlId = screen.getByText('setl_K1QFNZ3mxXK0A2');
    expect(stlId).toBeInTheDocument();
  });

  test('should render RM as currency symbol for malaysia', () => {
    const props = { settlementCurrency: 'MYR' };
    renderApp(props);
    const amountCurrency = screen.getByTestId('settlement-amount').querySelector('.rzp-currency');
    expect(amountCurrency).toHaveTextContent('RM');
  });
});
