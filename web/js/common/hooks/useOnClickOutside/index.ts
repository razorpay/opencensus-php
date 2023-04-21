import { useEffect } from 'react';

const useOnClickOutside = (elements, handler): void => {
  useEffect(
    () => {
      const listener = (event) => {
        // Do nothing on clicking element
        if (elements.find((each) => each.current?.contains(event.target))) {
          return;
        }
        handler(event);
      };

      document.addEventListener('mousedown', listener);
      document.addEventListener('touchstart', listener);
      return () => {
        document.removeEventListener('mousedown', listener);
        document.removeEventListener('touchstart', listener);
      };
    },
    // Add element and handler as effect dependencies
    [elements, handler],
  );
};

export default useOnClickOutside;
