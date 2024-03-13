import React, { useMemo, useState } from 'react';
import {
  Button,
  Box,
  List,
  ListItem,
  Text,
  Link,
  Checkbox,
  ListItemLink,
  ExternalLinkIcon,
} from '@razorpay/blade/components';
import { connect, ConnectedProps } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ModalHeader from 'common/ui/ModalHeader';
import {
  activateAccountError,
  activateAccountPending,
  activateAccountSuccess,
} from 'merchant/reducers/b2bExports/actions';
import lazy from 'merchant/routes/LazyLoader';
import {
  trackActivateClick,
  trackAccountActivated,
  trackAccountError,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';
import {
  VA_USD,
  REQUEST_ACCOUNT_TYPE,
  ACTIVATION_POPUP_CONTENT,
  B2B_EXPORTS_TNC_LINK,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import { activateAccount } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';
import { AcknowledgementPopupProps } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/types';
import { hasMCCInEligibleError } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/utils';
import { STANDARD_PRICING_URL } from 'merchant/views/Settings/PaymentMethods/constants';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

const MCCIneligiblePopup = lazy(
  () =>
    import(
      /* webpackChunkName: "MCCIneligiblePopup" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/MCCIneligiblePopup'
    ),
);

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      showNotification,
      openModal,
      closeModal,
      activateAccountError,
      activateAccountPending,
      activateAccountSuccess,
    },
    dispatch,
  );

const connector = connect(null, mapDispatchToProps);

const AcknowledgementPopup: React.FC<
  AcknowledgementPopupProps<ConnectedProps<typeof connector>>
> = ({
  account = VA_USD,
  showTnC = true,
  showNotification,
  openModal,
  closeModal,
  activateAccountError,
  activateAccountPending,
  activateAccountSuccess,
}) => {
  const [isChecked, setIsChecked] = useState<boolean>(true);
  const [isLoading, setIsLoading] = useState(false);
  const popupContent = useMemo(() => ACTIVATION_POPUP_CONTENT[account], [account]);

  //functions
  const onChange = ({ isChecked }) => {
    setIsChecked(isChecked);
  };

  const onOpenMCCIneligiblePopup = (error: string) => {
    openModal({
      size: 'medium',
      component: (
        <SuspenseWithLoader>
          <MCCIneligiblePopup error={error} />
        </SuspenseWithLoader>
      ),
    });
  };

  /**
   * Api call to request for ACH bank account
   */
  const onRequest = async () => {
    try {
      const isTnCAccepted = showTnC && isChecked;

      trackActivateClick(account, isTnCAccepted);
      setIsLoading(true);
      activateAccountPending({ type: REQUEST_ACCOUNT_TYPE[account], va_currency: account });

      const response = await activateAccount(account, isTnCAccepted ? 1 : 0);

      setIsLoading(false);
      activateAccountSuccess({
        type: REQUEST_ACCOUNT_TYPE[account],
        response: response?.data ?? [],
      });

      if (response?.success) {
        trackAccountActivated(account);
        closeModal();
        showNotification({
          type: 'success',
          message: 'Account have been successfully created!',
        });
      }
    } catch (errorMessage) {
      trackAccountError(errorMessage as string, account);
      setIsLoading(false);
      showNotification({
        type: 'error',
        message: errorMessage,
      });
      activateAccountError({ type: REQUEST_ACCOUNT_TYPE[account], errors: errorMessage });

      if (hasMCCInEligibleError(errorMessage)) {
        onOpenMCCIneligiblePopup(errorMessage as string);
      }
    }
  };

  return (
    <div className="b2b-acknowledgement-popup">
      <ModalHeader title={popupContent.title} onCloseClick={closeModal} />
      <div className="modal-body">
        <Text>{popupContent.description}</Text>

        <List>
          {popupContent.faqs.map((item) => (
            <ListItem key={item}>{item}</ListItem>
          ))}
          <ListItem>
            Default payment charges will be applied for these transactions. (Refer International
            Payments{' '}
            <ListItemLink href={STANDARD_PRICING_URL} target="_blank" rel="noopener">
              Pricing
            </ListItemLink>
            )
          </ListItem>
        </List>

        {showTnC ? (
          <Box marginTop="spacing.4">
            <Checkbox isChecked={isChecked} onChange={onChange}>
              I agree to the{' '}
              <Link
                target="_blank"
                rel="noopener noreferrer"
                href={B2B_EXPORTS_TNC_LINK}
                icon={ExternalLinkIcon}
                iconPosition="right"
              >
                Terms and Conditions
              </Link>
            </Checkbox>
          </Box>
        ) : null}

        <Box marginTop="spacing.4">
          <Button isLoading={isLoading} isDisabled={!isChecked} isFullWidth onClick={onRequest}>
            Activate Now
          </Button>
        </Box>
      </div>
    </div>
  );
};

export default connector(AcknowledgementPopup);
