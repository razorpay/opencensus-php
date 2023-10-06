import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import ChooseOAuthApp from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ChooseOAuthApp';
import { render, screen, waitFor, server } from 'test-utils';

import { applications } from './mocks/fixtures';
import { getApplications } from './mocks/handlers';

const defaultProps = {
  orgName: 'Razorpay',
  productType: null,
  onNextClick: jest.fn(),
};
const defaultUserExtra = {};
const defaultOrgExtra = {};
describe('ChooseOAuthApp', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    render(<ChooseOAuthApp {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });
  test('should render the list of applications', async () => {
    server.use(getApplications(applications));
    renderApp();

    await waitFor(() => {
      expect(screen.queryByLabelText('fetching-applications-spinner')).not.toBeInTheDocument();
    });

    expect(screen.getByText('Acme App')).toBeInTheDocument();
    expect(screen.getByText('App Id : LqduheFxXVXR3G')).toBeInTheDocument();
  });
});
