import React from 'react';
import { DotIcon } from '@razorpay/blade/components';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import ImagePostCard from '../ImagePostCard';

// Mock isMobileDevice utility
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

// Import after mocking
import { isMobileDevice } from '@libs/shared-utils';

const mockProps = {
  tagIcon: DotIcon,
  tagText: 'Test Tag',
  image: 'test-image.jpg',
  title: 'Test Title',
  description: 'Test Description',
  linkIcon: DotIcon,
  linkText: 'Learn More',
  handleClick: jest.fn(),
};

describe('ImagePostCard Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false); // Default to desktop
  });

  test('renders with all required props', () => {
    renderWithWrappers(<ImagePostCard {...mockProps} />);

    // Check if content is rendered
    expect(screen.getByText('Test Tag')).toBeInTheDocument();
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
    expect(screen.getByText('Learn More')).toBeInTheDocument();

    // Check if image is rendered with correct props
    const image = screen.getByAltText('Test Title');
    expect(image).toBeInTheDocument();
    expect(image).toHaveAttribute('src', 'test-image.jpg');
  });

  test('calls handleClick when link is clicked', () => {
    renderWithWrappers(<ImagePostCard {...mockProps} />);

    const link = screen.getByText('Learn More');
    link.click();

    expect(mockProps.handleClick).toHaveBeenCalledTimes(1);
  });

  test('renders with different props values', () => {
    const customProps = {
      ...mockProps,
      tagText: 'Custom Tag',
      title: 'Custom Title',
      description: 'Custom Description',
      linkText: 'Custom Link',
    };

    renderWithWrappers(<ImagePostCard {...customProps} />);

    expect(screen.getByText('Custom Tag')).toBeInTheDocument();
    expect(screen.getByText('Custom Title')).toBeInTheDocument();
    expect(screen.getByText('Custom Description')).toBeInTheDocument();
    expect(screen.getByText('Custom Link')).toBeInTheDocument();
  });

  test('renders correctly on mobile device', () => {
    // Mock mobile device
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    const { container } = renderWithWrappers(<ImagePostCard {...mockProps} />);

    // Main assertions
    expect(screen.getByText('Test Tag')).toBeInTheDocument();
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
  });
});
