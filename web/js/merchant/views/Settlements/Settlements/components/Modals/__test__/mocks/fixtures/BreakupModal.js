export const initialState = {
  loading: false,
  items: [],
  error: null,
  isBreakupNew: null,
};

const onMount = jest.fn();
const onUnMount = jest.fn();

const settlement = {
  analyticsPayload: jest.fn(),
};

export const props = {
  settlementId: 'setl_JFeIgD63bF8doh',
  settlement,
  onMount,
  onUnmount: onUnMount,
};
