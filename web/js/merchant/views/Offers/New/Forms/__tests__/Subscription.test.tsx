import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import SubscriptionOffersForm from '../Subscription';
const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const renderApp = () => {
  return render(
    <SubscriptionOffersForm values={{}} errors={{}} touched={{}} setFieldValue={jest.fn()} />,
  );
};

describe('Subscription Form', () => {
  it('should render subscription Form', async () => {
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Offer for Subscription')).toBeInTheDocument();
    });
  });

  it('should render all tabs of the form', async () => {
    renderApp();

    await waitFor(() => {
      expect(screen.getAllByText('Description').length).toBe(2);
      expect(screen.getByText('Discount type')).toBeInTheDocument();
      expect(screen.getByText('Applicable On')).toBeInTheDocument();
      expect(screen.getByText('Offer Validity')).toBeInTheDocument();
      expect(screen.getByText('Overview')).toBeInTheDocument();
    });
  });

  it('should disable the form if creation_terms_accepted is not "1"', async () => {
    const values = {
      type: 'instant',
      redemption_type: 'single',
      applicable_on: 'both',
      creation_terms_accepted: undefined,
    };

    render(
      <SubscriptionOffersForm
        values={values}
        isFormLocked={false}
        errors={{}}
        touched={{}}
        setFieldValue={jest.fn()}
      />,
    );

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Next' })).toBeDisabled();
    });
  });
});
