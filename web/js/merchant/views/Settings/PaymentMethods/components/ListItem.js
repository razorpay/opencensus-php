import { useEffect } from 'react';
import qs from 'query-string';
import { connect } from 'react-redux';

import {
  setInstrument,
  clearIntermediateInstrument,
  clearLeafInstrument,
} from 'merchant/reducers/instrumentRequests';

import { getIcon } from './paymentMethodIcons';

const ListItem = ({
  index,
  /* eslint-disable no-shadow */
  instrument,
  handleClickedInstument,
  from,
  setInstrument,
  clearLeafInstrument,
  clearIntermediateInstrument,
  clickedName,
  /* eslint-disable no-shadow */
}) => {
  // eslint-disable-next-line no-shadow
  const handleSetInstrument = (instrument, from) => {
    if (from === 'root') {
      clearLeafInstrument();
      clearIntermediateInstrument();
    }
    handleClickedInstument(instrument.name);
    setInstrument(instrument);
  };

  // for auto-selecting the first child node
  useEffect(() => {
    const query = qs.parse(window.location.search);
    if (query && query.instrument && instrument.slug === query.instrument) {
      handleSetInstrument(instrument, from);
    }

    if (!clickedName && index === 0) {
      handleSetInstrument(instrument, from);
    }
  }, []);

  const instrumentActions = instrument.actionItems && Object.keys(instrument.actionItems).length;
  return (
    <li
      onClick={() => handleSetInstrument(instrument, from)}
      className={`${clickedName === instrument.name ? 'highlight' : ''}`}
    >
      {instrument.icon && <div className="icon">{getIcon(instrument.icon)}</div>}
      <div className="detail">
        <strong>
          {instrument.name}&nbsp;
          {instrumentActions ? (
            <span>
              <span className="notify-badge">{instrumentActions}</span>
              {/* <Popover align="bottom" theme="dark">
                <PopoverBody>
                  <div style={{ textAlign: 'left' }}>
                    item requires user action. Please complete your activation form.
                  </div>
                </PopoverBody>
              </Popover> */}
            </span>
          ) : null}
        </strong>
        <p>{instrument.description}</p>
      </div>
      <div
        className={`expand show-expand ${
          clickedName === instrument.name ? 'highlight-expand' : ''
        }`}
      >
        <i className="i i-chevron-right" />
      </div>
    </li>
  );
};

export default connect(null, {
  setInstrument,
  clearIntermediateInstrument,
  clearLeafInstrument,
})(ListItem);
