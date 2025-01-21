import React from 'react';

import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import SalesCard from '@apps/digital-bills/src/views/BillsView/containers/BillsOverviewContainer/components/SalesCard';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';

const setSelectedOverviewCategory = jest.fn();
const expandGraph = jest.fn();
const SALES_CARD_PROPS = {
  setSelectedOverviewCategory,
  expandGraph,
  isLoading: false,
};

describe('SalesCard', () => {
  test('should render SalesCard component with heading as "Total Sales" and value as 10000000', () => {
    const { getByText } = renderWithWrappers(
      <SalesCard {...SALES_CARD_PROPS} heading="Total Sales" value={10000000} />,
    );
    expect(getByText('Total Sales')).toBeInTheDocument();
    expect(getByText('10M')).toBeInTheDocument();
  });

  test('should render SalesCard component with heading as "Average Sales" and value as 100000', () => {
    const { getByText } = renderWithWrappers(
      <SalesCard {...SALES_CARD_PROPS} heading="Average Sales" value={100000} />,
    );
    expect(getByText('Average Sales')).toBeInTheDocument();
    expect(getByText('100K')).toBeInTheDocument();
  });

  test('should calls setSelectedOverviewCategory and expandGraph when SalesCard (Total Sales) is clicked', async () => {
    const { getByRole } = renderWithWrappers(
      <SalesCard {...SALES_CARD_PROPS} heading="Total Sales" value={10000000} />,
    );
    await userEvent.click(getByRole('button', { name: /Total Sales Card/i }));
    expect(setSelectedOverviewCategory).toHaveBeenCalled();
    expect(expandGraph).toHaveBeenCalled();
  });
});
