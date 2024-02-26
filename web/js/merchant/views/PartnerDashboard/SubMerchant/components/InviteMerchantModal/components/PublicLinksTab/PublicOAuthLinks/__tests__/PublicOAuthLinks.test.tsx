import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import PublicOAuthLinks from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab/PublicOAuthLinks';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, server, waitFor } from 'test-utils';

import { referralData } from './mocks/fixtures';
import { fetchReferralsHandler } from './mocks/once-handlers';

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
  selectedApp: {
    application_id: 'string_application_id',
    client_id: 'string_client_id',
    redirect_uri: 'string_redirect_uri',
    name: 'string_name',
  },
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('PublicOAuthLinks', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<PublicOAuthLinks {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });
  beforeEach(() => {
    server.use(fetchReferralsHandler());
  });

  test('should correctly render public oauth invites content for PG', async () => {
    renderApp({ productType: PRODUCT_TYPE.PG });
    await waitFor(() => {
      expect(screen.queryByLabelText('public-oauth-links-spinner')).not.toBeInTheDocument();
    });
    expect(screen.getByText(referralData.value)).toBeInTheDocument();
  });
});
