export type InvitationStatusT = 'awaiting' | 'onboarded';
export type RoleT = 'partner_agent'; //more roles to be added in v2

export interface InviteMemberDataT {
  role: RoleT;
  name: string;
  contactMobile: string;
  id?: string;
}

export interface SendInviteApiT {
  name: string;
  role: RoleT;
  contactMobile: string;
  senderName: string;
  dialCode: string;
}

export interface ResendSmsApiT {
  id: string;
  senderName: string;
}

export interface UpdateMemberApiT {
  id: string;
  role: RoleT;
  name: string;
}

export interface TableItemT {
  name: string;
  contactMobile: string;
  role: RoleT;
  isConfirmed: boolean;
  id: string;
}

export interface InvitationT {
  id: string;
  contact_mobile: string;
  role: RoleT;
}

export interface InvitationResponseT {
  data: {
    items: InvitationT[];
  };
}

export interface AcceptedUserT {
  name: string;
  contact_mobile: string;
  role: RoleT;
  confirmed: boolean;
  id: string;
}
