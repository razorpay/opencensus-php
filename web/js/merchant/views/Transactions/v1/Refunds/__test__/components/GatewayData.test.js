import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

import GatewayData from 'merchant/views/Transactions/v1/Refunds/components/GatewayData';

describe('GatewayData Component', () => {
  test('renders nothing when value is falsy', () => {
    render(<GatewayData status="processed" value={null} />);
    // Expect the component not to be in the document
    expect(screen.queryByTestId('refund-gateway-data')).not.toBeInTheDocument();
  });

  test('renders nothing when value is an empty object', () => {
    render(<GatewayData status="processed" value={{}} />);
    // Expect the component not to be in the document
    expect(screen.queryByTestId('refund-gateway-data')).not.toBeInTheDocument();
  });

  test('renders nothing when value is an empty array', () => {
    render(<GatewayData status="processed" value={[]} />);
    // Expect the component not to be in the document
    expect(screen.queryByTestId('refund-gateway-data')).not.toBeInTheDocument();
  });

  test('renders the gateway data when value is a non-empty object', () => {
    const mockData = {
      refund_code: 'ERROR_CODE',
      refund_message: 'Sample message',
    };

    render(<GatewayData status="processed" value={mockData} />);

    expect(screen.getByTestId('refund-gateway-data')).toBeInTheDocument();
    expect(screen.getByText('Gateway response')).toBeInTheDocument();
    expect(screen.getByText(`Error code: ${mockData.refund_code}`)).toBeInTheDocument();
    expect(screen.getByText(mockData.refund_message)).toBeInTheDocument();
  });
});
