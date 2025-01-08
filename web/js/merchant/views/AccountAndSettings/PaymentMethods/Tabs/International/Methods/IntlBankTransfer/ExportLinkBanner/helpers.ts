import { merchantFetch } from 'merchant/utils/ajax';

export const getExportLink = () =>
  merchantFetch({
    url: 'payments_cross_border_live/v1/export-link',
    mode: 'live',
  }).then((res) => res?.data?.export_id);

export const postExportLink = () =>
  merchantFetch({
    url: 'payments_cross_border_live/v1/export-link',
    mode: 'live',
    method: 'POST',
  });
