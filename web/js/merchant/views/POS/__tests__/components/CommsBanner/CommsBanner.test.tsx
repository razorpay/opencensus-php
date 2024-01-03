import React from 'react';

import CommsBanner from 'merchant/views/POS/CommsBanner';
import { MOCK_USER } from 'merchant/views/POS/__tests__/mocks/fixtures';
import { commsOrderFetchHandler } from 'merchant/views/POS/__tests__/mocks/handlers';
import * as posHooks from 'merchant/views/POS/hooks';
import { render, server, screen, waitFor, userEvent } from 'test-utils';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

export const MOCK_STAGES = [
  {
    title: 'First Stage',
    description: 'First Stage Description',
    status: 'active',
    cta: [
      {
        name: 'First CTA',
        url: '/firstcta',
        type: 'button',
      },
    ],
  },
  {
    title: 'Second Stage',
    description: 'Second Stage Description',
    status: 'negative',
    cta: [
      {
        name: 'Second CTA Button',
        url: '/secondCTAbtn',
        type: 'button',
      },
      {
        name: 'Second CTA Link',
        url: '/secondCTAlink',
        type: 'link',
      },
    ],
  },
  {
    title: 'Third Stage',
    description: <div>Third Stage Description</div>,
    status: 'dispatched',
    cta: null,
  },
];

jest.mock('merchant/views/POS/CommsBanner/getMerchantComms', () => ({
  getMerchantComms: () => MOCK_STAGES,
}));

const mockedUsedNavigate = jest.fn();

jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useNavigate: () => mockedUsedNavigate,
}));

type Mode = 'live' | 'test';

const renderApp = ({ mode = 'live', user = MOCK_USER }) => {
  render(<CommsBanner mode={mode as Mode} user={user} />);
};

describe('<CommsBanner/>', () => {
  beforeEach(() => {
    server.use(commsOrderFetchHandler());
  });
  test('should render Comms Banner on screen', async () => {
    renderApp({});
    await waitFor(() => {
      expect(screen.getByText('First Stage')).toBeVisible();
    });

    expect(screen.getByText('First Stage Description')).toBeVisible();
    expect(screen.getByText('Third Stage')).toBeVisible();
    expect(screen.getByText('Third Stage Description')).toBeVisible();
  });

  test('should render correct number of connectors on screen', async () => {
    renderApp({});
    await waitFor(() => {
      expect(screen.getAllByLabelText('visible-connector').length).toBe(4);
    });
  });

  test('should trigger navigate with correct url if cta clicked', async () => {
    renderApp({});
    await waitFor(() => {
      expect(screen.getByText('First Stage')).toBeVisible();
    });
    await userEvent.click(screen.getByText('First CTA'));
    expect(mockedUsedNavigate).toHaveBeenCalledWith('/firstcta');

    await userEvent.click(screen.getByText('Second CTA Link'));
    expect(mockedUsedNavigate).toHaveBeenCalledWith('/secondCTAlink');
  });

  test('should render Comms Banner on screen for mobile screen', async () => {
    jest.spyOn(posHooks, 'useBladeBreakpoints').mockReturnValue({
      matchedBreakpoint: 'm',
      isMobile: true,
      isDesktop: false,
      isLargeScreen: false,
    });
    renderApp({});

    await waitFor(() => {
      expect(screen.getByText('First Stage')).toBeVisible();
    });

    expect(screen.getByText('First Stage Description')).toBeVisible();
    expect(screen.getByText('Third Stage')).toBeVisible();
    expect(screen.getByText('Third Stage Description')).toBeVisible();
  });

  test('should call track_EXPERIMENTAL with the correct parameters on mount', () => {
    renderApp({});

    expect(analytics.track_EXPERIMENTAL).toHaveBeenCalledWith(SignUpEvents.pageViewed, {
      pageType: 'Merchant Activation Status Bar - POS Catalog',
      orderId: '',
    });
  });
});
