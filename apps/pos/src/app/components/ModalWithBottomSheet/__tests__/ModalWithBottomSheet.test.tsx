import React from 'react';
import ModalWithBottomSheet from '../ModalWithBottomSheet';
import { render, screen } from 'apps/pos/src/services/test/test-utils';
import * as useScreenHooks from 'apps/pos/src/app/utils/hooks/useScreen';

describe('<ModalWithBottomSheet/>', () => {
  test('should render modal with bottom sheet on screen for desktop', async () => {
    const props = {
      isOpen: true,
      headerText: 'Merchant Details',
      onDismiss: jest.fn(),
      content: <div>Some Random Test Content</div>,
    };
    render(<ModalWithBottomSheet {...props} />);
    expect(screen.getByText('Merchant Details')).toBeInTheDocument();
    expect(screen.getByText('Some Random Test Content')).toBeInTheDocument();
  });

  test('should render modal with bottom sheet on screen for mobile', async () => {
    const useScreenSpy = jest.spyOn(useScreenHooks, 'useScreen');
    useScreenSpy.mockReturnValue({ isMobile: true });
    const props = {
      isOpen: true,
      headerText: 'Merchant Details',
      onDismiss: jest.fn(),
      content: <div>Some Random Test Content</div>,
    };
    render(<ModalWithBottomSheet {...props} />);
    expect(screen.getByText('Merchant Details')).toBeInTheDocument();
    expect(screen.getByText('Some Random Test Content')).toBeInTheDocument();
  });

  test('should render modal with bottom sheet on screen without header for mobile', async () => {
    const useScreenSpy = jest.spyOn(useScreenHooks, 'useScreen');
    useScreenSpy.mockReturnValue({ isMobile: true });
    const props = {
      isOpen: true,
      onDismiss: jest.fn(),
      content: <div>Some Random Test Content</div>,
      footer: <div>Some Random Test Footer</div>,
    };
    render(<ModalWithBottomSheet {...props} />);
    expect(screen.queryByText('Merchant Details')).toBeNull();
    expect(screen.getByText('Some Random Test Content')).toBeInTheDocument();
    expect(screen.getByText('Some Random Test Footer')).toBeInTheDocument();
  });
});
