import React, { useRef } from 'react';
import scrollTo from 'common/utils/scrollTo';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';

type Props = {
  children: JSX.Element;
  queryKey: string;
  queryValue: string;
  elementRef?: React.RefObject<HTMLDivElement>;
};

const handleScrollIntoView = (offsetTop: number) => {
  // add delay to wait for whole dom to load then scroll to the target element
  setTimeout(() => {
    if (offsetTop) {
      scrollTo({ endPos: offsetTop });
    }
  }, 500);
};

function IntoViewUsingQueryParams({
  children,
  queryKey,
  queryValue,
  elementRef,
}: Props): JSX.Element {
  const showView = useRef<HTMLDivElement>(null);

  const scrollIntoView = () => {
    if (elementRef?.current) {
      handleScrollIntoView(elementRef.current.offsetTop);
    } else if (showView.current) {
      handleScrollIntoView(showView.current.offsetTop);
    }
  };

  return (
    <TriggerOnQueryParamMatch
      queryParamsMapping={[
        {
          key: queryKey,
          value: queryValue,
          trigger: scrollIntoView,
        },
      ]}
    >
      <div ref={showView}>{children}</div>
    </TriggerOnQueryParamMatch>
  );
}

export default IntoViewUsingQueryParams;
