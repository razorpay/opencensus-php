import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, userEvent, waitFor } from 'test-utils';
import * as trackGa from 'common/utils/googleAnalytics';
import * as trackEvents from 'merchant/views/Settlements/trackEvents';
import {
  options,
  defaultProps,
} from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/__test__/mocks/fixtures/DetailsListContainer';
import PayoutDetailsContainer from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/DetailsListContainer';

describe('PayoutDetailsContainer', () => {
  const trackSpy = jest.spyOn(trackGa, 'setTrackData');
  const trackEventSpy = jest.spyOn(trackEvents, 'trackOnDemandPayoutSearch');

  const renderApp = ({ initialState, props } = {}) =>
    render(<PayoutDetailsContainer {...defaultProps} {...props} />, {
      initialState,
    });

  beforeEach(() => {
    trackSpy.mockClear();
    trackEventSpy.mockClear();
  });

  describe('PayoutFilters', () => {
    test('should render payout id filter and track event on change of payload id', async () => {
      renderApp();
      expect(screen.getByText('Ondemand Payout ID')).toBeInTheDocument();
      const field = screen.getByRole('textbox');
      expect(field).toBeInTheDocument();

      const payoutId = 'setlodp_InXEtJ23TyveQt';
      userEvent.type(field, payoutId);

      await waitFor(() => {
        expect(field).toHaveAttribute('value', payoutId);
        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Search - Instant Settlements',
          eventLabel: 'Payout Details Page | Search Instant Settlements',
        });
      });
    });

    test('should render status filter and track event on selecting status dropdown', async () => {
      renderApp();
      expect(screen.getByText('Status')).toBeInTheDocument();
      const statusFilter = screen.getByRole('combobox');
      expect(statusFilter).toBeInTheDocument();

      const selectedOption = options[2];
      userEvent.selectOptions(statusFilter, selectedOption.value);
      await waitFor(() => {
        expect(screen.getByRole('option', { name: selectedOption.label }).selected).toBe(true);
        options.forEach((each) => {
          each.label !== selectedOption.label &&
            expect(screen.getByRole('option', { name: each.label }).selected).toBe(false);
          expect(screen.getByText(each.label)).toBeInTheDocument();
        });

        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Click - Status Filter',
          eventLabel: 'Payout Details Page | Click Status Filter',
        });
      });
    });

    test('should render search button and trigger apply filter callback', async () => {
      renderApp();
      const searchButton = screen.getByRole('button', {
        name: 'Search',
      });
      expect(searchButton).toBeInTheDocument();
      // fill the form fields
      const user = userEvent.setup();
      await user.type(screen.getByRole('textbox'), 'setlodp_InXEtJ23TyveQt');
      await user.selectOptions(screen.getByRole('combobox'), options[2].value);
      user.click(searchButton);

      await waitFor(() => {
        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Click CTA - Search Instant Settlements',
          eventLabel: 'Payout Details Page | Search CTA',
        });

        expect(trackEventSpy).toHaveBeenCalled();
      });
    });

    test('should render clear filter button', async () => {
      renderApp();
      const clearButton = screen.getByRole('button', {
        name: 'Clear',
      });
      expect(clearButton).toBeInTheDocument();
      userEvent.click(clearButton);
      await waitFor(() => {
        expect(trackSpy).toHaveBeenCalled();
        expect(trackSpy).toHaveBeenCalledWith({
          eventCategory: 'Dashboard - Instant Settlement',
          eventAction: 'Click CTA - Clear Seach Parameters',
          eventLabel: 'Payout Details Page | Clear Search CTA',
        });
      });
    });

    test('should render payout list items', () => {
      renderApp();
      expect(screen.getByText('Payout List Items')).toBeInTheDocument();
    });
  });
});
