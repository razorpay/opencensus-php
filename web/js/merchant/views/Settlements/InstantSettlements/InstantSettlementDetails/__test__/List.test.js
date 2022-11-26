import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import InstantDetailsList from 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails/List';
import { render, screen, userEvent } from 'test-utils';
import { titleCase, getFormattedAmount } from 'common/utils/rzp-utils';
import * as trackEvents from 'merchant/views/Settlements/trackEvents';
import * as trackGa from 'common/utils/googleAnalytics';
import { defaultProps, tableHeader } from './mocks/fixtures/List';

describe('InstantDetailsList', () => {
  const trackEventSpy = jest.spyOn(trackEvents, 'trackOnDemandPayoutIdClick');
  const trackGaSpy = jest.spyOn(trackGa, 'setTrackData');

  const renderApp = ({ initialState, props } = {}) =>
    render(<InstantDetailsList {...defaultProps} {...props} />, {
      initialState,
    });

  beforeEach(() => {
    trackEventSpy.mockClear();
    trackGaSpy.mockClear();
  });

  test('should render instant settlement details list table', () => {
    renderApp();
    expect(screen.getByRole('table')).toBeInTheDocument();
    expect(screen.getAllByRole('table')).toHaveLength(1);
  });

  describe('DetailsListTable', () => {
    test('should render table head for instant detail listing', () => {
      renderApp();
      const tableHead = screen.getAllByRole('columnheader');
      expect(tableHead).toHaveLength(tableHeader.length);

      tableHeader.forEach((each) => {
        expect(screen.getByText(each)).toBeInTheDocument();
      });
    });

    test('should render payout list items', () => {
      renderApp();
      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(defaultProps.settlement.ondemand_payouts.items.length + 1);

      defaultProps.settlement.ondemand_payouts.items.forEach((each, index) => {
        expect(screen.getByText(titleCase(each.status))).toBeInTheDocument();
        expect(screen.getByText(each.utr ? each.utr : '-')).toBeInTheDocument();

        const formattedAmount = getFormattedAmount(each.amount);
        expect(screen.getAllByLabelText('amount-info')[index].textContent).toContain(
          `₹ ${formattedAmount?.split('.')[0]}.${formattedAmount?.split('.')[1]}`,
        );
      });
    });

    test('should render instant detail links and call track events when links are clicked', async () => {
      renderApp();
      const link = screen.getAllByRole('link');
      expect(link).toHaveLength(defaultProps.settlement.ondemand_payouts.items.length);

      defaultProps.settlement.ondemand_payouts.items.forEach((each, index) => {
        expect(link[index]).toHaveAttribute(
          'href',
          `/instantsettlement_details/${defaultProps.settlement.id}`,
        );
      });

      await userEvent.click(link[1]);
      expect(trackEventSpy).toHaveBeenCalledTimes(1);
      expect(trackGaSpy).toHaveBeenCalledTimes(1);
      expect(trackGaSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Click Id - On Demand UTR',
        eventLabel: 'Drawer | Click on On Demand UTR',
      });
    });

    test('should render empty table message when no instant settlement detail items are found', () => {
      renderApp({
        props: {
          settlement: {
            ...defaultProps.settlement,
            ondemand_payouts: {
              ...defaultProps.ondemand_payouts,
              items: [],
            },
          },
        },
      });
      const emptyMessage = 'No Settlements found!';
      expect(screen.getByText(emptyMessage)).toBeInTheDocument();
      expect(screen.getByRole('cell', { name: emptyMessage })).toBeInTheDocument();
      expect(screen.getAllByRole('cell', { name: emptyMessage })).toHaveLength(1);

      const tableRows = screen.getAllByRole('row');
      expect(tableRows).toHaveLength(2);
    });
  });
});
