import { renderHook } from '@testing-library/react-hooks';

import { SpiltzContextState } from 'common/splitz/types';
import * as merchantStore from 'merchant/store';
import usePartnerDashboardExperiments from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

const defaultMockUser = {
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
  test('should return correct output for isPartnershipsInviteFlowEnabled', () => {
    mockAbExperiments = {
      partnerships_easier_access_to_submerchant_kyc: variantOn,
    };

    const {
      result: { current: experiments },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experiments.isPartnershipsInviteFlowEnabled).toBe(true);

    userSpy.mockImplementation(() => ({
      ...defaultMockUser,
      isPartner: (partner_type) => partner_type !== 'reseller',
    }));
    const {
      result: { current: experimentsNext },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experimentsNext.isPartnershipsInviteFlowEnabled).toBe(false);
  });

  test('should return correct output for isPlatformPartnerInviteFlowEnabled', () => {
    userSpy.mockImplementation(() => ({
      ...defaultMockUser,
      isPartner: (partner_type) => partner_type === 'pure_platform',
    }));
    mockAbExperiments = {
      partnerships_oauth_phantom: variantOn,
    };

    const {
      result: { current: experiments },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experiments.isPlatformPartnerInviteFlowEnabled).toBe(true);

    mockAbExperiments = {
      partnerships_oauth_phantom: variantOff,
    };

    const {
      result: { current: experimentsNext },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experimentsNext.isPlatformPartnerInviteFlowEnabled).toBe(false);
  });

  test('should return correct output for isPartnershipCapitalBureauLinkEnabled', () => {
    mockAbExperiments = {
      partnership_capital_bureau_link: variantOn,
    };

    const {
      result: { current: experiments },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experiments.isPartnershipCapitalBureauLinkEnabled).toBe(true);

    mockAbExperiments = {
      partnership_capital_bureau_link: variantOff,
    };

    const {
      result: { current: experimentsNext },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experimentsNext.isPartnershipCapitalBureauLinkEnabled).toBe(false);
  });

  test('should return correct output for isPartnershipsForPosEnabled', () => {
    userSpy.mockImplementation(() => ({
      ...defaultMockUser,
      isPartner: (partner_type) => partner_type === 'reseller',
    }));
    mockAbExperiments = {
      partnerships_for_pos: variantOn,
    };

    const {
      result: { current: experiments },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experiments.isPartnershipsForPosEnabled).toBe(true);

    mockAbExperiments = {
      partnership_for_pos: variantOff,
    };
    const {
      result: { current: experimentsNext },
    } = renderHook(() => usePartnerDashboardExperiments());
    expect(experimentsNext.isPartnershipsForPosEnabled).toBe(false);
  });
});
