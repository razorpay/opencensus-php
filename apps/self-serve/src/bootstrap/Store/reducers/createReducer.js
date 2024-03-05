const createReducer = ({ handlers, initialState }) => {
  return (state = initialState, action) => {
    if (handlers.hasOwnProperty(action.type)) {
      return handlers[action.type](state, action, initialState);
    } else {
      return state;
    }
  };
};

export default createReducer;
