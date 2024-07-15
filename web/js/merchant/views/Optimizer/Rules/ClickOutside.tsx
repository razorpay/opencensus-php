import React, { useState, useEffect, useRef } from 'react';

export const ClickOutside = ({
  onClickOutside,
  children,
  ...props
}: {
  onClickOutside: () => void;
  children: React.ReactNode;
}): JSX.Element => {
  const container = useRef(null);
  const [isTouch, setIsTouch] = useState(false);

  const handle = ({ type, target }) => {
    if (type === 'touchend') {
      setIsTouch(true);
    }
    if (type === 'click' && isTouch) {
      return;
    }
    const el = container.current;
    if (el && !(el as HTMLElement).contains(target)) onClickOutside();
  };

  useEffect(() => {
    document.addEventListener('touchend', handle, true);
    document.addEventListener('click', handle, true);
    return () => {
      document.removeEventListener('touchend', handle, true);
      document.removeEventListener('click', handle, true);
    };
  }, []);

  return (
    <div {...props} ref={container}>
      {children}
    </div>
  );
};
