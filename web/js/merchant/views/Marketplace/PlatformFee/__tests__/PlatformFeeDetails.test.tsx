import React from 'react';

import { render, screen, waitFor, server, userEvent } from 'common/services/test/test-utils';
import { getInitialUserOrgState } from 'common/tests/utils';
import * as analytics from 'common/utils/analytics';
import { paiseToRupees } from 'common/utils/rzp-utils';
import PlatformFeeDetails from 'merchant/views/Marketplace/PlatformFee/Details';
import * as store from 'merchant/views/Marketplace/store';
import * as modals from 'merchant_common/reducers/modals';

import { platformFeedDetailsData as data, reversalsData } from './mocks/fixtures';
import { platformFeeDetailsSuccess, reversalSuccess } from './mocks/handlers';

jest.mock('@razorpay/blade/components', () => {
  const bladeActual = jest.requireActual('@razorpay/blade/components');
  return {
    __esModule: true,
    ...bladeActual,
    Amount: ({ value }) => <>{value}</>,
    Spinner: ({ testID }) => <div data-testid={testID}>spinner</div>,
    InfoIcon: () => <span>Icon</span>,
  };
});

jest.mock('merchant/views/Marketplace/Transfers/components/TransferReversal', () => ({
  __esModule: true,
  default: ({ openTransferReversalModal }) => {
    return (
      <div>
        <div>No reversals created</div>
        <button type="button" onClick={openTransferReversalModal}>
          Create reversal
        </button>
      </div>
    );
  },
}));

jest.spyOn(modals, 'openModal');

const defaultUserExtra = {
  id: 'testUserId',
  isAllowedEdit: () => true,
};
describe('Platform Fee Details', () => {
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');
  beforeEach(() => {
    jest.clearAllMocks();
  });
  const renderApp = ({ userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg: true,
      userExtra: {
        ...defaultUserExtra,
        ...userExtra,
      },
      orgExtra,
    });
    render(<PlatformFeeDetails id={data.id} />, {
      initialState: { session },
    });
  };

  test('should render loader if data is loading', () => {
    server.use(platformFeeDetailsSuccess(data));
    renderApp();
    const spinner = screen.getByTestId('spinner');
    expect(spinner).toBeInTheDocument();
  });

  test('should show the details once the data is loaded', async () => {
    server.use(platformFeeDetailsSuccess(data));
    server.use(reversalSuccess(reversalsData));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(`Partner Fee ID:`)).toBeInTheDocument();
    });
    expect(screen.getByText(data.id)).toBeInTheDocument();
    expect(screen.getByText('Partner Fee Amount')).toBeInTheDocument();

    expect(screen.getByText(paiseToRupees(data.amount + data.fees))).toBeInTheDocument();

    await waitFor(() => {
      expect(
        screen.getByText(
          `Payment to ${data.recipient_details.name} = ${paiseToRupees(data.amount)}`,
        ),
      );
      expect(screen.getByText('No reversals created')).toBeInTheDocument();
    });
  });

  test('should show N/A if data is not available', async () => {
    server.use(platformFeeDetailsSuccess({}));
    server.use(reversalSuccess({}));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(`Partner Fee Amount`)).toBeInTheDocument();
    });
    expect(screen.getAllByText('N/A')).toHaveLength(7);
  });

  test('should render empty lines if notes are not available', async () => {
    server.use(platformFeeDetailsSuccess({ ...data, notes: {} }));
    server.use(reversalSuccess(reversalsData));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(`Partner Fee Amount`)).toBeInTheDocument();
      expect(screen.getByText('--')).toBeInTheDocument();
    });
  });

  test.skip('should open modal if create reversal is clicked', async () => {
    server.use(platformFeeDetailsSuccess(data));
    server.use(reversalSuccess(reversalsData));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(`Partner Fee ID:`)).toBeInTheDocument();
    });
    expect(screen.getByText(data.id)).toBeInTheDocument();
    expect(screen.getByText(`Partner Fee Amount`)).toBeInTheDocument();
    expect(screen.getByText(paiseToRupees(data.amount + data.fees))).toBeInTheDocument();
    const reversalButton = screen.getByRole('button', { name: 'Create reversal' });
    expect(reversalButton).toBeInTheDocument();
    await userEvent.click(reversalButton);

    await waitFor(() => {
      expect(modals.openModal).toHaveBeenCalled();
    });
  });

  test('should capture platformFeeDetails tab opened event', async () => {
    server.use(platformFeeDetailsSuccess(data));
    server.use(reversalSuccess(reversalsData));
    renderApp();
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        screen: 'platform fee details page',
        objectName: 'route partnership platform fee details',
        actionName: 'tab opened',
        properties: {
          mid: 'testUserId',
        },
        toLumberjack: true,
      });
    });
  });

  test('should show platformFee if isPartnerPlatformFeeEnabled is true', async () => {
    jest.spyOn(store, 'useMarketplaceStore').mockImplementation(() => ({
      isPartnerPlatformFeeEnabled: true,
    }));
    server.use(platformFeeDetailsSuccess(data));
    server.use(reversalSuccess(reversalsData));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(`Platform Fee ID:`)).toBeInTheDocument();
    });
    expect(screen.getByText(data.id)).toBeInTheDocument();
    expect(screen.getByText('Platform Fee Amount')).toBeInTheDocument();
  });
});
