/* eslint-disable no-relative-import-paths/no-relative-import-paths */
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Alert from 'common/ui/Forms/Alert';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { isInteger } from 'common/utils/validators';
import AnnouncementBar from 'merchant/components/AnnouncementBar';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import MaskedContact from 'merchant/components/Mask/Contact';
import MaskedEmail from 'merchant/components/Mask/Email';
import ShowWhen from 'merchant/components/ShowWhen';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import { isOrgFeatureExist } from 'merchant/models/User';
import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';
import { isPlatformTransaction } from 'merchant/views/Transactions/v1/Payments/Utils/platformUtils';
import { OptimizerDetails } from 'merchant/views/Transactions/v1/Payments/components/OptimizerDetails';
import PaymentDownloadSwiftCopy from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/DownloadSwiftCopy';
import PaymentMethod from 'merchant/views/Transactions/v1/Payments/components/PaymentMethod';
import PaymentProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentProvider';
import PaymentRefund from 'merchant/views/Transactions/v1/Payments/components/PaymentRefund';
import PaymentTransfers from 'merchant/views/Transactions/v1/Payments/components/PaymentTransfers';
import {
  REFUND_STATUSES,
  FETCH_EZETAP_KEY_NAME,
  ERRORCODETOCHECK,
} from 'merchant/views/Transactions/v1/Payments/constants';
import track from 'merchant/views/Transactions/v1/Payments/track';
import {
  isBounceMemoEnabled,
  isTransactionsV2Enabled,
} from 'merchant/views/Transactions/v2/common/utils';

import PaymentDisputes from './PaymentDisputes';
import PaymentPageDetails from './PaymentPageDetails';
import PaymentReceipt from './PaymentReceipt';
import PaymentSplitInItems from './PaymentSplitInItems';
import SettlementOverview from './SettlementOverview';
import './Payments.styl';

import { DownloadIcon } from '@razorpay/blade/components';
import { openModal } from 'merchant_common/reducers/modals';
import BounceMemoPopup from '../BounceMemoPopup';
import { PaymentFeeBreakdown } from './PaymentFee';
const INIT_POINT = 'payment-details';

