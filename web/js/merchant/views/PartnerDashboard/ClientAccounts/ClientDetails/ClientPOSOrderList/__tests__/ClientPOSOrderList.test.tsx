import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import {
  getOrdersListHandler,
  getProductPricingHandler,
  getSubmerchantOrdersListHandler,
  getSubmerchantProductPricingHandler,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/__tests__/mocks/handlers';
import ClientPOSOrderList from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList';
import { submerchantWithKYCAccess } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import { submerchantDetailsHandler } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/once-handlers';
import { render, screen, server, userEvent, waitFor, waitForElementToBeRemoved } from 'test-utils';

const mockLocation = {
  key: '',
  hash: '',
  search: '',
  state: {},
  pathname: `/partners/submerchants/pos/${submerchantWithKYCAccess.id}/orders`,
};
const mockUseNavigate = jest.fn();
let mockNavigationType = '';
jest.mock('react-router-dom', () => ({
  ...(jest.requireActual('react-router-dom') as Record<string, string>),
  useLocation: () => mockLocation,
  useNavigate: () => mockUseNavigate,
  useNavigationType: () => mockNavigationType,
}));

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
}));

const renderApp = ({ userExtra = {}, orgExtra = {} } = {}, props = {}) => {
  const session = getInitialUserOrgState({
    isRzpOrg: true,
    userExtra: {
      id: 'testUserId',
      ...userExtra,
    },
    orgExtra,
  });
  render(<ClientPOSOrderList {...props} />, {
    initialState: {
      session,
    },
  });
};

describe('<ClientPOSOrderList/>', () => {
  beforeEach(() => {
    mockNavigationType = 'POP';
    server.use(
      getProductPricingHandler(),
      getOrdersListHandler(),
      submerchantDetailsHandler(submerchantWithKYCAccess),
      getSubmerchantOrdersListHandler(),
      getSubmerchantProductPricingHandler(),
    );
  });
  afterEach(() => {
    server.resetHandlers();
    jest.clearAllMocks();
  });
  test('should render client POS order list', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Client Orders')).toBeInTheDocument();

    // Check account ID and contact details
    const { id: accountId, email: contactEmail, name: contactName } = submerchantWithKYCAccess;

    await waitFor(() => {
      expect(screen.getByText(accountId)).toBeInTheDocument();
    });
    expect(screen.getByText(contactName)).toBeInTheDocument();
    expect(screen.getByText(contactEmail)).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getAllByText('Order Placed').length).toBe(3);
    });
    // Check disabled CTAs
    expect(screen.getAllByRole('button', { name: 'View Order Details' })[0]).toBeDisabled();
  });

  test('should navigate back if Back to Dashboard is clicked', async () => {
    mockNavigationType = 'PUSH';
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Back to Dashboard')).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText('Back to Dashboard'));
    expect(mockUseNavigate).toHaveBeenCalledWith(-1);
  });
});
