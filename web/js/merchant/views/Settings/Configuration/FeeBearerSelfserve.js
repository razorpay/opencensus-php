import { useMemo, useState } from 'react';
import styled from 'styled-components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Badge, Heading, Link, Text } from '@razorpay/blade/components';
import { showNotification } from 'merchant_common/reducers/notifications';
import TextHighlighter from 'common/ui/TextHighlighter';
import { merchantFetch } from 'merchant/utils/ajax';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, openTicketModal } from 'common/utils/rzp-utils';
import LoaderDots from 'common/ui/LoaderDots';
import { updateMerchant as updateMerchantReducer } from 'merchant/reducers/session';
import { FEE_BEARER_TYPES } from 'merchant/constants/feeBearer';

const NotSupportedFooter = styled.div`
  background: #f8f8f9;
  width: 100%;
  font-size: ${({ theme }) => theme.typography.fonts.size[75]}px;
  line-height: ${({ theme }) => theme.typography.lineHeights[50]}px;
  padding: ${({ theme }) => `${theme.spacing[2]}px ${theme.spacing[4]}px`};
  border-radius: 3px;
`;

const CustomerFeeHeading = styled.div`
  margin-top: 10px;
  margin-bottom: 10px;
  @media screen and (min-width: 1024px) {
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
`;

const CustomerFeeTitle = styled.div`
  @media screen and (max-width: 1024px) {
    margin-bottom: 4px;
  }
`;

const Loader = (
  <div className="panel-loader">
    <LoaderDots />
  </div>
);

