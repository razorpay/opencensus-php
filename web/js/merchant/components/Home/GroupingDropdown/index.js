import React from 'react';
import Group, { GroupItem } from 'common/ui/Group';
import { classList } from 'common/utils/rzp-utils';
import { PowerSelect } from 'react-power-select';

const GroupingDropdown = ({
  grouping,
  onGroupChange,
  selectedGrouping,
  displayTextKey = 'text',
  className,
}) => (
  <div className="grouping-dropdown">
    <Group>
      <GroupItem>
        <i className="i i-sort grouping-icon" />
      </GroupItem>
      <GroupItem className="dropdown-group-item">
        <PowerSelect
          className={classList(className, 'react-normal-select')}
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
