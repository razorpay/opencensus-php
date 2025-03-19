import { POPULAR_GATEWAYS } from 'merchant/views/Optimizer/OnBoarding/constants';
import { UPI_FEATURES, NETBANKING_FEATURES } from 'merchant/views/Navigator/constants';

export const filterPopularGateways = (
  supportedGateways: Record<string, unknown>,
): { label: string; value: string }[] => {
  const supportedGatewaysList: { label: string; value: string }[] = [];
  Object.keys(supportedGateways).forEach((gateway) => {
    if (!POPULAR_GATEWAYS.find((popularGateway) => popularGateway.value === gateway)) {
      supportedGatewaysList.push({
        label: supportedGateways[gateway]?.['Gateway Name']?.data_value || '',
        value: gateway,
      });
    }
  });
  return supportedGatewaysList;
};

export const handleTPVFeatures = (payload: Record<string, unknown>) => {
  const paymentMethods = (payload.Gateway_details as Object)['Payment Methods'];
  const hasNBMethod = paymentMethods.includes('netbanking');
  const hasUPIMethod = paymentMethods.includes('upi');
  if (hasUPIMethod) {
    payload.Gateway_details = {
      ...(payload.Gateway_details as Object),
      [UPI_FEATURES]: { tpv: 0 },
    };
  }
  if (hasNBMethod) {
    payload.Gateway_details = {
      ...(payload.Gateway_details as Object),
      [NETBANKING_FEATURES]: { tpv: 0 },
    };
  }
  return payload;
};
