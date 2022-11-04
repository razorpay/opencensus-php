import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as fixtures from 'merchant/views/Settlements/RouteOndemandSettlements/__test__/mocks/fixtures';
import RouteSettlementListFilter from 'merchant/views/Settlements/RouteOndemandSettlements/components/RouteSettlementListFilter';
import { render, screen, waitFor } from 'test-utils';
import userEvent from '@testing-library/user-event';

describe('RouteSettlementListFilter', () => {
  const defaultProps = {
    form: 'instantRouteSettlementListFilter',
    count: 25,
  };

  const App = (props) => <RouteSettlementListFilter {...defaultProps} {...props} />;

  test('should render route settlement filter form', () => {
    render(<App />);
    expect(screen.getByRole('form')).toBeInTheDocument();
    expect(screen.getByRole('form')).toHaveAttribute('name', defaultProps.form);
    expect(screen.getAllByRole('form')).toHaveLength(1);
  });

  describe('FilterFields', () => {
    test('should render settlement id filter', async () => {
      render(<App />);
      expect(screen.getByText('Settlement Id')).toBeInTheDocument();
      const field = screen.getByRole('textbox');
      expect(field).toBeInTheDocument();
      expect(field).toHaveAttribute('name', 'id');

      const settlementId = 'AWEPL77489W';
      userEvent.type(field, settlementId);
      await waitFor(() => {
        expect(field).toHaveAttribute('value', settlementId);
      });
    });

    test('should render settlement status dropdown filter', async () => {
      render(<App />);
      expect(screen.getByText('Status')).toBeInTheDocument();
      const field = screen.getByRole('combobox');
      expect(field).toBeInTheDocument();
      expect(field).toHaveAttribute('name', 'status');

      const optionToSelect = fixtures.options[2];
      userEvent.selectOptions(field, optionToSelect.value);
      await waitFor(() => {
        expect(screen.getByRole('option', { name: optionToSelect.label }).selected).toBe(true);
        fixtures.options.forEach((each) => {
          each.label !== optionToSelect.label &&
            expect(screen.getByRole('option', { name: each.label }).selected).toBe(false);
          expect(screen.getByText(each.label)).toBeInTheDocument();
        });
      });
    });

    test('should render settlement item count within range', () => {
      render(<App />);
      expect(screen.getByText('Count')).toBeInTheDocument();
      const field = screen.getByRole('spinbutton');
      expect(field).toBeInTheDocument();
      expect(field).toHaveAttribute('name', 'count');
      expect(field).toHaveAttribute('type', 'number');
      expect(field).toHaveAttribute('min', '1');
      expect(field).toHaveAttribute('max', '100');
    });

    test('should render submit and clear filter button', async () => {
      const onSubmit = jest.fn();
      render(<App onSubmit={onSubmit} />);
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
