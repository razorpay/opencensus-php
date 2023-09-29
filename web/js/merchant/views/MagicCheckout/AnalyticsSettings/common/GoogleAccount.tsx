import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch, AnyAction } from 'redux';
import styled from 'styled-components';

import { Text } from '@razorpay/blade/components';

import GoogleSignInButton from 'merchant/views/MagicCheckout/AnalyticsSettings/common/SignInGoogle';

import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchOauthId } from 'merchant/reducers/magicCheckout/analyticsSettings/actions';
import { setCookie } from 'common/utils/cookies';

import GoogleIcon from 'assets/google-icon.svg';

import { GoogleAccountPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import { NOTIFICATION_TEXTS } from 'merchant/views/MagicCheckout/AnalyticsSettings/constants';

import {
  InputContainerTable,
  TableHeader,
  TableFooter,
  ConfigContainer,
  ConfigLabel,
  ConfigValue,
  HeadingText,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/InputContainer';
import { EditIconContainer } from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/AnalyticsEventsPreview';

const GoogleAccountWrapper = styled.div`
  margin-bottom: 24px;
`;

const GoogleAccount = (props: GoogleAccountPropsType) => {
  const { oAuthAccountConfigs, showNotification, fetchOauthId } = props;

  const [isPreviewMode, setShowPreviewMode] = useState<boolean>(true);

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
    <GoogleAccountWrapper>
      <InputContainerTable>
        <TableHeader>
          <HeadingText>
            <img src={GoogleIcon} alt="google-icon" />
            <Text size="medium" weight="bold">
              Google Account
            </Text>
          </HeadingText>
          {isPreviewMode ? (
            <EditIconContainer onClick={() => setShowPreviewMode(false)} data-testid="edit-icon">
              <i className="i i-edit_board" />
              Edit
            </EditIconContainer>
          ) : (
            <EditIconContainer onClick={() => setShowPreviewMode(true)} data-testid="back-icon">
              Back
            </EditIconContainer>
          )}
        </TableHeader>
        <TableFooter>
          {!isPreviewMode ? (
            <GoogleSignInButton onClick={handleOauthLogin} />
          ) : (
            oAuthAccountConfigs?.map((config: Record<string, string>) => {
              return (
                <ConfigContainer key={'test@gmail.com'}>
                  <ConfigLabel>Email</ConfigLabel>
                  <ConfigValue>{config?.email}</ConfigValue>
                </ConfigContainer>
              );
            })
          )}
        </TableFooter>
      </InputContainerTable>
    </GoogleAccountWrapper>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      showNotification,
      fetchOauthId,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(GoogleAccount);
