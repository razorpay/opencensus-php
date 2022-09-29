import { connect } from 'react-redux';
import Instrument from './Instrument';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import { GREYED, REQUESTABLE } from 'merchant/views/Settings/PaymentMethods/constants';

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
    showButton = true,
    showListAction = true,
    onButtonClick,
    instrumentRow,
    isActivating = false,
    error,
  } = props;
  const { listHeader, listDescription, list } = leafList;
  const InstrumentRow = instrumentRow || Instrument;

  const onRequest = () => {
    onButtonClick && onButtonClick();
  };

  return (
    <div className="instruments-methods-container">
      <div className="top-container">
        <div className="left-text-wrapper">
          {listHeader ? <h4>{listHeader}</h4> : null}
          {listDescription ? <p>{listDescription}</p> : null}
        </div>
        {showButton &&
          ([REQUESTABLE, GREYED].includes(containerStatus) ? (
            <button
              className="pull-right btn btn-primary"
              disabled={isActivating}
              onClick={onRequest}
              type="button"
            >
              {isActivating ? 'Processing...' : buttonText}
            </button>
          ) : (
            <InternationalStatusLabel status={containerStatus} />
          ))}
      </div>
      {error ? (
        <div className="bottom-container">
          <div className="error-message">
            <i className="i i-info-outline" />
            <p className="message">{error?.message}</p>
          </div>
          <div className="action">{error?.action}</div>
        </div>
      ) : null}
      <div className="method-list-container">
        {list?.map((leafItem, index) => (
          <InstrumentRow key={index} data={leafItem} showAction={showListAction} {...props} />
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
