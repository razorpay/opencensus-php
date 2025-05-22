import React from 'react';
import { fireEvent, screen } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import ProductCategories from '../ProductCategories';
import { PRODUCT_CATEGORIES } from '@FTUX/constants/products';

// Mock the isMobileDevice utility
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn().mockReturnValue(false),
}));

describe('ProductCategories', () => {
  const mockOnDismiss = jest.fn();
  const mockMakeSelection = jest.fn();

  const defaultProps = {
    onDismiss: mockOnDismiss,
    makeSelection: mockMakeSelection,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders modal with correct title and subtitle', () => {
    renderWithWrappers(<ProductCategories {...defaultProps} />);

    expect(screen.getByText('Select your use-case')).toBeInTheDocument();
    expect(
      screen.getByText('Based on your selection we will recommend the right product for you'),
    ).toBeInTheDocument();
  });

  it('renders all product categories correctly', () => {
    renderWithWrappers(<ProductCategories {...defaultProps} />);

    PRODUCT_CATEGORIES.forEach((product) => {
      expect(screen.getByText(product.description)).toBeInTheDocument();
    });
  });

  it('calls onDismiss when "Explore 10+ Other Products" button is clicked', () => {
    renderWithWrappers(<ProductCategories {...defaultProps} />);

    // Click the explore button
    fireEvent.click(screen.getByText('Explore 10+ Other Products'));

    // Verify onDismiss was called
    expect(mockOnDismiss).toHaveBeenCalled();
  });
});
