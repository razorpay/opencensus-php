import React, { ReactElement } from 'react';
import { Route, RouteComponentProps } from 'react-router-dom';
import { useQuery } from 'react-query';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { merchantFetch } from 'merchant/utils/ajax';

interface WrapperProps extends RouteComponentProps {
  component: React.ComponentType<RouteComponentProps<any>> | React.ComponentType<any> | any;
  user: {
    isSubMerchant?: boolean;
  };
}

const fetchPlatformFeature = () => {
  return merchantFetch({
    url: 'submerchant/partner_feature_check/route_partnerships',
    method: 'get',
  });
};

function Wrapper({ user, component: Component, ...rest }: WrapperProps): ReactElement {
  const { data, isLoading, isError } = useQuery('platform-check', fetchPlatformFeature, {
    refetchOnWindowFocus: false,
  });
  const isPlatformFeeTabEnabled =
    (user.isSubMerchant && !isLoading && !isError && data?.data?.feature_enabled) || false;

  return (
    <Route
      {...rest}
      render={(props: RouteComponentProps) => (
        <Component {...props} isPlatformFeeTabEnabled={isPlatformFeeTabEnabled} />
      )}
    />
  );
}

export default compose<any>(connect((state) => ({ user: state.session.user })))(Wrapper);
