export const rewardsHistory = {
  tableData: {
    nodes: [
      {
        id: '1',
        start_date: 1677954600,
        end_date: 1732213800,
        rewards_type: 'fee_credit',
        rewards_earned: 6845,
        milestone_details: {
          current_gmv: 378880,
          milestone: '25 Lakhs',
        },
        disbursal_date: 1715106600,
        rewards_disbursal_status: 'rejected',
      },
      {
        id: '2',
        start_date: 1697740200,
        end_date: 1719945000,
        rewards_type: 'voucher',
        rewards_earned: 9,
        milestone_details: {
          current_gmv: 695882,
          milestone: '10 Lakhs',
        },
        disbursal_date: 1685039400,
        rewards_disbursal_status: 'processed',
      },
      {
        id: '3',
        start_date: 1693852200,
        end_date: 1721500200,
        rewards_type: 'fee_credit',
        rewards_earned: 3308,
        milestone_details: {
          current_gmv: 320626,
          milestone: '25 Lakhs',
        },
        disbursal_date: 1711218600,
        rewards_disbursal_status: 'pending',
      },
    ],
  },
  totalCount: 3,
};

export const expectedRewards = [
  [
    '04 Mar 2023 - 21 Nov 2024',
    '₹378,880.00',
    '6845 credits',
    '07 May 2024',
    '25 Lakhs',
    'Rejected',
  ],
  ['19 Oct 2023 - 02 Jul 2024', '₹695,882.00', '9 vouchers', '25 May 2023', '10 Lakhs', 'Credited'],
  [
    '04 Sep 2023 - 20 Jul 2024',
    '₹320,626.00',
    '3308 credits',
    '23 Mar 2024',
    '5 Lakhs',
    'Processing',
  ],
];
