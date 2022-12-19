import React, { useEffect, useState, useRef } from 'react';
import { CollapsibleContainer, Content } from './styled';

interface CollapsiblePropInterface {
  open: boolean;
  children: JSX.Element;
}

const Collapsible = ({ open, children }: CollapsiblePropInterface): JSX.Element => {
  const [height, setHeight] = useState<number | undefined>(open ? undefined : 0);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!height || !open || !ref.current) return undefined;
    const resizeObserver = new ResizeObserver((el) => {
      setHeight(el[0].contentRect.height);
    });
    resizeObserver.observe(ref.current);
    return () => {
      resizeObserver.disconnect();
    };
  }, [height, open]);

  useEffect(() => {
    if (open) setHeight(ref.current?.getBoundingClientRect().height);
    else setHeight(0);
  }, [open]);

  return (
    <CollapsibleContainer>
      <Content height={height}>
        <div ref={ref}>{children}</div>
      </Content>
    </CollapsibleContainer>
  );
};

export default Collapsible;
