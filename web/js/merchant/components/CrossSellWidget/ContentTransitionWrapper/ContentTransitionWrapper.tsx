import React, { useEffect } from 'react';
import { ContentTransiton } from './styled';
import { getWidgetContentTransition } from './utils';
import { useTheme } from '@razorpay/blade/utils';

function ContentTransitionWrapper({ children, selectedViewId, id }) {
  const [contentTransition, setContentTransition] = React.useState('');
  const { theme } = useTheme();

  useEffect(() => {
    const getTimeoutDuration = () => {
      return id !== selectedViewId ? theme.motion.duration.quick : 0;
    };

    const transitionTimeout = setTimeout(() => {
      setContentTransition(getWidgetContentTransition({ theme, id, selectedViewId }));
    }, getTimeoutDuration());

    return () => {
      clearTimeout(transitionTimeout);
    };
  }, [selectedViewId]);

  return (
    <ContentTransiton transition={contentTransition} id={id} selectedViewId={selectedViewId}>
      {children}
    </ContentTransiton>
  );
}

export default ContentTransitionWrapper;
