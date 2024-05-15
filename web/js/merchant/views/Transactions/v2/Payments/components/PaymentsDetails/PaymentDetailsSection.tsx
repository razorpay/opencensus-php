import React, { lazy, useEffect, useState } from 'react';
import {
  Box,
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
import { bindActionCreators, compose } from 'redux';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { User } from 'common/typings';

import { useMobile } from 'common/hooks/useMobile';
import copyToClipboard from 'common/utils/copyToClipboard';

import getNotes from './Notes';
import PaymentMethod from './PaymentMethod';
import Tooltip from './Tooltip';
import {
  CardWrapper,
  CollapsibleContainer,
  CopyWrapper,
  RowsWrapper,
  RowWrapper,
  SectionHeader,
} from './styled';
import { IPaymentDetails, ApplicationDetails } from './types';
import { isChargeSlipForPosEnabled, onCopy } from './utils';
import PaymentTransfers from './PaymentTransfers';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { openModal } from 'merchant_common/reducers/modals';
import { fetchEncodedPaymentReceipt } from 'merchant/views/Transactions/model';
import { withSplitzService } from 'common/splitz';
import { SpiltzContextState } from 'common/splitz/types';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import fileDownload from 'common/utils/file-download';

const PaymentReceipt = lazy(
  () =>
    import(
      /* webpackChunkName: 'PaymentReceipt' */ 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentReceipt'
    ),
);

interface IPaymentDetailsSection extends RouteComponentProps<{ id: string }> {
  paymentDetails: IPaymentDetails;
  applicationDetails: ApplicationDetails | null;
  user: User;
  splitz: SpiltzContextState;
  showNotification: (payload: { type: 'error' | 'success'; message: string }) => void;
}

function PaymentDetailsSection({
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
}: IPaymentDetailsSection): React.ReactElement {
  const [isOpen, setIsOpen] = useState<boolean>(true);

  const toggleAccordian = () => {
    setIsOpen((prevState) => !prevState);
  };
  const isMobile = useMobile();
  const isStorefront = location.hash === '#storefront';

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
  } = paymentDetails;

  const isChargeSlipExperimentEnabled = isChargeSlipForPosEnabled(splitz);

  const isOmniChannelMerchant =
    isChargeSlipExperimentEnabled &&
    (user.isOmniEnabledMerchant || (!!user?.pos_activation_status && user?.isOmniChannelMerchant));

  const onDownloadClick = async (e) => {
    e.stopPropagation();

    try {
      const response = await fetchEncodedPaymentReceipt(id);
      fileDownload(response.receipt_encoded_image, `${id}.png`, 'image/png');
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'No Charge Slip found.',
      });
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
  return (
    <Box testID="payment-details-section">
      <SectionHeader enableBorderBottomRadius={!isOpen}>
        <Text weight="semibold" size="large" color="surface.text.gray.normal">
          Details
        </Text>
        {isMobile && (
          <CollapsibleContainer onClick={toggleAccordian} data-testid="collapsible-container">
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
      {isOpen ? (
        <CardWrapper enableBorderBottomRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Payment ID <Tooltip size="small" />
                  </Text>
                  <CopyWrapper
                    onClick={onCopy('Payment ID', { transactionIDActual, paymentId: id }).bind(
                      null,
                      id,
                    )}
                  >
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      {id}
                    </Text>
                  </CopyWrapper>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Bank RRN <Tooltip size="small" />
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {acquirer_data.rrn || '--'}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Order ID <Tooltip size="small" />
                  </Text>
                  {order_id ? (
                    <CopyWrapper
                      onClick={onCopy('Order ID', { transactionIDActual, orderId: order_id }).bind(
                        null,
                        order_id,
                      )}
                    >
                      <Text
                        variant="body"
                        size="medium"
                        weight="semibold"
                        color="surface.text.gray.normal"
                      >
                        {order_id}
                      </Text>
                    </CopyWrapper>
                  ) : (
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      --
                    </Text>
                  )}
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Invoice ID
                  </Text>
                  {invoice_id ? (
                    <CopyWrapper onClick={copyToClipboard.bind(null, invoice_id)}>
                      <Text
                        variant="body"
                        size="medium"
                        weight="semibold"
                        color="surface.text.gray.normal"
                      >
                        {invoice_id}
                      </Text>
                    </CopyWrapper>
                  ) : (
                    <Text
                      variant="body"
                      size="medium"
                      weight="semibold"
                      color="surface.text.gray.normal"
                    >
                      --
                    </Text>
                  )}
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Payment method
                  </Text>
                  <PaymentMethod
                    payment={paymentDetails}
                    method={method}
                    card={card}
                    bank={bank}
                    vpa={vpa}
                    wallet={wallet}
                  />
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Customer details
                  </Text>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    {notes.name && (
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.normal"
                      >
                        {notes.name}
                      </Text>
                    )}
                    {contact && (
                      <Box display="inline-flex" gap="spacing.3" alignItems="center">
                        <PhoneIcon size="medium" color="interactive.icon.gray.subtle" />
                        <Text
                          variant="body"
                          size="medium"
                          weight="regular"
                          color="surface.text.gray.normal"
                        >
                          {getI18FormattedPhoneNumber(contact)}
                        </Text>
                      </Box>
                    )}
                    {email && (
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
                    )}
                    {/* if nothing exists, show -- */}
                    {!notes.name && !contact && !email && (
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.normal"
                      >
                        --
                      </Text>
                    )}
                  </Box>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Fee bearer
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {fee_bearer === 'platform'
                      ? 'You pay the Razorpay platform fee'
                      : 'The customer has paid the fees for this payment'}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    App Name
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {applicationDetails?.name || `--`}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    App ID
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {applicationDetails?.id || `--`}
                  </Text>
                </RowWrapper>
                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Description
                  </Text>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.normal"
                  >
                    {description || `--`}
                  </Text>
                </RowWrapper>
                {isOmniChannelMerchant ? (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <RowWrapper>
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.subtle"
                      >
                        Charge Slip
                      </Text>
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
                    </RowWrapper>
                  </>
                ) : null}

                <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                <RowWrapper>
                  <Text
                    variant="body"
                    size="medium"
                    weight="regular"
                    color="surface.text.gray.subtle"
                  >
                    Notes
                  </Text>
                  {getNotes({ notes, isStorefront })}
                </RowWrapper>
                <PaymentTransfers paymentDetails={paymentDetails} />
                {/* Show only if dispute is raised */}
                {disputes.items.length > 0 && (
                  <>
                    <Divider dividerStyle="solid" thickness="thick" variant="muted" />
                    <RowWrapper>
                      <Text
                        variant="body"
                        size="medium"
                        weight="regular"
                        color="surface.text.gray.subtle"
                      >
                        Dispute ID
                      </Text>
                      <Box display="flex" flexDirection="column" gap="spacing.2">
                        {disputes.items.map((item) => (
                          <Link key={item.id} onClick={() => history.push(`/disputes/${item.id}`)}>
                            {item.id}
                          </Link>
                        ))}
                      </Box>
                    </RowWrapper>
                  </>
                )}
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
      ) : null}
    </Box>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

function mapDispatchToProps(dispatch) {
  return bindActionCreators(
    {
      showNotification: showNotificationAction,
    },
    dispatch,
  );
}

export default withSplitzService(
  withRouter<any>(compose(connect(mapStateToProps, mapDispatchToProps)(PaymentDetailsSection))),
);
