import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import InstantSettlementListFilter from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/ListFilter';
import { render, screen, waitFor, userEvent } from 'test-utils';
import * as trackGa from 'common/utils/googleAnalytics';
import { defaultProps, options } from './mocks/fixtures/ListFilter';

describe('InstantSettlementListFilter', () => {
  const trackSpy = jest.spyOn(trackGa, 'setTrackData');

  const renderApp = ({ props } = {}) =>
    render(<InstantSettlementListFilter {...defaultProps} {...props} />);

  beforeEach(() => {
    trackSpy.mockClear();
  });

  test('should render instant settlement list filter form', () => {
    renderApp();
    expect(screen.getByRole('form')).toBeInTheDocument();
    expect(screen.getByRole('form')).toHaveAttribute('name', defaultProps.form);
    expect(screen.getAllByRole('form')).toHaveLength(1);
  });

  describe('InstantSettlementFilterFields', () => {
    test('should render settlement id filter', async () => {
      renderApp();
      expect(screen.getByText('Settlement Id')).toBeInTheDocument();
      const field = screen.getByRole('textbox');
      expect(field).toBeInTheDocument();
      expect(field).toHaveAttribute('name', 'id');

      const settlementId = 'setlodp_InXEtJ23TyveQt';
      await userEvent.type(field, settlementId);
      expect(field).toHaveAttribute('value', settlementId);
    });

    test('should render settlement status dropdown filter', async () => {
      renderApp();
      expect(screen.getByText('Status')).toBeInTheDocument();
      const field = screen.getByRole('combobox');
      expect(field).toBeInTheDocument();
      expect(field).toHaveAttribute('name', 'status');

      const optionToSelect = options[2];
      userEvent.selectOptions(field, optionToSelect.value);
      await waitFor(() => {
        expect(trackSpy).toHaveBeenCalled();
      });
      expect(screen.getByRole('option', { name: optionToSelect.label }).selected).toBe(true);
      options.forEach((each) => {
        if (each.label !== optionToSelect.label) {
          expect(screen.getByRole('option', { name: each.label }).selected).toBe(false);
        }
        expect(screen.getByText(each.label)).toBeInTheDocument();
      });
      expect(trackSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Click - Status Filter',
        eventLabel: 'Data Table | Click Status Filter',
      });
    });

    test('should render settlement item count within range', async () => {
      renderApp();
      expect(screen.getByText('Count')).toBeInTheDocument();
      const field = screen.getByRole('spinbutton');

      expect(field).toBeInTheDocument();
      expect(field).toHaveAttribute('name', 'count');
      expect(field).toHaveAttribute('type', 'number');
      expect(field).toHaveAttribute('min', '1');
      expect(field).toHaveAttribute('max', '100');

      userEvent.click(field);

      await waitFor(() => {
        expect(trackSpy).toHaveBeenCalled();
      });
      expect(trackSpy).toHaveBeenCalledWith({
        eventCategory: 'Dashboard - Instant Settlement',
        eventAction: 'Click - Count Parameter',
        eventLabel: 'Data Table | Click Count Filed',
      });
    });

    test('should render submit and clear filter button', async () => {
      const onSubmit = jest.fn();
      renderApp({
        props: {
          onSubmit,
        },
      });
      const searchButton = screen.getByRole('button', {
        name: 'Search',
      });
      expect(searchButton).toBeInTheDocument();
      const clearButton = screen.getByRole('button', {
        name: 'Clear',
      });
      expect(clearButton).toBeInTheDocument();
      userEvent.click(searchButton);
      await waitFor(() => {
        expect(onSubmit).toHaveBeenCalledTimes(1);
      });
    });
  });
});
