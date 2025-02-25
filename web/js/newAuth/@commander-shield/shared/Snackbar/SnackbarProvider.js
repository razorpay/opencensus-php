import React, { useState } from 'react';
import PropTypes from 'prop-types';
import isEmpty from '@razorpay/universe-utils/isEmpty';
import Failure from '@razorpay/blade-old/src/icons/Failure';
import Success from '@razorpay/blade-old/src/icons/Success';
import Snackbar from './Snackbar';
import SnackbarContext from './SnackbarContext';

const snackbarMap = {
  error: {
    color: 'negative.900',
    icon: Failure,
  },
  success: {
    color: 'positive.900',
    icon: Success,
  },
};

const defaultDurationMs = 5000;

const SnackbarProvider = ({ children }) => {
  const [snackbar, setSnackbar] = useState({});
  const [shouldAnimateIn, setShouldAnimateIn] = useState(false);
  let timer = null;
  let animationTimer = null;

  const clearSnackbar = (duration = 0) => {
    animationTimer = setTimeout(() => {
      setShouldAnimateIn(false);
      timer = setTimeout(() => {
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
      icon: snackbarMap?.[type]?.icon,
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
    <SnackbarContext.Provider value={snackbarActions}>
      {children}
      <Snackbar
        message={snackbar.message}
        type={snackbar.type}
        color={snackbar.color}
        icon={snackbar.icon}
        onClose={() => clearSnackbar()}
        shouldAnimateIn={shouldAnimateIn}
      />
    </SnackbarContext.Provider>
  );
};

SnackbarProvider.propTypes = {
  children: PropTypes.node,
};

export default SnackbarProvider;
