import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import {
  fetchSuccessRate,
  fetchMerchantErrors,
  setFailureReasonType,
  setMethodType,
} from 'merchant/reducers/successRate';
import { METHOD_TYPES_MAP } from 'merchant/views/Transactions/SuccessRate/constants';
import GroupingDropdown from 'merchant/containers/Home/GroupingDropdown';
import {
  checkIfFilterValid,
  queryFilters,
  getMerchantErrorsPayload,
} from 'merchant/views/Transactions/SuccessRate/helper';
import MethodType from './MethodType';

const MethodFilter = (props) => {
  const {
    tab,
    filtersList,
    handleGroupingChange,
    disabled,
    selectedGrouping,
    isInternationalEnabled,
    activeTab,
    fetchSuccessRate,
    fetchMerchantErrors,
    setFailureReasonType,
    user,
    setMethodType,
  } = props;

  const { selectedMethodType } = tab;

  const renderGroupingDropdown = (groupingData = [], index) => {
    const filteredGroupingData = groupingData.filter(({ value }) =>
      checkIfFilterValid({ activeTab, filter: value, flags: { isInternationalEnabled } }),
    );

    if (filteredGroupingData.length > 0) {
      return (
        <GroupingDropdown
          key={index}
          className={`sr-tab__filter ${disabled ? ' PowerSelect--disabled' : ''}`}
          onGroupChange={handleGroupingChange}
          grouping={filteredGroupingData}
          selectedGrouping={selectedGrouping?.[index] || filteredGroupingData?.[0]}
        />
      );
    }
    return null;
  };

  const handleCardTypeChange = (selectedValue) => {
    if (selectedMethodType === selectedValue) return;

    setMethodType(selectedValue);
    setFailureReasonType('default');
    const updateDropdownOptions = activeTab !== 'Overall';
    const payload = queryFilters(updateDropdownOptions);
    fetchSuccessRate({ payload, resetSelectedInterval: false });
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);
  };

  const currentMethodType = METHOD_TYPES_MAP[activeTab];
  const renderMethodType = currentMethodType?.shouldRender?.({
    user,
  });

  return (
    <div className="sr-filter sr-method-filters flex">
      {renderMethodType ? (
        <MethodType
          currentMethodType={currentMethodType}
          selectedMethodType={selectedMethodType}
          handleCardTypeChange={handleCardTypeChange}
        />
      ) : null}
      {filtersList.length ? (
        <div>
          <label>Filter:</label>
          <div data-testid="group-filters-dropdown" className="flex">
            {filtersList?.map(renderGroupingDropdown)}
          </div>
        </div>
      ) : null}
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchSuccessRate,
      fetchMerchantErrors,
      setFailureReasonType,
      setMethodType,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(MethodFilter);
