import React from 'react';
import { fireEvent, screen } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import SelectableOptionCard from '../SelectableOptionCard';
import { isMobileDevice } from '@libs/shared-utils';

// Mock the isMobileDevice function
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

describe('SelectableOptionCard', () => {
  const mockHandleClick = jest.fn();
  const defaultProps = {
    customTitle: <div>Test Title</div>,
    subTitle: 'Test Subtitle',
    cardImageUrl: 'test-image.png',
    handleClick: mockHandleClick,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false);
  });

  it('renders with correct content', () => {
    renderWithWrappers(<SelectableOptionCard {...defaultProps} />);

    expect(screen.getByTestId('card-subtitle')).toHaveTextContent('Test Subtitle');
    const imgElement = screen.getByTestId('card-image');
    expect(imgElement).toBeInTheDocument();
    expect(imgElement).toHaveAttribute('src', 'test-image.png');
  });

  it('applies correct padding based on device type - desktop', () => {
    (isMobileDevice as jest.Mock).mockReturnValue(false);

    renderWithWrappers(<SelectableOptionCard {...defaultProps} />);
    const card = screen.getByTestId('selectable-option-card');

    expect(card).toHaveStyle('padding: var(--spacing-7)');
  });

  it('applies correct padding based on device type - mobile', () => {
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    renderWithWrappers(<SelectableOptionCard {...defaultProps} />);
    const card = screen.getByTestId('selectable-option-card');

    expect(card).toHaveStyle('padding: var(--spacing-5)');
  });

  it('does not call handleClick when card is disabled', () => {
    renderWithWrappers(<SelectableOptionCard {...defaultProps} isDisabled={true} />);
    const card = screen.getByTestId('selectable-option-card');

    fireEvent.click(card);

    expect(mockHandleClick).not.toHaveBeenCalled();
  });

  it('applies disabled styling when isDisabled is true', () => {
    renderWithWrappers(<SelectableOptionCard {...defaultProps} isDisabled={true} />);
    const card = screen.getByTestId('selectable-option-card');

    expect(card).toHaveStyle('background-color: var(--surface-background-gray-subtle)');
  });

  it('applies default styling when isDisabled is false', () => {
    renderWithWrappers(<SelectableOptionCard {...defaultProps} isDisabled={false} />);
    const card = screen.getByTestId('selectable-option-card');

    expect(card).toHaveStyle('background-color: var(--surface-background-gray-intense)');
  });

  it('sets correct image size and style', () => {
    renderWithWrappers(<SelectableOptionCard {...defaultProps} />);
    const imgElement = screen.getByTestId('card-image');

    expect(imgElement).toHaveStyle({
      width: '50px',
      height: '50px',
      objectFit: 'contain',
    });
  });
});
