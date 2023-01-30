import { activationStatusMap } from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';
import { CAPITAL_STATUS } from 'merchant/views/PartnerDashboard/constants';

describe('activationStatusHelper', () => {
  test('should return status based on received value', () => {
    expect(activationStatusMap(CAPITAL_STATUS.bureau_submission)).toStrictEqual(
      'Bureau submission',
    );
    expect(activationStatusMap(CAPITAL_STATUS.income_proof_submission)).toStrictEqual(
      'Income proof submission',
    );
    expect(activationStatusMap(CAPITAL_STATUS.pv_pending)).toStrictEqual('Pre-offer Verification');
    expect(activationStatusMap(CAPITAL_STATUS.pv_processing)).toStrictEqual(
      'Pre-offer Verification',
    );
    expect(activationStatusMap(CAPITAL_STATUS.stp_processing)).toStrictEqual(
      'Pre-offer Verification',
    );
    expect(activationStatusMap(CAPITAL_STATUS.offer_acceptance)).toStrictEqual('Offer acceptance');
    expect(activationStatusMap(CAPITAL_STATUS.post_offer_docs_collection)).toStrictEqual(
      'Post offer docs collection',
    );
    expect(activationStatusMap(CAPITAL_STATUS.post_offer_docs_verification)).toStrictEqual(
      'Post offer docs verification',
    );
    expect(activationStatusMap(CAPITAL_STATUS.esign_initiaiton)).toStrictEqual('Esign initiaiton');
    expect(activationStatusMap(CAPITAL_STATUS.merchant_esign_pending)).toStrictEqual(
      'Merchant esign pending',
    );
    expect(activationStatusMap(CAPITAL_STATUS.nach_pending)).toStrictEqual('NACH pending');
    expect(activationStatusMap(CAPITAL_STATUS.rzp_esign_pending)).toStrictEqual(
      'RZP esign pending',
    );
    expect(activationStatusMap(CAPITAL_STATUS.lender_decision)).toStrictEqual('Lender decision');
    expect(activationStatusMap(CAPITAL_STATUS.lender_response)).toStrictEqual('Lender response');
    expect(activationStatusMap(CAPITAL_STATUS.post_offer_docs_resubmission)).toStrictEqual(
      'Post offer docs resubmission',
    );
    expect(activationStatusMap(CAPITAL_STATUS.rejection_bucket)).toStrictEqual('Rejection bucket');
    expect(activationStatusMap(CAPITAL_STATUS.uw_processing)).toStrictEqual('UW processing');
    expect(activationStatusMap(CAPITAL_STATUS.uw_hold)).toStrictEqual('UW hold');
    expect(activationStatusMap(CAPITAL_STATUS.application_closed)).toStrictEqual(
      'Application closed',
    );
    expect(activationStatusMap(CAPITAL_STATUS.go_live)).toStrictEqual('Go-live');
    expect(activationStatusMap(CAPITAL_STATUS.application_rejected)).toStrictEqual(
      'Application rejected',
    );
    expect(activationStatusMap('something default')).toStrictEqual('');
  });
});
