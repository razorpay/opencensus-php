import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import OfferTypeSelector from '../OfferTypeSelector.js';

const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderApp = () => {
  return render(<OfferTypeSelector />);
};
describe('Offers Type Selector', () => {
  it('should render offers type select', async () => {
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Discounts & Cash Backs')).toBeInTheDocument();
    });
    expect(screen.getAllByText('Create Now')).toHaveLength(2);
  });
});
