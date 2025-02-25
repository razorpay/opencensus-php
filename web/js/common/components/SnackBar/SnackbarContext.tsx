import React, { createContext, ReactNode, useState, lazy, Suspense } from 'react';
import isEmpty from 'lodash/isEmpty';
import { Layer } from 'common/components/Layer';

const Snackbar = lazy(() => import(/* webpackChunkName: 'Snackbar' */ './Snackbar'));

const snackbarMap = {
  error: {
    color: 'negative.900',
    icon: 'failure',
  },
  success: {
    color: 'positive.900',
    icon: 'success',
  },
};
export interface SnackContextTypes {
  error: (msg: string) => void;
  success: (msg: string) => void;
}

export const snackbarContext = createContext<SnackContextTypes | undefined>(undefined);

export const useSnackbar = (): SnackContextTypes => {
  const context = React.useContext(snackbarContext);
  if (context === undefined) {
    throw Error('useSnackbar must be used within SnackbarProvider');
  }
  return context;
};

interface ProviderPropsT {
  children: ReactNode;
}

const defaultDurationMs = 5000;

export const SnackbarProvider: React.FC<ProviderPropsT> = ({ children }) => {
  const [snackbar, setSnackbar] = useState<any>({});
  const [shouldAnimateIn, setShouldAnimateIn] = useState(false);
  let timer = 0;
  let animationTimer = 0;

  const clearSnackbar = (duration = 0) => {
    // use window.setTimeout to fix typescript error https://stackoverflow.com/a/55550147/6127580

    animationTimer = window.setTimeout(() => {
      setShouldAnimateIn(false);
      timer = window.setTimeout(() => {
        setSnackbar({});
      }, 400); // snackbar transition duration
    }, duration);
  };

  const clearTimer = () => {
    clearTimeout(animationTimer);
    clearTimeout(timer);
  };

  const invokeNewSnackbar = React.useCallback(({ type, message }) => {
    clearTimer();
    setShouldAnimateIn(true);
    setSnackbar({
      type,
      message,
      icon: snackbarMap[type].icon,
      color: snackbarMap[type].color,
    });
    clearSnackbar(defaultDurationMs);
  }, []);

  const showSnackbar = React.useCallback(
    (type) => (message) => {
      if (!isEmpty(snackbar)) {
        clearSnackbar();
        setTimeout(() => {
          invokeNewSnackbar({ type, message });
        }, 500);
      } else {
        invokeNewSnackbar({ type, message });
      }
    },
    [snackbar, invokeNewSnackbar],
  );

  const snackbarActions = React.useMemo(
    () => ({
      success: showSnackbar('success'),
      error: showSnackbar('error'),
      clear: clearSnackbar,
    }),
    [showSnackbar],
  );

  return (
    <snackbarContext.Provider value={snackbarActions}>
      {children}
      {snackbar.message && (
        <Layer>
          <Suspense fallback={<></>}>
            <Snackbar
              message={snackbar.message}
              type={snackbar.type}
              color={snackbar.color}
              icon={snackbar.icon}
              onClose={() => clearSnackbar()}
              shouldAnimateIn={shouldAnimateIn}
            />
          </Suspense>
        </Layer>
      )}
    </snackbarContext.Provider>
  );
};

export default snackbarContext;
