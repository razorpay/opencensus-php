import { SpiltzContextState } from 'common/splitz/types';
import * as merchantStore from 'merchant/store';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

const defaultMockUser = {
  isPartnershipsInviteFlowEnabled: true,
  isOrgRZP: true,
  isPartner: (partner_type) => partner_type === 'reseller',
};
const userSpy = jest.spyOn(merchantStore, 'getUser');
userSpy.mockImplementation(() => defaultMockUser);

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };

const defaultAbExperiments = {};

let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments } as unknown as SpiltzContextState),
}));

describe('usePartnerDashboardExperiments', () => {
  beforeEach(() => {
    mockAbExperiments = defaultAbExperiments;
  });
  test('should return correct output for isEasierAccessToSubmerchantKycEnabled', () => {
    mockAbExperiments = {
      partnerships_easier_access_to_submerchant_kyc: variantOn,
    };
    let experiments = usePartnerDashboardExperiments();
    expect(experiments.isEasierAccessToSubmerchantKycEnabled).toBe(true);
    userSpy.mockImplementation(() => ({
      ...defaultMockUser,
      isPartnershipsInviteFlowEnabled: false,
    }));
    experiments = usePartnerDashboardExperiments();
    expect(experiments.isEasierAccessToSubmerchantKycEnabled).toBe(false);
  });

  test('should return correct output for isPlatformPartnerInviteFlowEnabled', () => {
    userSpy.mockImplementation(() => ({
      ...defaultMockUser,
      isPartner: (partner_type) => partner_type === 'pure_platform',
    }));
    mockAbExperiments = {
      partnerships_oauth_phantom: variantOn,
    };
    let experiments = usePartnerDashboardExperiments();
    expect(experiments.isPlatformPartnerInviteFlowEnabled).toBe(true);

    mockAbExperiments = {
      partnerships_oauth_phantom: variantOff,
    };
    experiments = usePartnerDashboardExperiments();
    expect(experiments.isPlatformPartnerInviteFlowEnabled).toBe(false);
  });

  test('should return correct output for isPartnershipCapitalBureauLinkEnabled', () => {
    mockAbExperiments = {
      partnership_capital_bureau_link: variantOn,
    };
    let experiments = usePartnerDashboardExperiments();
    expect(experiments.isPartnershipCapitalBureauLinkEnabled).toBe(true);

    mockAbExperiments = {
      partnership_capital_bureau_link: variantOff,
    };
    experiments = usePartnerDashboardExperiments();
    expect(experiments.isPartnershipCapitalBureauLinkEnabled).toBe(false);
  });
});
