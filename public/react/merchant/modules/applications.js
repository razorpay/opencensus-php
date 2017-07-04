import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';
import Application from 'merchant/models/Application';

const FETCH_APPLICATIONS = 'FETCH_APPLICATIONS';
const FETCH_APPLICATION_DETAILS = 'FETCH_APPLICATION_DETAILS';
const CREATE_APPLICATION = 'CREATE_APPLICATION';
const UPDATE_APPLICATION = 'UPDATE_APPLICATION';
const DELETE_APPLICATION = 'DELETE_APPLICATION';

export const fetchApplications = params => {
  let application = new Application();

  return {
    type: FETCH_APPLICATIONS,
    payload: application.fetchAll(params),
  };
};

export const fetchApplication = params => {
  let application = new Application();

  return {
    type: FETCH_APPLICATION_DETAILS,
    payload: application.fetch(params),
  };
};

export const deleteApplication = id => {
  let application = new Application({id});

  return {
    type: DELETE_APPLICATION,
    payload: application.delete(id),
  };
};

export const createApplication = params => {
  console.log('save from module', params)
  let application = new Application();

  return {
    type: CREATE_APPLICATION,
    payload: application.create(params),
  };
};
export const updateApplication = params => {
  console.log('save from module', params)
  let application = new Application();

  return {
    type: UPDATE_APPLICATION,
    payload: application.update(params),
  };
};

let initialState = {
  loading: true,
  items: [],
  count: 0,
  details: {},
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_APPLICATIONS}::PENDING`:
    case `${CREATE_APPLICATION}::PENDING`:
    case `${UPDATE_APPLICATION}::PENDING`:
    case `${DELETE_APPLICATION}::PENDING`:
    case `${FETCH_APPLICATION_DETAILS}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${FETCH_APPLICATIONS}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${CREATE_APPLICATION}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: [action.payload],
      });

    case `${DELETE_APPLICATION}::SUCCESS`:
      console.log('deleted app')
      return merge(state, {
        loading: false,
      })

    case `${FETCH_APPLICATION_DETAILS}::SUCCESS`:
      return merge(state, {
        loading: false,
      });

    case `${UPDATE_APPLICATION}::SUCCESS`:
      return merge(state, {
        loading: false,
      });
    default:
      return state;
  }
}
