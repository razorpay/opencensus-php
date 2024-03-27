import React from 'react';

import { POSAgents } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/api';
import { GetFiltersTypeNoArgs } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper';
import {
  commonFilterInputs,
  ListFilterConfig,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/CommonFilters';

import InvitedByFilter from './InvitedByFilter';
const { nameField, emailField, contactField } = commonFilterInputs;

type customFiltersGetterArgs = { posAgents: POSAgents };
export const customFiltersGetter = ({
  posAgents,
}: customFiltersGetterArgs): GetFiltersTypeNoArgs => {
  const getFiltersList = () => {
    const invitedByField: ListFilterConfig = {
      fieldType: 'custom',
      fieldName: 'inviter_user_id',
      fieldWidth: '190px',
      Component: ({ onChange, value }) => (
        <InvitedByFilter value={value} posAgents={posAgents} onChange={onChange} />
      ),
    };
    return [nameField, emailField, contactField, invitedByField];
  };
  return getFiltersList;
};
