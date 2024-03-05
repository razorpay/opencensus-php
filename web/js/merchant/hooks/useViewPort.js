// Todo: delete this file, it's available in @dashboard/shared-ui
import React, { useState, useEffect, createContext, useContext, useCallback } from 'react';

const defaultValue = {};
const ViewportContext = createContext(defaultValue);

const getDeviceConfig = (width) => {
  if (width < 400) {
    return 'PHONE';
  } else if (width < 768) {
    return 'PHABLET';
  } else if (width < 1024) {
    return 'TABLET';
  } else if (width < 1536) {
    return 'DESKTOP';
  } else {
    return 'WIDE';
  }
};

export function ViewportProvider({ children, reqViewport }) {
  const initialViewport = reqViewport || 'WIDE';
  const [viewport, setViewport] = useState(initialViewport);

  const setCurrentViewport = useCallback(
    (currentViewport) => {
      if (viewport !== currentViewport) {
        setViewport(currentViewport);
      }
    },
    [viewport],
  );

  useEffect(() => {
    // initial state
    const initialViewportCS = getDeviceConfig(window.innerWidth);
    setCurrentViewport(initialViewportCS);

    const calcInnerWidth = () => {
      const newViewport = getDeviceConfig(window.innerWidth);
      setCurrentViewport(newViewport);
    };

    // add event listener
    window.addEventListener('resize', calcInnerWidth);

    // remove event listener
    return () => {
      window.removeEventListener('resize', calcInnerWidth);
    };
    // if we add viewport the whole setup of setting the event listener only once is gone
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return <ViewportContext.Provider value={viewport}>{children}</ViewportContext.Provider>;
}

function useViewport() {
  const context = useContext(ViewportContext);
  if (context === defaultValue) {
    throw new Error('useViewport is not used within a ViewportProvider');
  }
  return context;
}

export default useViewport;
