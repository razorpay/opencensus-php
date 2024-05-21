import {
  Button,
  Tooltip,
  TooltipInteractiveWrapper,
  Alert,
  Box,
  Text,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';

import copyToClipboard from 'common/utils/copyToClipboard';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import ErrorContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/ErrorContainer';
import { trackCopyLinkClicked } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';
import { BASE_PAYMENT_LINK_URL } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import { GREYED, REQUESTABLE } from 'merchant/views/Settings/PaymentMethods/constants';

import Instrument from './Instrument';
import './InstrumentList.styl';

/*
 * @param  {*} leafList instrument list
 * @param  {*} buttonText='Request' instrument container
 * @param  {*} containerStatus='Request' instrument container
 * @param  {*} showButton=true instrument container
 * @param  {*} showListAction=true instrument list
 * @param  {*} onButtonClick instrument container
 * @param  {*} instrumentRow instrument list
 * @param  {*} isActivating=false instrument container button state
 * @param  {*} error pass down error to show on the instrument container
 */
const InstrumentContainer = (props) => {
  const {
    leafList,
    buttonText = 'Request',
    containerStatus = GREYED,
    showAction = true,
    showListAction = true,
    onButtonClick,
    instrumentRow,
    isActivating = false,
    requestTooltipText,
    isRequestButtonDisabled = false,
    publicPaymentLink = false,
    error,
  } = props;
  const { listHeader, listDescription, list } = leafList;
  const InstrumentRow = instrumentRow || Instrument;

  const onRequest = () => {
    onButtonClick && onButtonClick();
  };

  const onCopyClick = () => {
    copyToClipboard(BASE_PAYMENT_LINK_URL + publicPaymentLink);
    trackCopyLinkClicked();
  };

  return (
    <div className="instruments-methods-container">
      <div className="top-container">
        <div className="action-wrapper">
          <div className="left-text-wrapper">
            {listHeader ? <h4>{listHeader}</h4> : null}
            {listDescription ? <p>{listDescription}</p> : null}
            {publicPaymentLink && !showAction ? (
              <Alert
                actions={{
                  primary: {
                    onClick: onCopyClick,
                    text: 'Copy Link',
                  },
                }}
                marginTop="spacing.3"
                color="positive"
                description={
                  <Box>
                    <Text>
                      Congratulations! You now have a dedicated page with details of your local
                      currency bank account(s) which can be shared with anyone:
                    </Text>
                    <Text weight="semibold">{`${BASE_PAYMENT_LINK_URL}${publicPaymentLink}`}</Text>
                  </Box>
                }
                emphasis="subtle"
                isDismissible={false}
                isFullWidth
              />
            ) : null}
          </div>
          {showAction &&
            ([REQUESTABLE, GREYED].includes(containerStatus) ? (
              <div className="request-cta">
                {requestTooltipText ? (
                  <Tooltip content={requestTooltipText} position="top">
                    <TooltipInteractiveWrapper width="100%">
                      <Button
                        variant="primary"
                        isLoading={isActivating}
                        size="small"
                        isFullWidth
                        onClick={onRequest}
                        isDisabled={isRequestButtonDisabled}
                      >
                        {buttonText}
                      </Button>
                    </TooltipInteractiveWrapper>
                  </Tooltip>
                ) : (
                  <Button
                    variant="primary"
                    isLoading={isActivating}
                    size="small"
                    isFullWidth
                    onClick={onRequest}
                    isDisabled={isRequestButtonDisabled}
                  >
                    {buttonText}
                  </Button>
                )}
              </div>
            ) : (
              <InternationalStatusLabel status={containerStatus} />
            ))}
        </div>
        {error ? <ErrorContainer message={error.message} action={error.action} /> : null}
      </div>
      <div className="method-list-container">
        {list?.map((leafItem, index) => (
          <InstrumentRow
            key={index}
            data={leafItem}
            showInstrumentAction={showListAction}
            {...props}
          />
        ))}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  instrumentsTat: state.instrumentRequests.instrumentsTat,
  leafInstrument: state.instrumentRequests.leafInstrument,
});

export default connect(mapStateToProps, null)(InstrumentContainer);
