import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose, ActionCreator, bindActionCreators } from 'redux';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import shallow from 'zustand/shallow';
import { Amount, Spinner, InfoIcon } from '@razorpay/blade/components';
import { paiseToRupees } from 'common/utils/rzp-utils';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import TransferSource from 'merchant/views/Marketplace/Transfers/components/TransferSource';
import { RouteTransfersStatusLabel } from 'merchant/components/StatusLabel';
import TransferReversal from 'merchant/views/Marketplace/Transfers/components/TransferReversal';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  AmountContainer,
  ErrorText,
  DetailsSpinnerContainer,
  IconContainer,
} from 'merchant/views/Marketplace/PlatformFee/components/styles';
import { openModal } from 'merchant_common/reducers/modals';
import ReversalModal from 'merchant/views/Marketplace/Transfers/ReversalModal';
import { OpenModalPayload } from 'common/typings/Store/modal';
import { Notification } from 'common/typings/Store/notifications';
import { User } from 'common/typings';
import { fetchTransfersById, fetchReversals } from './api';
import { platformFeeDetailsOpenedAnalytics } from 'merchant/views/Marketplace/MarketplaceAnalytics';
import { useMarketplaceStore } from 'merchant/views/Marketplace/store';

const ERROR_CODE_CTAS_MAP = {
  BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT: 'Please create another transfer.',
  BAD_REQUEST_TRANSFER_INSUFFICIENT_BALANCE: (
    <span>
      Please <Link to="/addfunds">add funds</Link> to your account and then create a transfer.
    </span>
  ),
  INTERNAL_SERVER_ERROR:
    'Please create the transfer again. If the issue persists, please contact Razorpay Support.',
};

interface PlatformFeeDetailsProps {
  showNotification?: ActionCreator<Notification>;
  openModal?: ActionCreator<OpenModalPayload>;
  id: string;
  user: User;
}

interface transferTypes {
  id: string;
  amount: number;
  fees: number;
  tax: number;
  partner_details: {
    name: string;
    email: string;
    id: string;
  };
  recipient_details: {
    name: string;
    email: string;
  };
  recipient: string;
  source: string;
  status: string;
  error: {
    code: string;
    description: string;
    field: string;
    id: string;
    metadata: string;
    reason: string;
    source: string;
    step: string;
  };
  settlement_status: string;
  notes: { name: string; roll_no: string };
  linked_account_notes: string[];
}

