import { merchantFetch } from 'rzp/utils/ajax';
import Activation from 'merchant/models/Activation';
import { set, merge, push } from 'rzp/utils/immutable';
import store from 'merchant/store';

export const ACTIVATION_FETCH = 'ACTIVATION_FETCH';
export const ACTIVATION_SAVE_STEP = 'ACTIVATION_SAVE_STEP';
export const ACTIVATION_SAVE_FILE = 'ACTIVATION_SAVE_FILE';
export const ACTIVATION_FORM_SUBMIT = 'ACTIVATION_FORM_SUBMIT';

export const fetchActivationDetails = (accountId = '') => {
  let activation = new Activation({ accountId });
  return {
    type: ACTIVATION_FETCH,
    payload: activation.fetch(),
  };
};

export const saveStep = ({ data, accountId = '' }) => {
  let activation = new Activation({
    ...data,
    accountId,
  });
  return {
    type: ACTIVATION_SAVE_STEP,
    payload: activation.saveStep(),
    mode: 'live',
    data,
  };
};

export const saveFile = ({ step, file, fieldName, accountId = '' }) => {
  let formData = new FormData();
  let fieldNameMapping = {
    business_proof: 'business_proof_url',
    business_operation_proof: 'business_operation_proof_url',
    business_pan_proof: 'business_pan_url',
    address_proof: 'address_proof_url',
    promoter_proof: 'promoter_proof_url',
    promoter_pan_proof: 'promoter_pan_url',
    promoter_address_proof: 'promoter_address_url',
    ngo_12a_proof: 'form_12a_url',
    ngo_80g_proof: 'form_80g_url',
  };
  formData.append(fieldNameMapping[fieldName], file);

  return {
    type: ACTIVATION_SAVE_FILE,
    payload: merchantFetch({
      url: 'merchant/activation/upload',
      method: 'post',
      mode: 'live',
      data: formData,
      accountId,
    }),
    fileName: file.name,
    fieldName,
    step,
  };
};

export const submitForm = ({ step, data, accountId = '' }) => {
  let activation = new Activation({
    ...data,
    accountId,
  });
  return {
    type: ACTIVATION_FORM_SUBMIT,
    payload: activation.submit().then(response => {
      if (!response.data.can_submit) {
        throw { errors: ['Some mandatory fields are required'] };
      }
      return response;
    }),
    step,
    data,
  };
};

let initialState = {
  loading: true,
  error: null,
  data: {},
  steps: {
    1: undefined,
    2: undefined,
    3: undefined,
    4: undefined,
    5: undefined,
  },
  uploadedFiles: {},
};

export default function(state = initialState, action) {
  let updatedSteps, uploadedFiles;

  switch (action.type) {
    case `${ACTIVATION_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${ACTIVATION_FETCH}::SUCCESS`:
      let data = action.payload;
      let stepsFinished = data.steps_finished;
      let steps = Object.keys(initialState.steps).reduce((prev, key) => {
        if (stepsFinished.indexOf(+key) !== -1) {
          prev[key] = 'success';
        } else {
          prev[key] = initialState.steps[key];
        }
        return prev;
      }, {});

      uploadedFiles = (data.files || []).reduce((prev, key) => {
        prev[key] = 'File Already Uploaded  ✔';
        return prev;
      }, {});

      return merge(state, {
        loading: false,
        error: null,
        data,
        steps,
        uploadedFiles,
      });

    case `${ACTIVATION_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        data: initialState.data,
        error: action.payload.errors,
      });

    case `${ACTIVATION_SAVE_STEP}::SUCCESS`:
    case `${ACTIVATION_FORM_SUBMIT}::SUCCESS`:
      return merge(state, {
        data: action.data,
      });

    case `${ACTIVATION_SAVE_STEP}::ERROR`:
    case `${ACTIVATION_FORM_SUBMIT}::ERROR`:
      updatedSteps = set(state.steps, action.step, 'error');
      return set(state, 'steps', updatedSteps);

    case `${ACTIVATION_SAVE_FILE}::SUCCESS`:
      uploadedFiles = set(
        state.uploadedFiles,
        action.fieldName,
        action.fileName
      );

      updatedSteps = state.steps;

      //max doc uploads for merchant/linked account
      let maxUploads = action.step === 4 ? 4 : 2;

      //max doc uploads for ngo merchants
      if (action.step === 4 && state.data.business_type === '7') {
        maxUploads = 6;
      }

      if (Object.keys(uploadedFiles).length === maxUploads) {
        updatedSteps = set(state.steps, action.step, 'success');
      }

      return merge(state, {
        steps: updatedSteps,
        uploadedFiles,
      });

    default:
      return state;
  }
}

/**
 * Fetch city and state based on pincode provided
 */
export const getPincodeDetails = (pincode, changeFunc) => {
  const mode = store.getState().session.mode;

  if (pincode.length === 6) {
    merchantFetch(`pincodes/${pincode}`)
      .then(response => {
        if (response.data) {
          changeFunc(response.data.city, response.data.state_code);
        }
      })
      .catch(e => changeFunc()); //- send empty values if error
  } else {
    //- send empty values if length less or greater than 6
    changeFunc();
  }
};
