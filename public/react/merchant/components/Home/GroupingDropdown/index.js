import React from 'react';
import Group, { GroupItem } from 'rzp/ui/Group';
import { PowerSelect } from 'react-power-select';

import './styles.styl';

const GroupingDropdown = ({
  grouping,
  onGroupChange,
  selectedGrouping,
  displayTextKey = 'text',
}) => (
  <div className="grouping-dropdown">
    <Group>
      <GroupItem>
        <svg
          className="grouping-icon"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
        >
          <path d="M21.698 10.658l2.302 1.342-12.002 7-11.998-7 2.301-1.342 9.697 5.658 9.7-5.658zm-9.7 10.657l-9.697-5.658-2.301 1.343 11.998 7 12.002-7-2.302-1.342-9.7 5.657zm12.002-14.315l-12.002-7-11.998 7 11.998 7 12.002-7z" />
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
