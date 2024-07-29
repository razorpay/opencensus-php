import React from 'react';
import { render } from '@testing-library/react';

import { getBadgeIcon } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/utils';
import RefundAnimationData from 'merchant/views/Transactions/v2/Payments/lottie/Refund';
import RefundAnimationDataGeneric from 'merchant/views/Transactions/v2/Payments/lottie/RefundGeneric';

// Mock the Lottie component
jest.mock('react-lottie', () => {
  return {
    __esModule: true,
    default: ({ options }) => (
      <div
        data-testid="lottie-component"
        data-animation-data={JSON.stringify(options.animationData)}
      />
    ),
  };
});

describe('getBadgeIcon -', () => {
  afterEach(() => {
    jest.resetAllMocks();
  });

  const renderComponent = (status, isCountryIndia) => {
    const BadgeIconComponent = getBadgeIcon(status, isCountryIndia);

    return render(BadgeIconComponent);
  };

  const testCases = [
    { status: 'refunded', isCountryIndia: true, expectedData: RefundAnimationData },
    { status: 'refunded', isCountryIndia: false, expectedData: RefundAnimationDataGeneric },
  ];

  testCases.forEach(({ status, isCountryIndia, expectedData }) => {
    it(`should return ${
      expectedData === RefundAnimationData ? 'RefundAnimationData' : 'RefundAnimationDataGeneric'
    } for ${status} status when country is ${isCountryIndia ? 'India' : 'not India'}`, () => {
      const { getByTestId } = renderComponent(status, isCountryIndia);

      // Check if the Lottie component is rendered with correct animation data
      const lottieComponent = getByTestId('lottie-component');
      expect(lottieComponent).toBeInTheDocument();
      expect(lottieComponent.dataset.animationData).toBe(JSON.stringify(expectedData));
    });
  });
});
