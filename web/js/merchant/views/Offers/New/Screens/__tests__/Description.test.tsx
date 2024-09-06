import 'react-dates/initialize';
import React from 'react';
import { screen, waitFor } from '@testing-library/react';
import { render } from 'test-utils';

import Description, { validateDisplayText, validateName, validateTerms } from '../Description';

const mockAbExperiments = { razorpay_offers: { variables: { result: 'on' } } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('Description on form', () => {
  const values = {
    name: 'New Year Sale',
    display_text: '10% off on all HDFC Debit Cards',
    terms: 'Terms and conditions for offer',
  };
  it('should render input fields with correct labels and placeholders', () => {
    render(<Description values={values} isFormLocked={false} errors={{}} touched={{}} />);

    // NOTE: Static text testing, not sure how much robustness
    // it provides to the application apart from code coverage
    expect(screen.getByText('Offer Name')).toBeInTheDocument();
    const inputElements = screen.getAllByRole('textbox');
    inputElements.forEach((inputElement, index) => {
      if (index === 0) {
        expect(inputElement).toHaveAttribute(
          'placeholder',
          'Example: New Year Sale (This name appears on your dashboard)',
        );
      } else if (index === 1) {
        expect(inputElement).toHaveAttribute(
          'placeholder',
          '10% off on all HDFC Debit Cards (This appears on checkout for your customers)',
        );
      } else if (index === 2) {
        expect(inputElement).toHaveAttribute('placeholder', 'Terms and conditions for offer');
      }
    });
    expect(screen.getByText('Display Text')).toBeInTheDocument();
    expect(screen.getByText('Terms')).toBeInTheDocument();
  });
});

describe('Validation Functions', () => {
  describe('validateName', () => {
    it('should return error message if name is too short', () => {
      expect(validateName('abc')).toBe('Short name should be at least of 4 characters');
    });

    it('should return false if name is valid', () => {
      expect(validateName('Valid Name')).toBe(false);
    });

    it('should return error message if name is empty', () => {
      expect(validateName('')).toBe('Please fill out this field');
    });
  });

  describe('validateDisplayText', () => {
    it('should return error message if display text is too short', () => {
      expect(validateDisplayText('abc')).toBe(
        'Short description should be at least of 4 characters',
      );
    });

    it('should return false if display text is valid', () => {
      expect(validateDisplayText('Valid Display Text')).toBe(false);
    });

    it('should return error message if display text is empty', () => {
      expect(validateDisplayText('')).toBe('Please fill out this field');
    });
  });

  describe('validateTerms', () => {
    it('should return error message if terms are too short', () => {
      expect(validateTerms('abc')).toBe('Offer terms should contain at least of 4 characters');
    });

    it('should return false if terms are valid', () => {
      expect(validateTerms('Valid Terms')).toBe(false);
    });

    it('should return error message if terms are empty', () => {
      expect(validateTerms('')).toBe('Please fill out this field');
    });
  });
});
