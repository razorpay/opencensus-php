export type FunnelTypes =
  | 'conversion_funnel'
  | 'conversion_funnel_logged_in'
  | 'conversion_funnel_logged_out';

export type FunnelTypeMaps = {
  [key: string]: FunnelTypes;
};
