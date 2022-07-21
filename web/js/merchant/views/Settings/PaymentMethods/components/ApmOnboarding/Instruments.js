import InstrumentRow from './InstrumentRow';
import { connect } from 'react-redux';
import { INSTRUMENT_LIST } from './constants';

const Instruments = ({ instrumentList, instrumentsTat }) => (
  <div className="instruments-container">
    {INSTRUMENT_LIST.map(({ key, description }) => {
      return (
        <InstrumentRow
          key={key}
          instrumentKey={key}
          description={description}
          instrumentList={instrumentList}
          instrumentsTat={instrumentsTat}
        />
      );
    })}
  </div>
);

const mapStateToProps = (state) => ({
  instrumentsTat: state.instrumentRequests.instrumentsTat,
});

export default connect(mapStateToProps, null)(Instruments);
