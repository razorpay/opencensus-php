import React from 'react';
import { PARTNER_TYPE_DISPLAY_NAMES } from 'merchant/views/PartnerDashboard/constants';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

interface PageHeadingProps {
  user: TODO_PD;
  org: Org;
  partnerName: string;
}

const PageHeading = ({ org, user, partnerName }: PageHeadingProps): JSX.Element => {
  const partnerTypeDisplayName = PARTNER_TYPE_DISPLAY_NAMES[user.partner_type] || '';
  const partnerDashboardPrefix = org.custom_code !== 'rzp' ? '' : `${partnerTypeDisplayName} `;
  return (
    <h2 className="page-heading">{`Welcome to ${partnerDashboardPrefix}Partner dashboard, ${partnerName}!`}</h2>
  );
};

export default PageHeading;
