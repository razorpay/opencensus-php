import React from 'react';
import { Box, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { ShowNotificationType } from 'common/typings';
import lazy from 'merchant/routes/LazyLoader';
import useReferralLinks from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useReferralLinks';
import { ORG_CUSTOM_CODE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
// eslint-disable-next-line prettier/prettier
const RzpSuccessContainer = lazy(
  () => import('merchant/views/PartnerDashboard/SubMerchant/components/RzpSuccessContainer'),
);

// eslint-disable-next-line prettier/prettier
const CurlecSuccessContainer = lazy(
  () => import('merchant/views/PartnerDashboard/SubMerchant/components/CurlecSuccessContainer'),
);
type SuccessScreenProps = {
  orgCode?: string;
  productType: string;
  source: string;
  partnerID?: string;
  merchantEmail: string;
  merchantContact: string;
  showNotification: ShowNotificationType;
};
const SuccessScreen = ({
  orgCode,
  productType,
  merchantEmail,
  merchantContact,
  source,
  partnerID,
  showNotification,
}: SuccessScreenProps): JSX.Element => {
  const { data: referralData, isLoading } = useReferralLinks({ showNotification });
  if (isLoading)
    return (
      <Box display="flex" alignItems="center" justifyContent="center" marginTop="spacing.5">
        <Spinner alignSelf="center" accessibilityLabel="public-links-spinner" />
      </Box>
    );
  const referralUrl = referralData?.[productType]?.url;
  return (
    <Box backgroundColor="surface.background.level2.lowContrast" paddingTop="spacing.5">
      {/* Note: these classnames are needed for inner styling of the old BatchValidate component */}
      <div className="partner-submerchant-modal">
        <div className="modal-body">
          {orgCode === ORG_CUSTOM_CODE.CURLEC ? (
            <SuspenseWithLoader>
              <CurlecSuccessContainer />
            </SuspenseWithLoader>
          ) : (
            <SuspenseWithLoader>
              <RzpSuccessContainer
                merchantEmail={merchantEmail}
                merchantContact={merchantContact}
                referralUrl={referralUrl}
                source={source}
                merchantType={productType}
                partnerID={partnerID}
              />
            </SuspenseWithLoader>
          )}
        </div>
      </div>
    </Box>
  );
};

export default connect(
  () => ({}),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(SuccessScreen);
