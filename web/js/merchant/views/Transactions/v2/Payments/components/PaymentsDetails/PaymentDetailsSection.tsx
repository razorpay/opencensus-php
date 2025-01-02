import React, { lazy, useEffect, useState } from 'react';
import {
  Box,
  Button,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronUpIcon,
  Divider,
  DownloadIcon,
  IconButton,
  Link,
  MailIcon,
  PhoneIcon,
  Text,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { useMobile } from 'common/hooks/useMobile';
import { useI18Service } from 'common/i18';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';
import { User } from 'common/typings';
import copyToClipboard from 'common/utils/copyToClipboard';
import { noop } from 'common/utils/rzp-utils';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';
import {
  fetchBankTransfersFn,
  fetchEncodedPaymentReceipt,
  fetchTransfersFn,
} from 'merchant/views/Transactions/model';
import {
  isPaymentV2ParityFeatureEnabled,
  isTransactionsV2Enabled,
} from 'merchant/views/Transactions/v2/common/utils';
import { isOmniChannelMerchant as _isOmniChannelMerchant } from 'merchant/utils/omniUtils';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import PaymentDownloadSwiftCopy from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/DownloadSwiftCopy';

import getNotes from './Notes';
import PaymentMethod from './PaymentMethod';
import PaymentPageDetails from './PaymentPageDetails';
import PaymentPagePaymentReceipt from './PaymentPagePaymentReceipt';
import PaymentSplitItems from './PaymentSplitItems';
import PaymentTransfers from './PaymentTransfers';
import Tooltip from './Tooltip';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from './styled';
import { IPaymentDetails, ApplicationDetails, TooltipKeys } from './types';
import {
  imageDownload,
  isBounceMemoSingleTransactionEnabled,
  isChargeSlipForPosEnabled,
  isPosTransaction,
  onCopy,
} from './utils';

import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { PaymentFeeBreakdown } from 'merchant/views/Transactions/v1/Payments/components/PaymentFee';
import { fetchTransfers, fetchBankTransfer } from 'merchant/reducers/payments/details';
import { isExperimentEnabled } from 'common/splitz/utils';
import PaymentOptimizerDetails from './PaymentOptimizerDetails';
import { getPaymentReferenceNumber } from 'merchant/views/Transactions/v1/utils';
import { isOrgFeatureExist } from 'merchant/models/User';
import PaymentProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentProvider';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import {
  ERROR_CODE_FOR_BOUNCE_MEMO,
  FALLBACK_ERROR_FOR_BOUNCE_MEMO,
  METHODS_FOR_BOUNCE_MEMO,
} from './constants';
import pdfCreation from 'merchant/views/Transactions/v1/Payments/components/PdfCreation';
import { fetchBouncememo } from 'merchant/views/Transactions/v1/Payments/BounceMemo.types';
import { useMutation } from '@tanstack/react-query';

const PaymentReceipt = lazy(
  () =>
    import(
      /* webpackChunkName: 'PaymentReceipt' */ 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentReceipt'
    ),
);

interface IPaymentDetailsSectionProps extends RouteComponentProps<{ id: string }> {
  paymentDetails: IPaymentDetails;
  applicationDetails: ApplicationDetails | null;
  user: User;
  splitz: SpiltzContextState;
  showNotification: (payload: { type: 'error' | 'success'; message: string }) => void;
  orgName: string;
  orgFeatures: string[];
  fetchTransfers: (paymentModel: any) => void;
  transfers: any;
  fetchBankTransfer: (paymentModel: any) => void;
  bankTransfer: any;
  terminalProviders: any;
  shouldShowOptimizerDetails: boolean;
}

interface DetailRowProps {
  label: string;
  value: React.ReactNode;
  tooltipType?: TooltipKeys;
  copyable?: boolean;
  onCopyAction?: () => void;
}

export const DetailRow: React.FC<DetailRowProps> = ({
  label,
  value,
  tooltipType = null,
  copyable = false,
  onCopyAction,
}) => (
  <RowWrapper>
    <Text variant="body" size="medium" weight="regular" color="surface.text.gray.subtle">
      {label} {tooltipType && <Tooltip size="small" type={tooltipType} />}
    </Text>
    {copyable && value !== '--' ? (
      <CopyWrapper onClick={onCopyAction || noop}>
        <Text variant="body" size="medium" weight="semibold" color="surface.text.gray.normal">
          {value}
        </Text>
      </CopyWrapper>
    ) : (
      <Text variant="body" size="medium" weight="regular" color="surface.text.gray.normal">
        {value}
      </Text>
    )}
  </RowWrapper>
);

