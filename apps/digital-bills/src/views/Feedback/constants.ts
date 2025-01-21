import type { BreadCrumbType } from '@apps/digital-bills/src/common/components/Breadcrumbs/types';

export const PAGE_ROUTES = {
  campaign: {
    link: '/feedback/campaigns',
    label: 'Campaign',
  },
  responses: {
    link: '/feedback/responses',
    label: 'Responses',
  },
  customerComplaints: {
    link: '/bill-complaints',
    label: 'Customer Complaints',
  },
};

export const PAGE_BREADCRUMBS: BreadCrumbType[] = [
  { label: 'BillMe' },
  { label: 'Feedback and Complaints' },
];
