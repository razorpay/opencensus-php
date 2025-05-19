import React from 'react';
import { render, screen } from 'test-utils';
import { DateCell, BaseCell } from 'merchant/views/MagicCheckout/SSODashboard/components/Table';

describe('Table Cells', () => {
  describe('DateCell', () => {
    it('formats date correctly', () => {
      const date = '2024-01-01T12:00:00Z';
      render(<DateCell value={date} />);
      expect(screen.getByText('01/01/2024 12:00 PM')).toBeInTheDocument();
    });

    it('handles invalid date', () => {
      render(<DateCell value="invalid-date" />);
      expect(screen.getByText('Invalid date')).toBeInTheDocument();
    });
  });

  describe('BaseCell', () => {
    it('renders value correctly', () => {
      render(<BaseCell value="Test Value" />);
      expect(screen.getByText('Test Value')).toBeInTheDocument();
    });

    it('handles empty value', () => {
      render(<BaseCell value={null} />);
      expect(screen.getByText('-')).toBeInTheDocument();
    });
  });

});
