import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import {
  mockAdditionalWebsites,
  mockAppstoreUrl,
  mockMainPageUrl,
  mockPlaystoreUrl,
} from './mocks/fixtures';
import RenderWebsites from '../RenderWebsites';
import { WebsiteUpdateAutomationStatus } from '../types';

const mockOnClickAddWebsite = jest.fn();

const defaultProps = {
  isMobile: false,
  onClickAddWebsite: mockOnClickAddWebsite,
  businessWebsiteWorkflow: {},
  websiteUpdateData: undefined,
  isMainWebsiteEditActionAllowed: true,
  user: {
    business_website: '',
    appstore_url: '',
    playstore_url: '',
    additional_websites: [],
    has_key_access: true,
  },
};

const renderApp = (props) => {
  const renderOutput = render(<RenderWebsites {...defaultProps} {...props} />);
  return renderOutput;
};

describe('Business website automation - RenderWebsites', () => {
  it('should show the main websites', async () => {
    renderApp({
      user: {
        ...defaultProps.user,
        business_website: mockMainPageUrl,
      },
    });
    await waitFor(() => {
      expect(screen.getByText(mockMainPageUrl)).toBeInTheDocument();
      expect(screen.getByText('Active')).toBeInTheDocument();
    });
  });

  it('should show CTA to edit the main website', async () => {
    renderApp({
      user: {
        ...defaultProps.user,
        business_website: mockMainPageUrl,
      },
    });
    await waitFor(() => {
      expect(screen.getByText(mockMainPageUrl)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText('Edit'));

    await waitFor(() => {
      expect(mockOnClickAddWebsite).toHaveBeenCalled();
    });
  });

  it('should show the appstore and playstore url', async () => {
    renderApp({
      user: {
        ...defaultProps.user,
        appstore_url: mockAppstoreUrl,
        playstore_url: mockPlaystoreUrl,
      },
    });

    await waitFor(() => {
      expect(screen.getByText(mockAppstoreUrl)).toBeInTheDocument();
      expect(screen.getByText(mockPlaystoreUrl)).toBeInTheDocument();
    });
  });

  it('should show the additional websites', async () => {
    renderApp({
      user: {
        ...defaultProps.user,
        additional_websites: mockAdditionalWebsites,
      },
    });

    await waitFor(() => {
      mockAdditionalWebsites.forEach((website) => {
        expect(screen.getByText(website)).toBeInTheDocument();
      });
    });
  });

  it('should show empty state for user who dont have any websites', async () => {
    renderApp({
      user: {
        business_website: '',
        appstore_url: '',
        playstore_url: '',
        additional_websites: [],
      },
    });

    await waitFor(() => {
      expect(
        screen.getByText('Your websites and apps linked to Razorpay appear here'),
      ).toBeInTheDocument();
    });
  });

  it('shound show the under review status when website is in progress', async () => {
    renderApp({
      user: {
        ...defaultProps.user,
        business_website: mockMainPageUrl,
      },
      websiteUpdateData: {
        main_page_url: mockAppstoreUrl,
        current_status: WebsiteUpdateAutomationStatus.IN_PROGRESS,
      },
    });
    await waitFor(() => {
      expect(screen.getByText(mockAppstoreUrl)).toBeInTheDocument();
      expect(screen.getByText('Under Review')).toBeInTheDocument();
    });
  });

  it('should show websites on mobile', async () => {
    renderApp({
      isMobile: true,
      user: {
        ...defaultProps.user,
        business_website: mockMainPageUrl,
      },
    });
    await waitFor(() => {
      expect(screen.getByText(mockMainPageUrl)).toBeInTheDocument();
    });
  });
});
