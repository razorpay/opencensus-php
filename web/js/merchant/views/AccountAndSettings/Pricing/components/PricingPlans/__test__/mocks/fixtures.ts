import store from 'merchant/store';

const merchantId = 'XXXXXXXXXXXXXX';
const state = store.getState();

const getState = (enrollmentStatus = {}) => ({
  session: {
    ...state.session,
    user: {
      ...state.session.user,
      current: merchantId,
    },
  },
  bundlePricing: {
    enrollmentStatus: {
      loading: null,
      hasEnrolled: null,
      error: null,
      ...enrollmentStatus,
    },
  },
});

export { getState, merchantId };
