import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'common/services/test/test-utils';
import { rest } from 'msw';
import {
  CapitalSubMerchantList,
  XSubMerchantList,
  PrimarySubMerchantList,
} from 'merchant/views/PartnerDashboard/SubMerchant/AccountsList';
import {
  referralData,
  emptyAccountsListResponse,
  items,
  orgDetails,
} from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import * as downloadSubmerchantsActions from 'merchant/reducers/submerchant';

// TODO : covered only Capital use case, have to cover others later

const ComponentsProductMapping = {
  [PRODUCT_TYPE.PG]: PrimarySubMerchantList,
  [PRODUCT_TYPE.X]: XSubMerchantList,
  [PRODUCT_TYPE.CAPITAL]: CapitalSubMerchantList,
};

jest.mock('merchant/components/ShowWhen', () => ({ children }) => <div>{children}</div>);

jest.mock(
  'merchant/views/PartnerDashboard/SubMerchant/components/ConfirmGenerateReport',
  () =>
    ({ onDownload, closeModal }) =>
      (
        <div>
          <span>
            This report only contains data for affiliate accounts added during the last month.
          </span>
          <button type="button" onClick={onDownload}>
            Generate Report
          </button>
          <button type="button" onClick={closeModal}>
            Close
          </button>
        </div>
      ),
);
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
const getLocation = (path) => ({
  search: '',
  pathname: `/partners/submerchants/${path}`,
});
let downloadSubMerchant;
describe('AccountsList', () => {
  beforeAll(() => {
    downloadSubMerchant = jest.spyOn(downloadSubmerchantsActions, 'downloadSubmerchants');
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
  const renderApp = (product = PRODUCT_TYPE.PG, path = '') => {
    const Component = ComponentsProductMapping[product];
    return render(
      <Component
        location={getLocation(path)}
        referralData={referralData}
        product={product}
        org={orgDetails}
      />,
      {
        showModal: true,
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
  describe.each([
    { product: PRODUCT_TYPE.PG, path: '' },
    { product: PRODUCT_TYPE.X, path: 'x' },
    { product: PRODUCT_TYPE.CAPITAL, path: 'capital' },
  ])('Main describe block', ({ product, path }) => {
    test(`should render welcome screen once the data is fetched and is empty for ${product} submerchants`, async () => {
      server.use(
        rest.get('*/merchant/api/test/submerchants', (req, res, ctx) => {
          return res(
            ctx.status(200),
            ctx.json({
              status_code: 200,
              success: true,
              data: emptyAccountsListResponse,
            }),
            ctx.delay(50),
          );
        }),
      );
      renderApp(product, path);

      await waitFor(() => {
        expect(screen.getByText('Welcome to Partner Dashboard')).toBeInTheDocument();
      });
      expect(screen.getByText('Get started by adding merchants to Razorpay')).toBeInTheDocument();
      expect(screen.getByText('Add New Merchant')).toBeInTheDocument();
      expect(screen.getByText('Copy Link')).toBeInTheDocument();
    });
    test(`should render the list once the data is fetched and is not empty for ${product}`, async () => {
      renderApp(product, path);
      // negative testing
      expect(screen.queryByText('Welcome to Partner Dashboard')).not.toBeInTheDocument();
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

      if (product === PRODUCT_TYPE.CAPITAL) {
        expect(screen.getByText('Bureau Submission')).toBeInTheDocument();
        expect(screen.getAllByText('Not Available')).toHaveLength(2);
      }

      const downloadButton = screen.getByText('Export All (CSV)');
      expect(downloadButton).toBeInTheDocument();
      await userEvent.click(downloadButton);
      await waitFor(() => {
        expect(
          screen.getByText(
            'This report only contains data for affiliate accounts added during the last month.',
          ),
        ).toBeInTheDocument();
      });
      const generateBtn = screen.getByRole('button', { name: 'Generate Report' });
      expect(generateBtn).toBeInTheDocument();
      await userEvent.click(generateBtn);
      await waitFor(() => {
        expect(downloadSubMerchant).toBeCalled();
      });
    });
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
    renderApp(PRODUCT_TYPE.CAPITAL, 'capital');

    await waitFor(() => {
      expect(screen.getByText('There was an error while fetching Status')).toBeInTheDocument();
    });
  });
});
