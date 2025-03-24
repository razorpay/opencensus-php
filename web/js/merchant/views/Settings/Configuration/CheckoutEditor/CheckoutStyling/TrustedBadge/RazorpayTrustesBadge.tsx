import React, { useState } from 'react';
import {
  Box,
  Link,
  Text,
  Modal,
  ModalBody,
  ModalHeader,
  AlertCircleIcon,
  Badge,
  Switch,
} from '@razorpay/blade/components';
import BadgeIcon from 'assets/checkout-editor/trusted-badge/rtb-icon.svg';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { fetchTrustedBadgeStatus } from 'merchant/reducers/trustedBadge';
import TrustedBadge from 'merchant/views/Account/TrustedBadge';
import { TRUSTED_BADGE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

import { TrustedIconWrapper } from './styled';
import track from './track';

export type RazorpayTrustedBadgeProps = {
  isActive: boolean;
  fetchTrustedBadgeStatus: typeof fetchTrustedBadgeStatus;
};

const RazorpayTrustedBadge = ({
  isActive = false,
  fetchTrustedBadgeStatus,
}: RazorpayTrustedBadgeProps) => {
  const { values, handleRtbEnable } = useCheckoutEditor();

  function handleRTBToggle(isChecked: boolean) {
    handleRtbEnable(isChecked);
    track.toggleRTBVisibility(isChecked ? 'visible' : 'hidden');
  }

  const [isShowTrutedBadgeModal, setShowTrustedBadgeModal] = useState(false);
  const rightChildren = !isActive ? (
    <Badge color="neutral" size="medium" emphasis="subtle" icon={AlertCircleIcon}>
      Inactive
    </Badge>
  ) : (
    <Switch
      accessibilityLabel="razorpay-trusted-badge"
      isChecked={values[CHECKOUT_EDITOR_FIELDS.RTB_ENABLED]}
      onChange={({ isChecked }) => handleRTBToggle(isChecked)}
    />
  );

  return (
    <>
      <LineItems
        title={TRUSTED_BADGE_DEFAULT_VALUE.title}
        subTitle={
          <Box display="flex" flexDirection="column" alignItems="flex-start">
            <Box display="flex" justifyContent="center" gap="spacing.2">
              <Text>
                <Text
                  color="surface.text.gray.muted"
                  variant="body"
                  size="small"
                  weight="regular"
                  marginRight="spacing.2"
                >
                  {TRUSTED_BADGE_DEFAULT_VALUE.subTitle}
                </Text>
                <Link size="small" onClick={() => setShowTrustedBadgeModal(true)} variant="button">
                  Know More
                </Link>
              </Text>
            </Box>
            <TrustedIconWrapper>
              <img src={BadgeIcon} alt="trusted-badge-icon" />
              <Text color="surface.text.gray.normal" variant="body" size="xsmall" weight="regular">
                Razorpay Trusted Business
              </Text>
            </TrustedIconWrapper>
          </Box>
        }
        rightChildren={rightChildren}
      />
      {isShowTrutedBadgeModal && (
        <Modal
          isOpen={isShowTrutedBadgeModal}
          onDismiss={() => {
            setShowTrustedBadgeModal(false);
            fetchTrustedBadgeStatus();
          }}
          size="large"
        >
          <ModalHeader title="Razorpay Trusted Badge" />
          <ModalBody>
            <TrustedBadge />
          </ModalBody>
        </Modal>
      )}
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      fetchTrustedBadgeStatus,
    },
    dispatch,
  );

export default connect((state) => {
  return {
    trustedBadge: state.trustedBadge,
  };
}, mapDispatchToProps)(RazorpayTrustedBadge);
