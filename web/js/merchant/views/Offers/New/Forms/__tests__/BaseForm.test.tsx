import React from 'react';
import { fireEvent, waitFor, screen } from '@testing-library/react';

import { getCurrency } from 'common/ui/Amount';
import { render } from 'test-utils';

import BaseForm from '../BaseForm';

jest.mock('merchant/views/Offers/New/Screens/NoCostEMI/helpers/helper', () => ({
  isLowCostExperimentEnabled: jest.fn().mockReturnValue(true),
}));

jest.mock('common/ui/Amount', () => ({
  getCurrency: jest.fn(),
}));

describe('BaseForm Component', () => {
  let props;

  beforeEach(() => {
    props = {
      onSubmit: jest.fn(),
      onClose: jest.fn(),
      splitz: {
        abExperiments: {
          Low_cost_offer: {},
        },
      },
      values: {},
      errors: {},
      touched: {},
    };
  });

  beforeAll(() => {
    (getCurrency as jest.Mock).mockReturnValue({ symbol: 'INR' }); // Ensure this matches the expected currency symbol
  });

  it('currencySymbol getter returns correct symbol', () => {
    render(<BaseForm {...props} />);
    expect(BaseForm.prototype.currencySymbol).toBe('INR');
  });

  it('should render the BaseForm component correctly', () => {
    const { getByText, getByRole } = render(<BaseForm {...props} />);
    expect(getByText('Create an Offer')).toBeInTheDocument();
    expect(screen.getAllByText('Description')).toHaveLength(2);
    expect(getByRole('button', { name: /next/i })).toBeDisabled();
  });

  it('should handle field changes correctly', () => {
    const { getAllByText, getByText } = render(<BaseForm {...props} />);
    const descriptionElements = getAllByText('Description');
    const descriptionInput = descriptionElements[0];
    fireEvent.change(descriptionInput, {
      target: { name: 'description', value: 'New description' },
    });

    const submitButton = getByText('Create an Offer');
    fireEvent.click(submitButton);

    waitFor(() => {
      expect(props.onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          description: 'New description',
        }),
      );
    });
  });

  it('should disable the Next button when invalid input is provided', () => {
    const { getByRole, getByTestId } = render(<BaseForm {...props} />);
    const componentWrapper = getByTestId('component-wrapper');
    const input = document.createElement('input');

    input.setAttribute('name', 'iins');
    componentWrapper.appendChild(input);

    fireEvent.change(input, { target: { value: 'invalid, input, values' } });

    fireEvent.blur(input);

    expect(getByRole('button', { name: /next/i })).toBeDisabled();
  });

  it('should handle onFieldChange correctly', () => {
    const { getByTestId } = render(<BaseForm {...props} />);
    const componentWrapper = getByTestId('component-wrapper');
    const input = document.createElement('input');

    input.setAttribute('name', 'iins');
    componentWrapper.appendChild(input);

    fireEvent.change(input, { target: { value: '123456, 654321, abcdef' } });
  });
});
