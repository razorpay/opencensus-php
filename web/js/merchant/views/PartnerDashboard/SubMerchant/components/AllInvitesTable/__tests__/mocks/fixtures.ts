// Note: these fixtures are reused in an AccountsList test as well.
export const allInvitesData = {
  status_code: 200,
  success: true,
  data: {
    count: 2,
    items: [
      {
        id: 'M0zHasnGQMmkdG',
        partner_id: 'KVAmykHjGq8Dv1',
        email: 'as@as.com',
        name: 'ab123',
        product: 'primary',
        email_status: 'SENT',
        contact_status: 'SENT',
        created_at: '2023-06-12T10:35:21Z',
        updated_at: '2023-06-12T11:28:42Z',
      },
      {
        id: 'N0zHasnGQMmkdG',
        partner_id: 'KVAmykHjGq8Dv1',
        email: 'as1@as.com',
        name: 'ab124',
        product: 'primary',
        email_status: 'SENT',
        contact_status: 'SENT',
        created_at: '2023-06-12T10:35:21Z',
        updated_at: '2023-06-12T11:28:42Z',
      },
    ],
  },
};

export const allInvitesDataEmpty = {
  status_code: 200,
  success: true,
  data: { count: 0, items: [] },
};

export const resendInviteData = {
  success: true,
};

export const resendInviteErrorData = {
  success: false,
};
