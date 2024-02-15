import React, { useState } from 'react';
import { Box, Heading, Divider, Spinner } from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { ShowNotificationType } from 'common/typings';
import { showNotification } from 'merchant_common/reducers/notifications';

import { DetailsRow } from './Components/DetailsRow';
import KycActionButton from './Components/KycActionButton';
import { KycHistoryTimeline } from './Components/KycHistoryTimeline';
import { PosSubmerchantDetailsResponseDataType } from './TypeDeclares';
import { fetchPosSubmerchantDetails } from './api';
import { formatDate } from './utils';

interface PosSubmerchantDetailsProps {
  id: string;
  showNotification: ShowNotificationType;
}
const POSSubmerchantDetails = ({
  id,
  showNotification,
}: PosSubmerchantDetailsProps): JSX.Element => {
  const [responseData, setResponseData] = useState<PosSubmerchantDetailsResponseDataType>();

  const { isLoading } = useQuery({
    queryKey: ['get-pos-submerchant-details', id],
    queryFn: () => fetchPosSubmerchantDetails(id),
    refetchOnWindowFocus: false,
    onSuccess: (response) => {
      setResponseData(response.data);
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });
  return (
    <SuspenseWithLoader>
      <div className="content-wrapper content-sm txn-details">
        {isLoading ? (
          <Box display="flex" justifyContent="center" alignItems="center" minHeight="100%">
            <Spinner testID="spinner" accessibilityLabel="spinner" size="xlarge" />
          </Box>
        ) : (
          <div className="panel panel-default SliderPanel SubmerchantDetail__Panel">
            <div className="panel-heading">
              <Box
                display="flex"
                justifyContent="space-between"
                paddingY="spacing.5"
                paddingRight="spacing.8"
              >
                <Heading size="medium">{responseData?.name ?? 'N/A'}</Heading>
                <KycActionButton submerchant={responseData} />
              </Box>
            </div>
            <div className="SliderPanel__Body">
              <div className="panel-body">
                <DetailsRow label="Account ID" values={[responseData?.id]} />
                <DetailsRow
                  label="Contact Details"
                  values={[responseData?.user?.contact_mobile, responseData?.user?.email]}
                />
                <DetailsRow
                  label="Invite Accepted On"
                  values={[formatDate(responseData?.created_at, 'MMM DD, YYYY')]}
                />
                <DetailsRow
                  label="KYC Performed By"
                  values={[responseData?.pos?.last_kyc_performed_by?.name]}
                />
                <DetailsRow
                  label="Contact Details"
                  values={[responseData?.pos?.last_kyc_performed_by?.contact_email]}
                />
                <DetailsRow
                  label="KYC Status"
                  values={[responseData?.pos?.activation_status]}
                  isActivationStatus
                  kycAccess={responseData?.kyc_access}
                />
                <DetailsRow
                  label="Client's Orders"
                  values={['View Order Details']}
                  isLink
                  href={`/partners/submerchants/pos/${id}/orders`}
                />
                <Divider marginY="spacing.4" />
                <KycHistoryTimeline
                  actionState={responseData?.pos?.action_state}
                  clarificationReasons={
                    responseData?.details?.kyc_clarification_reasons?.clarification_reasons_v2
                  }
                />
              </div>
            </div>
          </div>
        )}
      </div>
    </SuspenseWithLoader>
  );
};
export default connect(null, { showNotification })(POSSubmerchantDetails);
