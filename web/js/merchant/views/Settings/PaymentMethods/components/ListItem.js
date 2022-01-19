import { useEffect } from 'react';
import { connect } from 'react-redux';
import qs from 'query-string';
import { getIcon } from './paymentMethodIcons';
import {
  setIntrument,
  clearIntermediateInstrument,
  clearLeafInstrument,
} from 'merchant/reducers/instrumentRequests';

const ListItem = ({
  index,
  /* eslint-disable no-shadow */
  instrument,
  handleClickedInstument,
  from,
  setIntrument,
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
    setIntrument(instrument);
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
      class={`${clickedName === instrument.name ? 'highlight' : ''}`}
    >
      {instrument.icon && <div class="icon">{getIcon(instrument.icon)}</div>}
      <div class="detail">
        <strong>
          {instrument.name}&nbsp;
          {instrumentActions ? (
            <span>
              <span class="notify-badge">{instrumentActions}</span>
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
        class={`expand show-expand ${clickedName === instrument.name ? 'highlight-expand' : ''}`}
      >
        <i class="i i-chevron-right" />
      </div>
    </li>
  );
};

export default connect(null, {
  setIntrument,
  clearIntermediateInstrument,
  clearLeafInstrument,
})(ListItem);
