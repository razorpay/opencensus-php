import React from 'react';

import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';

const MethodFilter = (props) => {
  const { filtersList, handleGroupingChange, disabled, selectedGrouping } = props;

  const renderGroupingDropdown = (groupingData = [], index) => {
    if (groupingData?.length > 0) {
      return (
        <GroupingDropdown
          key={index}
          className={`sr-tab__filter ${disabled ? ' PowerSelect--disabled' : ''}`}
          onGroupChange={handleGroupingChange}
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
