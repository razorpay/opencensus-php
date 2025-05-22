import React from 'react';
import { fireEvent, screen } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import ProductRecommendations from '../ProductRecommendations';
import { PRODUCT_TYPES, AVAILABLE_PRODUCTS_MAP } from '@FTUX/constants/products';
import { ArrowUpRightIcon } from '@razorpay/blade/components';

// Mock the shared utils
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

// Import after mocking
import { isMobileDevice } from '@libs/shared-utils';

// Mock the shared-ui
jest.mock('@libs/shared-ui', () => ({
  useModalComponents: jest.fn().mockReturnValue({
    Modal: ({
      children,
      isOpen,
      onDismiss,
    }: {
      children: React.ReactNode;
      isOpen: boolean;
      onDismiss: () => void;
    }) => (
      <div data-testid="modal" data-open={isOpen} onClick={onDismiss}>
        {children}
      </div>
    ),
    ModalHeader: ({ title, subtitle }: { title: string; subtitle: string }) => (
      <div data-testid="modal-header">
        <h2>{title}</h2>
        <p>{subtitle}</p>
      </div>
    ),
    ModalBody: ({ children }: { children: React.ReactNode }) => (
      <div data-testid="modal-body">{children}</div>
    ),
    ModalFooter: ({ children }: { children: React.ReactNode }) => (
      <div data-testid="modal-footer">{children}</div>
    ),
  }),
}));

describe('ProductRecommendations', () => {
  const mockOnDismiss = jest.fn();
  const mockOnBackClick = jest.fn();
  const mockOnExploreAllProducts = jest.fn();

  const defaultProps = {
    productsList: [PRODUCT_TYPES.PAYMENT_LINKS, PRODUCT_TYPES.PAYMENT_PAGES],
    onDismiss: mockOnDismiss,
    onBackClick: mockOnBackClick,
    onExploreAllProducts: mockOnExploreAllProducts,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false); // Default to desktop
  });

  it('renders modal with correct title and subtitle', () => {
    renderWithWrappers(<ProductRecommendations {...defaultProps} />);

    expect(screen.getByText('Recommendation for you')).toBeInTheDocument();
    expect(
      screen.getByText("If this isn't a right fit, you can explore all other products"),
    ).toBeInTheDocument();
  });

  it('renders the product cards correctly', () => {
    renderWithWrappers(<ProductRecommendations {...defaultProps} />);

    // Check that each product in the list is rendered
    defaultProps.productsList.forEach((product) => {
      const productDetails = AVAILABLE_PRODUCTS_MAP[product];
      expect(screen.getByText(productDetails.title)).toBeInTheDocument();
      expect(screen.getByText(productDetails.description)).toBeInTheDocument();
    });
  });

  it('calls onBackClick when "Check for a different use-case" button is clicked', () => {
    renderWithWrappers(<ProductRecommendations {...defaultProps} />);

    const backButton = screen.getByText('Check for a different use-case');
    fireEvent.click(backButton);

    expect(mockOnBackClick).toHaveBeenCalledTimes(1);
  });

  it('calls onExploreAllProducts when "Explore 10+ Other Products" button is clicked', () => {
    renderWithWrappers(<ProductRecommendations {...defaultProps} />);

    const exploreButton = screen.getByText('Explore 10+ Other Products');
    fireEvent.click(exploreButton);

    expect(mockOnExploreAllProducts).toHaveBeenCalledTimes(1);
  });

  it('calls onDismiss when modal is dismissed', () => {
    renderWithWrappers(<ProductRecommendations {...defaultProps} />);

    const modal = screen.getByTestId('modal');
    fireEvent.click(modal);

    expect(mockOnDismiss).toHaveBeenCalledTimes(1);
  });
});
