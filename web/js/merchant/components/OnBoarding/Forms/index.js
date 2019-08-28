import { RZPFeatures } from 'rzp/utils/constants';

import RouteForm from './Routes';

const FORM_TYPE = {
  [RZPFeatures.ROUTE]: {
    formComponent: RouteForm,
  },
};

export default FORM_TYPE;

export const isFormValid = (type, state) => {
  switch (type) {
    case RZPFeatures.ROUTE: {
      return !(state.settling_to && state.use_case && state.uploadedFile);
    }

    default: {
      return true;
    }
  }
};
