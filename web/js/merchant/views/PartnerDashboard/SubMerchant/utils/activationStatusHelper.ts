import { merchantFetch } from 'merchant/utils/ajax';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { CAPITAL_STATUS } from 'merchant/views/PartnerDashboard/constants';

const filterApplications = (data: TODO_PD): TODO_PD => {
  if (data.length > 0) {
    let dataIndex;
    let value;
    data.forEach((item, index) => {
      if (index === 0) {
        value = item.created_at;
        dataIndex = index;
      }
      if (item.created_at > value) {
        dataIndex = index;
        value = item.created_at;
      }
    });
    return data[dataIndex];
  }
  return null;
};

export const getActivationStatusData = async (merchantId: string): Promise<TODO_PD> => {
  const body = {
    product_id: 'EzKCyq0So3rVWU',
    merchant_id: merchantId.replace('acc_', ''),
    states: ['STATE_CREATED', 'STATE_COMPLETED'],
  };
  const response = await merchantFetch({
    url: `los/service/twirp/rzp.capital.los.origination.v1.ApplicationAPI/GetApplicationsByParam`,
    method: 'post',
    data: body,
  });
  if (response?.status_code === 200 && response?.data) {
    const {
      data: { applications },
    } = response;
    return filterApplications(applications);
  } else {
    return null;
  }
};

export const activationStatusMap = (status: string): string => {
  switch (status.toLocaleLowerCase()) {
    case CAPITAL_STATUS.bureau_submission:
      return 'Bureau submission';
    case CAPITAL_STATUS.income_proof_submission:
      return 'Income proof submission';
    case CAPITAL_STATUS.pv_pending:
      return 'Pre-offer Verification';
    case CAPITAL_STATUS.pv_processing:
      return 'Pre-offer Verification';
    case CAPITAL_STATUS.stp_processing:
      return 'Pre-offer Verification';
    case CAPITAL_STATUS.offer_acceptance:
      return 'Offer acceptance';
    case CAPITAL_STATUS.post_offer_docs_collection:
      return 'Post offer docs collection';
    case CAPITAL_STATUS.post_offer_docs_verification:
      return 'Post offer docs verification';
    case CAPITAL_STATUS.esign_initiaiton:
      return 'Esign initiaiton';
    case CAPITAL_STATUS.merchant_esign_pending:
      return 'Merchant esign pending';
    case CAPITAL_STATUS.nach_pending:
      return 'NACH pending';
    case CAPITAL_STATUS.rzp_esign_pending:
      return 'RZP esign pending';
    case CAPITAL_STATUS.lender_decision:
      return 'Lender decision';
    case CAPITAL_STATUS.lender_response:
      return 'Lender response';
    case CAPITAL_STATUS.post_offer_docs_resubmission:
      return 'Post offer docs resubmission';
    case CAPITAL_STATUS.rejection_bucket:
      return 'Rejection bucket';
    case CAPITAL_STATUS.uw_processing:
      return 'UW processing';
    case CAPITAL_STATUS.uw_hold:
      return 'UW hold';
    case CAPITAL_STATUS.application_closed:
      return 'Application closed';
    case CAPITAL_STATUS.go_live:
      return 'Go-live';
    case CAPITAL_STATUS.application_rejected:
      return 'Application rejected';

    default:
      return '';
  }
};
