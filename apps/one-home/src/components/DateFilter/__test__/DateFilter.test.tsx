import React from 'react';
import { customRender, screen } from '@apps/one-home/src/services/test/test-utils';
import DateFilter from '../DateFilter';
import { FilterOption } from '../types';

// Mock filter options
const mockOptions: FilterOption[] = [
  { key: 'yesterday', value: 'Yesterday' },
  { key: 'last7days', value: 'This Week' },
  { key: 'last30Days', value: 'Last 30 Days' },
];

describe('DateFilter Component', () => {
  const mockOnChange = jest.fn();

  test('renders the DateFilter component with default options', () => {
    customRender(
      <DateFilter
        options={mockOptions}
        selected="yesterday"
        onChange={mockOnChange}
        isDisabled={false}
      />,
    );

    const allElements = screen.getAllByText('Yesterday');

    expect(allElements.length).toBeGreaterThan(0);
    expect(allElements[0]).toBeInTheDocument();
  });
});
