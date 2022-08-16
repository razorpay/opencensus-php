import React from 'react';
import { isEmpty, map } from 'lodash';
import store from 'merchant/store';
import Spinner from 'common/ui/Spinner';
import ReasonsPanel from '../components/ReasonsPanel';
import VTab from '../components/VTab';
import NoDataMessage from '../components/NoDataMessage';
import StyledHeader from '../components/StyledHeader';
import { ERROR_CATEGORIES_VS_DISPLAY_TEXT } from '../constants';

const geTabData = (merchantErrors) => {
  return map(merchantErrors, (errorDetails, errorLabel) => {
    const errorDisplayText = ERROR_CATEGORIES_VS_DISPLAY_TEXT[errorLabel] || errorLabel;
    return {
      label: errorLabel,
      errorDisplayText,
      totalCount: errorDetails.reduce((count, data) => count + Number(data?.count), 0),
      panel: <ReasonsPanel heading={`Failure reasons: ${errorDisplayText}`} data={errorDetails} />,
    };
  });
};

const FailureReasonsWidget = () => {
  const [activeTab, setActiveTab] = React.useState(0);
  const { successRate } = store?.getState();
  const { isLoadingMerchantErrors, merchantErrors } = successRate;

  const handleTabChange = (tab) => {
    setActiveTab(tab);
  };

  if (isLoadingMerchantErrors) {
    return (
      <div className="box-widget volume-container">
        <Spinner />
      </div>
    );
  }

  if (isEmpty(merchantErrors)) {
    return (
      <div className="box-widget">
        <StyledHeader text="Failure reasons" />
        <NoDataMessage title="No data available." />
      </div>
    );
  }

  return (
    <div className="box-widget reasons-container">
      <VTab
        defaultActiveKey={0}
        selected={activeTab}
        tabs={geTabData(merchantErrors)}
        onTabChange={handleTabChange}
        ariaLabel="Vertical Tabs"
      />
    </div>
  );
};

export default FailureReasonsWidget;
