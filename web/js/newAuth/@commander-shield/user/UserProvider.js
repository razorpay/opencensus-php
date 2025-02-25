import React from 'react';
import PropTypes from 'prop-types';
import Loader from '../shared/Loader/Loader';
import * as userDuck from './userDuck';
import UserContext from './UserContext';

const UserProvider = ({ children, initState }) => {
  const [state, dispatch] = React.useReducer(userDuck.userReducer, initState);

  const value = React.useMemo(() => {
    return {
      state,
      actions: userDuck.userActions(state, dispatch),
    };
  }, [state, dispatch]);

  return (
    <UserContext.Provider value={value}>
      {children}
      {state.isLoading ? <Loader /> : null}
    </UserContext.Provider>
  );
};

UserProvider.propTypes = {
  children: PropTypes.node,
  initState: PropTypes.object,
};

UserProvider.defaultProps = {
  initState: userDuck.userInitialState,
};

export default UserProvider;
