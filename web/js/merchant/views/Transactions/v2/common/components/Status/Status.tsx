import React from 'react';
import {
  Badge,
  InfoIcon,
  Box,
  Tooltip,
  TooltipInteractiveWrapper,
  CloseIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { titleCase } from 'common/utils/rzp-utils';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { PaymentStatus } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

import { StatusProps } from './types';
import GatewayDataInfo from 'merchant/components/GatewayDataInfo';
import { useLocation } from 'react-router-dom';

const Status = ({
  gatewayData,
  variant,
  content,
  status,
  isFailedIconEnabled,
  user,
}: StatusProps): JSX.Element => {
  const { pathname } = useLocation();
  const isRefundsRoute = pathname.includes('/refunds');

  const shouldDisplayGatewayInformation = user.isOptimizerView() && isRefundsRoute;

  const getTooltipContent = () => {
    if (shouldDisplayGatewayInformation) {
      return <GatewayDataInfo gatewayData={gatewayData} isTransactionV2={true} />;
    }
    return content;
  };

  return (
    <Box display="flex">
      <Badge
        marginLeft={{
          base: 'auto',
          l: 'spacing.0',
        }}
        color={variant}
        icon={(props) =>
          isFailedIconEnabled && status === PaymentStatus.FAILED ? (
            <CloseIcon {...props} />
          ) : (
            <TooltipWrapper
              onClick={(e) => {
                e.stopPropagation();
              }}
            >
              <Tooltip
                title={shouldDisplayGatewayInformation ? 'Gateway response' : undefined}
                // eslint-disable-next-line
                // @ts-expect-error JSX can be assigned to Tooltip content prop
                content={getTooltipContent()}
              >
                <TooltipInteractiveWrapper>
                  <InfoIcon {...props} />
                </TooltipInteractiveWrapper>
              </Tooltip>
            </TooltipWrapper>
          )
        }
        size="large"
      >
        {titleCase(status)}
      </Badge>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(Status);
