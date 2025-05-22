import React from 'react';
import { fireEvent, screen } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import ExploreAllProducts from '../ExploreAllProducts';
import { ALL_PRODUCTS } from '@FTUX/constants/products';

// Mock the window.open function
const mockOpen = jest.fn();
Object.defineProperty(window, 'open', {
  writable: true,
  value: mockOpen,
});

// Mock the isMobileDevice utility
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn().mockReturnValue(false),
}));

describe('ExploreAllProducts', () => {
  const mockOnDismiss = jest.fn();
  const mockHandleMoveToCategories = jest.fn();

  const defaultProps = {
    onDismiss: mockOnDismiss,
    handleMoveToCategories: mockHandleMoveToCategories,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders modal with correct title and subtitle', () => {
    renderWithWrappers(<ExploreAllProducts {...defaultProps} />);

    expect(screen.getByText('Explore all products')).toBeInTheDocument();
    expect(screen.getByText('Select the product best for your needs')).toBeInTheDocument();
  });

  it('renders all products correctly', () => {
    renderWithWrappers(<ExploreAllProducts {...defaultProps} />);

    ALL_PRODUCTS.forEach((product) => {
      expect(screen.getByText(product.title)).toBeInTheDocument();
      expect(screen.getByText(product.description)).toBeInTheDocument();
    });
  });

  it('calls handleMoveToCategories when "Suggest the right product" button is clicked', () => {
    renderWithWrappers(<ExploreAllProducts {...defaultProps} />);

    // Click the button
    fireEvent.click(screen.getByText('Suggest the right product'));

    // Verify handleMoveToCategories was called
    expect(mockHandleMoveToCategories).toHaveBeenCalled();
  });
});
