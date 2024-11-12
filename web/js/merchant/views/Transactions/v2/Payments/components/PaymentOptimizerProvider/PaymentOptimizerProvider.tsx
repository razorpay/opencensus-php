import React from 'react';
import { Box, Text, Tooltip, TooltipInteractiveWrapper } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import { findProviderDetails } from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { Item } from 'merchant/views/Transactions/v2/Payments/types';

import { StyledText } from './styled';

type PaymentOptimizerProviderProps = {
  item: Item;
  terminalProviders: {
    created_at: number;
    Currency: string[];
    Description: string;
    Gateway_acquirer: string;
    Gateway: string;
    Provider_name: string;
    Status: string;
    Terminal_id?: string;
    updated_at: number;
  }[];
};

function PaymentOptimierProvider(props: PaymentOptimizerProviderProps) {
  const { item, terminalProviders } = props;
  const provider = findProviderDetails(terminalProviders, item.optimizer_provider, item.settled_by);

  if (!provider) {
    return <Text>--</Text>;
  }

  return (
    <Tooltip content={provider.Provider_name} placement="top">
      <TooltipInteractiveWrapper>
        <Box display="flex" gap="spacing.3" maxWidth="120px">
          <img alt={provider.Gateway} height="20" src={gatewayLogos[provider.Gateway]} />
          <StyledText as="span">{provider.Provider_name}</StyledText>
        </Box>
      </TooltipInteractiveWrapper>
    </Tooltip>
  );
}

function mapStateToProps(state) {
  return {
    terminalProviders: state.navigator.terminalProviders,
  };
}

export default connect(mapStateToProps)(PaymentOptimierProvider);
