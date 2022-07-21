import { SET_APM_FORM_DATA, SET_APM_FORM_ERROR, FETCHING_APM_FORM } from './constants';

const setFormFetching = () => {
  return {
    type: FETCHING_APM_FORM,
  };
};

const setFormData = (data) => {
  return {
    type: SET_APM_FORM_DATA,
    payload: data,
  };
};

const setFormError = (data) => {
  return {
    type: SET_APM_FORM_ERROR,
    payload: data,
  };
};

export { setFormFetching, setFormData, setFormError };
