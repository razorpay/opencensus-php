import { merchantFetch } from 'merchant/utils/ajax';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { CAPITAL_STATUS } from 'merchant/views/PartnerDashboard/constants';

export const activationStatusMap = (status: string): string => {
  switch (status.toLocaleLowerCase()) {
    case CAPITAL_STATUS.bureau_submission:
      return 'Bureau Submission';
    case CAPITAL_STATUS.income_proof_submission:
      return 'Income Proof Submission';
    case CAPITAL_STATUS.pv_pending:
      return 'In Process';
    case CAPITAL_STATUS.pv_processing:
      return 'In Process';
    case CAPITAL_STATUS.stp_processing:
      return 'In Process';
    case CAPITAL_STATUS.offer_acceptance:
      return 'Offer Acceptance';
    case CAPITAL_STATUS.post_offer_docs_collection:
      return 'Post Offer Docs Collection';
    case CAPITAL_STATUS.post_offer_docs_verification:
      return 'In Process';
    case CAPITAL_STATUS.esign_initiaiton:
      return 'In Process';
    case CAPITAL_STATUS.merchant_esign_pending:
      return 'Merchant ESign Pending';
    case CAPITAL_STATUS.merchant_nach_pending:
      return 'Merchant Nach Pending';
    case CAPITAL_STATUS.razorpay_esign_pending:
      return 'In Process';
    case CAPITAL_STATUS.lender_decision:
      return 'In Process';
    case CAPITAL_STATUS.lender_response:
      return 'In Process';
    case CAPITAL_STATUS.pre_offer_docs_resubmission:
      return 'Pre Offer Docs Resubmission';
    case CAPITAL_STATUS.rejection_bucket:
      return 'Rejection';
    case CAPITAL_STATUS.uw_processing:
      return 'In Process';
    case CAPITAL_STATUS.uw_hold:
      return 'In Process';
    case CAPITAL_STATUS.application_closed:
      return 'Application Closed';
    case CAPITAL_STATUS.go_live:
      return 'Go Live';
    case CAPITAL_STATUS.pre_offer_verification:
      return 'In Process';
    case CAPITAL_STATUS.send_to_lender:
      return 'In Process';
    case CAPITAL_STATUS.lender_docs_resubmission:
      return 'In Process';
    case CAPITAL_STATUS.cpv_pending:
      return 'CPV Pending';
    case CAPITAL_STATUS.in_process:
      return 'In Process';
    case CAPITAL_STATUS.application_initiation:
      return 'Application Initiation';
    case CAPITAL_STATUS.application_rejected:
      return 'Rejection';

    default:
      return '';
  }
};

const getUnixTimeStamp = (date: string): number => Math.floor(new Date(date).getTime() / 1000);

export const filterApplications = (data: TODO_PD): TODO_PD => {
  if (data.length > 0) {
    let dataIndex;
    let value;
    data.forEach((item, index) => {
      if (index === 0) {
        value = getUnixTimeStamp(item.created_at);
        dataIndex = index;
      }
      if (getUnixTimeStamp(item.created_at) > value) {
        dataIndex = index;
        value = getUnixTimeStamp(item.created_at);
      }
    });
    return data[dataIndex];
  }
  return null;
};

export const getFormattedCapitalResponse = (data: TODO_PD, subMerchantData: TODO_PD): TODO_PD => {
  if (data?.response) {
    const { response } = data;
    const formattedResponse = subMerchantData.map((item) => {
      const status: TODO_PD = Object.keys(response)
        .filter((key) => key === item.id.replace('acc_', ''))
        .reduce((cur, key) => {
          return response[key];
        }, {});
      if (status?.partner_applications?.length > 0) {
        const { partner_applications } = status;
        const filteredApplication = filterApplications(partner_applications);
        return {
          ...item,
          capitalActivationStatus: activationStatusMap(filteredApplication.stage),
        };
      }
      return { ...item, capitalActivationStatus: '' };
    });
    return formattedResponse;
  }
  return subMerchantData;
};

export const getActivationStatusBulk = async (
  merchantId: string[],
  productId: string,
): Promise<TODO_PD> => {
  const body = {
    product_id: productId,
    merchant_id: merchantId,
  };
  return merchantFetch({
    url: `submerchants/capital/applications`,
    method: 'post',
    data: body,
  });
};
