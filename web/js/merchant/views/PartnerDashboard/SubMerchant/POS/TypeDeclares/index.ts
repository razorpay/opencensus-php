export type actionStateType = {
  name: string;
  admin_id: string;
  merchant_id: string;
  metadata: {
    actor_email: string;
    actor_name: string;
    actor_id: string;
    actor_role: string;
  };
  created_at: string;
  updated_at: string;
}[];

export type clarificationReasonType = {
  from: string;
  nc_count: number;
  created_at: string;
  is_current: boolean;
  reason_code: string;
  reason_type: string;
};
export type clarificationReasonsType = {
  [key: string]: clarificationReasonType[];
};

export interface PosSubmerchantDetailsResponseDataType {
  id: string;
  name: string;
  email: string;
  created_at: number;
  entity: string;
  partner_type: string | null;
  dashboard_access: boolean;
  application: {
    id: string;
  };
  product: {
    [key: string]: string[];
  };
  user: {
    id: string;
    name: string;
    email: string;
    contact_mobile: string;
    contact_mobile_verified: boolean;
  };
  kyc_access: {
    state?: string | null;
    token_expiry?: number | null;
    rejection_count?: number | null;
  } | null;
  details: {
    activation_status: string;
    kyc_clarification_reasons: {
      nc_count?: number;
      additional_details?: [];
      clarification_reasons_v2?: clarificationReasonsType;
    };
  };
  pos: {
    activation_status: string;
    success: boolean;
    action_states: actionStateType;
    last_kyc_performed_by: {
      contact_email: string;
      contact_no: string;
      name: string;
      type: string;
      id: string;
    };
  };
}

export type kycHistoryObjectType = {
  status: string;
  date: string;
  performedBy: string;
  reason?: { field: string; reason: string }[];
};

export type parsedActionStateType = Omit<actionStateType[0], 'created_at' | 'updated_at'> & {
  created_at: number;
  updated_at: number;
};

export type parsedActionStatesType = parsedActionStateType[];

type parsedClarificationReasonType = Omit<clarificationReasonType, 'created_at'> & {
  created_at: number;
};

export type parsedClarificationReasonsType = {
  [key: string]: parsedClarificationReasonType[];
};
export type parsedDetailsType = Omit<
  PosSubmerchantDetailsResponseDataType['details'],
  'kyc_clarification_reasons'
> & {
  kyc_clarification_reasons: {
    clarification_reasons_v2?: parsedClarificationReasonsType;
    nc_count?: number;
    additional_details?: [];
  };
};

type parsedPosType = Omit<PosSubmerchantDetailsResponseDataType['pos'], 'action_states'> & {
  action_states: parsedActionStateType[];
};

export type parsedResponseDataType = Omit<
  PosSubmerchantDetailsResponseDataType,
  'details' | 'pos'
> & {
  details: parsedDetailsType;
  pos: parsedPosType;
};
