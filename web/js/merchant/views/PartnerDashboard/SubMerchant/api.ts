import pickBy from 'lodash/pickBy';

import { CommonApiResponse, User } from 'common/typings';
import { merchantFetch } from 'merchant/utils/ajax';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

interface CreateSubmerchantInvitePayload {
  contact_no: string;
  name: string;
  email: string;
  request_kyc_access?: boolean | null;
  partner_id: string;
  inviter_user_id?: string;
  inviter_email?: string;
  product: string;
  metadata?: {
    application_id: string;
    client_id: string;
    oauth_referral: boolean;
    redirect_uri: string;
    scope: string;
  };
}
type createSubmerchantInviteArgs = {
  user: User;
  productType: string;
} & Pick<
  CreateSubmerchantInvitePayload,
  'name' | 'email' | 'contact_no' | 'request_kyc_access' | 'metadata'
>;
export type CommonCreateSubmerchantResponse = CommonApiResponse<{ success: boolean }, string[]>;
export const createSubmerchantInvite = ({
  user,
  productType,
  name,
  email,
  contact_no,
  request_kyc_access,
  metadata,
}: createSubmerchantInviteArgs): Promise<CommonCreateSubmerchantResponse> => {
  const partner_id = user.id as string;

  let invite: Partial<CreateSubmerchantInvitePayload> = {
    name,
    email,
    contact_no,
    partner_id,
    product: productType,
    request_kyc_access,
    metadata,
  };
  // additional payload for POS Partner or POS Agent
  if (productType === PRODUCT_TYPE.POS) {
    invite = {
      ...invite,
      inviter_user_id: user.user?.id,
      inviter_email: user.user?.email,
    };
  }
  // removed undefined keys
  invite = pickBy(invite, (v) => v !== undefined);

  return merchantFetch({
    url: 'partnerships/twirp/rzp.commissions.invites.v1.InviteAPI/Create',
    mode: 'live',
    method: 'post',
    data: { invite },
  });
};

export type CommonSubmerchantBatchResponse = CommonApiResponse<{ status: boolean }, string[]>;

export type ValidateReferralInvitesBatchType = () => Promise<CommonSubmerchantBatchResponse>;
export type CreateReferralInvitesBatchType = (args: {
  file_id: string;
  config: Pick<CreateSubmerchantInvitePayload, 'product' | 'metadata' | 'request_kyc_access'>;
}) => Promise<CommonSubmerchantBatchResponse>;

export type ValidateSubmerchantsBatchType = () => Promise<CommonSubmerchantBatchResponse>;
export type CreateSubmerchantsBatchType = (args: {
  file_id: string;
  config: { product: string };
}) => Promise<CommonSubmerchantBatchResponse>;

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

type sendKYCRequestResponse = CommonApiResponse<{ status: string }>;

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

export const sendKYCRequest = (submerchantId: string): Promise<sendKYCRequestResponse> => {
  return merchantFetch({
    url: 'partner/kyc_access_request',
    method: 'post',
    data: { entity_id: submerchantId },
  });
};