interface reversalType {
  entity: string;
  id: string;
}
const PlatformFeeDetailsContainer = ({
  id,
  showNotification,
  openModal,
  user,
}: PlatformFeeDetailsProps): JSX.Element => {
  const [transferData, setTransferData] = useState<transferTypes>();
  const [reversalsData, setReversalsData] = useState<reversalType>();

  const { isLoading, refetch } = useQuery({
    queryKey: ['get-transfer-details'],
    queryFn: () => fetchTransfersById(id),
    refetchOnWindowFocus: false,
    onSuccess: (data) => {
      setTransferData(data.data);
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });

  const { isPartnerPlatformFeeEnabled } = useMarketplaceStore(
    (state) => ({
      isPartnerPlatformFeeEnabled: state.isPartnerPlatformFeeEnabled,
    }),
    shallow,
  );

  const { isLoading: isReversalLoading, refetch: refetchReversal } = useQuery({
    queryKey: ['get-reversal-details'],
    queryFn: () => fetchReversals(id),
    refetchOnWindowFocus: false,
    onSuccess: (data) => {
      setReversalsData(data.data);
    },
    onError: (err: { errors: Array<string> }) => {
      showNotification?.({
        type: 'error',
        message: err.errors,
      });
    },
  });

  useEffect(() => {
    refetch();
    refetchReversal();
  }, [id]);

  useEffect(() => {
    platformFeeDetailsOpenedAnalytics(user.id);
  }, []);

  const openReversalModal = (transfer) => {
    openModal?.({
      component: <ReversalModal transfer={transfer} />,
      size: 'small',
    });
  };

  return (
    <SuspenseWithLoader>
      <div className="content-wrapper content-sm txn-details">
        {isLoading && isReversalLoading ? (
          <DetailsSpinnerContainer>
            <Spinner testID="spinner" accessibilityLabel="spinner" size="xlarge" />
          </DetailsSpinnerContainer>
        ) : (
          <div className="panel panel-default SliderPanel">
            {transferData?.id ? (
              <div className="panel-heading">
                {isPartnerPlatformFeeEnabled ? 'Platform Fee ID' : 'Partner Fee ID'}:{' '}
                <strong>{transferData.id}</strong>
              </div>
            ) : null}

            <div className="SliderPanel__Body">
              <div className="panel-body">
                <EntityDetailRow
                  label={isPartnerPlatformFeeEnabled ? 'Platform Fee Amount' : 'Partner Fee Amount'}
                >
                  {transferData?.amount ? (
                    <Definition>
                      <span>
                        <Amount value={paiseToRupees(transferData.amount + transferData.fees)} />
                      </span>
                      <AmountContainer>
                        Payment to {transferData.recipient_details.name} ={' '}
                        <Amount
                          value={paiseToRupees(transferData.amount)}
                          type="body"
                          size="small"
                        />
                      </AmountContainer>
                      <ContentToggler>
                        <AmountContainer>
                          Razorpay Charges Incl Tax ={' '}
                          <Amount
                            value={paiseToRupees(transferData.fees)}
                            type="body"
                            size="small"
                          />
                        </AmountContainer>
                        <div className="m-t m-l">
                          <AmountContainer>
                            Razorpay Transfer Fee ={' '}
                            <Amount
                              value={paiseToRupees(transferData.fees)}
                              type="body"
                              size="small"
                            />
                          </AmountContainer>
                          <AmountContainer>
                            GST ={' '}
                            <Amount
                              value={paiseToRupees(transferData.tax)}
                              type="body"
                              size="small"
                            />
                          </AmountContainer>
                        </div>
                      </ContentToggler>
                    </Definition>
                  ) : (
                    <span>N/A</span>
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Initiated By">
                  {transferData?.partner_details ? (
                    <Definition>
                      <span>{transferData.partner_details.name}</span>
                      <span>{transferData.partner_details.email}</span>
                      <span>{transferData.partner_details.id}</span>
                    </Definition>
                  ) : (
                    <span>N/A</span>
                  )}
                </EntityDetailRow>
                <EntityDetailRow
                  label="Source ID"
                  value={() => (
                    <div>
                      {transferData?.source ? (
                        <TransferSource
                          source={transferData.source}
                          initiatePoint="platformFee-details"
                        />
                      ) : (
                        <span>N/A</span>
                      )}
                    </div>
                  )}
                />
                <EntityDetailRow label="Status">
                  {transferData?.status ? (
                    <>
                      <RouteTransfersStatusLabel status={transferData.status} />
                      {transferData.status === 'failed' && transferData.error?.description && (
                        <ErrorText>{transferData.error.description}.</ErrorText>
                      )}
                      {transferData.status === 'failed' &&
                        ERROR_CODE_CTAS_MAP[transferData.error?.code] && (
                          <ErrorText>{ERROR_CODE_CTAS_MAP[transferData.error.code]}</ErrorText>
                        )}
                    </>
                  ) : (
                    <span>N/A</span>
                  )}
                </EntityDetailRow>
                <EntityDetailRow label="Settlement Status">
                  {transferData?.settlement_status ? (
                    <Definition>
                      <span>{transferData.settlement_status}</span>
                    </Definition>
                  ) : (
                    <span>N/A</span>
                  )}
                </EntityDetailRow>
                <EntityDetailRow label="Reversal">
                  {reversalsData?.entity && transferData?.id ? (
                    <TransferReversal
                      transfer={transferData}
                      reversals={reversalsData}
                      openTransferReversalModal={openReversalModal}
                    />
                  ) : (
                    <span>N/A</span>
                  )}
                </EntityDetailRow>

                {/* Notes */}
                <EntityDetailRow label="Notes">
                  {transferData?.notes ? (
                    Object.keys(transferData.notes).length === 0 ? (
                      '--'
                    ) : (
                      Object.keys(transferData.notes).map((key, index) => (
                        <div className="m-b" key={index}>
                          <Definition>
                            {key}
                            {String(transferData.notes[key])}
                            {!!transferData.linked_account_notes &&
                              transferData.linked_account_notes.indexOf(key) > -1 && (
                                <IconContainer>
                                  <InfoIcon
                                    color="feedback.icon.neutral.intense"
                                    size="small"
                                    marginRight={'5px'}
                                  />
                                  This note is shown to the linked account
                                </IconContainer>
                              )}
                            <i />
                          </Definition>
                        </div>
                      ))
                    )
                  ) : (
                    <span>N/A</span>
                  )}
                </EntityDetailRow>
              </div>
            </div>
          </div>
        )}
      </div>
    </SuspenseWithLoader>
  );
};

export default compose<any>(
  connect(
    (state) => ({ user: state.session.user }),
    (dispatch) => bindActionCreators({ showNotification, openModal }, dispatch),
  ),
)(PlatformFeeDetailsContainer);
