import React from 'react';

import * as analytics from 'common/utils/analytics';
import PartnerPlaybook from 'merchant/views/PartnerDashboard/PartnerPlaybook';
import { render, screen, userEvent, waitFor } from 'test-utils';

const analyticsTrackWithUserInfoSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');

const defaultProps = {};
describe('PartnerPlaybook', () => {
  const renderApp = (props = {}, { renderWithBrowserRouter = false } = {}) => {
    // eslint-disable-next-line
    // @ts-ignore
    render(<PartnerPlaybook {...defaultProps} {...props} />, { renderWithBrowserRouter });
  };
  const mockScrollIntoView = jest.fn();
  beforeAll(() => {
    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
    // @ts-ignore
    window.IntersectionObserver = jest.fn(() => ({
      observe: jest.fn(),
      disconnect: jest.fn(),
    }));
    window.HTMLElement.prototype.scrollIntoView = mockScrollIntoView;
  });
  afterEach(() => {
    jest.clearAllMocks();
  });

  const waitForPlaybookSectionToLoad = () => {
    return waitFor(
      () => {
        expect(screen.queryByTestId('playbook-sections-spinner')).not.toBeInTheDocument();
      },
      { timeout: 2000 },
    );
  };
  test('page render and scroll on navlinks', async () => {
    renderApp();
    await waitForPlaybookSectionToLoad();

    expect(screen.queryAllByText('Get Started')).toHaveLength(2);
    expect(screen.queryAllByText('Grow Your Business')).toHaveLength(2);
    expect(screen.queryAllByText('Help & Support')).toHaveLength(2);
    await userEvent.click(screen.queryAllByText('Get Started')[0]);
    expect(mockScrollIntoView).toHaveBeenCalled();
    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Top Headings',
        actionName: 'Clicked',
      }),
    );
  }, 20000);

  test('should open intro video modal', async () => {
    renderApp();
    await userEvent.click(screen.getByTestId('playbook-intro-overlay'));
    expect(screen.getByText('Introduction to Partner Playbook')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('Close'));
    await waitFor(() => {
      expect(screen.queryByText('Introduction to Partner Playbook')).toBeNull();
    });
  });

  test('search functionality', async () => {
    // Need browser router for reflecting location changes with navigate() call
    renderApp({}, { renderWithBrowserRouter: true });
    await waitForPlaybookSectionToLoad();

    const searchBar = screen.getByPlaceholderText('Type to search for different content pieces');
    // Initial counts
    expect(screen.getByTestId('badge-help-and-support-0')).toHaveTextContent('1 item');
    // Count Heading + tab bar
    expect(screen.queryAllByText('Grow Your Business')).toHaveLength(2);
    // Non expanded state
    expect(screen.queryByText('Claim Your Commission')).not.toBeNull();
    expect(screen.queryByText('KYC Checklist')).toBeNull();

    await userEvent.type(searchBar, 'KYC');
    await userEvent.click(
      screen.getByRole('button', {
        name: 'Search',
      }),
    );

    expect(analyticsTrackWithUserInfoSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'Partner Playbook Page Search Cta',
        actionName: 'Clicked',
        properties: {
          searchMessage: 'KYC',
        },
      }),
    );
    await waitForPlaybookSectionToLoad();

    // after-search counts
    // Tab bar should get hidden
    expect(screen.queryAllByText('Get Started')).toHaveLength(1);
    expect(screen.queryByText('Grow Your Business')).toBeNull();
    // Filtered results and count should be reflected
    expect(screen.queryByText('Claim Your Commission')).toBeNull();
    expect(screen.getByTestId('badge-help-and-support-0')).toHaveTextContent('2 items');
    expect(screen.getByText('Found 4 results')).toBeInTheDocument();
  }, 20000);

  test('empty state for search', async () => {
    renderApp({}, { renderWithBrowserRouter: true });
    await waitForPlaybookSectionToLoad();

    const searchBar = screen.getByPlaceholderText('Type to search for different content pieces');
    await userEvent.type(searchBar, 'non matching query');
    await userEvent.click(
      screen.getByRole('button', {
        name: 'Search',
      }),
    );
    await waitForPlaybookSectionToLoad();

    expect(
      screen.getByText("Oops! Couldn't find any results. Try searching something else."),
    ).toBeInTheDocument();
  }, 20000);
});
