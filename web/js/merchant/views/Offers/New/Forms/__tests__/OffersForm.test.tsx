import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import OffersForm from '../Offers';
const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderApp = () => {
  return render(<OffersForm />);
};

describe('Offers Form', () => {
  it('should render count of description on page', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getAllByText('Description').length).toBe(2);
    });
  });

  it('should render section on offers form', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Discount type')).toBeInTheDocument();
      expect(screen.getByText('Applicable On')).toBeInTheDocument();
      expect(screen.getByText('Offer Validity')).toBeInTheDocument();
      expect(screen.getByText('Overview')).toBeInTheDocument();
    });
  });

  it('should disable next button if required data not filled', async () => {
    const formData = {
      description: {},
      discountType: {},
      applicableOn: {},
      offerValidity: {},
      creation_terms_accepted: '0',
    };

    render(<OffersForm formData={formData} isFormLocked={false} />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
    });
  });
});
