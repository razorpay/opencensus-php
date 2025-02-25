import React from 'react';
import UserContext from './UserContext';

/**
 * @returns {{state: import('./userDuck').State, actions: import('./userDuck').Actions}}
 */
const useUserContext = () => {
  const context = React.useContext(UserContext);

  if (context === undefined) {
    throw new Error('useUserContext must be used within UserProvider');
  }

  return context;
};

export default useUserContext;