const PaymentDetailsSection: React.FC<IPaymentDetailsSectionProps> = ({
  paymentDetails,
  applicationDetails,
  history,
  location,
  match: {
    params: { id: transactionIDActual },
  },
  user,
  splitz,
  showNotification,
  orgName,
  orgFeatures,
  fetchTransfers,
  transfers,
  fetchBankTransfer,
  bankTransfer,
  terminalProviders,
  shouldShowOptimizerDetails,
}) => {
  const [isOpen, setIsOpen] = useState<boolean>(true);
  const isStorefront = location.hash === '#storefront';

  const toggleAccordion = () => setIsOpen((prevState) => !prevState);
  const isMobile = useMobile();

  useEffect(() => {
    // if device type changes to desktop, ensure isOpen is reset to true
    if (!isMobile) {
      setIsOpen(true);
    }
  }, [isMobile]);

  const {
    id,
    acquirer_data = {},
    notes,
    order_id,
    card,
    contact,
    email,
    disputes,
    fee_bearer,
    description,
    method,
    bank,
    vpa,
    wallet,
    invoice_id,
    gateway_terminal_id,
    gateway_merchant_id,
    device_id,
    source_channel,
    payee_vpa,
    device_detail,
    upi,
    status,
    error_code,
    error_source,
    error_step,
    error_reason,
  } = paymentDetails;
  const { isConfigTagEnabled } = useI18Service();

  useEffect(() => {
    if (['created', 'authorized', 'failed'].indexOf(paymentDetails.status) < 0) {
      fetchTransfers({ fetchTransfers: fetchTransfersFn(id) });
    }
  }, [paymentDetails.status]);

  useEffect(() => {
    if (method === 'bank_transfer') {
      fetchBankTransfer({ fetchBankTransfer: fetchBankTransfersFn(id) });
    }
  }, [method, id]);

  const { abExperiments } = splitz || {
    abExperiments: {
      enable_trxn_v2_parity_features: undefined,
      enable_trxn_v2_fee_breakup: undefined,
    },
  };

  const isChargeSlipExperimentEnabled = isChargeSlipForPosEnabled(splitz);
  const isTxnV2ParityFeaturesEnabled = isPaymentV2ParityFeatureEnabled(splitz, user);
  const isTxnFeeBreakupEnabled =
    isTransactionsV2Enabled(splitz, user) &&
    isExperimentEnabled(abExperiments?.enable_trxn_v2_fee_breakup);

  const isLateAuthAttributeEnabled = orgFeatures?.includes('show_late_auth_attributes');

  const isOmniChannelMerchant = isPosTransaction(source_channel) && _isOmniChannelMerchant(user);

  const bankReferenceNumber = bankTransfer?.details?.bank_reference;

  const isBounceMemoDownloadEnabled = isBounceMemoSingleTransactionEnabled(splitz);

  const onDownloadClick = async (e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      const response = await fetchEncodedPaymentReceipt(id);
      if (response?.receipt_encoded_image) {
        const base64EncodedImage = `data:image/png;base64,${response.receipt_encoded_image}`;
        imageDownload({
          base64EncodedImage,
          imageName: id,
        });
      } else {
        showNotification({ type: 'error', message: 'No Charge Slip found.' });
      }
    } catch (err) {
      showNotification({ type: 'error', message: 'No Charge Slip found.' });
    }
  };

  const openChargeSlip = () => {
    openModal({
      size: 'medium',
      isNew: true,
      component: (
        <SuspenseWithLoader>
          <PaymentReceipt id={id} />
        </SuspenseWithLoader>
      ),
    });
  };

  const posGatewayId = gateway_terminal_id || payee_vpa;
  const posDeviceSerialNumber = device_id || device_detail;

  const isPaymentSplitSectionAllowed = (hashValue: string) => {
    const allowedModules = [
      'paymentpages',
      'paymentbuttons',
      'subscription_buttons',
      'stores',
      'storefront',
    ];
    return allowedModules.includes(hashValue);
  };

  const hash = location.hash;

  const isPaymentReceiptSectionAllowed = () => {
    if (hash) {
      const module = hash.substring(1);
      const allowedModules = [
        'paymentpages',
        'paymentbuttons',
        'subscription_buttons',
        'batchpaymentpages',
      ];

      return allowedModules.indexOf(module) > -1;
    }
    return false;
  };

  const isFailedDownloadMemoAllowed = Boolean(
    // isOpen is responsible for open and close of Details dropdown
    isOpen &&
      isBounceMemoDownloadEnabled &&
      error_reason &&
      ERROR_CODE_FOR_BOUNCE_MEMO.includes(error_reason) &&
      METHODS_FOR_BOUNCE_MEMO.includes(method),
  );

  const fetchBounceMemoParams = {
    paymentID: id,
    merchantId: user.id,
  };
  const { mutate: handleBounceMemoDownload, isLoading: isBounceMemoDownloading } = useMutation({
    mutationFn: (params: typeof fetchBounceMemoParams) => fetchBouncememo(params),
    onSuccess: (response) => {
      if (response?.data?.data) {
        pdfCreation(response.data.data, fetchBounceMemoParams.merchantId, 'singlePage');
      } else if (response?.data?.error?.description) {
        showNotification({
          type: 'error',
          message: response.data.error.description,
        });
      } else {
        showNotification({
          type: 'error',
          message: FALLBACK_ERROR_FOR_BOUNCE_MEMO,
        });
      }
    },
    onError: () => {
      showNotification({
        type: 'error',
        message: FALLBACK_ERROR_FOR_BOUNCE_MEMO,
      });
    },
  });

  return (
    <Box testID="payment-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
        </Text>
        {isMobile && (
          <CollapsibleContainer onClick={toggleAccordion} data-testid="collapsible-container">
            <Text size="medium" weight="semibold" color="surface.text.gray.subtle">
              {!isOpen ? (
                <ChevronDownIcon
                  size="medium"
                  color="feedback.icon.neutral.intense"
                  data-testid="chevron-down"
                />
              ) : (
                <ChevronUpIcon
                  size="medium"
                  color="feedback.icon.neutral.intense"
                  data-testid="chevron-up"
                />
              )}
            </Text>
          </CollapsibleContainer>
        )}
      </SectionHeader>
      {isOpen && (
        <CardWrapper enableBorderBottomRadius={!isFailedDownloadMemoAllowed}>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <DetailRow
                  label="Payment ID"
                  value={id}
                  tooltipType="paymentId"
                  copyable
                  onCopyAction={onCopy('Payment ID', { transactionIDActual, paymentId: id }).bind(
                    null,
                    id,
                  )}
                />
                {status === 'failed' ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow label="Status" value={<PaymentStatusLabel status={status} />} />
                    {error_code ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Error" value={error_code} />
                      </>
                    ) : null}
                    {error_source ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Error Source" value={error_source} />
                      </>
                    ) : null}
                    {error_step ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Error Step" value={error_step} />
                      </>
                    ) : null}
                    {error_reason ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Error Reason" value={error_reason} />
                      </>
                    ) : null}
                  </>
                ) : null}
                {isTxnV2ParityFeaturesEnabled ? <PaymentPageDetails id={order_id} /> : null}
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Bank RRN"
                  value={acquirer_data.rrn || '--'}
                  tooltipType="bankRRN"
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Order ID"
                  value={
                    order_id ? (
                      <Link onClick={() => history.push(`/orders/${order_id}`)} variant="button">
                        {order_id}
                      </Link>
                    ) : (
                      '--'
                    )
                  }
                  tooltipType="orderId"
                  copyable
                  onCopyAction={onCopy('Order ID', { transactionIDActual, orderId: order_id }).bind(
                    null,
                    order_id,
                  )}
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Invoice ID"
                  value={
                    invoice_id ? (
                      <Link
                        onClick={() => history.push(`/invoices/${invoice_id}`)}
                        variant="button"
                      >
                        {invoice_id}
                      </Link>
                    ) : (
                      '--'
                    )
                  }
                  copyable
                  onCopyAction={copyToClipboard.bind(null, invoice_id)}
                />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Payment method"
                  value={
                    <PaymentMethod
                      payment={paymentDetails}
                      method={method}
                      card={card}
                      bank={bank}
                      vpa={vpa}
                      wallet={wallet}
                      upi={upi}
                    />
                  }
                />
                {isTxnV2ParityFeaturesEnabled && bankReferenceNumber ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow label="Bank Reference" value={bankReferenceNumber} />
                  </>
                ) : null}
                {isPaymentReceiptSectionAllowed() && isTxnV2ParityFeaturesEnabled ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Payment Receipt"
                      value={<PaymentPagePaymentReceipt paymentId={id} />}
                    />
                  </>
                ) : null}
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow
                  label="Customer details"
                  value={
                    <Box display="flex" flexDirection="column" gap="spacing.2">
                      {notes.name ? (
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          {notes.name}
                        </Text>
                      ) : null}
                      {contact ? (
                        <Box display="inline-flex" gap="spacing.3" alignItems="center">
                          <PhoneIcon size="medium" color="interactive.icon.gray.subtle" />
                          <CopyWrapper onClick={copyToClipboard.bind(null, contact)}>
                            <Text
                              variant="body"
                              size="medium"
                              weight="regular"
                              color="surface.text.gray.normal"
                            >
                              {getI18FormattedPhoneNumber(contact)}
                            </Text>
                          </CopyWrapper>
                        </Box>
                      ) : null}
                      {email ? (
                        <Box display="inline-flex" gap="spacing.3" alignItems="center">
                          <MailIcon size="medium" color="interactive.icon.gray.subtle" />
                          <Text
                            variant="body"
                            size="medium"
                            weight="regular"
                            color="surface.text.gray.normal"
                          >
                            {email}
                          </Text>
                        </Box>
                      ) : null}
                      {!notes.name && !contact && !email ? (
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          --
                        </Text>
                      ) : null}
                    </Box>
                  }
                />
                {paymentDetails?.gateway_provider && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow label="Gateway" value={paymentDetails.gateway_provider} />
                  </>
                )}
                {isTxnFeeBreakupEnabled && !user.isJnKOmniEnabled ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Total Fee"
                      value={<PaymentFeeBreakdown transfers={transfers} payment={paymentDetails} />}
                    />
                  </>
                ) : null}
                {/* {isTxnFeeBreakupEnabled &&
                isInteger(`${paymentDetails.customer_fee}`) &&
                isInteger(`${paymentDetails.customer_fee_gst}`) ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Total Convenience Fee"
                      value={
                        <Definition>
                          <Amount
                            value={paymentDetails.customer_fee + paymentDetails.customer_fee_gst}
                          />
                          <span>
                            Convenience Fee -{' '}
                            <Amount
                              value={paymentDetails.customer_fee}
                              currency={paymentDetails.currency}
                            />
                          </span>
                          <span>
                            GST -{' '}
                            <Amount
                              value={paymentDetails.customer_fee_gst}
                              currency={paymentDetails.currency}
                            />
                          </span>
                        </Definition>
                      }
                    />
                  </>
                ) : null} */}
                {user?.isPayerNameEnabled && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow label="Payer Name" value={upi?.payer_name || '--'} />
                  </>
                )}
                {!user.isJnKOmniEnabled && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Fee bearer"
                      value={
                        fee_bearer === 'platform'
                          ? `You pay the ${orgName} platform fee`
                          : 'The customer has paid the fees for this payment'
                      }
                    />
                  </>
                )}
                {isTxnV2ParityFeaturesEnabled && isOrgFeatureExist('vas_merchant') ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Payment Reference Number"
                      value={getPaymentReferenceNumber(method, acquirer_data)}
                    />
                  </>
                ) : null}
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="App Name" value={applicationDetails?.name || '--'} />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="App ID" value={applicationDetails?.id || '--'} />
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Description" value={description || '--'} />
                {isOmniChannelMerchant && (
                  <>
                    {gateway_merchant_id && (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow label="Payment Gateway ID" value={gateway_merchant_id} />
                      </>
                    )}
                    {posGatewayId ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow
                          label="Device details"
                          value={
                            <Box display="flex" flexDirection="column">
                              <CopyWrapper onClick={copyToClipboard.bind(null, posGatewayId)}>
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.gray.normal"
                                >
                                  {gateway_terminal_id ? 'TID' : 'UPI'}: {posGatewayId}
                                </Text>
                              </CopyWrapper>
                              {posDeviceSerialNumber && (
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.gray.normal"
                                >
                                  DSN: {posDeviceSerialNumber}
                                </Text>
                              )}
                            </Box>
                          }
                        />
                      </>
                    ) : null}
                    {isChargeSlipExperimentEnabled ? (
                      <>
                        <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                        <DetailRow
                          label="Charge Slip"
                          value={
                            <div
                              onClick={openChargeSlip}
                              onKeyDown={openChargeSlip}
                              role="button"
                              tabIndex={0}
                              style={{ cursor: 'pointer' }}
                            >
                              <Box display="flex" flexDirection="row" alignItems="center">
                                <Text
                                  variant="body"
                                  size="medium"
                                  weight="regular"
                                  color="surface.text.primary.normal"
                                >
                                  {id}.png
                                </Text>
                                <IconButton
                                  icon={() => (
                                    <DownloadIcon
                                      marginLeft="spacing.3"
                                      size="medium"
                                      color="interactive.icon.primary.normal"
                                    />
                                  )}
                                  size="medium"
                                  accessibilityLabel="download"
                                  onClick={onDownloadClick}
                                />
                              </Box>
                            </div>
                          }
                        />
                      </>
                    ) : null}
                  </>
                )}
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <DetailRow label="Notes" value={getNotes({ notes, isStorefront })} />

                {paymentDetails.provider && isTxnV2ParityFeaturesEnabled && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Provider"
                      value={
                        <PaymentProvider
                          payment={paymentDetails}
                          isTransactionV2DetailsView={true}
                        />
                      }
                    />
                  </>
                )}

                {hash && isPaymentSplitSectionAllowed(hash.substring(1)) ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <RowWrapper>
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.subtle"
                        marginBottom="spacing.2"
                      >
                        Payment Split
                      </Text>
                      <PaymentSplitItems order_id={paymentDetails.order_id} />
                    </RowWrapper>
                  </>
                ) : null}
                {(user.isOrgCurlec || !isConfigTagEnabled('payment_transfer.transfers')) && (
                  <PaymentTransfers paymentDetails={paymentDetails} />
                )}
                {isTxnV2ParityFeaturesEnabled && user.isLRSEducationFlow ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Documents"
                      value={<PaymentDownloadSwiftCopy paymentId={paymentDetails.id} />}
                    />
                  </>
                ) : null}
                {isLateAuthAttributeEnabled ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Late Authorized"
                      value={paymentDetails.late_authorized ? 'Yes' : 'No'}
                    />
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Auto Captured"
                      value={paymentDetails.auto_captured ? 'Yes' : 'No'}
                    />
                  </>
                ) : null}
                {shouldShowOptimizerDetails && !!paymentDetails?.optimizer_provider ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Optimizer details"
                      value={
                        <PaymentOptimizerDetails
                          payment={paymentDetails}
                          terminalProviders={terminalProviders}
                          page="Payment Detail"
                        />
                      }
                    />
                  </>
                ) : null}
                {disputes.items.length > 0 && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <DetailRow
                      label="Dispute ID"
                      value={
                        <Box display="flex" flexDirection="column" gap="spacing.2">
                          {disputes.items.map((item) => (
                            <Link
                              key={item.id}
                              onClick={() => history.push(`/disputes/${item.id}`)}
                            >
                              {item.id}
                            </Link>
                          ))}
                        </Box>
                      }
                      tooltipType="disputeId"
                    />
                  </>
                )}
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      )}
      {/* Do not add below this, this is supposed to be the last element */}
      {isFailedDownloadMemoAllowed && (
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="semibold"
                    color="surface.text.gray.subtle"
                    alignSelf={isMobile ? 'start' : 'center'}
                  >
                    Failed Transaction Memo
                  </Text>
                  <Box
                    display="flex"
                    gap="spacing.4"
                    alignItems="flex-end"
                    paddingTop={isMobile ? 'spacing.3' : 'spacing.0'}
                  >
                    <Button
                      onClick={() => handleBounceMemoDownload(fetchBounceMemoParams)}
                      isLoading={isBounceMemoDownloading}
                      variant="secondary"
                    >
                      Download
                    </Button>
                  </Box>
                </RowWrapper>
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      )}
      {/* Do not add below this, this is supposed to be the last element */}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  orgName: state.session.org?.business_name,
  orgFeatures: state.session.org?.features,
  transfers: state.payment.transfers,
  bankTransfer: state.payment.bankTransfer,
});

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      showNotification: showNotificationAction,
      fetchTransfers,
      fetchBankTransfer,
    },
    dispatch,
  );
}

export default withSplitzService(
  withRouter<any>(compose(connect(mapStateToProps, mapDispatchToProps)(PaymentDetailsSection))),
);
