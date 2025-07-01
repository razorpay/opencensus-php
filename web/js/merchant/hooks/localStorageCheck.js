import React from "react";
import LocalStorageService from 'common/utils/localStorage';

const useLocalStorageCheck = (key) => {
  const [isHidden, setIsHidden] = React.useState(!!LocalStorageService.getItem(key));

  function toggleIsHidden() {
    setIsHidden((state) => {
      const newState = !state;
      LocalStorageService.setItem(key, newState);
      return newState;
    });
  }

  return [isHidden, toggleIsHidden];
};

export default useLocalStorageCheck;
