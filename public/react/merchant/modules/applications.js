import ajax from 'merchant/utils/ajax';
import { set, merge } from 'rzp/utils/immutable';
import Application from 'merchant/models/Application';

const FETCH_APPLICATIONS = 'FETCH_APPLICATIONS';
const CREATE_APPLICATION = 'CREATE_APPLICATION';
const UPDATE_APPLICATION = 'UPDATE_APPLICATION';

export const fetchApplications = params => {
  let application = new Application();

  return {
    type: FETCH_APPLICATIONS,
    payload: application.fetchAll(params),
  };
};

export const saveApplication = params => {
  console.log('save from module', params)
  let application = new Application();

  return {
    type: CREATE_APPLICATION,
    payload: application.save(params),
  };
};

let initialState = {
  loading: true,
  items: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_APPLICATIONS}::PENDING`:
    case `${CREATE_APPLICATION}::PENDING`:
    case `${UPDATE_APPLICATION}::PENDING`:
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

    default:
      return state;
  }
}
