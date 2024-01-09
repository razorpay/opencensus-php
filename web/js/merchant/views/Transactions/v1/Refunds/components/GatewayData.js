import React from 'react';

const statusClassMap = {
  processed: 'text-success',
  processing: 'text-info',
  failed: 'text-danger',
  created: 'text-primary',
  initiated: 'text-light',
};

/**
 * @prop {string} status - The status of the gateway.
 * @prop {Object|Array} value - The value (gateway_data), can be an object or an empty array.
 */

const GatewayData = ({ status, value }) => {
  // Check if the "value" is an object and it has keys and not an array
  if (value && typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length) {
    const statusClass = `${statusClassMap[status?.toLowerCase()]}` || '';

    return (
      <div className="refund-gateway-data" data-testid="refund-gateway-data">
        <p className="refund-gateway-data--label">Gateway response</p>
        <p className="refund-gateway-data--code">
          Gateway code: <span className={statusClass}>{value?.refund_code}</span>
        </p>
        <p className="refund-gateway-data--message">Gateway message: {value?.refund_message}</p>
      </div>
    );
  }

  return null;
};

export default GatewayData;
