import { useSplitzService } from 'common/splitz';
import { Store } from 'common/typings';

export const useGSTUpdateExperiment = (): {
  isGSTUpdateEnabled: boolean;
} => {
  const {
    abExperiments: { gst_update },
  } = useSplitzService();

  return {
    isGSTUpdateEnabled: gst_update?.variables?.result === 'on',
  };
};

export const getBusinessRegisteredAddress = ({
  business_registered_address,
  business_registered_address_l2,
  business_operation_city,
  business_operation_district,
  business_registered_state,
  business_registered_country,
  business_registered_pin,
}: Store['session']['user']) => {
  const address = [
    business_registered_address,
    business_registered_address_l2,
    business_operation_city,
    business_operation_district,
    business_registered_state,
    business_registered_country,
    business_registered_pin,
  ]
    .filter((value) => value)
    .join(', ');

  return address;
};
