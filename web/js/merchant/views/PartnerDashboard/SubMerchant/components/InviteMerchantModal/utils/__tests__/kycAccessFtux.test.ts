import {
  getHasSelectedKycAccess,
  setHasSelectedKycAccess,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/utils/kycAccessFtux';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

describe('kycAccessFtux', () => {
  test('set and get kycAccess value', () => {
    // PG
    let kycAccessValue;
    kycAccessValue = getHasSelectedKycAccess(PRODUCT_TYPE.PG);
    expect(kycAccessValue).toBeNull();
    setHasSelectedKycAccess(true, PRODUCT_TYPE.PG);
    kycAccessValue = getHasSelectedKycAccess(PRODUCT_TYPE.PG);
    expect(kycAccessValue).toBe(true);

    // POS
    let posKycAccessValue;
    posKycAccessValue = getHasSelectedKycAccess(PRODUCT_TYPE.POS);
    expect(posKycAccessValue).toBeNull();
    setHasSelectedKycAccess(true, PRODUCT_TYPE.POS);
    posKycAccessValue = getHasSelectedKycAccess(PRODUCT_TYPE.POS);
    expect(posKycAccessValue).toBe(true);
  });
});
