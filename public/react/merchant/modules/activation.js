import ajax from 'merchant/utils/ajax';
import { set, merge, push } from 'rzp/utils/immutable';

export const ACTIVATION_FETCH = 'ACTIVATION_FETCH';
export const ACTIVATION_SAVE_STEP = 'ACTIVATION_SAVE_STEP';
export const ACTIVATION_SAVE_FILE = 'ACTIVATION_SAVE_FILE';
export const ACTIVATION_FORM_SUBMIT = 'ACTIVATION_FORM_SUBMIT';

export const fetchActivationDetails = () => {
  return dispatch => {
    return dispatch({
      type: ACTIVATION_FETCH,
      payload: ajax({
        url: '/activation/details',
        appendModeInURL: false,
      }).then(response => {
        response.data.bank_account_number_confirmation =
          response.data.bank_account_number;
        return response;
      }),
    });
  };
};

export const saveStep = (step, data) => {
  return dispatch => {
    return dispatch({
      type: ACTIVATION_SAVE_STEP,
      payload: ajax({
        url: `/activation/save/step/${step}`,
        method: 'post',
        appendModeInURL: false,
        data,
      }),
      extraArgs: {
        step,
        data,
      },
    });
  };
};

export const saveFile = (file, fieldName) => {
  return dispatch => {
    let formData = new FormData();
    formData.append(fieldName, file);

    return dispatch({
      type: ACTIVATION_SAVE_FILE,
      payload: ajax({
        url: '/activation/save/file',
        method: 'post',
        data: formData,
        processData: false,
        contentType: false,
        appendModeInURL: false,
      }),
      extraArgs: {
        fieldName,
        fileName: file.name,
        step: 5,
      },
    });
  };
};

export const submitForm = data => {
  return dispatch => {
    return dispatch({
      type: ACTIVATION_FORM_SUBMIT,
      payload: ajax({
        url: '/activation',
        method: 'post',
        appendModeInURL: false,
        data,
      }),
      extraArgs: {
        step: 6,
        data,
      },
    });
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
    6: undefined,
  },
  uploadedFiles: {},
};

export default function(state = initialState, action) {
  let updatedSteps, uploadedFiles;

  switch (action.type) {
    case `${ACTIVATION_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${ACTIVATION_FETCH}::SUCCESS`:
      let data = action.payload.data;
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
      updatedSteps = set(state.steps, action.extraArgs.step, 'success');
      return merge(state, {
        steps: updatedSteps,
        data: action.extraArgs.data,
      });

    case `${ACTIVATION_SAVE_STEP}::ERROR`:
    case `${ACTIVATION_FORM_SUBMIT}::ERROR`:
      updatedSteps = set(state.steps, action.extraArgs.step, 'error');
      return set(state, 'steps', updatedSteps);

    case `${ACTIVATION_SAVE_FILE}::SUCCESS`:
      uploadedFiles = set(
        state.uploadedFiles,
        action.extraArgs.fieldName,
        action.extraArgs.fileName
      );
      updatedSteps = state.steps;

      if (Object.keys(uploadedFiles).length === 4) {
        updatedSteps = set(state.steps, action.extraArgs.step, 'success');
      }

      return merge(state, {
        steps: updatedSteps,
        uploadedFiles,
      });

    default:
      return state;
  }
}
