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
        <i className="i i-sort grouping-icon" />
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
