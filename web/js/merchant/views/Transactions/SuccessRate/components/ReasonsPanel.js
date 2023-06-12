import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import StyledHeader from './StyledHeader';
import NoDataMessage from './NoDataMessage';
import {
  getFormattedNumber,
  getNoDataTitle,
  getNoDataSubTitle,
} from 'merchant/views/Transactions/SuccessRate/helper';

import { PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT } from 'merchant/views/Transactions/SuccessRate/constants';
import SwitchField from 'common/ui/Forms/SwitchField';
import { Box, Text } from '@razorpay/blade/components';

const LoadingState = (
  <div className="rp-panel">
    <PlaceholderLoader style={{ width: '60%' }} />
    <PlaceholderLoader />
  </div>
);

const renderErrorDetails = ({ count, reason }, index) => (
  <div key={index} className="col-md-4" data-testid="failure-reason-item">
    <div className="rp-grid__item">
      <p className="item-count">{getFormattedNumber(count)}</p>
      <p className="item-description" data-testid="failure-reason-text">
        {reason}
      </p>
    </div>
  </div>
);

const ReasonsPanel = ({
  isLoading,
  title = '',
  heading = '',
  data = [],
  tab,
  toggleOption,
  enabledToggleOption,
  toggleErrorType,
}) => {
  const noDataTitle = !tab?.data?.total
    ? getNoDataTitle(tab)
    : `There were no ${title?.toLowerCase()} payment failure reasons reported for ${
        PAYMENT_METHOD_VS_CALLOUT_DISPLAY_TEXT[tab?.name] || tab?.name
      } in the selected date range`;

  if (isLoading) return LoadingState;

  return (
    <div className="rp-panel">
      <div className="rp-panel--header">
        <StyledHeader text={heading} dataTestId="failure-reasons-header" />
        {!!toggleOption ? (
          <Box display="flex" alignItems="center" gap="spacing.4">
            <Text size="medium" variant="subdued">
              {toggleOption.name}
            </Text>
            <SwitchField
              type="prime round"
              onChange={toggleErrorType}
              checked={enabledToggleOption === toggleOption.key}
              aria-label="failure-reason-toggle"
            />
          </Box>
        ) : null}
      </div>
      {data?.length > 0 ? (
        <div className="row rp-grid">{data.map(renderErrorDetails)}</div>
      ) : (
        <NoDataMessage
          title={noDataTitle}
          subtitle={!tab?.data?.total ? getNoDataSubTitle(tab) : ''}
        />
      )}
    </div>
  );
};

export default ReasonsPanel;