function PaymentDetails(props) {
  const splitz = useSplitzService();
  const {
    payment,
    card,
    bankTransfer, //virtual account details
    upiTransfer, //virtual account details
    refunds,
    transfers,
    isLoading,
    openRefundModal,
    collectEzetapKeys,
    statusMsg = {},
    onRefundDetailsToggleClick = () => {},
    onUpdateReferenceId = () => {},
    isRoleAllowedEdit,
    viewSettlementOverview,
    user,
    org,
    location,
    terminalProviders,
    goToLink,
    customSettlementLoading,
    adminAsMerchant,
    showCustomSettlDetails,
    bankSettleStatus,
    fetchEzetapKeys,
  } = props;

  const isFromHomePage = location?.state?.fromHomePage;
  const paymentId = payment?.id;
  const bankTransferDetails = bankTransfer?.details;
  const bankReference = bankTransferDetails?.bank_reference;
  const paymentMethodtoCheck = ['nach', 'emandate'];
  const isAccountClosed = bankTransferDetails?.virtual_account?.status === 'closed';
  const bankReferenceLoading = bankTransfer?.loading;
  const qrPaymentDescription = payment?.description === 'QRv2 Payment';
  const hideActions =
    payment.method === 'bank_transfer' && (isAccountClosed || bankReferenceLoading);
  const scroller = useRef();
  const [scrolledToBottom, setScrolledToBottom] = useState(false);
  const [isUPIVisible, setUPIVisible] = useState(false);
  const { isConfigTagEnabled } = useI18Service();

  const params = new Proxy(new URLSearchParams(window.location?.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });

  const initiatePage = params?.init_page;
  const screen = initiatePage?.split('.')[0] || 'Payment Details';
  const page = initiatePage?.split('.')[1];

  const showPlatformFee = isPlatformTransaction(transfers);

  const getPaymentReferenceNumber = (method, acquirer_data) => {
    switch (method) {
      case 'netbanking':
        return acquirer_data?.bank_transaction_id;
      case 'wallet':
        return acquirer_data?.transaction_id;
      default:
        return acquirer_data?.rrn;
    }
  };

  const paymentByCardOffline = payment.method === 'card' && payment.receiver_type === 'pos';
  const { refetch: refetchEzetapAppKey, data: ezetapData } = useQuery({
    queryKey: [FETCH_EZETAP_KEY_NAME],
    queryFn: async () => {
      const dataPromise = await fetchEzetapKeys();
      return dataPromise?.data;
    },
    enabled: false,
    refetchOnWindowFocus: false,
    staleTime: Infinity,
  });

  useEffect(() => {
    if (paymentByCardOffline) {
      refetchEzetapAppKey();
    }
  }, [paymentByCardOffline]);

  const getProductType = useCallback(() => {
    const isQrCode = () => {
      if (paymentId && qrPaymentDescription) {
        return true;
      }
      return false;
    };

    if (isQrCode()) {
      return 'QR payments';
    }
    return 'payments';
  }, [paymentId, qrPaymentDescription]);

  const handleScroll = useCallback(() => {
    const ele = scroller.current;
    if (ele.scrollTop >= ele.scrollHeight - ele.clientHeight - 100) {
      setScrolledToBottom(true);
    } else if (ele.scrollTop <= 10 && scrolledToBottom) {
      setScrolledToBottom(false);
    }
  }, [scrolledToBottom]);

  useEffect(() => {
    if (user.isSingleReconEnabled && user.isOptimizerEnabled) {
      scroller.current.addEventListener('scroll', handleScroll);
    }

    if (payment.id) {
      track.init({
        ...payment.analyticsPayload(),
        ...getCommonAnalyticsProperties(window.rzp_user),
        location: 'payments',
      });
      const screen = isFromHomePage ? 'home page' : 'transactions';
      track.paymentDetails(`${getProductType()} details`, 'fetched', screen);
      track.paymentDetailsSidebar('payment details sidebar', 'rendered', screen);
    }

    return () => {
      const productType = getProductType();
      track.paymentDetailsUnmount(`${productType} detail close`, productType);
    };
  }, [isFromHomePage, payment, user, handleScroll, getProductType]);

  const trackSettlementOverView = () => {
    const productType = getProductType();
    track.settlementOverView(`${productType} settlement viewed`, productType);
  };

  const isFeatureEnabled = useCallback(() => {
    return org?.features?.indexOf('show_late_auth_attributes') > -1;
  }, [org?.features]);

  const handleSettlementGuideClick = () => {
    track.handleSettlementGuide(`${getProductType()} detail guide`, getProductType());
  };

  const trackContactSupport = () => {
    track.trackContactSupport(`${getProductType()} detail support`, getProductType());
  };

  const onCreateTransfer = () => {
    track.onCreateTransfer(`${getProductType()} detail transfer`, getProductType());
    goToLink('transfers/new');
  };

  const trackKnowMore = () => {
    track.knowMore(`${getProductType()} detail know more`, getProductType());
  };

  const trackSameDaySettlement = () => {
    track.sameDaySettlement(`${getProductType()} detail settlement enabled`, getProductType());
  };

  const trackSettlementClose = () => {
    track.settlementClose(`${getProductType()} detail popup closed`, getProductType());
  };

  const trackSelfServe = () => {
    const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
    const selfServeInitiateData = {
      selfServeAction: 'Order Details Fetched',
      page,
      screen,
      version,
      props: {
        initiatePoint: INIT_POINT,
      },
    };
    if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;

    selfServeTrackInitiate(selfServeInitiateData);
  };

  const triggerRefund = () => {
    const paymentByCardOffline = payment.receiver_type === 'pos';
    if (paymentByCardOffline && !ezetapData?.appKey) {
      collectEzetapKeys();
    } else {
      openRefundModal();
    }
  };

  const isCardOfflineTransaction = payment.receiver_type === 'pos' && payment.method === 'card';
  const disableRefund =
    payment?.status !== 'refunded' &&
    payment?.notes?.refund_status === REFUND_STATUSES.PROCESSING &&
    isCardOfflineTransaction;
  const isStorefront = location.hash === '#storefront';

  const isOptimizerView =
    user?.isSingleReconEnabled && user?.isOptimizerEnabled && !!payment?.optimizer_provider;

  const blockTransfer =
    user?.isOptimizerEnabled && !!payment?.optimizer_provider && payment.settled_by !== 'Razorpay';

  //BounceMemosplitz experiment - only for enabled merchant bounce memo will be released based splitz experiment
  const isBounceModalMemoEnabled = isBounceMemoEnabled(splitz);

  return (
    <div
      className="content-wrapper content-sm txn-details"
      data-testid="payment-details"
      ref={scroller}
    >
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div
          className={`panel panel-default SliderPanel ${
            user.isSingleReconEnabled && user.isOptimizerEnabled ? 'opt-remove-margin' : ''
          }`}
        >
          <div className="panel-heading">
            {props.onClose && (
              <button type="button" className="close close-secondary" onClick={props.onClose}>
                <i className="i i-close" />
              </button>
            )}
            Payment Id: <b>{payment.id}</b>
          </div>

          <div className="SliderPanel__Body">
            <div
              className={`panel-body ${
                user.isSingleReconEnabled && user.isOptimizerEnabled ? 'optimizer-panel-body' : ''
              }`}
            >
              {payment.status === 'authorized' &&
                isRoleAllowedEdit &&
                payment.method !== 'intl_bank_transfer' &&
                !hideActions && (
                  <div className="payments-manual-actions">
                    <button
                      onClick={() => {
                        track.capturePayment(
                          'capture payment',
                          isFromHomePage ? 'home page' : 'transactions',
                        );
                        track.onActionSideBar(
                          'action items on sidebar',
                          isFromHomePage ? 'home page' : 'transactions',
                        );
                        props.confirmCapture(payment);
                      }}
                      type="button"
                      className="btn btn-primary"
                      disabled={isCardOfflineTransaction}
                    >
                      Capture Payment
                    </button>
                    <ShowWhen
                      additionalCondition={(user, session) =>
                        !(session?.org?.features?.indexOf('block_payment_refund') > -1) &&
                        !isConfigTagEnabled('refunds.refund')
                      }
                    >
                      <button
                        type="button"
                        onClick={triggerRefund}
                        disabled={disableRefund}
                        className="btn btn-primary btn-refund-payment"
                      >
                        Refund Payment
                      </button>
                    </ShowWhen>
                  </div>
                )}
              <Alert type={statusMsg.type} message={statusMsg.message} />
              {hideActions && !bankReferenceLoading && payment.status === 'authorized' && (
                <Alert
                  type="error"
                  message="This payment will be refunded within 72 hours"
                  showDismiss={false}
                />
              )}
              <div
                className={`list-group pair-row-container ${
                  user.isSingleReconEnabled && user.isOptimizerEnabled ? 'opt-remove-margin' : ''
                }`}
              >
                <PaymentPageDetails payment={payment} />

                <EntityDetailRow label="Amount">
                  <b>
                    <Amount value={payment.amount} currency={payment.currency} />
                  </b>
                </EntityDetailRow>

                <EntityDetailRow label="Status">
                  <PaymentStatusLabel status={payment.status} />
                </EntityDetailRow>

                {payment.error_code && (
                  <EntityDetailRow label="Error">
                    <Definition>
                      <span>{payment.error_code}</span>
                      {payment.error_description && <span>{payment.error_description}</span>}
                    </Definition>
                  </EntityDetailRow>
                )}

                {payment.error_source && (
                  <EntityDetailRow label="Error Source">
                    <Definition>
                      <span>{payment.error_source}</span>
                    </Definition>
                  </EntityDetailRow>
                )}

                {payment.error_step && (
                  <EntityDetailRow label="Error Step">
                    <Definition>
                      <span>{payment.error_step}</span>
                    </Definition>
                  </EntityDetailRow>
                )}

                {payment.error_reason && (
                  <EntityDetailRow label="Error Reason">
                    <Definition>
                      <span>{payment.error_reason}</span>
                    </Definition>
                  </EntityDetailRow>
                )}

                <ShowWhen
                  apiFeatureEnabled="Marketplace"
                  additionalCondition={() =>
                    !isConfigTagEnabled('payment_transfer.transfers') && !showPlatformFee
                  }
                >
                  <EntityDetailRow label="Transfer">
                    <PaymentTransfers
                      payment={payment}
                      transfers={transfers}
                      blockTransfer={blockTransfer}
                      onCreateTransfer={onCreateTransfer}
                    />
                  </EntityDetailRow>
                </ShowWhen>

                <ShowWhen additionalCondition={() => !isConfigTagEnabled('refunds.refund')}>
                  {payment.method !== 'cod' && (
                    <EntityDetailRow label="Refunds">
                      <PaymentRefund
                        payment={payment}
                        refunds={refunds}
                        isOptimizerView={isOptimizerView}
                        openRefundModal={openRefundModal}
                        onToggleClick={onRefundDetailsToggleClick}
                        collectEzetapKeys={collectEzetapKeys}
                        fetchEzetapKeys={fetchEzetapKeys}
                      />
                    </EntityDetailRow>
                  )}
                </ShowWhen>

                <EntityDetailRow label="Payment Method">
                  <PaymentMethod
                    payment={payment}
                    card={card}
                    bankTransfer={bankTransfer}
                    upiTransfer={upiTransfer}
                    onUPIClick={() => {
                      setUPIVisible(!isUPIVisible);
                      if (!isUPIVisible) {
                        const productType = getProductType();
                        track.methodViewed(`${productType} detail method viewed`, productType);
                      }
                    }}
                  />
                </EntityDetailRow>

                {(bankReference || bankReferenceLoading) && (
                  <EntityDetailRow label="Bank Reference">
                    <Definition>{bankReference ? bankReference : <PlaceholderLoader />}</Definition>
                  </EntityDetailRow>
                )}

                {payment.provider && (
                  <EntityDetailRow label="Provider">
                    <PaymentProvider payment={payment} />
                  </EntityDetailRow>
                )}

                {payment.gateway_provider && (
                  <EntityDetailRow label="Gateway">{payment.gateway_provider}</EntityDetailRow>
                )}

                <EntityDetailRow label="Created At">
                  <Time value={payment.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                </EntityDetailRow>

                <ShowWhen additionalCondition={isFeatureEnabled}>
                  <EntityDetailRow label="Late Authorized">
                    <Definition>
                      <span>{payment.late_authorized ? 'Yes' : 'No'}</span>
                    </Definition>
                  </EntityDetailRow>

                  <EntityDetailRow label="Authorised At">
                    <Time value={payment.authorized_at} format="DD MMM YYYY, hh:mm:ss a" />
                  </EntityDetailRow>

                  <EntityDetailRow label="Auto Captured">
                    <Definition>
                      <span>{payment.auto_captured ? 'Yes' : 'No'}</span>
                    </Definition>
                  </EntityDetailRow>

                  <EntityDetailRow label="Captured At">
                    <Time value={payment.captured_at} format="DD MMM YYYY, hh:mm:ss a" />
                  </EntityDetailRow>
                </ShowWhen>

                <ShowWhen
                  additionalCondition={() =>
                    user.isUxRevampPhase2Enabled &&
                    payment.transaction &&
                    (!user.isSingleReconEnabled ||
                      !user.isOptimizerEnabled ||
                      payment.optimizer_provider === 'Razorpay')
                  }
                >
                  <EntityDetailRow label="Settlement Details">
                    <div onClick={trackSettlementOverView}>
                      <SettlementInfo
                        data={payment}
                        entityType="payment"
                        showTimeline
                        handleSettlementGuideClick={handleSettlementGuideClick}
                        trackContactSupport={trackContactSupport}
                        trackKnowMore={trackKnowMore}
                        trackSameDaySettlement={trackSameDaySettlement}
                        trackSettlementClose={trackSettlementClose}
                        page="Payment Detail"
                        customSettlementLoading={customSettlementLoading}
                        adminAsMerchant={adminAsMerchant}
                        showCustomSettlDetails={showCustomSettlDetails}
                        bankSettleStatus={bankSettleStatus}
                      />
                    </div>
                  </EntityDetailRow>
                </ShowWhen>
                <EntityDetailRow label="Description">{payment.description}</EntityDetailRow>

                <ShowWhen additionalCondition={() => !isConfigTagEnabled('disputes.disputes')}>
                  <EntityDetailRow label="Disputes">
                    {payment.disputes && payment.disputes.count ? (
                      <PaymentDisputes
                        disputes={payment.disputes.items}
                        onDisputeClick={props.goToLink}
                      />
                    ) : (
                      '--'
                    )}
                  </EntityDetailRow>
                </ShowWhen>

                <EntityDetailRow label="Customer">
                  <Definition placeholder="No customer linked">
                    <MaskedEmail email={payment.email} />
                    <MaskedContact contact={payment.contact} />
                  </Definition>
                </EntityDetailRow>

                <ShowWhen additionalCondition={() => user.isPayerNameEnabled}>
                  <EntityDetailRow label="Payer Name">{payment.upi?.payer_name}</EntityDetailRow>
                </ShowWhen>

                <EntityDetailRow label="Total Fee">
                  <PaymentFeeBreakdown transfers={transfers} payment={payment} />
                </EntityDetailRow>

                {isInteger(payment?.customer_fee) && isInteger(payment?.customer_fee_gst) && (
                  <EntityDetailRow label="Total Convenience Fee">
                    <Definition>
                      <Amount value={payment.customer_fee + payment.customer_fee_gst} />
                      <span>
                        Convenience Fee -{' '}
                        <Amount value={payment.customer_fee} currency={payment.currency} />
                      </span>
                      <span>
                        GST -{' '}
                        <Amount value={payment.customer_fee_gst} currency={payment.currency} />
                      </span>
                    </Definition>
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Fee Bearer">
                  <Definition>
                    {payment.fee_bearer === 'platform'
                      ? 'You are the fee bearer for this payment'
                      : 'The customer has paid the fees for this payment'}
                  </Definition>
                </EntityDetailRow>

                <ShowWhen additionalCondition={() => isOrgFeatureExist('vas_merchant')}>
                  <EntityDetailRow label="Payment Reference Number">
                    {getPaymentReferenceNumber(payment.method, payment.acquirer_data)}
                  </EntityDetailRow>
                </ShowWhen>

                <ShowWhen additionalCondition={() => user.isProjectNitroEnabled}>
                  <AnnouncementBar
                    fromWhere="transactions"
                    url="https://lp.razorpay.com/razorpayxca-pymnts2"
                  />
                </ShowWhen>

                <EntityDetailRow label="Order ID">
                  {payment.order_id ? (
                    <Link
                      to={`/orders/${payment.order_id}?init_point=${INIT_POINT}&init_page=${initiatePage}`}
                      onClick={() => {
                        trackSelfServe();
                      }}
                    >
                      <code>{payment.order_id}</code>
                    </Link>
                  ) : (
                    '--'
                  )}
                </EntityDetailRow>

                {payment.invoice_id && (
                  <EntityDetailRow label="Invoice ID">
                    <Link to={`/invoices/${payment.invoice_id}`}>
                      <code>{payment.invoice_id}</code>
                    </Link>
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Notes">
                  {Object.keys(payment.notes).length
                    ? Object.keys(payment.notes).map((key, index) =>
                        isStorefront && key === 'line_items' ? (
                          ''
                        ) : (
                          <Definition key={index} customClass="notes">
                            {key}
                            {String(payment.notes[key] || '--')}
                          </Definition>
                        ),
                      )
                    : '--'}
                </EntityDetailRow>
                <ShowWhen
                  className="if-condition"
                  additionalCondition={() =>
                    ERRORCODETOCHECK.includes(payment.error_reason) &&
                    isBounceModalMemoEnabled &&
                    paymentMethodtoCheck.includes(payment.method)
                  }
                >
                  <EntityDetailRow label="Failed Transaction Memo">
                    <div>
                      <button
                        data-testid="failed-trasaction-memo"
                        onClick={() => {
                          openModal({
                            size: 'med-large',
                            component: (
                              <BounceMemoPopup paymentPage="singlePage" paymentID={paymentId} />
                            ),
                          });
                        }}
                      >
                        <DownloadIcon /> Download
                      </button>
                    </div>
                  </EntityDetailRow>
                </ShowWhen>
                {user.isPaymentPageReceiptsEnabled && (
                  <PaymentReceipt payment={payment} onUpdateReferenceId={onUpdateReferenceId} />
                )}

                <PaymentSplitInItems payment={payment} />

                {!user.isUxRevampPhase2Enabled &&
                payment.transaction &&
                (!user.isSingleReconEnabled ||
                  !user.isOptimizerEnabled ||
                  payment.optimizer_provider === 'Razorpay') ? (
                  <EntityDetailRow label="Settlement Details">
                    {payment.transaction.settlement ? (
                      <ContentToggler onToggleClick={viewSettlementOverview}>
                        <span>
                          Settled on{' '}
                          <Time value={payment.transaction.settled_at} format="DD MMM YYYY" />
                        </span>
                        <SettlementOverview payment={payment} user={user} />
                      </ContentToggler>
                    ) : payment.transaction.settled_at ? (
                      <span className="link">
                        To be settled on{' '}
                        <Time value={payment.transaction.settled_at} format="DD MMM YYYY" />
                      </span>
                    ) : (
                      '--'
                    )}
                  </EntityDetailRow>
                ) : null}
              </div>
              {isOptimizerView && (
                <OptimizerDetails
                  payment={payment}
                  terminalProviders={terminalProviders}
                  scrolledToBottom={scrolledToBottom}
                  page="Payment Detail"
                />
              )}

              <ShowWhen additionalCondition={() => user.isLRSEducationFlow}>
                <EntityDetailRow label="Documents">
                  <PaymentDownloadSwiftCopy paymentId={payment.id} />
                </EntityDetailRow>
              </ShowWhen>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default withRouter(PaymentDetails);
