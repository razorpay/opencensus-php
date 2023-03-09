import BundlePricing from 'merchant/models/BundlePricing';
import { set } from 'common/utils/immutable';

const FETCH_ENROLLMENT_STATUS = 'FETCH_ENROLLMENT_STATUS';

export const fetchEnrollmentStatus = () => {
  const bundlePricing = new BundlePricing();
  return {
    type: FETCH_ENROLLMENT_STATUS,
    payload: bundlePricing?.fetchEnrollmentStatus(),
  };
};

const initialState = {
  enrollmentStatus: {
    loading: false, // Can be true or false
    hasEnrolled: null, // Can be null, true or false
    message: null, // Can be null or a string
    error: null,
  },
};

function reducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_ENROLLMENT_STATUS}::PENDING`:
      return set(state, 'enrollmentStatus', {
        loading: true,
        hasEnrolled: state?.enrollmentStatus?.hasEnrolled ?? null,
        message: state?.enrollmentStatus?.message ?? null,
        error: null,
      });

    case `${FETCH_ENROLLMENT_STATUS}::SUCCESS`:
      return set(state, 'enrollmentStatus', {
        loading: false,
        hasEnrolled: action?.payload?.hasEnrolled ?? null,
        message: action?.payload?.message ?? null,
        error: null,
      });

    case `${FETCH_ENROLLMENT_STATUS}::ERROR`:
      return set(state, 'enrollmentStatus', {
        loading: false,
        hasEnrolled: state?.enrollmentStatus?.hasEnrolled ?? null,
        message: state?.enrollmentStatus?.message ?? null,
        error: action?.payload,
      });

    default:
      return state;
  }
}

export default reducer;
