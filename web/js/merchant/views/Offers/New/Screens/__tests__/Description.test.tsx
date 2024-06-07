import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import Description, {
  validateDiscountType,
  validateDisplayText,
  validateName,
  validateTerms,
} from '../Description';

const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('Description on form', () => {
  const formData = {
    name: 'New Year Sale',
    display_text: '10% off on all HDFC Debit Cards',
    terms: 'Terms and conditions for offer',
    type: 'Cashback',
  };
  it('should render input fields with correct labels and placeholders', () => {
    render(<Description formData={formData} isFormLocked={false} hideType={false} />);

    expect(screen.getByText('Offer Name')).toBeInTheDocument();
    expect(
      screen.getByPlaceholderText('Example: New Year Sale (This name appears on your dashboard)'),
    ).toBeInTheDocument();

    expect(screen.getByText('Display Text')).toBeInTheDocument();
    expect(
      screen.getByPlaceholderText(
        '10% off on all HDFC Debit Cards (This appears on checkout for your customers)',
      ),
    ).toBeInTheDocument();

    expect(screen.getByText('Terms')).toBeInTheDocument();
    expect(screen.getByPlaceholderText('Terms and conditions for offer')).toBeInTheDocument();
  });

  it('should display validation errors for empty input fields', async () => {
    render(<Description formData={formData} isFormLocked={false} hideType={false} />);
    await waitFor(() => {
      expect(screen.getByText('Please fill out this field')).toBeInTheDocument();
    });
  });
});

describe('Validation Functions', () => {
  describe('validateName', () => {
    it('should return error message if name is too short', () => {
      expect(validateName('abc')).toBe('Short name should be at least of 4 characters');
    });

    it('should return undefined if name is valid', () => {
      expect(validateName('Valid Name')).toBeUndefined();
    });

    it('should return error message if name is empty', () => {
      expect(validateName('')).toBe('Short name should be at least of 4 characters');
    });
  });

  describe('validateDisplayText', () => {
    it('should return error message if display text is too short', () => {
      expect(validateDisplayText('abc')).toBe(
        'Short description should be at least of 4 characters',
      );
    });

    it('should return undefined if display text is valid', () => {
      expect(validateDisplayText('Valid Display Text')).toBeUndefined();
    });

    it('should return error message if display text is empty', () => {
      expect(validateDisplayText('')).toBe('Short description should be at least of 4 characters');
    });
  });

  describe('validateTerms', () => {
    it('should return error message if terms are too short', () => {
      expect(validateTerms('abc')).toBe('Offer terms should contain at least of 4 characters');
    });

    it('should return undefined if terms are valid', () => {
      expect(validateTerms('Valid Terms')).toBeUndefined();
    });

    it('should return error message if terms are empty', () => {
      expect(validateTerms('')).toBe('Offer terms should contain at least of 4 characters');
    });
  });

  describe('validateDiscountType', () => {
    it('should return error message if discount type is empty', () => {
      expect(validateDiscountType('')).toBe('Please select a discount type');
    });

    it('should return undefined if discount type is valid', () => {
      expect(validateDiscountType('instant')).toBeUndefined();
    });
  });
});
