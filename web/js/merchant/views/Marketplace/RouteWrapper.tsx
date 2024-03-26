import React, { ReactElement } from 'react';
import { useQuery } from '@tanstack/react-query';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { connect } from 'react-redux';
import { compose } from 'redux';
import shallow from 'zustand/shallow';
import { merchantFetch } from 'merchant/utils/ajax';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { fetchPartnerFeeFeature } from 'merchant/views/Marketplace/api';
import { useMarketplaceStore } from 'merchant/views/Marketplace/store';

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
  const { setIsPartnerPlatformFeeEnabled, isPartnerPlatformFeeEnabled } = useMarketplaceStore(
    (state) => ({
      setIsPartnerPlatformFeeEnabled: state.setIsPartnerPlatformFeeEnabled,
      isPartnerPlatformFeeEnabled: state.isPartnerPlatformFeeEnabled,
    }),
    shallow,
  );
  useQuery({
    queryKey: ['partner-feature-check'],
    queryFn: fetchPartnerFeeFeature,
    refetchOnWindowFocus: false,
    onSuccess: (partnerFeatureData) => {
      setIsPartnerPlatformFeeEnabled(partnerFeatureData?.data?.feature_enabled || false);
    },
  });

  const isPlatformFeeTabEnabled =
    (user.isSubMerchant && !isLoading && !isError && data?.data?.feature_enabled) || false;

  return (
    <RouteGuard>
      {React.cloneElement(children, { isPlatformFeeTabEnabled, isPartnerPlatformFeeEnabled })}
    </RouteGuard>
  );
}

export default compose<any>(connect((state) => ({ user: state.session.user })))(Wrapper);
