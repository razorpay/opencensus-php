import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BreakupList from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/BreakupList';
import { render, screen } from 'test-utils';
import { getFormattedAmount } from 'common/utils/rzp-utils';
import {
  defaultProps,
  tableHeader,
  state,
} from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/__test__/mocks/fixtures/BreakupList';

describe('BreakupList', () => {
  const renderApp = ({ initialState = state, props } = {}) =>
    render(<BreakupList {...defaultProps} {...props} />, {
      initialState,
    });

  test('should render instant settlement breakup list table', () => {
    renderApp();
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getAllByRole('table')).toHaveLength(1);
  });

  describe('BreakupListTable', () => {
    const genericAmountTest = ({ amount, infoIndex = 0 }) => {
      const formattedAmount = getFormattedAmount(amount);
      expect(screen.getAllByLabelText('amount-info')[infoIndex].textContent).toContain(
        `₹ ${formattedAmount?.split('.')[0]}.${formattedAmount?.split('.')[1]}`,
      );
    };

    test('should render table heading for breakup listing', () => {
      renderApp();
      const tableHead = screen.getAllByRole('columnheader');
      expect(tableHead).toHaveLength(tableHeader.length);

      tableHeader.forEach((each) => {
        expect(screen.getByText(each)).toBeInTheDocument();
      });
    });

    test('should render payout list rows', () => {
      renderApp();
      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(4);
    });

    test('should render payout table body items', () => {
      renderApp();
      const {
        instantSettlement: { amount_settled, fees, tax },
      } = defaultProps;

      expect(screen.getByText('Settled Amount')).toBeInTheDocument();
      genericAmountTest({
        amount: amount_settled,
      });

      expect(screen.getByText('Ondemand Fee')).toBeInTheDocument();
      genericAmountTest({
        amount: fees - tax,
        infoIndex: 1,
      });

      expect(screen.getByText('Tax')).toBeInTheDocument();
      genericAmountTest({
        amount: tax,
        infoIndex: 2,
      });
    });

    test('should render pending amount when instant settlement pending amount not zero', () => {
      const props = {
        ...defaultProps,
        instantSettlement: {
          ...defaultProps.instantSettlement,
          amount_settled: 5000,
          amount_pending: 4971,
          ondemand_payouts: {
            ...defaultProps.instantSettlement.ondemand_payouts,
            items: [
              {
                ...defaultProps.instantSettlement.ondemand_payouts.items[0],
                status: 'processed',
              },
            ],
          },
        },
      };
      renderApp({ props });
      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(5);

      expect(screen.getByText('Pending Amount')).toBeInTheDocument();
      genericAmountTest({
        amount: props.instantSettlement.amount_pending,
        infoIndex: 1,
      });
    });

    test('should render reversed amount when instant settlement reversed amount not zero', () => {
      const props = {
        ...defaultProps,
        instantSettlement: {
          ...defaultProps.instantSettlement,
          amount_reversed: 3000,
          ondemand_payouts: {
            ...defaultProps.instantSettlement.ondemand_payouts,
            items: [
              {
                ...defaultProps.instantSettlement.ondemand_payouts.items[0],
                status: 'reversed',
              },
            ],
          },
        },
      };
      renderApp({ props });
      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(5);

      expect(screen.getByText('Reversed Amount')).toBeInTheDocument();
      genericAmountTest({
        amount: props.instantSettlement.amount_reversed,
        infoIndex: 1,
      });
    });
  });
});
