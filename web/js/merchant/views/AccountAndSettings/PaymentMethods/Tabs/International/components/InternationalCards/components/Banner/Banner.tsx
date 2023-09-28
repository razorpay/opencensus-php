import { Alert } from '@razorpay/blade/components';
import { OpenModalType } from 'common/typings';
import { StyledAlertSection } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';
import {
  BannerType,
  ICProductStates,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { getBannerProps, trackBannerDisplayed } from './config';

export interface BannerProps {
  type: BannerType;
  workflowEta: string | null;
  bannerMessage: string | null;
  history: RouteComponentProps['history'];
  pgProductState: ICProductStates;
  ppliProductState: ICProductStates;
  isRequestRejectedFor90Days: boolean;
}

type Props = StoreProps & RouteComponentProps & BannerProps;

type StoreProps = {
  openModal: OpenModalType;
};

const Banner = ({
  type,
  openModal,
  workflowEta = '--',
  bannerMessage,
  history,
  pgProductState,
  ppliProductState,
  isRequestRejectedFor90Days,
}: Props): JSX.Element | null => {
  useEffect(() => {
    trackBannerDisplayed({ type, bannerMessage, pgProductState, ppliProductState });
  }, [type, bannerMessage, pgProductState, ppliProductState]);

  const alertProps = getBannerProps({
    type,
    openModal,
    workflowEta,
    bannerMessage,
    history,
    isRequestRejectedFor90Days,
  });

  return (
    <StyledAlertSection>
      <Alert {...alertProps} />
    </StyledAlertSection>
  );
};

export default connect(null, {
  openModal: fnOpenModal,
})(withRouter<any>(Banner));
