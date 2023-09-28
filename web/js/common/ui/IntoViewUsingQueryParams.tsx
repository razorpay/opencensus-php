import React, { useEffect, useRef } from 'react';
import scrollTo from 'common/utils/scrollTo';
import qs from 'query-string';
import get from 'lodash/get';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps, WithRouterProps } from 'common/deprecated/RouteComponentProps';

type Props = {
  children: JSX.Element;
  queryKey: string;
  queryValue: string;
  elementRef?: React.RefObject<HTMLDivElement>;
} & RouteComponentProps &
  WithRouterProps;

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
  location,
}: Props): JSX.Element {
  const showView = useRef<HTMLDivElement>(null);

  const scrollIntoView = () => {
    if (elementRef?.current) {
      handleScrollIntoView(elementRef.current.offsetTop);
    } else if (showView.current) {
      handleScrollIntoView(showView.current.offsetTop);
    }
  };

  useEffect(() => {
    if (!location.search) return;
    const queryParams = qs.parse(location.search);
    if (get(queryParams, queryKey) === queryValue) {
      scrollIntoView();
    }
  }, [location.search]);

  return <div ref={showView}>{children}</div>;
}

export default withRouter(IntoViewUsingQueryParams);
