import React from 'react';
import { Box, Button, RadioGroup, Radio, Text, Spinner } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import { ShowNotificationType } from 'common/typings';
import Time from 'common/ui/Time';
import Application from 'merchant/models/Application';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import useFetchApplications from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useFetchApplications';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

type ChooseOAuthAppProps = {
  onNextClick: () => void;
  setSelectedApp: (args: OAuthAppDetailsType) => void;
  selectedApp: OAuthAppDetailsType;
  showNotification: ShowNotificationType;
};
const ChooseOAuthApp = ({
  onNextClick,
  selectedApp,
  setSelectedApp,
  showNotification,
}: ChooseOAuthAppProps): JSX.Element => {
  const { data: applications, isLoading } = useFetchApplications({ showNotification });

  const applicationModel = new Application();

  const handleApplicationSelect = (applicationId) => {
    const selectedApplicationDetails = applications.find(
      (application) => application.id === applicationId,
    );

    if (selectedApplicationDetails) {
      setSelectedApp({
        application_id: applicationId,
        client_id: selectedApplicationDetails.client_details?.prod?.id,
        redirect_uri: selectedApplicationDetails.client_details?.prod?.redirect_url?.[0],
        name: selectedApplicationDetails.name,
      });
    }
  };

  if (isLoading)
    return (
      <Box
        display="flex"
        alignItems="center"
        justifyContent="center"
        marginTop="spacing.5"
        minHeight="425px"
      >
        <Spinner alignSelf="center" accessibilityLabel="fetching-applications-spinner" />
      </Box>
    );

  return (
    <Box display="flex" flexDirection="column" gap="spacing.6" flex="1" minHeight="425px">
      <RadioGroup
        onChange={handleApplicationSelect}
        value={selectedApp?.application_id}
        size="small"
        label=""
      >
        <Box
          display="flex"
          flexDirection="column"
          justifyContent="center"
          backgroundColor="surface.background.level2.lowContrast"
        >
          {applications?.map((application) => (
            <div onClick={() => handleApplicationSelect(application.id)} key={application.id}>
              <Box
                display="flex"
                flexDirection="column"
                gap="spacing.5"
                justifyContent="center"
                padding="spacing.6"
                backgroundColor="surface.background.level2.lowContrast"
                borderColor="surface.border.normal.lowContrast"
                borderWidth="thin"
              >
                <Box
                  display="flex"
                  gap="spacing.5"
                  alignItems="center"
                  flex="1"
                  justifyContent="space-between"
                >
                  <Box
                    display="flex"
                    gap="spacing.5"
                    alignItems="center"
                    paddingX="none"
                    paddingY="spacing.6"
                  >
                    <Box display="flex" gap="spacing.4" alignItems="center" flex="1">
                      <Box>
                        <img
                          src={applicationModel.formatLogoUrl(application.logo_url)}
                          alt="application logo"
                          height={80}
                        />
                      </Box>
                      <Box>
                        <Text type="subdued" weight="bold">
                          {application.name}
                        </Text>
                        <Text type="subdued">App Id : {application.id}</Text>
                        <Text type="subdued">
                          Created on : <Time value={application.created_at} format="DD MMM YYYY" />
                        </Text>
                      </Box>
                    </Box>
                  </Box>
                  <Radio value={application.id}>{''}</Radio>
                </Box>
              </Box>
            </div>
          ))}
        </Box>
      </RadioGroup>
      <ModalFooter>
        <Button onClick={onNextClick}>Next</Button>
      </ModalFooter>
    </Box>
  );
};

export default compose<TODO_PD>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification }, dispatch),
  ),
)(ChooseOAuthApp);
