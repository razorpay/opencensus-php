import { capitalApplicationsResponse } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/CapitalClients/__tests__/mocks/fixtures';
import { accountsListResponse } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/__tests__/mocks/fixtures';
import {
  activationStatusMap,
  filterApplications,
  getFormattedCapitalResponse,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';
import { CAPITAL_STATUS } from 'merchant/views/PartnerDashboard/constants';

describe('activationStatusHelper', () => {
  test('should return status based on received value', () => {
    expect(activationStatusMap(CAPITAL_STATUS.bureau_submission)).toStrictEqual(
      'Bureau Submission',
    );
    expect(activationStatusMap(CAPITAL_STATUS.income_proof_submission)).toStrictEqual(
      'Income Proof Submission',
    );
    expect(activationStatusMap(CAPITAL_STATUS.pv_pending)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.pv_processing)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.stp_processing)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.offer_acceptance)).toStrictEqual('Offer Acceptance');
    expect(activationStatusMap(CAPITAL_STATUS.post_offer_docs_collection)).toStrictEqual(
      'Post Offer Docs Collection',
    );
    expect(activationStatusMap(CAPITAL_STATUS.post_offer_docs_verification)).toStrictEqual(
      'In Process',
    );
    expect(activationStatusMap(CAPITAL_STATUS.esign_initiaiton)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.merchant_esign_pending)).toStrictEqual(
      'Merchant ESign Pending',
    );
    expect(activationStatusMap(CAPITAL_STATUS.merchant_nach_pending)).toStrictEqual(
      'Merchant Nach Pending',
    );
    expect(activationStatusMap(CAPITAL_STATUS.razorpay_esign_pending)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.lender_decision)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.lender_response)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.pre_offer_docs_resubmission)).toStrictEqual(
      'Pre Offer Docs Resubmission',
    );
    expect(activationStatusMap(CAPITAL_STATUS.rejection_bucket)).toStrictEqual('Rejection');
    expect(activationStatusMap(CAPITAL_STATUS.uw_processing)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.uw_hold)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.application_closed)).toStrictEqual(
      'Application Closed',
    );
    expect(activationStatusMap(CAPITAL_STATUS.go_live)).toStrictEqual('Go Live');
    expect(activationStatusMap(CAPITAL_STATUS.pre_offer_verification)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.send_to_lender)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.lender_docs_resubmission)).toStrictEqual(
      'In Process',
    );
    expect(activationStatusMap(CAPITAL_STATUS.cpv_pending)).toStrictEqual('CPV Pending');
    expect(activationStatusMap(CAPITAL_STATUS.in_process)).toStrictEqual('In Process');
    expect(activationStatusMap(CAPITAL_STATUS.application_initiation)).toStrictEqual(
      'Application Initiation',
    );
    expect(activationStatusMap(CAPITAL_STATUS.application_rejected)).toStrictEqual('Rejection');
    expect(activationStatusMap('something default')).toStrictEqual('');
  });
});

describe('filterApplication', () => {
  test('should return null if data is empty', () => {
    expect(filterApplications([])).toStrictEqual(null);
  });
});

describe('getFormattedCapitalResponse', () => {
  const { items } = accountsListResponse;
  test('should return subMerchant data if applicationData is not passed or it is empty', () => {
    const data = {};
    expect(getFormattedCapitalResponse(data, items)).toStrictEqual(items);
  });

  test('should return capitalActivationStatus as empty string if applicationData is not there for passed id', () => {
    expect(getFormattedCapitalResponse(capitalApplicationsResponse, [items[2]])).toStrictEqual([
      {
        ...items[2],
        capitalActivationStatus: '',
      },
    ]);
  });
});
