import React, { ReactElement } from 'react';
import { useQuery } from '@tanstack/react-query';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { merchantFetch } from 'merchant/utils/ajax';
import { RouteGuard } from 'merchant/components/ShowWhen';

interface WrapperProps extends RouteComponentProps {
  children: JSX.Element;
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

function Wrapper({ user, children }: WrapperProps): ReactElement {
  const { data, isLoading, isError } = useQuery({
    queryKey: ['platform-check'],
    queryFn: fetchPlatformFeature,
    refetchOnWindowFocus: false,
  });

  const isPlatformFeeTabEnabled =
    (user.isSubMerchant && !isLoading && !isError && data?.data?.feature_enabled) || false;

  return <RouteGuard>{React.cloneElement(children, { isPlatformFeeTabEnabled })}</RouteGuard>;
}

export default compose<any>(connect((state) => ({ user: state.session.user })))(Wrapper);
