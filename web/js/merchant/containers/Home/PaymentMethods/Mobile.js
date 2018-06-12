import React, { Component } from 'react';

import { globalGroupTitleMap } from 'rzp/utils/pokedex';
import {
  getFormattedAmountNew,
  getFormattedNumber,
  titleCase,
} from 'rzp/utils/rzp-utils';

import GenericPanel, {
  PanelTopbar,
  PanelBody,
  PanelFooter,
} from 'merchant/components/Home/GenericPanel';
import GroupingDropdown from 'merchant/components/Home/GroupingDropdown';
import StackedBars from 'merchant/containers/Home/StackedBars';
import {
  paymentMethodsOrder,
  getPaymentMethodColor,
} from 'merchant/components/Home/data';

const formatText = text => {
  return globalGroupTitleMap[text.toLowerCase()] || titleCase(text);
};

class MobilePaymentMethods extends Component {
  constructor(props) {
    super(props);
  }

  render() {
    const {
      isLoading,
      hasNoData,
      aggTypes,
      onAggChange,
      selectedAgg,
      error,
      data,
      aggKey,
      isCurrency,
    } = this.props;

    return (
      <GenericPanel
        className="payment-methods-container"
        isLoading={isLoading}
        hasNoData={hasNoData}
        error={error}
      >
        <PanelTopbar className="clearfix">
          <GroupingDropdown
            grouping={aggTypes}
            onGroupChange={onAggChange}
            selectedGrouping={selectedAgg}
          />
        </PanelTopbar>
        <PanelBody>
          {!isLoading &&
            !hasNoData && (
              <StackedBars
                data={data}
                textKey={aggKey}
                getColor={getPaymentMethodColor}
                formatText={formatText}
                formatValue={
                  (isCurrency && getFormattedAmountNew) || getFormattedNumber
                }
              />
            )}
        </PanelBody>
      </GenericPanel>
    );
  }
}

export default MobilePaymentMethods;
