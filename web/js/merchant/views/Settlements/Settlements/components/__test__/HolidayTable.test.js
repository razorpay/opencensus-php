import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import HolidayTable from 'merchant/views/Settlements/Settlements/components/HolidayTable';
import { holidays } from 'merchant/views/Settlements/Settlements/components/__test__/mocks/fixtures/HolidayTable';

describe('HolidayTable.js', () => {
  const App = () => {
    return <HolidayTable items={holidays} />;
  };

  test('should render table tag', () => {
    render(<App />);
    expect(screen.queryByRole('table')).toBeInTheDocument();
  });

  test('should render columns correctly', () => {
    render(<App />);
    expect(screen.queryByText('Date')).toBeInTheDocument();
    expect(screen.queryByText('Name')).toBeInTheDocument();
  });

  test('should render table data correctly', () => {
    render(<App />);
    holidays.forEach((item) => {
      expect(screen.queryByText(item.description)).toBeInTheDocument();
    });
  });
});
