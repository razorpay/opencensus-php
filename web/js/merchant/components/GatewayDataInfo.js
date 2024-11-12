import React from 'react';
import { Text, Box } from '@razorpay/blade/components';

import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

/**
 * @prop {Object|Array} gatewayData - The value of 'gatewayData', can be an object or an empty array.
 */

const GatewayDataInfo = ({ gatewayData, isTransactionV2 = false }) => {
  // Check if the "gatewayData" is an object and it has keys and not an array
  if (
    gatewayData &&
    typeof gatewayData === 'object' &&
    !Array.isArray(gatewayData) &&
    Object.keys(gatewayData).length
  ) {
    return isTransactionV2 ? (
      <Box display="flex" flexDirection="column" gap="spacing.2">
        <Box display="flex" flexDirection="column">
          <Text as="span" color="surface.text.staticWhite.normal" size="small" weight="semibold">
            Gateway code :
          </Text>
          <Text as="span" color="surface.text.staticWhite.subtle" size="small">
            {gatewayData.refund_code || '--'}
          </Text>
        </Box>
        <Box display="flex" flexDirection="column">
          <Text as="span" color="surface.text.staticWhite.normal" size="small" weight="semibold">
            Gateway message :
          </Text>
          <Text as="span" color="surface.text.staticWhite.subtle" size="small">
            {gatewayData.refund_message || '--'}
          </Text>
        </Box>
      </Box>
    ) : (
      <span>
        <i className="i i-info-tooltip" data-testid="info-icon" />
        <PopoverComponent theme="dark" align="bottom">
          <PopoverBody>
            <div className="refund-status--error" data-testid="refund-gateway-data">
              <b>Gateway response</b>
              <p className="refund-status--error-code">Gateway code: {gatewayData?.refund_code}</p>
              <p className="refund-status--error-message">
                Gateway message: {gatewayData?.refund_message}
              </p>
            </div>
          </PopoverBody>
        </PopoverComponent>
      </span>
    );
  }
  return isTransactionV2 ? (
    <Text as="span" color="surface.text.staticWhite.normal" size="small">
      NA
    </Text>
  ) : null;
};

export default GatewayDataInfo;
