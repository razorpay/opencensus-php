import { CommonApiResponse } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';

export const createSubmerchantInvite = (params) => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Create',
    mode: 'live',
    method: 'post',
    data: { invite: params },
  });
};

type FetchBureauLinkResponse = CommonApiResponse<{
  bureau_link: string;
  sms_count?: number;
}>;

type SendMessageResponse = CommonApiResponse<{
  status?: string;
  sms_count?: number;
}>;

type uploadBankStatementResponse = CommonApiResponse<{
  status: string;
  store_id: string;
}>;

type submitBankStatementResponse = CommonApiResponse<{
  status: string;
}>;

export const fetchBureauLink = (
  partnerId: string,
  merchantId: string,
): Promise<FetchBureauLinkResponse> => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/GenerateBureauLink',
    method: 'post',
    data: {
      merchant_id: merchantId,
      partner_id: partnerId,
    },
  });
};

export const sendMessage = (
  partnerId: string,
  merchantId: string,
  bureauLink: string,
): Promise<SendMessageResponse> => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/CommunicateBureauLink',
    method: 'post',
    data: {
      merchant_id: merchantId,
      partner_id: partnerId,
      bureau_link: bureauLink,
    },
  });
};

type UploadData = {
  document: string;
  partner_id: string;
  merchant_id: string;
  document_type: string;
  display_name: string;
  name: string;
};
export const uploadBankStatement = (
  uploadData: UploadData,
  progressTracker: any,
): Promise<uploadBankStatementResponse> => {
  return merchantFetch({
    url: 'los/service/twirp/rzp.capital.los.origination.v1.ApplicationAPI/UploadDocumentToStore',
    method: 'post',
    data: uploadData,
    onUploadProgress: progressTracker,
  });
};

export const submitBankStatements = (data: {
  partner_id: string;
  merchant_id: string;
  application_id: string;
  files: {
    id: string;
    store_id: string | undefined;
    store_type: string;
    file_type: string;
    name: string | undefined;
  }[];
}): Promise<submitBankStatementResponse> => {
  return merchantFetch({
    url: 'partnerships/twirp/rzp.partnerships.onboarding.capital.v1.CapitalAPI/UploadDocuments',
    method: 'post',
    data,
    headers: {
      'Content-Type': 'application/json',
    },
  });
};
