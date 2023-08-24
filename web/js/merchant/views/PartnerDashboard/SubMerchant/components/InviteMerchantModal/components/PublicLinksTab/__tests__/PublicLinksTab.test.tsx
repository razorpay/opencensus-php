import React from 'react';

import { getInitialUserOrgState } from 'common/tests/utils';
import { referralData } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { fetchReferralsHandler } from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/once-handlers';
import PublicLinksTab from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/PublicLinksTab';
import * as kycAccessFtux from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { render, screen, server, waitFor, userEvent } from 'test-utils';
const getHasSelectedKycAccessSpy = jest.spyOn(kycAccessFtux, 'getHasSelectedKycAccess');

const isPartner = jest.fn();
isPartner.mockImplementation((type) => type === 'reseller');
const defaultProps = {
  productType: PRODUCT_TYPE.PG,
};

const defaultUserExtra = {
  id: 'K0KQSNE7BypZ5VE',
  isPartner,
};
const defaultOrgExtra = {};
describe('PublicLinksTab', () => {
  const renderApp = (props = {}, { isRzpOrg = true, userExtra = {}, orgExtra = {} } = {}) => {
    const session = getInitialUserOrgState({
      isRzpOrg,
      userExtra: { ...defaultUserExtra, ...userExtra },
      orgExtra: { ...defaultOrgExtra, ...orgExtra },
    });
    // eslint-disable-next-line
    // @ts-ignore
    render(<PublicLinksTab {...defaultProps} {...props} />, { initialState: { session } });
  };
  afterEach(() => {
    jest.clearAllMocks();
  });
  beforeEach(() => {
    server.use(fetchReferralsHandler());
  });

  test('should correctly render public invites content for PG FTUX', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => null);
    renderApp({ productType: PRODUCT_TYPE.PG });
    await waitFor(() => {
      expect(screen.queryByLabelText('public-links-spinner')).not.toBeInTheDocument();
    });
    expect(screen.getByText('New update')).toBeInTheDocument();
    expect(screen.queryByText(referralData[PRODUCT_TYPE.PG].url)).not.toBeInTheDocument();
    expect(screen.getByText(referralData[PRODUCT_TYPE.PG].easy_kyc_access_url)).toBeInTheDocument();
    await userEvent.click(screen.getByText('Yes, I will assist my client with their KYC'));
    expect(screen.getByText(referralData[PRODUCT_TYPE.PG].easy_kyc_access_url)).toBeInTheDocument();
    await userEvent.click(screen.getByText('No, my client will perform KYC on their own'));
    expect(screen.getByText(referralData[PRODUCT_TYPE.PG].url)).toBeInTheDocument();
  });

  test('should correctly render public invites content for PG nth time', async () => {
    getHasSelectedKycAccessSpy.mockImplementation(() => true);
    renderApp({ productType: PRODUCT_TYPE.PG });
    await waitFor(() => {
      expect(screen.queryByLabelText('public-links-spinner')).not.toBeInTheDocument();
    });
    expect(screen.queryByText('New update')).not.toBeInTheDocument();
    expect(screen.getByText(referralData[PRODUCT_TYPE.PG].easy_kyc_access_url)).toBeInTheDocument();
  });

  test('should correctly render public invites content for Capital', async () => {
    renderApp({ productType: PRODUCT_TYPE.CAPITAL });
    await waitFor(() => {
      expect(screen.queryByLabelText('public-links-spinner')).not.toBeInTheDocument();
    });
    expect(screen.queryByText('New update')).not.toBeInTheDocument();
    expect(screen.getByText(referralData[PRODUCT_TYPE.CAPITAL].url)).toBeInTheDocument();
  });

  test('should correctly render public invites content for X', async () => {
    renderApp({ productType: PRODUCT_TYPE.X });
    await waitFor(() => {
      expect(screen.queryByLabelText('public-links-spinner')).not.toBeInTheDocument();
    });
    expect(screen.queryByText('New update')).not.toBeInTheDocument();
    expect(screen.getByText(referralData[PRODUCT_TYPE.X].url)).toBeInTheDocument();
  });
});
