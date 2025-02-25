import React from 'react';
import { type QueryKey, useQuery } from '@tanstack/react-query';
import { type AxiosRequestConfig } from 'axios';
import { fetchUCS } from '@federated/apps/shell/rest-fetch';
import { User } from 'common/typings';
import { isRTUXHomepageEnabled } from '../utils';

// Todo : Add type for return value
export const useUCSLayoutQuery = (
  widgetName: QueryKey,
  requestData: AxiosRequestConfig['data'] = {},
  enabled = true,
): any =>
  useQuery<any>({
    queryKey: widgetName,
    queryFn: async () => {
      const response = fetchUCS({
        method: 'POST',
        url: `rzp.dashboard.component.v1.ComponentService/GetComponent`,
        data: requestData,
      });
      return response;
    },
    refetchOnWindowFocus: false,
    staleTime: 60000 * 1, // 1 minute (keep in sync with useUCSDataQuery for consistent skeleton)
    cacheTime: 5 * 60 * 1000, // Cache remains in memory for 5 minutes
    retry: false,
    enabled,
    // https://stackoverflow.com/a/78365989
    networkMode: 'always',
  });

interface RTUXComponentProps {
  user: User;
  splitz: {
    abExperiments: Record<string, any>;
  };
}

export const withRtuxLayoutData = <P extends RTUXComponentProps>(
  WrappedComponent: React.ComponentType<P>,
) => {
  const ComponentWithQuery = (props: P) => {
    const {
      user,
      splitz: { abExperiments },
    } = props;
    const isRTUXHomepage = isRTUXHomepageEnabled({ user, abExperiments });
    const isDashboardHome = window.location.pathname === '/app/dashboard';

    useUCSLayoutQuery(
      ['rtux-homepage', 'layout'],
      { alias: 'home_page' },
      isRTUXHomepage && isDashboardHome,
    );

    return <WrappedComponent {...props} />;
  };
  return ComponentWithQuery;
};
