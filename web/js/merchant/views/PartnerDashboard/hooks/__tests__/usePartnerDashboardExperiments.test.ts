import { SpiltzContextState } from 'common/splitz/types';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';
import * as merchantStore from 'merchant/store';
const userSpy = jest.spyOn(merchantStore, 'getUser');
userSpy.mockImplementation(() => ({
  isPartnershipsInviteFlowEnabled: true,
}));

const variantOn = { variables: { result: 'on' } };

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      partnerships_easier_access_to_submerchant_kyc: variantOn,
    } as unknown as SpiltzContextState,
  }),
}));

describe('usePartnerDashboardExperiments', () => {
  test('should return correct output for isEasierAccessToSubmerchantKycEnabled', () => {
    let experiments = usePartnerDashboardExperiments();
    expect(experiments.isEasierAccessToSubmerchantKycEnabled).toBe(true);
    userSpy.mockImplementation(() => ({
      isPartnershipsInviteFlowEnabled: false,
    }));
    experiments = usePartnerDashboardExperiments();
    expect(experiments.isEasierAccessToSubmerchantKycEnabled).toBe(false);
  });
});
