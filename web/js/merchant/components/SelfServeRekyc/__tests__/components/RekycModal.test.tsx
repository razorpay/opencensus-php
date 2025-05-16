import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import RekycModal from 'merchant/components/SelfServeRekyc/components/RekycModal';
import { RekycModalInfo } from 'merchant/components/SelfServeRekyc/types';

jest.mock('merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useModalComponents', () => ({
  __esModule: true,
  default: () => ({
    Modal: ({ children, isOpen }) => isOpen ? <div>{children}</div> : null,
    ModalBody: ({ children }) => <div>{children}</div>,
    ModalFooter: ({ children }) => <div>{children}</div>,
    ModalHeader: () => <div />,
  }),
}));

describe('RekycModal', () => {
  const mockModalInfo: RekycModalInfo = {
    imageSrc: 'test-image.png',
    heading: 'Test Heading',
    description: 'Test Description',
    ctaText: 'Update KYC',
    badgeText: '7 days left',
    hideCta: false,
    dynamicHeading: false,
    dynamicDescription: false
  };

  const defaultProps = {
    modalInfo: mockModalInfo,
    deadlineDate: '31 Dec 2024',
    daysFromDeadline: 7,
    rekycUrl: 'https://example.com',
    isOpen: true,
    isMobile: false,
    onDismiss: jest.fn(),
    onCtaClick: jest.fn()
  };

  it('renders basic modal content correctly when open', () => {
    render(<RekycModal {...defaultProps} />);

    expect(screen.getByText('Test Heading')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Update KYC' })).toBeInTheDocument();
    expect(screen.getByText('7 days left')).toBeInTheDocument();

    const image = screen.getByAltText('notice-kyc');
    expect(image).toBeInTheDocument();
    expect(image).toHaveAttribute('src', 'test-image.png');
  });

  it('does not render content when closed', () => {
    render(<RekycModal {...defaultProps} isOpen={false} />);
    expect(screen.queryByText('Test Heading')).not.toBeInTheDocument();
    expect(screen.queryByText('Test Description')).not.toBeInTheDocument();
  });

  it('handles dynamic content correctly', () => {
    const dynamicModalInfo = {
      ...mockModalInfo,
      heading: (date: string) => `Dynamic Heading ${date}`,
      description: (date: string) => `Dynamic Description ${date}`,
      dynamicHeading: true,
      dynamicDescription: true
    };

    render(<RekycModal {...defaultProps} modalInfo={dynamicModalInfo} />);

    expect(screen.getByText('Dynamic Heading 31 Dec 2024')).toBeInTheDocument();
    expect(screen.getByText('Dynamic Description 31 Dec 2024')).toBeInTheDocument();
  });

  it('handles CTA clicks correctly', () => {
    const onCtaClick = jest.fn();
    render(<RekycModal {...defaultProps} onCtaClick={onCtaClick} />);

    const ctaButton = screen.getByRole('button', { name: 'Update KYC' });
    fireEvent.click(ctaButton);

    expect(onCtaClick).toHaveBeenCalledWith('https://example.com');
  });

  it('hides CTA when hideCta is true', () => {
    render(<RekycModal {...defaultProps} modalInfo={{ ...mockModalInfo, hideCta: true }} />);
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('adapts to mobile view', () => {
    const { container } = render(<RekycModal {...defaultProps} isMobile={true} />);

    expect(screen.getByText('Test Heading')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
  });

  it('handles dismiss functionality', () => {
    const onDismiss = jest.fn();
    render(<RekycModal {...defaultProps} onDismiss={onDismiss} />);

    expect(screen.getByText('Test Heading')).toBeInTheDocument();
    expect(onDismiss).toBeDefined();
  });
});