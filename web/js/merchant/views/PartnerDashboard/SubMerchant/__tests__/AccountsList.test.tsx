import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, server, waitFor } from 'common/services/test/test-utils';
import { rest } from 'msw';
import { CapitalSubMerchantList } from 'merchant/views/PartnerDashboard/SubMerchant/AccountsList';
import {
  referralData,
  accountsListResponse,
  items,
} from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

// TODO : covered only Capital use case, have to cover others later

const isPartner = jest.fn();
const isPartnerIntent = jest.fn();
const isFeatureEnabled = jest.fn();
const instantActivation = { isWhitelistFlow: false };

const state = {
  session: {
    user: {
      isOrgRZP: true,
      isPartner,
      isPartnerIntent,
      isFeatureEnabled,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
    },
  },
};
const location = {
  search: '',
  pathname: '/partners/submerchants/capital',
};

describe('AccountsList', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
    window.rzp_user = {};
    window.rzpQ = {
      merchantActions: () => {
        return {
          initiated: jest.fn(),
        };
      },
      onbr: () => {
        return {
          interaction: jest.fn(),
        };
      },
    };

    window.rzpQ.component = jest.fn();
  });
  const renderApp = () => {
    return render(
      <CapitalSubMerchantList
        location={location}
        referralData={referralData}
        product={PRODUCT_TYPE.CAPITAL}
      />,
      {
        initialState: {
          ...state,
        },
      },
    );
  };

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render spinner if loading', () => {
    renderApp();
    const spinner = screen.getByTestId('spinner');
    expect(spinner).toBeInTheDocument();
  });

  test('should render welcome screen once the data is fetched and is empty', async () => {
    server.use(
      rest.get('*/merchant/api/test/submerchants', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 200,
            success: true,
            data: accountsListResponse,
          }),
          ctx.delay(50),
        );
      }),
    );
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('Welcome to Partner Dashboard')).toBeInTheDocument();
    });
    expect(screen.getByText('Get started by adding merchants to Razorpay')).toBeInTheDocument();
    expect(screen.getByText('Add New Merchant')).toBeInTheDocument();
    expect(screen.getByText('Copy Link')).toBeInTheDocument();
  });

  test('should show error notification and status as Not Available if bulk API return error', async () => {
    server.use(
      rest.post('*/merchant/api/test/submerchants/capital/applications', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            status_code: 400,
            success: false,
            data: ['something went wrong!'],
          }),
          ctx.delay(50),
        );
      }),
    );
    renderApp();

    await waitFor(() => {
      expect(screen.getByText('There was an error while fetching Status')).toBeInTheDocument();
    });
  });

  test('should render the list once the data is fetched and is not empty', async () => {
    renderApp();

    await waitFor(() => {
      expect(screen.getAllByText('Account Name')).toHaveLength(2);
    });
    expect(screen.getAllByText('Account ID')).toHaveLength(2);
    expect(screen.getByText('Email ID')).toBeInTheDocument();
    expect(screen.getByText('Registered Email')).toBeInTheDocument();
    expect(screen.getByText(items[0].id)).toBeInTheDocument();
    expect(screen.getByText(items[0].name)).toBeInTheDocument();
    expect(screen.getByText(items[0].email)).toBeInTheDocument();
    expect(screen.getByText(items[1].id)).toBeInTheDocument();
    expect(screen.getByText(items[1].name)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();
    expect(screen.getByText('Bureau Submission')).toBeInTheDocument();
    expect(screen.getByText('Not Available')).toBeInTheDocument();
  });
});
