export type RateTypes =
  | 'conversion_rate'
  | 'conversion_rate_logged_in'
  | 'conversion_rate_logged_out';

export type RateTypeMaps = {
  [key: string]: RateTypes;
};
