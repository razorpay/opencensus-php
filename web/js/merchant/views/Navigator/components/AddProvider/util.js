import { TPV_OPTIONS } from 'merchant/views/Navigator/constants';

export function getTPVOptions(options) {
  return options.map((option) => ({ label: TPV_OPTIONS[option] || '', value: option }));
}
