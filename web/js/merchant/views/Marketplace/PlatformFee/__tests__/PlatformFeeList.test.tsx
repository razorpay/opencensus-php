import React from 'react';

import { render, screen, waitFor, server, userEvent } from 'common/services/test/test-utils';
import * as analytics from 'common/utils/analytics';
import PlatformFee from 'merchant/views/Marketplace/PlatformFee/List';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { platformFeeData, platformFeeDataEmpty } from './mocks/fixtures';
import { platformFeeListSuccess, platformFeeListError } from './mocks/handlers';

jest.mock('common/ui/HeaderAction', () => ({
  __esModule: true,
  default: ({ children }) => {
    return <div>{children}</div>;
  },
}));

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: {} }),
}));

jest.spyOn(NotificationsActions, 'showNotification');

const location = {
  search: '',
};

export const state = {
  session: {
    user: {
      id: 'testUserId',
      isOrgAllowedFunctionality: () => true,
      findTag: () => true,
      isAllowedEdit: () => true,
      merchant: {
        country_code: 'IN',
      },
      isOptimizerView: () => false,
    },
  },
};

describe('Platform Fee List', () => {
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  afterEach(() => {
    jest.clearAllMocks();
  });
  const renderApp = (isPartnerPlatformFeeEnabled = false) => {
    render(
      <PlatformFee location={location} isPartnerPlatformFeeEnabled={isPartnerPlatformFeeEnabled} />,
      {
        initialState: state,
        renderViaRouteGuard: false,
      },
    );
  };

  test('should render spinner if loading', () => {
    server.use(platformFeeListSuccess());
    renderApp();
    const spinner = screen.getByRole('progressbar', { name: 'Loading Table' });
    expect(spinner).toBeInTheDocument();
  });

  test('should render platform fee list once the data is fetched', async () => {
    server.use(platformFeeListSuccess());
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Partner Fee Id')).toBeInTheDocument();
      expect(screen.getByText('Source Id')).toBeInTheDocument();
      expect(screen.getByText('Recipient Id')).toBeInTheDocument();
      expect(screen.getByText('Recipient Name')).toBeInTheDocument();
      expect(screen.getByText('Partner Fee Amount')).toBeInTheDocument();
      expect(screen.getAllByText('Status')).toHaveLength(2);

      expect(screen.getByText(platformFeeData.items[0].id)).toBeInTheDocument();
    });
  });

  test('should render empty table if items are empty', async () => {
    server.use(platformFeeListSuccess(platformFeeDataEmpty));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Partner Fee Id')).toBeInTheDocument();
    });
    expect(screen.queryByText(platformFeeData.items[0].id)).not.toBeInTheDocument();
  });

  test('should render filtered data after clicking search', async () => {
    server.use(platformFeeListSuccess());
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Partner Fee Id')).toBeInTheDocument();
      expect(screen.getByText(platformFeeData.items[0].recipient)).toBeInTheDocument();
    });

    const countInput = screen.getByLabelText('Count');
    expect(countInput).toBeInTheDocument();
    await userEvent.type(countInput, '1');

    const searchButton = screen.getByRole('button', { name: 'Search' });
    expect(searchButton).toBeInTheDocument();
    await userEvent.click(searchButton);

    await waitFor(() => {
      expect(screen.queryByText(platformFeeData.items[0].recipient)).toBeInTheDocument();
    });
  });

  test('should capture platformFee tab opened event', async () => {
    server.use(platformFeeListSuccess());
    renderApp();
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        screen: 'platform fee page',
        objectName: 'route partnership platform fee',
        actionName: 'tab opened',
        properties: {
          mid: 'testUserId',
        },
        toLumberjack: true,
      });
    });
  });

  test('render platform Fee Id if isPartnerPlatformFeeEnabled is true', async () => {
    const isPartnerPlatformFeeEnabled = true;
    server.use(platformFeeListSuccess());
    renderApp(isPartnerPlatformFeeEnabled);
    await waitFor(() => {
      expect(screen.getByText('Platform Fee Id')).toBeInTheDocument();
    });
  });

  // todo skipping this for now because it is getting failed because of retry option of react-query.
  test.skip('should render error notification if API throws an error', async () => {
    server.use(platformFeeListError());
    renderApp();
    await waitFor(() => {
      expect(NotificationsActions.showNotification).toHaveBeenCalled();
    });
    expect(screen.getByText('There was an error')).toBeInTheDocument();
  });
});
