export const partnerAgentsData = [
  {
    id: 'testInviter1',
    name: 'Test Inviter 1',
    email: 'test1@gmail.com',
    role: 'partner_agent',
  },
  {
    id: 'testInviter2',
    name: 'Test Inviter 2',
    email: 'test1@gmail.com',
    role: 'partner_agent',
  },
];

export const allInvitesDataPOS = {
  status_code: 200,
  success: true,
  data: {
    count: 2,
    items: [
      {
        id: 'NPnojQIPdBmqs2',
        partner_id: 'testUserId',
        email: 'udayraj.deshmukh+testpos@razorpay.com',
        name: 'test name 1',
        product: 'pos',
        email_status: 'SENT',
        contact_status: 'SENT',
        created_at: '2024-01-17T19:55:33Z',
        updated_at: '2024-01-17T19:55:35Z',
        inviter_user_id: partnerAgentsData[0].id,
        inviter_email: partnerAgentsData[0].email,
      },
      {
        id: 'NPno8dzUUxeDy1',
        partner_id: 'testUserId',
        email: 'test@test.com',
        name: 'test name 2',
        product: 'pos',
        email_status: 'SENT',
        contact_status: 'SENT',
        created_at: '2024-01-17T19:55:00Z',
        updated_at: '2024-01-17T19:55:01Z',
        inviter_user_id: 'testUserId',
        inviter_email: 'test@test.com',
      },
    ],
  },
};

export { allInvitesDataEmpty as allInvitesDataEmptyPOS } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/fixtures';

export { resendInviteData as resendInviteDataPOS } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/__tests__/mocks/fixtures';
