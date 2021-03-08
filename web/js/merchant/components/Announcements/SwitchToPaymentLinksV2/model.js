import { merchantFetch } from 'merchant/utils/ajax';

/*
 *
 * Specific Api Actions of Payment Links Switch to V2
 *
 * */
export function switchToV2() {
  return merchantFetch({
    method: 'post',
    url: 'payment_links_switch_versions',
    data: { switch_to: 'v2' },
  });
}
