import ajax from 'merchant/utils/ajax'
import { set, merge } from 'rzp/utils/immutable'

export const ACTIVATION_FETCH = 'ACTIVATION_FETCH'
export const ACTIVATION_SAVE_STEP = 'ACTIVATION_SAVE_STEP'
export const ACTIVATION_FORM_SUBMIT = 'ACTIVATION_FORM_SUBMIT'

export const fetchActivationDetails = () => {
  return (dispatch) => {
    return dispatch({
      type: ACTIVATION_FETCH,
      payload: ajax({
        url: '/activation/details',
        appendModeInURL: false,
      })
    })
  }
}

export const saveStep = (step, data) => {
  return (dispatch) => {
    return dispatch({
      type: ACTIVATION_SAVE_STEP,
      payload: ajax({
        url: `/activation/save/step/${step}`,
        method: 'post',
        appendModeInURL: false,
        data,
      })
    })
  }
}

export const saveFile = (file, fieldName) => {
  return (dispatch) => {
    let formData = new FormData()
    formData.append(fieldName, file)

    return ajax({
      url: '/activation/save/file',
      method: 'post',
      data: formData,
      processData: false,
      contentType: false,
      appendModeInURL: false,
    })
  }
}

export const submitForm = (data) => {
  return (dispatch) => {
    return dispatch({
      type: ACTIVATION_FORM_SUBMIT,
      payload: ajax({
        url: '/activation',
        method: 'post',
        appendModeInURL: false,
        data,
      })
    })
  }
}

let initialState = {
  loading: true,
  error: null,
  data: {}
}

export default function(state = initialState, action) {
  switch(action.type) {
    case `${ACTIVATION_FETCH}::PENDING`:
      return set(state, 'loading', true)

    case `${ACTIVATION_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        data: action.payload.data,
        error: null,
      })

    case `${ACTIVATION_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        data: initialState.data,
        error: action.payload.errors,
      })

    default:
      return state
  }
}
