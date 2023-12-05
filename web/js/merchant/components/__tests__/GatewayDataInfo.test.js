import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

import GatewayDataInfo from 'merchant/components/GatewayDataInfo';

describe('GatewayDataInfo Component', () => {
  // Test case 1: Rendering when gatewayData is an object
  test('renders with gatewayData as an object', () => {
    const mockData = {
      refund_code: 'ERROR_CODE',
      refund_message: 'Sample message',
    };

    render(<GatewayDataInfo gatewayData={mockData} />);

    expect(screen.getByTestId('refund-gateway-data')).toBeInTheDocument();
    expect(screen.getByText('Gateway response')).toBeInTheDocument();
    expect(screen.getByText(`Error code: ${mockData.refund_code}`)).toBeInTheDocument();
    expect(screen.getByText(mockData.refund_message)).toBeInTheDocument();
  });

  // Test case 2: Rendering when gatewayData is an empty object
  test('does not render with an empty object', () => {
    render(<GatewayDataInfo gatewayData={{}} />);
    expect(screen.queryByText('Gateway response')).not.toBeInTheDocument();
  });

  // Test case 3: Rendering when gatewayData is an empty array
  test('does not render with an empty array', () => {
    render(<GatewayDataInfo gatewayData={[]} />);
    expect(screen.queryByText('Gateway response')).not.toBeInTheDocument();
  });

  // Test case 4: Rendering when gatewayData is null
  test('does not render with gatewayData as null', () => {
    render(<GatewayDataInfo gatewayData={null} />);
    expect(screen.queryByText('Gateway response')).not.toBeInTheDocument();
  });
});
