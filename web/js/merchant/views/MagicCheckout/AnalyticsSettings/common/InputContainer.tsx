import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import { Text } from '@razorpay/blade/components';
import { Label } from 'common/new-ui/Input';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import GoogleSignInButton from 'merchant/views/MagicCheckout/AnalyticsSettings/common/SignInGoogle';

import { openModal } from 'merchant_common/reducers/modals';
import { fetchOauthId } from 'merchant/reducers/magicCheckout/analyticsSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import { setCookie } from 'common/utils/cookies';

import { InputContainerPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import TrashOutlineImage from 'assets/trash-outline.svg';

import {
  InputContainerTable,
  TableHeader,
  TableBody,
  StyledInput,
  InfoTextContainer,
  TableFooter,
  ConfigContainer,
  ConfigLabel,
  ConfigValue,
  IntegrationFieldWrapper,
  DeleteActionContainer,
  HeadingText,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/InputContainer';

import {
  ANALYTICS_PLATFORM,
  DEFAULT_INTEGRATION_OPTIONS,
  INTEGRATION_INFO_TEXT,
  INTEGRATION_TYPE,
  NOTIFICATION_TEXTS,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

const IntegrationModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicAnalyticsSettings" */ 'merchant/views/MagicCheckout/AnalyticsSettings/common/IntegrationModal'
    ),
);

const InputContainer = (props: InputContainerPropsType): JSX.Element => {
  const {
    tableHeader,
    customIntegrationOptions,
    headerIcon,
    openModal,
    integrationModalProps,
    setIntegrationMethod,
    deleteAccountConfig,
    fetchOauthId,
    merchantAnalyticsConfigs,
    oAuthAccountConfigs,
    showNotification,
  } = props;

  const [isIntegrationMethodHidden, setIsIntegrationMethodHidden] = useState<boolean>(true);

  const integrationMethod = merchantAnalyticsConfigs?.integrationMethod ?? '';

  const handleIntegrationPlatformSelection = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.value === integrationMethod) return;

    setIntegrationMethod(e.target.value);

    //case user chooses to integrate account with backend or both (backend + frontend) ask for creds
    if (e.target.value === INTEGRATION_TYPE.backend || e.target.value === INTEGRATION_TYPE.both) {
      openModal({
        size: 'large',
        className: 'link-analytics-account-modal',
        component: (
          <SuspenseWithLoader type="center">
            <IntegrationModal integrationModalProps={integrationModalProps} />
          </SuspenseWithLoader>
        ),
      });
    }
  };

  useEffect(() => {
    const hasAuthId =
      oAuthAccountConfigs &&
      !!Object.keys(oAuthAccountConfigs).length &&
      oAuthAccountConfigs[0]?.id;

    if (tableHeader === ANALYTICS_PLATFORM.googleAds.label && !hasAuthId) {
      setIsIntegrationMethodHidden(true);
    } else {
      setIsIntegrationMethodHidden(false);
    }
  }, [oAuthAccountConfigs]);

  const handleOauthLogin = () => {
    fetchOauthId()
      .then((response: Record<string, any>) => {
        const { magic_analytics_oauth_csrf, redirect_url } = response.data;

        const now = new Date();
        const minutes = 60; // session will expire in 60 mins.
        now.setTime(now.getTime() + minutes * 60 * 1000);

        setCookie('magic_analytics_oauth_csrf', magic_analytics_oauth_csrf, now, '/');

        window.location.href = redirect_url;
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_TEXTS.error,
        });
      });
  };

  return (
    <InputContainerTable>
      <TableHeader>
        <HeadingText>
          <img src={headerIcon} alt="analytics-icon" />
          <Text size="medium" weight="bold">
            {tableHeader}
          </Text>
        </HeadingText>
        {merchantAnalyticsConfigs?.isNotEditable ? (
          <DeleteActionContainer onClick={() => deleteAccountConfig(merchantAnalyticsConfigs?.id)}>
            <img src={TrashOutlineImage} alt="trash-outline" className="trash-outline" />
            <p className="remove-txt">Remove</p>
          </DeleteActionContainer>
        ) : null}
      </TableHeader>
      <TableBody>
        {!isIntegrationMethodHidden ? (
          <IntegrationFieldWrapper className="field-wrapper">
            <Label text="Integrate with" />
            <StyledInput
              id="integration-options"
              name="integration-type"
              options={customIntegrationOptions ?? DEFAULT_INTEGRATION_OPTIONS}
              value={integrationMethod}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                handleIntegrationPlatformSelection(e)
              }
              disabled={merchantAnalyticsConfigs?.isNotEditable}
              data-testid="integration-type"
            />
          </IntegrationFieldWrapper>
        ) : (
          <GoogleSignInButton onClick={handleOauthLogin} />
        )}
        {integrationMethod !== INTEGRATION_TYPE.backend ? (
          <InfoTextContainer>
            <Text size="small">{INTEGRATION_INFO_TEXT[tableHeader]}</Text>
          </InfoTextContainer>
        ) : null}
      </TableBody>
      {merchantAnalyticsConfigs?.data?.length > 0 &&
      integrationMethod !== INTEGRATION_TYPE.frontend ? (
        <TableFooter>
          {merchantAnalyticsConfigs?.data?.map((config: Record<string, string>) => {
            return (
              <ConfigContainer key={config.value}>
                <ConfigLabel>{config.label}</ConfigLabel>
                <ConfigValue>{config.value}</ConfigValue>
              </ConfigContainer>
            );
          })}
        </TableFooter>
      ) : null}
    </InputContainerTable>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      openModal,
      fetchOauthId,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(InputContainer);
