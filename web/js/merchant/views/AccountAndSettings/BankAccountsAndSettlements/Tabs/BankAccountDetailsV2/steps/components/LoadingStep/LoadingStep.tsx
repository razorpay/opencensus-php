import React, { useEffect } from 'react';
import { Button, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { classList } from 'common/utils/rzp-utils';
import lazy from 'merchant/routes/LazyLoader';
import { StyledDivider } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/common/styled';
import {
  LOADING_STATE,
  LoadingStepInterface,
} from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { trackBankAccountUpdateEvent } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/utils/track';
import { closeModal } from 'merchant_common/reducers/modals';

import { LOADING_STEP_DATA } from './constants';
import { DescriptionText, StyledLoadingContainer, StyledTitleGroup } from './styled';

const CustomLottie = lazy(
  () => import(/* webpackChunkName: 'CustomLottie' */ 'common/new-ui/Lottie'),
);

const LoadingStep = ({
  type,
  lottieClass,
  closeModal,
  isMobile,
}: LoadingStepInterface): JSX.Element | null => {
  useEffect(() => {
    if (type === LOADING_STATE.PENNY_TESTING_SUCCESS) {
      trackBankAccountUpdateEvent({
        objectName: 'Penny Test Success',
        actionName: 'Displayed',
      });
    }
  }, []);

  if (!LOADING_STATE[type]) return null;
  const { title, subTitle, animationData, description, closeCTALabel, mobileSubTitle } =
    LOADING_STEP_DATA[type];
  const handleClose = (): void => {
    trackBankAccountUpdateEvent({
      objectName: 'Penny Test Passed',
      actionName: 'Clicked',
    });
    closeModal();
  };
  return (
    <StyledLoadingContainer>
      <div className={classList(lottieClass)}>
        <SuspenseWithLoader>
          <CustomLottie
            animationData={animationData}
            autoplay
            loop
            width="100px"
            isStopped={false}
          />
        </SuspenseWithLoader>
      </div>
      <StyledTitleGroup>
        <Text weight="semibold" size="large">
          {title}
        </Text>
        <Text color="surface.text.gray.subtle">
          {mobileSubTitle && isMobile ? mobileSubTitle : subTitle}
        </Text>
      </StyledTitleGroup>
      {description && (
        <>
          <StyledDivider isFullWidth />
          <DescriptionText>
            <Text size="small" color="surface.text.gray.muted">
              {description}
            </Text>
          </DescriptionText>
        </>
      )}
      {closeCTALabel && (
        <Button type="button" onClick={handleClose}>
          {closeCTALabel}
        </Button>
      )}
    </StyledLoadingContainer>
  );
};

const mapStateToProps = (state) => ({
  isMobile: state.app.isMobileResolution,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal }, dispatch);
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(LoadingStep);
