import {
  getHasSelectedKycAccess,
  setHasSelectedKycAccess,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';

describe('kycAccessFtux', () => {
  test('set and get kycAccess value', () => {
    let kycAccessValue;
    kycAccessValue = getHasSelectedKycAccess();
    expect(kycAccessValue).toBeNull();
    setHasSelectedKycAccess(true);
    kycAccessValue = getHasSelectedKycAccess();
    expect(kycAccessValue).toBe(true);
  });
});