function FeeBearerSelfserve(props) {
  const currentUser = props.user;
  const [feeBearer, setfeeBearer] = useState(currentUser.merchant.fee_bearer);
  const [showLoader, setshowLoader] = useState(false);

  const handleToggle = async (type) => {
    const { updateMerchant } = props;
    // Track fee bearer toggle
    analyticsTrack({
      objectName: 'Fee bearer',
      actionName: 'Fee bearer toggled',
      screen: 'settings',
      properties: {
        currentFeeBearer: `${currentUser.merchant.fee_bearer}`,
        NewFeeBearer: `${type}`,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    setshowLoader(true);

    try {
      const response = await merchantFetch({
        url: `merchant/toggle_fee_bearer`,
        method: 'POST',
        data: { fee_bearer: type },
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response) {
        setshowLoader(false);
        setfeeBearer(type);
        updateMerchant({ fee_bearer: type });
        props.showNotification({
          type: 'success',
          message: `Configuration updated successfully`,
        });
        analyticsTrack({
          objectName: 'Fee bearer',
          actionName: 'Fee bearer result',
          screen: 'settings',
          properties: {
            currentFeeBearer: `${currentUser.merchant.fee_bearer}`,
            NewFeeBearer: `${type}`,
            result: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    } catch ({ errors }) {
      setshowLoader(false);
      props.showNotification({
        type: 'error',
        message: errors,
      });
      analyticsTrack({
        objectName: 'Fee bearer',
        actionName: 'Fee bearer result',
        screen: 'settings',
        properties: {
          currentFeeBearer: `${currentUser.merchant.fee_bearer}`,
          NewFeeBearer: `${type}`,
          result: 'Failure',
          failureMessage: `${errors}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  const selectCustomerFeeBearer = () => handleToggle(FEE_BEARER_TYPES.CUSTOMER);
  const selectPlatformFeeBearer = () => handleToggle(FEE_BEARER_TYPES.PLATFORM);

  const isPlatformFeeBearer = feeBearer === FEE_BEARER_TYPES.PLATFORM;
  const isCustomerFeeBearer = feeBearer === FEE_BEARER_TYPES.CUSTOMER;

  const defaultRefundSpeedValue = currentUser.merchant.default_refund_speed;
  const isRefundSpeedOptimum = defaultRefundSpeedValue === 'optimum';

  /**
   * Customer fee bearer is not supported in case user has QR/SC or Route enabled
   * Details : https://docs.google.com/document/d/1D1-mK0N3V6ft6BSufN-imGFXSWqPy3j6CyYD5vQFuR0/edit#
   */
  const isQREnabled = currentUser.isQRCodeProductEnabled;
  const isSCEnabled = currentUser.isVirtualAccountsEnabled;
  const isRoutesEnabled = currentUser.isMarketplaceEnabled;
  const productBeingUsed = useMemo(() => {
    // no case where none is enabled
    // in case only one is enabled
    // QR enabled
    if (isQREnabled && !isSCEnabled && !isRoutesEnabled) return 'QR code';
    // SC enabled
    if (!isQREnabled && isSCEnabled && !isRoutesEnabled) return 'Smart Collect';
    // Route enabled
    if (!isQREnabled && !isSCEnabled && isRoutesEnabled) return 'Route';

    // in case more than one is enabled
    return 'QR, Smart Collect and Route';
  }, [isQREnabled, isSCEnabled, isRoutesEnabled]);
  const isCustomerFeeNotSupported = isQREnabled || isSCEnabled || isRoutesEnabled;

  const renderCustomerFeeAction = () => {
    if (showLoader && isPlatformFeeBearer) {
      return Loader;
    }
    if (isRefundSpeedOptimum) {
      return <i className="i i-outline-lock" />;
    }
    if (isCustomerFeeNotSupported) {
      return <Badge variant="neutral">NOT SUPPORTED</Badge>;
    }
    return (
      <input
        type="radio"
        className="radio-pointer"
        checked={isCustomerFeeBearer}
        onChange={selectCustomerFeeBearer}
      />
    );
  };

  const renderPlatformFeeAction = () => {
    if (showLoader && isCustomerFeeBearer) {
      return Loader;
    }
    return (
      <input
        type="radio"
        className="radio-pointer"
        checked={isPlatformFeeBearer}
        onChange={selectPlatformFeeBearer}
      />
    );
  };

  return (
    <div className="panel panel-default fee-bearer-section">
      <div className="panel-heading pl10 pt0">
        <span className="title">
          <TextHighlighter>Fee Bearer</TextHighlighter>{' '}
        </span>

        <p className="subtitle">
          For every payment done on Razorpay, we levy a nominal platform fee. Choose your preferred
          mode of payment from the below options -
        </p>
      </div>

      <div className="panel-body pb6">
        <div className="row">
          <div className="col-sm-6 p5">
            <div
              className={`fee-bearer-panel-col fee-bearer-container${
                isPlatformFeeBearer ? ' active' : ''
              }`}
            >
              <h4>
                <b>You pay the fee</b>
                {renderPlatformFeeAction()}
              </h4>
              <p>Razorpay platform fee would be borne by you. </p>
              <br />
            </div>
          </div>
          <div className="col-sm-6 p5">
            <div
              className={`fee-bearer-panel-col${isCustomerFeeBearer ? ' active' : ''}${
                isRefundSpeedOptimum ? ' disabled' : ''
              }`}
            >
              <div className="fee-bearer-container">
                <CustomerFeeHeading>
                  <CustomerFeeTitle>
                    <Heading size="medium" type="subdued">
                      Customer pays the fee
                    </Heading>
                  </CustomerFeeTitle>
                  {renderCustomerFeeAction()}
                </CustomerFeeHeading>

                {!isCustomerFeeNotSupported && (
                  <Text>You charge a convenience fee to your customer.</Text>
                )}
              </div>
              {isCustomerFeeNotSupported && (
                <NotSupportedFooter>
                  This feature is not supported for merchants using {productBeingUsed}. If you still
                  wish to enable this model, please raise a{' '}
                  <Link
                    size="small"
                    htmlTitle="Raise a support ticket"
                    onClick={openTicketModal}
                    variant="button"
                  >
                    support ticket.
                  </Link>
                </NotSupportedFooter>
              )}
            </div>
            {isRefundSpeedOptimum && (
              <span className="customer-fee-bearer-disabled">
                Locked when instant refunds is active
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
export default connect(
  (state) => ({
    user: state.session.user,
  }),
  (dispatch) =>
    bindActionCreators({ showNotification, updateMerchant: updateMerchantReducer }, dispatch),
)(FeeBearerSelfserve);
