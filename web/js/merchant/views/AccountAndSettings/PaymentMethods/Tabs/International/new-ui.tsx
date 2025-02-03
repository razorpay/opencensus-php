import React, { lazy, Suspense, useEffect } from 'react';
import { Alert, Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { User, InstrumentListItem } from 'common/typings';
import { trackPageView } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/analytics/track';
import Firc from 'merchant/views/Settings/Configuration/components/FircAnnouncements/Firc';
import { trackLinkClick } from 'merchantLA/containers/TestModeBanner/ga';

import { INSTRUMENT_METHODS } from './constants';

const MethodApp = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlMethodApp' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/App'
    ),
);

const MethodCard = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlMethodCard' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/Card'
    ),
);

const MethodIntlBankTransfer = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlMethodIntlBankTransfer' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer'
    ),
);

const MethodWallet = lazy(
  () =>
    import(
      /* webpackChunkName: 'IntlMethodWallet' */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/Wallet'
    ),
);

export type Props = {
  user: User & {
    live: boolean;
  };
  instrument?: InstrumentListItem;
  isOrgCurlec: boolean;
};

const International = ({ user, instrument, isOrgCurlec }: Props): JSX.Element => {
  const isActivatedUser = user.activation_status === 'activated';
  const isIntlMethodsHidden = user.isInternationalMethodsHidden;
  const isIntlMerchant = user.international;
  const isLiveMerchant = user.live;

  useEffect(() => {
    trackPageView({
      isActivatedUser,
      isIntlMethodsHidden,
      isIntlMerchant: !!isIntlMerchant,
      isLiveMerchant,
    });
  }, [isActivatedUser, isIntlMerchant, isIntlMethodsHidden, isLiveMerchant]);

  return (
    <>
      {isIntlMerchant && !isOrgCurlec && <Firc />}
      <Box id="settings-payment-methods" display="flex" flexDirection="column" gap="spacing.7">
        {!isLiveMerchant ? (
          <Alert
            description={
              <Text color="surface.text.gray.muted">
                Request for Payment methods is unavailable as your account is not enabled to accept
                transactions.
              </Text>
            }
            isDismissible={false}
            testID="non-live-banner"
            isFullWidth
            color="notice"
          />
        ) : null}

        {!isActivatedUser ? (
          <Alert
            description={
              <Text color="surface.text.gray.muted">
                KYC verification is mandatory to request for new payment methods. Please complete
                your
                <Link to="/activation" onClick={() => trackLinkClick('Go To - Activation Form')}>
                  &nbsp; activation form
                </Link>
                , if not done already.
              </Text>
            }
            isDismissible={false}
            isFullWidth
            color="notice"
          />
        ) : null}

        <Suspense fallback={null}>
          {instrument?.leafList
            .map((item) => {
              if (item.slug === INSTRUMENT_METHODS.WALLET) {
                return <MethodWallet key={item.slug} item={item} />;
              }

              if (item.slug === INSTRUMENT_METHODS.INTL_BANK_TRANSFER) {
                if (!isIntlMethodsHidden) {
                  return <MethodIntlBankTransfer key={item.slug} item={item} />;
                }
                return null;
              }

              if (item.slug === INSTRUMENT_METHODS.APPS) {
                if (isIntlMerchant && !isIntlMethodsHidden) {
                  return <MethodApp key={item.slug} item={item} />;
                }

                return null;
              }

              if (item.slug === INSTRUMENT_METHODS.CARD) {
                return <MethodCard key={item.slug} item={item} />;
              }

              return null;
            })
            .filter(Boolean)}
        </Suspense>
      </Box>
    </>
  );
};

const mapStateToProps = ({ session, instrumentRequests }) => {
  let internationalInstrument = instrumentRequests.pg.find((item) => item.slug === 'international');
  const { user } = session;
  const { isOrgCurlec } = user;

  if (isOrgCurlec && internationalInstrument) {
    // for curlec merchant only Paypal is allowed
    internationalInstrument = {
      ...internationalInstrument,
      leafList: internationalInstrument.leafList.filter(
        ({ slug, list }) =>
          slug === 'wallet' && list.some((leafListItem) => leafListItem.slug === 'paypal'),
      ),
    };
  }

  return {
    user,
    isOrgCurlec,
    instrument: internationalInstrument,
  };
};

export default connect(mapStateToProps)(International);
