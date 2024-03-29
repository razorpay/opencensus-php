import React from 'react';
import {
  Box,
  Button,
  RadioGroup,
  Radio,
  Text,
  Spinner,
  CardBody,
  Card,
  InfoIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import styled from 'styled-components';

import { ShowNotificationType } from 'common/typings';
import Time from 'common/ui/Time';
import ConditionalTooltip from 'merchant/containers/ConditionalTooltip';
import Application from 'merchant/models/Application';
import { OAuthAppDetailsType } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';
import useFetchApplications from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/hooks/useFetchApplications';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { showNotification } from 'merchant_common/reducers/notifications';

const StyledApplicationTile = styled.div(
  ({ $hasInvalidAppSetting }: { $hasInvalidAppSetting: boolean }) =>
    `
    cursor: ${$hasInvalidAppSetting ? 'not-allowed;' : 'pointer;'}
    `,
);

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
    const selectedApplicationDetails = applications?.find(
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
          backgroundColor="surface.background.gray.intense"
        >
          {applications?.map(({ id, name, logo_url, created_at, hasInvalidAppSetting }) => {
            const onAppClick = () => {
              if (hasInvalidAppSetting) return null;
              return handleApplicationSelect(id);
            };
            return (
              <Card
                key={id}
                elevation="midRaised"
                padding="spacing.3"
                backgroundColor="surface.background.gray.moderate"
                as="label"
                accessibilityLabel={name}
                isSelected={id === selectedApp?.application_id}
                marginBottom="spacing.1"
              >
                <CardBody>
                  <StyledApplicationTile
                    $hasInvalidAppSetting={hasInvalidAppSetting}
                    onClick={onAppClick}
                  >
                    <Box
                      display="flex"
                      flexDirection="column"
                      gap="spacing.5"
                      justifyContent="center"
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
                                src={applicationModel.formatLogoUrl(logo_url)}
                                alt="application logo"
                                height={80}
                              />
                            </Box>
                            <Box>
                              <Text weight="semibold" color="surface.text.gray.muted">
                                {name}
                              </Text>
                              <Text color="surface.text.gray.muted">App Id : {id}</Text>
                              <Text color="surface.text.gray.muted">
                                Created on : <Time value={created_at} format="DD MMM YYYY" />
                              </Text>
                              <Text color="surface.text.gray.muted">
                                {hasInvalidAppSetting ? (
                                  <ConditionalTooltip
                                    showTooltip={hasInvalidAppSetting}
                                    content="Please update with the correct URI in Production Redirect URI of your app settings"
                                    onOpenChange={function noRefCheck() {}}
                                    placement="right"
                                    padding="spacing.1"
                                  >
                                    <Box>
                                      <Text display="inline-block">Invalid Link</Text>
                                      <InfoIcon
                                        marginLeft="spacing.2"
                                        size="small"
                                        color="feedback.icon.neutral.intense"
                                      />
                                    </Box>
                                  </ConditionalTooltip>
                                ) : null}
                              </Text>
                            </Box>
                          </Box>
                        </Box>
                        <Radio isDisabled={hasInvalidAppSetting} value={id}>
                          {''}
                        </Radio>
                      </Box>
                    </Box>
                  </StyledApplicationTile>
                </CardBody>
              </Card>
            );
          })}
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
