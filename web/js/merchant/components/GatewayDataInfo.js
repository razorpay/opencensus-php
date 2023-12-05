import React from 'react';

import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

/**
 * @prop {Object|Array} gatewayData - The value of 'gatewayData', can be an object or an empty array.
 */

const GatewayDataInfo = ({ gatewayData }) => {
  // Check if the "gatewayData" is an object and it has keys and not an array
  if (
    gatewayData &&
    typeof gatewayData === 'object' &&
    !Array.isArray(gatewayData) &&
    Object.keys(gatewayData).length
  ) {
    return (
      <span>
        <i className="i i-info-tooltip" />
        <PopoverComponent theme="dark" align="bottom">
          <PopoverBody>
            <div className="refund-status--error" data-testid="refund-gateway-data">
              <b>Gateway response</b>
              <p className="refund-status--error-code">Error code: {gatewayData?.refund_code}</p>
              <p className="refund-status--error-message">{gatewayData?.refund_message}</p>
            </div>
          </PopoverBody>
        </PopoverComponent>
      </span>
    );
  }

  return null;
};

export default GatewayDataInfo;
