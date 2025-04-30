import React from 'react';
import { DotIcon, ArrowRightIcon } from '@razorpay/blade/components';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import PitchProducts from '../index';

const mockProducts = [
  {
    tagIcon: DotIcon,
    tagText: 'Product 1',
    title: 'Product Title 1',
    description: 'Product Description 1',
    linkIcon: ArrowRightIcon,
    linkText: 'Learn More 1',
    handleClick: jest.fn(),
    image: 'product-image-1.jpg',
  },
  {
    tagIcon: DotIcon,
    tagText: 'Product 2',
    title: 'Product Title 2',
    description: 'Product Description 2',
    linkIcon: ArrowRightIcon,
    linkText: 'Learn More 2',
    handleClick: jest.fn(),
    image: 'product-image-2.jpg',
  },
];

const mockProps = {
  title: 'Products Section',
  subtitle: 'Explore our products',
  products: mockProducts,
};

describe('PitchProducts Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('renders with section title and subtitle', () => {
    renderWithWrappers(<PitchProducts {...mockProps} />);

    expect(screen.getByText('Products Section')).toBeInTheDocument();
    expect(screen.getByText('Explore our products')).toBeInTheDocument();
  });

  test('renders all product cards correctly', () => {
    renderWithWrappers(<PitchProducts {...mockProps} />);

    // Check if all products are rendered
    expect(screen.getByText('Product 1')).toBeInTheDocument();
    expect(screen.getByText('Product Title 1')).toBeInTheDocument();
    expect(screen.getByText('Product Description 1')).toBeInTheDocument();
    expect(screen.getByText('Learn More 1')).toBeInTheDocument();

    expect(screen.getByText('Product 2')).toBeInTheDocument();
    expect(screen.getByText('Product Title 2')).toBeInTheDocument();
    expect(screen.getByText('Product Description 2')).toBeInTheDocument();
    expect(screen.getByText('Learn More 2')).toBeInTheDocument();
  });

  test('calls handleClick when product link is clicked', () => {
    renderWithWrappers(<PitchProducts {...mockProps} />);

    const firstProductLink = screen.getByText('Learn More 1');
    firstProductLink.click();
    expect(mockProducts[0].handleClick).toHaveBeenCalledTimes(1);

    const secondProductLink = screen.getByText('Learn More 2');
    secondProductLink.click();
    expect(mockProducts[1].handleClick).toHaveBeenCalledTimes(1);
  });

  test('renders with different prop values', () => {
    const customProps = {
      title: 'Custom Section',
      subtitle: 'Custom subtitle',
      products: [
        {
          ...mockProducts[0],
          tagText: 'Custom Tag',
          title: 'Custom Title',
          description: 'Custom Description',
          linkText: 'Custom Link',
        },
      ],
    };

    renderWithWrappers(<PitchProducts {...customProps} />);

    expect(screen.getByText('Custom Section')).toBeInTheDocument();
    expect(screen.getByText('Custom subtitle')).toBeInTheDocument();
    expect(screen.getByText('Custom Tag')).toBeInTheDocument();
    expect(screen.getByText('Custom Title')).toBeInTheDocument();
    expect(screen.getByText('Custom Description')).toBeInTheDocument();
    expect(screen.getByText('Custom Link')).toBeInTheDocument();
  });

  test('handles empty products array', () => {
    const emptyProductsProps = {
      ...mockProps,
      products: [],
    };

    renderWithWrappers(<PitchProducts {...emptyProductsProps} />);

    expect(screen.getByText('Products Section')).toBeInTheDocument();
    expect(screen.getByText('Explore our products')).toBeInTheDocument();

    // Check that no product cards are rendered
    expect(screen.queryByText('Product 1')).not.toBeInTheDocument();
    expect(screen.queryByText('Product 2')).not.toBeInTheDocument();
  });
});
