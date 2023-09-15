import React from 'react';
import styled from 'styled-components';

import InputContainer from 'merchant/views/MagicCheckout/AnalyticsSettings/common/InputContainer';
import InfoPointsContainer from 'merchant/views/MagicCheckout/AnalyticsSettings/common/InfoPointsContainer';

import { ContainerContentPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import { ContentWrapper } from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/common';

const AnalyticsAccountWrapper = styled.div`
  display: flex;
  flex-direction: column;
  gap: 22px;
  width: 50%;
`;
const ContainerContent = (props: ContainerContentPropsType): JSX.Element => {
  const {
    tableHeader,
    headerIcon,
    customIntegrationOptions,
    integrationModalProps,
    merchantAnalyticsConfigs,
    oAuthAccountConfigs,
    setIntegrationMethod,
    deleteAccountConfig,
  } = props;

  return (
    <ContentWrapper>
      <AnalyticsAccountWrapper>
        {merchantAnalyticsConfigs?.map((accountConfig: Record<string, any>) => (
          <InputContainer
            tableHeader={tableHeader}
            headerIcon={headerIcon}
            oAuthAccountConfigs={oAuthAccountConfigs}
            customIntegrationOptions={
              accountConfig.customIntegrationOptions ?? customIntegrationOptions
            }
            integrationModalProps={integrationModalProps}
            merchantAnalyticsConfigs={accountConfig}
            setIntegrationMethod={setIntegrationMethod}
            deleteAccountConfig={deleteAccountConfig}
            key={accountConfig?.id ?? accountConfig?.integrationMethod}
          />
        ))}
      </AnalyticsAccountWrapper>
      <InfoPointsContainer />
    </ContentWrapper>
  );
};

export default ContainerContent;
