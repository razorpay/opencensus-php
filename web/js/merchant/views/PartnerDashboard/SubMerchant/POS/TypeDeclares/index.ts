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
  created_at: number;
  updated_at: number;
}[];

export type clarificationReasonsType = {
  [key: string]: {
    from: string;
    nc_count: number;
    created_at: number;
    is_current: boolean;
    reason_code: string;
    reason_type: string;
  }[];
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
      nc_count: number;
      additional_details?: [];
      clarification_reasons_v2: clarificationReasonsType;
    };
  };
  pos: {
    activation_status: string;
    success: boolean;
    action_state: actionStateType;
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
