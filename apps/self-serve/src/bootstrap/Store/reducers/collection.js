import { set, merge, unshift, remove } from '@dashboard/shared-utils/immutable';

import { analyticsTrack } from '@dashboard/shared-utils/analytics';
import { getCommonSegmentProperties } from '@dashboard/shared-utils/rzp-utils';
import createReducer from './createReducer';

const defaultInitialState = {
  loading: true,
  items: [],
  error: null,
};

const TRACK_NAMESPACES = ['PAYMENTS', 'REFUNDS', 'SETTLEMENTS'];

const trackNamespaceEvents = (action, success) => {
  const NAMESPACE = action?.type?.split('_')[0];

  if (TRACK_NAMESPACES.includes(NAMESPACE)) {
    const QUERY_PARAMS = window.location.href.split('?');
    const PROPERTIES = success
      ? { success }
      : {
          success,
          failureReason: action?.payload?.errors,
        };

    analyticsTrack({
      objectName: `${NAMESPACE?.toLowerCase()} collection search`,
      actionName: 'result',
      properties: {
        ...getCommonSegmentProperties(window.rzp_user, { addUserProperties: true }),
        ...PROPERTIES,
        queryParams: QUERY_PARAMS?.length > 1 ? QUERY_PARAMS[1] : '',
      },
      screen: window.location.pathname.split('/').pop() || NAMESPACE,
      toLumberjack: true,
    });
  }
};

export const getActionName = (namespace) => {
  return `${namespace}_FETCH`;
};

// useEntityReducer tells whether to use common reducer or entity-specific
export const fetchAll = (params, Entity, namespace) => {
  let entity;
  if (typeof Entity === 'function') {
    entity = new Entity();
  } else {
    entity = Entity;
  }

  return {
    type: getActionName(namespace),
    payload: entity.fetchAll(params),
  };
};

export const listFetchPendingState = (state, action, initialState) => {
  return initialState;
};

export const appendEntityToList = (state, action) => {
  return set(state, 'items', unshift(state.items, action.payload));
};

export const listFetchSuccessState = (state, action) => {
  trackNamespaceEvents(action, true);
  return merge(state, {
    loading: false,
    items: action.payload.data.items,
    error: null,
  });
};

export const listFetchErrorState = (state, action, initialState) => {
  trackNamespaceEvents(action, false);
  return merge(state, {
    loading: false,
    items: initialState.items,
    error: action.payload.errors,
  });
};

export const addUniqueEntityToList = (state, action) => {
  const itemIndex = state.items.findIndex((item) => item.id === action.payload.id);
  return itemIndex < 0 ? set(state, 'items', [...state.items, action.payload]) : state;
};

export const updateEntityInList = (state, action) => {
  const itemIndex = state.items.findIndex((item) => item.id === action.payload.id);
  if (itemIndex < 0) return state;
  return set(state, `items.${itemIndex}`, action.payload);
};

export const removeEntityFromList = (state, action) => {
  const itemsList = remove(state.items, (item) => item.id === action.payload.id);
  return set(state, 'items', itemsList);
};

// collection reducer

export const makeCollectionReducer = (
  namespace,
  actionHandlers = {},
  initialState = defaultInitialState,
) => {
  const fetchActionName = getActionName(namespace);
  const defaultHandlers = {
    [`${fetchActionName}::PENDING`]: listFetchPendingState,
    [`${fetchActionName}::SUCCESS`]: listFetchSuccessState,
    [`${fetchActionName}::ERROR`]: listFetchErrorState,
  };

  const handlers = {
    ...defaultHandlers,
    ...actionHandlers,
  };
  return createReducer({
    handlers,
    initialState,
  });
};

export const makeActionCollectionReducer = (
  namespace,
  actionHandlers = {},
  initialState = defaultInitialState,
) => {
  const singularNamespace = namespace.slice(0, namespace.length - 1);

  const defaultHandlers = {
    [`${singularNamespace}_CREATE::SUCCESS`]: appendEntityToList,
    [`${singularNamespace}_EDIT::SUCCESS`]: updateEntityInList,
    [`${singularNamespace}_CANCEL::SUCCESS`]: updateEntityInList,
    [`${singularNamespace}_DELETE::SUCCESS`]: removeEntityFromList,
  };

  const handlers = {
    ...defaultHandlers,
    ...actionHandlers,
  };
  return makeCollectionReducer(namespace, handlers, initialState);
};
