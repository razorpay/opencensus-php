import React, { useState, useEffect } from 'react';
import { Alert, Badge, InfoIcon } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { capitalize } from 'common/utils/rzp-utils';
import { LeafListItemHeader } from 'merchant/views/AccountAndSettings/PaymentMethods/components/LeafListItem';
import {
  getClassName,
  getStatusMessage,
  getBadgeVariant,
} from 'merchant/views/Settings/Configuration/InternationalPayments';
import PaypalOnboardingButton from 'merchant/views/Settings/Configuration/PaypalOnboarding';
import { GREYED } from 'merchant/views/Settings/PaymentMethods/constants';

import { getIcon } from './InstrumentIcons';

const Paypal = ({ user, instrument, terminals, isIERevamp }) => {
  const [isImageLoaded, setIsImageLoaded] = useState(false);
  const [status, setStatus] = useState(null);
  const disabled = status === GREYED;
  const showStatus = [
    'created',
    'activated',
    'permission_missing',
    'requested',
    'pending',
  ].includes(status);
  const statusText = ['created', 'permission_missing'].includes(status) ? 'pending' : status;

  useEffect(() => {
    if (terminals.length) {
      setStatus(terminals[0].terminal.status);
    }
  }, [terminals]);

  const instrumentDescription = user?.isOrgCurlec
    ? instrument.description.replace('Razorpay', 'Curlec')
    : instrument.description;

  return isIERevamp ? (
    <>
      <LeafListItemHeader
        name={instrument.name}
        description={instrumentDescription}
        actionComponent={
          showStatus ? (
            <Badge
              emphasis="intense"
              size="large"
              variant={getBadgeVariant(status)}
              icon={(props) => (
                <>
                  <InfoIcon {...props} />
                  {status === 'activated' && (
                    <span>
                      <Popover theme="dark" align="bottom">
                        <PopoverBody>
                          <div>{getStatusMessage(status)}</div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  )}
                </>
              )}
            >
              {capitalize(statusText)}
            </Badge>
          ) : undefined
        }
      />
      <PaypalOnboardingButton
        showLogo={false}
        disabled={disabled}
        disabledText={instrument.fade_comment}
        isInternationalPayment={true}
        setStatus={(st) => setStatus(st)}
        isIERevamp={isIERevamp}
      />
      {!user?.isOrgCurlec && !disabled && (
        <div className="mt20">
          <Alert
            description={
              <>
                You can accept Payments in <strong>International Currencies only</strong> using
                PayPal. They CANNOT be collected in INR.
              </>
            }
            color="neutral"
            isFullWidth
            isDismissible={false}
            emphasis="subtle"
          />
        </div>
      )}
    </>
  ) : (
    <li className="paypal-leaf-item">
      <div className={`status-bar ${showStatus ? 'bar-wrap' : 'bar-nowrap'}`}>
        <div className="instrument">
          {instrument.icon && (
            <div className="icon">
              <img
                src={getIcon(instrument.icon)}
                alt={instrument.name}
                width="30px"
                height="30px"
                onLoad={() => setIsImageLoaded(true)}
                style={{
                  display: `${isImageLoaded ? 'initial' : 'none'}`,
                }}
              />
              {!isImageLoaded && (
                <div className="flex">
                  <p className="PlaceholderLoader" />
                </div>
              )}
            </div>
          )}
          <div className="detail">
            <strong>{instrument.name}</strong>
          </div>
          {showStatus ? (
            <a className={`status-pill status-pill-${getClassName(status)}`}>
              <span className="status-text">{statusText}</span>{' '}
              {status === 'activated' && (
                <span>
                  <Popover theme="dark" align="bottom">
                    <PopoverBody>
                      <div>{getStatusMessage(status)}</div>
                    </PopoverBody>
                  </Popover>
                </span>
              )}
            </a>
          ) : null}
        </div>

        <PaypalOnboardingButton
          showLogo={false}
          disabled={disabled}
          disabledText={instrument.fade_comment}
          isInternationalPayment={true}
          setStatus={(st) => setStatus(st)}
        />
      </div>
      {terminals.length === 0 && instrument.description && (
        <p className="desc">{instrumentDescription}</p>
      )}
      {!user?.isOrgCurlec && !disabled && (
        <div className="paypal-info mt20">
          <p>
            You can accept Payments in <strong>International Currencies only</strong> using PayPal
          </p>
          <p className="mt10">You CANNOT accept Payments in INR</p>
        </div>
      )}
    </li>
  );
};

const mapStateToProps = (state) => {
  return {
    terminals: state.config.paypal_terminals,
  };
};

export default connect(mapStateToProps, null)(Paypal);
