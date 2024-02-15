import React from 'react';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { User } from 'common/typings';
import { POSAgents } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/POSClients/AllInvites/api';
import { ListFiltersContextType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/context';

type InvitedByFilterProps = {
  onChange: ListFiltersContextType['handleChange'];
  posAgents: POSAgents;
  user: User;
  value: string;
};
const InvitedByFilter = ({
  onChange,
  posAgents,
  user,
  value,
}: InvitedByFilterProps): JSX.Element => {
  const invitedByFilterMenu = [{ id: user.user?.id as string, name: 'Self' }].concat(
    posAgents.filter((agent) => agent.id !== user.user?.id),
  );
  return (
    <Dropdown selectionType="single">
      <SelectInput
        label="Invited By"
        labelPosition="top"
        name="inviter_user_id"
        onChange={({ name, values }) => {
          onChange({ name, value: values[0] });
        }}
        placeholder="Value"
        validationState="none"
        value={value}
      />
      <DropdownOverlay>
        <ActionList>
          {invitedByFilterMenu.map(({ name, id }) => (
            <ActionListItem title={name} value={id} key={id} />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  null,
)(InvitedByFilter);
