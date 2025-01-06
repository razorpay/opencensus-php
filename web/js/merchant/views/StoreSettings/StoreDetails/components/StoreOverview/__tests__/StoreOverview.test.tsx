import React from 'react';

import StoreOverview from 'merchant/views/StoreSettings/StoreDetails/components/StoreOverview';
import { FETCHED_STORE_INFO } from 'merchant/views/StoreSettings/StoreDetails/components/DigitalBillingInfo/__tests__/mocks';
import { screen, render } from 'test-utils';

describe('StoreOverview', () => {
  test("should render 'StoreOverview' component as expected", () => {
    render(<StoreOverview fetchedStoreInfo={FETCHED_STORE_INFO} />);
    // Basic Details
    expect(screen.getByText('Details')).toBeInTheDocument();
    expect(screen.getByText('Store Details')).toBeInTheDocument();
    expect(screen.getByText('Custom Fields')).toBeInTheDocument();
    // Location Details
    expect(screen.getByText('Location Details')).toBeInTheDocument();
    // Store Contact Details
    expect(screen.getByText('Store Contact Details')).toBeInTheDocument();
  });
});
