import React, { useState } from 'react';
import {
  Box,
  Link,
  Text,
  Button,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  AlertCircleIcon,
  Badge,
  Switch,
} from '@razorpay/blade/components';
import BadgeIcon from 'assets/checkout-editor/trusted-badge/rtb-icon.svg';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { updateRTBMerchantStatus } from 'merchant/reducers/trustedBadge';
import TrustedBadge from 'merchant/views/Account/TrustedBadge';
import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import { TRUSTED_BADGE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { LeftWrapper, TopWrapper, Wrapper, TrustedIconWrapper } from './styled';
import track from './track';

const RazorpayTrustedBadge = ({ trustedBadge, updateRTBMerchantStatus: updateStatus }) => {
  const badgeStatus = trustedBadge?.status?.badgeStatus;

  const { values, handleRtbEnable } = useCheckoutEditor();

  function handleRTBToggle(isChecked: boolean) {
    handleRtbEnable(isChecked);
    track.toggleRTBVisibility(isChecked ? 'visible' : 'hidden');
  }

  const [isShowTrutedBadgeModal, setShowTrustedBadgeModal] = useState(false);
  const rightChildren =
    badgeStatus === STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED ? (
      <Badge color="neutral" size="medium" emphasis="subtle" icon={AlertCircleIcon}>
        Not Active
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
      <Wrapper>
        <TopWrapper>
          <LeftWrapper>
            <Text weight="medium" color="surface.text.gray.normal" variant="body" size="medium">
              {TRUSTED_BADGE_DEFAULT_VALUE.title}
            </Text>
            <Box display="flex" justifyContent="center" gap="spacing.2">
              <Text color="surface.text.gray.muted" variant="body" size="small" weight="regular">
                {TRUSTED_BADGE_DEFAULT_VALUE.subTitle}
                <Link
                  marginLeft="spacing.2"
                  size="small"
                  onClick={() => setShowTrustedBadgeModal(true)}
                  variant="button"
                >
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
          </LeftWrapper>
          {rightChildren}
        </TopWrapper>
      </Wrapper>
      {isShowTrutedBadgeModal && (
        <Modal
          isOpen={isShowTrutedBadgeModal}
          onDismiss={() => setShowTrustedBadgeModal(false)}
          size="large"
        >
          <ModalHeader title="Razorpay trusted badge" />
          <ModalBody>
            <TrustedBadge />
          </ModalBody>
          {badgeStatus === 'NOT_ELIGIBLE_DELISTED_YES_WAITLISTED' ||
          badgeStatus === 'YES_ELIGIBLE_LIVE' ||
          badgeStatus === 'NOT_ELIGIBLE_YES_WAITLISTED_DELISTED' ? null : (
            <ModalFooter>
              <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
                <Button
                  onClick={() => {
                    updateStatus(
                      badgeStatus === STATUS.NOT_ELIGIBLE_WAITLISTED_DELISTED
                        ? 'waitlist'
                        : 'optin',
                    );
                  }}
                >
                  {badgeStatus === STATUS.NOT_ELIGIBLE_WAITLISTED_DELISTED
                    ? 'Join the waitlist'
                    : 'Activate your badge'}
                </Button>
              </Box>
            </ModalFooter>
          )}
        </Modal>
      )}
    </>
  );
};

RazorpayTrustedBadge.propTypes = {
  trustedBadge: PropTypes.shape({
    status: PropTypes.shape({
      status: PropTypes.string,
      original: PropTypes.any,
    }),
    loading: PropTypes.bool,
    updatePending: PropTypes.bool,
    updateError: PropTypes.bool,
    updateAction: PropTypes.string,
  }),
  updateRTBMerchantStatus: PropTypes.func,
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateRTBMerchantStatus,
    },
    dispatch,
  );

export default connect((state) => {
  return {
    trustedBadge: state.trustedBadge,
  };
}, mapDispatchToProps)(RazorpayTrustedBadge);
