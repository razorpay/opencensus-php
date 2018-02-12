import React from 'react';
import Group, { GroupItem } from 'rzp/ui/Group';
import { PowerSelect } from 'react-power-select';

const GroupingDropdown = ({
  grouping,
  onGroupChange,
  selectedGrouping,
  displayTextKey = 'text',
}) => (
  <div className="grouping-dropdown">
    <Group>
      <GroupItem>
        <svg xmlns="http://www.w3.org/2000/svg" className="grouping-icon">
          <path d="M0 12h6v-2H0v2zM0 0v2h18V0H0zm0 7h12V5H0v2z" />
        </svg>
      </GroupItem>
      <GroupItem className="dropdown-group-item">
        <PowerSelect
          className="react-normal-select"
          onChange={onGroupChange}
          options={grouping}
          optionLabelPath={displayTextKey}
          searchEnabled={false}
          selected={selectedGrouping}
        />
      </GroupItem>
    </Group>
  </div>
);

export default GroupingDropdown;
