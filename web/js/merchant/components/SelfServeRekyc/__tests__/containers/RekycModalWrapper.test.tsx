import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import RekycModalWrapper from 'merchant/components/SelfServeRekyc/containers/RekycModalWrapper';
import { RekycModalInfo, RekycModalWrapperProps, RekycDetailsApiData } from 'merchant/components/SelfServeRekyc/types';
import * as analytics from '@libs/shared-utils';
import { SELF_SERVE_REKYC_HIDE_MODAL } from 'merchant/components/SelfServeRekyc/constants';

jest.mock('common/hooks/useMobile', () => ({
  useMobile: jest.fn().mockReturnValue(false)
}));

window.rzp_user = {};

const mockAnalyticsTrack = jest.spyOn(analytics, 'analyticsTrack');
const mockOpenTicketModal = jest.fn();
const mockOpenUrlInNewTab = jest.fn();
const mockGetCommonAnalyticsProperties = jest.fn().mockReturnValue({
  user_id: 'test_user',
  merchant_id: 'test_merchant'
});

jest.mock('common/utils/rzp-utils', () => ({
  openTicketModal: (...args: any[]) => mockOpenTicketModal(...args),
  getCommonAnalyticsProperties: (...args: any[]) => mockGetCommonAnalyticsProperties(...args),
  openUrlInNewTab: (...args: any[]) => mockOpenUrlInNewTab(...args)
}));

describe('RekycModalWrapper', () => {
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

  const defaultProps: RekycModalWrapperProps = {
    modalInfo: mockModalInfo,
    deadlineDate: '31 Dec 2024',
    daysFromDeadline: 7,
    rekycUrl: 'https://example.com',
    rekycStatus: 'pending' as RekycDetailsApiData['status']
  };

  beforeEach(() => {
    jest.clearAllMocks();
    localStorage.clear();
  });

  it('tracks modal display on mount', async () => {
    render(<RekycModalWrapper {...defaultProps} />);

    await waitFor(() => {
      expect(mockAnalyticsTrack).toHaveBeenCalledWith({
        objectName: 'self serve rekyc modal',
        actionName: 'displayed',
        screen: 'self serve rekyc modal',
        properties: {
          ...mockGetCommonAnalyticsProperties(),
          rekycStatus: 'pending',
          experimentName: 'self-serve-rekyc'
        }
      });
    });
  });

  it('handles modal dismissal with analytics and localStorage', async () => {
    render(<RekycModalWrapper {...defaultProps} />);

    const closeButton = screen.getByRole('button', { name: /close/i });
    await userEvent.click(closeButton);

    await waitFor(() => {
      expect(mockAnalyticsTrack).toHaveBeenCalledWith({
        objectName: 'self serve rekyc modal',
        actionName: 'closed',
        screen: 'self serve rekyc modal',
        properties: {
          ...mockGetCommonAnalyticsProperties(),
          rekycStatus: 'pending',
          experimentName: 'self-serve-rekyc'
        }
      });
      expect(localStorage.getItem(SELF_SERVE_REKYC_HIDE_MODAL)).toBe('true');
    });
  });

  it('handles KYC update click with regular URL', async () => {
    render(<RekycModalWrapper {...defaultProps} />);

    const updateButton = screen.getByRole('button', { name: 'Update KYC' });
    await userEvent.click(updateButton);

    await waitFor(() => {
      expect(mockAnalyticsTrack).toHaveBeenCalledWith({
        objectName: 'self serve rekyc modal cta',
        actionName: 'clicked',
        screen: 'self serve rekyc modal',
        properties: {
          ...mockGetCommonAnalyticsProperties(),
          rekycStatus: 'pending',
          experimentName: 'self-serve-rekyc',
          redirectionUrl: 'https://example.com',
          ctaText: 'Update KYC'
        }
      });
      expect(mockOpenUrlInNewTab).toHaveBeenCalledWith('https://example.com');
    });
  });

  it('handles support ticket creation when rekycUrl is contactSupport', async () => {
    render(<RekycModalWrapper {...defaultProps} rekycUrl="contactSupport" />);

    const updateButton = screen.getByRole('button', { name: 'Update KYC' });
    await userEvent.click(updateButton);

    await waitFor(() => {
      expect(mockOpenTicketModal).toHaveBeenCalledWith({
        subject: 'ReKYC',
        description: 'Please help me with the rekyc process.',
        tags: ['ReKYC']
      });
    });
  });

  it('does not render modal when modalInfo is null', () => {
    const propsWithNullModal = {
      ...defaultProps,
      modalInfo: undefined
    };
    render(<RekycModalWrapper {...propsWithNullModal} />);
    expect(screen.queryByText('Test Heading')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Update KYC' })).not.toBeInTheDocument();
  });

  it('adapts to mobile view', async () => {
    const useMobile = require('common/hooks/useMobile').useMobile;
    useMobile.mockReturnValue(true);

    render(<RekycModalWrapper {...defaultProps} />);
    await waitFor(() => {
      expect(screen.getByText('Test Heading')).toBeInTheDocument();
    });
  });
});