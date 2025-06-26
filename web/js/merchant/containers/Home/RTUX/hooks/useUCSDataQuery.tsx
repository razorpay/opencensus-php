import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { type AxiosRequestConfig } from 'axios';
import { fetchUCS } from '@federated/apps/shell/rest-fetch';
import { isRTUXHomepageEnabled } from '../utils';
import { User } from 'common/typings';
import { isEligibleForFtuxV2 } from 'merchant/containers/Home/FTUX/utils';

// Todo : Add type for response
export const fetchUCSData = async (requestData: AxiosRequestConfig['data']): Promise<any> => {
  const response = fetchUCS({
    method: 'POST',
    url: `rzp.dashboard.component.v1.ComponentService/GetComponentData`,
    data: requestData,
  });
  return response;
};

export const useUCSDataQuery = (widgetName: Array<string>, data = {}, enabled = true): any =>
  useQuery<any>({
    queryKey: widgetName,
    queryFn: () => fetchUCSData(data),
    refetchOnWindowFocus: false,
    staleTime: 60000 * 1, // 1 minute
    retry: false,
    cacheTime: 5 * 60 * 1000, // Cache remains in memory for 5 minutes
    refetchOnMount: true,
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

export const withRtuxComponentData = <P extends RTUXComponentProps>(
  WrappedComponent: React.ComponentType<P>,
) => {
  const ComponentWithQuery = (props: P) => {
    const {
      user,
      splitz: { abExperiments },
    } = props;
    const isFTUXHomepage = isEligibleForFtuxV2({ user, abExperiments });
    const isRTUXHomepage = isRTUXHomepageEnabled({ user, abExperiments });
    const isDashboardHome = window.location.pathname === '/app/dashboard';

    useUCSDataQuery(
      ['rtux-homepage', 'data'],
      { alias: 'home_page' },
      !isFTUXHomepage && isRTUXHomepage && isDashboardHome,
    );

    return <WrappedComponent {...props} />;
  };
  return ComponentWithQuery;
};
