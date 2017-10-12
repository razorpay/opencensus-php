/* RESOURCE UTILS */

export function openMerchantEntity() {
  const url = window.location.href + '/' + this.id;
  console.log('MERCHANT ENTITY..', this.id, window.location.href);
  window.open(url);
}

export const fields = [
  ['Merchant ID', item => item.id],
  ['Name', item => item.name],
  ['Email', item => item.email],
  ['Referrer', item => item.referrer],
  ['Marketplace Owner', item => item.parent_id],
  ['Status', item => item.count],
  ['Registered At', item => item.created_at],
  ['Submitted At', item => item.merchant_detail.submitted_at],
  ['Tags', item => item.tag_list.join()],
];

export function getDetails(data) {
  return [
    {
      label: 'Group Details',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Admins',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Tags',
      value: '',
    },
    {
      label: 'Features',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Balance(Test)',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Balance(Live)',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Max Payment Amount',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Name',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Email',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Website',
      value: data.merchantDetails.amount,
    },
    {
      label: 'MCC',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Category 2',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Billing Label',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Merchant Handle',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Transaction Report Email',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Internationl',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Registration Date',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Submission Date',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Activation Date',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Confirmed',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Activation Form Progress',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Activation Form Submitted',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Activation Form Status',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Activated',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Live',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Funds on Hold',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Risk Rating',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Risk Threshold',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Fee Bearer',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Fee Model',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Settlement Schedule',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Methods',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Archived',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Suspended',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Customer Receipt Emails',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Print Screenshots',
      value: data.merchantDetails.amount,
    },
    {
      label: 'Pricing Plan',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Terminal',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Gateway Rules',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Offers',
      value: () => <button>Show/Hide</button>,
    },
    {
      label: 'Credits',
      value: () => <button>Show/Hide</button>,
    },
  ];
}
