import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { BtnGroup, Btn } from 'common/ui/BtnGroup/index';
import {
  setCardTypeFilter,
  fetchSuccessRate,
  fetchMerchantErrors,
} from 'merchant/reducers/successRate';

import { CARD_TYPES } from 'merchant/views/Transactions/SuccessRate/constants';
import {
  queryFilters,
  getMerchantErrorsPayload,
} from 'merchant/views/Transactions/SuccessRate/helper';

const CardTypes = ({
  selectedCardType,
  setCardTypeFilter,
  fetchSuccessRate,
  fetchMerchantErrors,
}) => {
  const handleCardTypeChange = (selectedValue) => {
    if (selectedCardType === selectedValue) return;

    setCardTypeFilter(selectedValue);

    const payload = queryFilters();
    fetchSuccessRate({ payload, resetSelectedInterval: false });
    const errorsPaylod = getMerchantErrorsPayload();
    fetchMerchantErrors(errorsPaylod);
  };

  return (
    <BtnGroup
      className="panel-action-item"
      value={selectedCardType}
      onChange={handleCardTypeChange}
    >
      {CARD_TYPES.map(({ label, name }) => (
        <Btn className="btn-default" value={name} key={name}>
          {label}
        </Btn>
      ))}
    </BtnGroup>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { activeTab, tabs } = successRate;
  const { selectedCardType = '' } = tabs[activeTab];

  return { selectedCardType };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ setCardTypeFilter, fetchSuccessRate, fetchMerchantErrors }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(CardTypes);
