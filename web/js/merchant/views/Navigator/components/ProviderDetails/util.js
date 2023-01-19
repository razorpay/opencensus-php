import { TPV_OPTIONS } from 'merchant/views/Navigator/constants';

export function getUPIOptionLabel(val) {
  const { label = '' } = TPV_OPTIONS.find(({ value }) => value === val) || {};

  return label;
}
