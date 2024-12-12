import { POPULAR_GATEWAYS } from 'merchant/views/Optimizer/OnBoarding/constants';
import {
  HAS_UPI_FEATURES,
  HAS_NETBANKING_FEATURES,
  UPI_FEATURES,
  NETBANKING_FEATURES,
} from 'merchant/views/Navigator/constants';

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

export const handleTPVFeatures = (payload: Record<string, unknown>, gateway: string) => {
  const paymentMethods = (payload.Gateway_details as Object)['Payment Methods'];
  const hasNBMethod = paymentMethods.includes('netbanking');
  const hasUPIMethod = paymentMethods.includes('upi');
  if (hasUPIMethod && HAS_UPI_FEATURES.includes(gateway)) {
    payload.Gateway_details = {
      ...(payload.Gateway_details as Object),
      [UPI_FEATURES]: { tpv: 0 },
    };
  }
  if (hasNBMethod && HAS_NETBANKING_FEATURES.includes(gateway)) {
    payload.Gateway_details = {
      ...(payload.Gateway_details as Object),
      [NETBANKING_FEATURES]: { tpv: 0 },
    };
  }
  return payload;
};
