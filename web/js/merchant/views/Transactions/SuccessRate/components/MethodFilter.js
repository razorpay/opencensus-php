import React from 'react';

import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';

const MethodFilter = (props) => {
  const { filtersList, handleGroupingChange, selectedGrouping } = props;

  const renderGroupingDropdown = (groupingData = [], index) => {
    if (groupingData?.length > 0) {
      return (
        <GroupingDropdown
          className="success-rate-tab-filter"
          onGroupChange={handleGroupingChange(index)}
          grouping={groupingData}
          selectedGrouping={selectedGrouping?.[index] || groupingData?.[0]}
        />
      );
    }
    return null;
  };

  if (filtersList?.length > 0) {
    return (
      <div className="filter">
        <div>
          <label>Filter Via:</label>
          <div className="flex">{filtersList?.map(renderGroupingDropdown)}</div>
        </div>
      </div>
    );
  }
  return null;
};

export default MethodFilter;
