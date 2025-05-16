import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import RekycBanner from 'merchant/components/SelfServeRekyc/components/RekycBanner';
import { AlertCircleIcon, IconColors, HeadingProps } from '@razorpay/blade/components';
import { FeedbackColors, RekycBannerInfo, RekycStepInfo } from 'merchant/components/SelfServeRekyc/types';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn().mockReturnValue(false)
}));

jest.mock('common/utils/rzp-utils', () => ({
  analyticsTrack: jest.fn(),
  openTicketModal: jest.fn(),
  getCommonAnalyticsProperties: jest.fn()
}));

describe('RekycBanner', () => {
  const mockBannerDetails: RekycBannerInfo = {
    heading: 'Test Heading',
    description: 'Test Description',
    IconComponent: AlertCircleIcon,
    showChip: true,
    iconColor: 'feedback.icon.negative.intense' as IconColors,
    headingColor: 'surface.text.gray.normal' as HeadingProps['color'],
    iconBackgroundColor: 'feedback.background.negative.subtle',
    ctaText: 'Update KYC',
    dynamicDate: '',
    chipType: 'negative' as FeedbackColors,
    hideActionables: false,
    hideTimeline: false,
    dynamicDescription: false,
    showImageInBanner: false,
    imageSrc: '',
    hideIcon: false
  };

  const mockStepInfo: RekycStepInfo = {
    heading: 'Step 1',
    IconComponent: AlertCircleIcon,
    iconColor: 'feedback.icon.negative.intense' as IconColors,
    iconBackgroundColor: 'feedback.background.negative.subtle',
    headingColor: 'surface.text.gray.normal' as HeadingProps['color'],
    textContent: '1',
    textColor: 'surface.text.gray.normal' as HeadingProps['color'],
    completed: false,
    currentStep: true,
    borderColor: 'surface.border.primary.normal'
  };

  const defaultProps = {
    bannerDetails: mockBannerDetails,
    deadlineDate: '31 Dec 2024',
    daysFromDeadline: 7,
    stepsInfo: mockStepInfo,
    rekycUrl: 'https://example.com',
    rekycStatus: 'pending' as const
  };

  it('renders basic banner content correctly', () => {
    render(<RekycBanner {...defaultProps} />);

    expect(screen.getByText('Test Heading')).toBeInTheDocument();
    expect(screen.getByText('Test Description')).toBeInTheDocument();
    expect(screen.getByText('Update KYC')).toBeInTheDocument();
    expect(screen.getByText('Why')).toBeInTheDocument();
    expect(screen.getByText('7 days left')).toBeInTheDocument();
  });

  it('handles visibility toggles correctly', () => {
    render(<RekycBanner {...defaultProps} bannerDetails={{
      ...mockBannerDetails,
      showChip: false,
      hideTimeline: true,
      hideActionables: true
    }} />);

    expect(screen.queryByText('7 days left')).not.toBeInTheDocument();
    expect(screen.queryByText('Update KYC')).not.toBeInTheDocument();
    expect(screen.queryByText('Why')).not.toBeInTheDocument();
  });

  it('handles dynamic content correctly', () => {
    const dynamicHeading = (date: string) => `Dynamic Heading ${date}`;
    const dynamicDescription = (date: string) => `Dynamic Description ${date}`;

    render(<RekycBanner {...defaultProps} bannerDetails={{
      ...mockBannerDetails,
      heading: dynamicHeading,
      description: dynamicDescription,
      dynamicDate: 'true',
      dynamicDescription: true
    }} />);

    expect(screen.getByText('Dynamic Heading 31 Dec 2024')).toBeInTheDocument();
    expect(screen.getByText('Dynamic Description 31 Dec 2024')).toBeInTheDocument();
  });

  it('renders banner image when specified', () => {
    render(<RekycBanner {...defaultProps} bannerDetails={{
      ...mockBannerDetails,
      showImageInBanner: true,
      imageSrc: 'test-image.png'
    }} />);

    const image = screen.getByAltText('banner-img');
    expect(image).toBeInTheDocument();
    expect(image).toHaveAttribute('src', 'test-image.png');
  });
}); 