import { initialState } from 'merchant/reducers/instrumentRequests';

export const INTL_BANK_TRANSFER_LEAF =
  initialState.pg
    .find((item) => item.slug === 'international')
    ?.leafList?.find((item) => item.slug === 'moneysaverexportaccount')?.leafList ?? [];
