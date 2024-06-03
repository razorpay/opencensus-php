import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import Description from '../Description';

const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('Description on form', () => {
  it('should render description form', async () => {
    const formData = {
      name: 'New Year Sale',
      display_text: '10% off on all HDFC Debit Cards',
      terms: 'Terms and conditions for offer',
      type: 'Cashback',
    };

    render(<Description formData={formData} isFormLocked={false} hideType={false} />);

    await waitFor(() => {
      expect(screen.getByText('Offer Name')).toBeInTheDocument();
      expect(screen.getByText('Display Text')).toBeInTheDocument();
      expect(screen.getByText('Terms')).toBeInTheDocument();
    });
  });
});
