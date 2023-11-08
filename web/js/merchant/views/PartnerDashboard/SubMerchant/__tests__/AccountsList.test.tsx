import React from 'react';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  fireEvent,
} from 'common/services/test/test-utils';
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

import { getInitialUserOrgState } from 'common/tests/utils';
import { allInvitesListSuccess } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/handlers';
import {
  allInvitesData,
  allInvitesDataEmpty,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/fixtures';
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

const getLocation = (path) => ({
  search: '',
  pathname: `/partners/submerchants/${path}`,
});

const renderApp = (
  product = PRODUCT_TYPE.PG,
  path = '',
  { userExtra = {}, orgExtra = {} } = {},
) => {
  const Component = ComponentsProductMapping[product];
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      isPartner,
      isPartnerIntent,
      isFeatureEnabled,
      isPartnershipForCapitalEnabled: true,
      isPartnershipFUX: true,
      instantActivation,
      ...userExtra,
    },
    orgExtra,
  });

  return render(
    <Component
      location={getLocation(path)}
      referralData={referralData}
      product={product}
      org={orgDetails}
    />,
    {
      showModal: true,
      initialState: { session },
      renderViaRouteGuard: false,
    },
  );
};

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

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render spinner if loading', () => {
    renderApp();
    const spinner = screen.getByTestId('spinner');
    expect(spinner).toBeInTheDocument();
  });

  describe('partnerships invite flow', () => {
    const renderAppForPGInviteFlow = ({ userExtra = {}, ...sessionArgs } = {}) =>
      renderApp(PRODUCT_TYPE.PG, '', {
        ...sessionArgs,
        userExtra: {
          isPartnershipsInviteFlowEnabled: true,
          isPartnershipForCapitalEnabled: false,
          isPartnershipFUX: false,
          ...userExtra,
        },
      });

    test(`should render accepted invites table when accepted invites is non empty and all invites is empty`, async () => {
      server.use(allInvitesListSuccess(allInvitesDataEmpty));
      renderAppForPGInviteFlow();

      await waitFor(() => {
        expect(screen.getByText('Invite Accepted On')).toBeInTheDocument();
      });
      expect(screen.queryAllByText('Welcome to Partner Dashboard')).toHaveLength(0);
    });
    test(`should render welcome screen correctly for partnerships invite flow when both invites data is empty`, async () => {
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
      server.use(allInvitesListSuccess(allInvitesDataEmpty));
      renderAppForPGInviteFlow();
      await waitFor(() => {
        expect(screen.getByText('Welcome to Partner Dashboard')).toBeInTheDocument();
      });
      // No table column render
      expect(screen.queryByText('Invite Accepted On')).not.toBeInTheDocument();
      // No empty table
      expect(screen.queryByText('All Accepted Invites')).not.toBeInTheDocument();
    });

    test(`should render empty table when accepted invites is empty and all invites is non empty`, async () => {
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
      server.use(allInvitesListSuccess(allInvitesData));
      renderAppForPGInviteFlow();

      await waitFor(() => {
        expect(screen.getByText('All Accepted Invites')).toBeInTheDocument();
      });
      // table column should render
      expect(screen.queryByText('Invite Accepted On')).toBeInTheDocument();
      // Common welcome screen should not render
      expect(screen.queryByText('Welcome to Partner Dashboard')).not.toBeInTheDocument();
    });
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
      // No table column render
      expect(screen.queryByText('Account ID')).not.toBeInTheDocument();
    });
    test(`should render the list once the data is fetched and is not empty for ${product}`, async () => {
      renderApp(product, path);
      // negative testing
      expect(screen.queryByText('Welcome to Partner Dashboard')).not.toBeInTheDocument();
      await waitFor(() => {
        expect(screen.getAllByText('Account Name')).toHaveLength(2);
      });
      expect(screen.getAllByText('Account ID')).toHaveLength(2);
      if (product === 'primary') {
        expect(screen.getAllByText('Contact')).toHaveLength(2);
      } else {
        expect(screen.getByText('Email ID')).toBeInTheDocument();
        expect(screen.getByText('Registered Email')).toBeInTheDocument();
      }
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

  test(`should render successfully open and render Add Merchant modal without breaking`, async () => {
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
    renderApp(PRODUCT_TYPE.PG, '');

    await waitFor(() => {
      expect(screen.getByText('Welcome to Partner Dashboard')).toBeInTheDocument();
      expect(screen.getByText('Get started by adding merchants to Razorpay')).toBeInTheDocument();
      fireEvent.click(screen.getByText('Add New Merchant'));
    });

    expect(screen.queryByText('Add New Merchants')).toBeInTheDocument();
  });
});
