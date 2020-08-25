import { useState } from 'react';
import { connect } from 'react-redux';
import LeafListItem from './LeafListItem';

const LeafList = ({ instrument, intermediateInstrument }) => {
  const [filter, setFilter] = useState('active');
  if (!instrument) return null;
  function renderLeafList(leafList) {
    let list = leafList.list
      .filter(_ => {
        if (
          intermediateInstrument &&
          intermediateInstrument.slug === 'netbanking'
        ) {
          if (filter === 'active') {
            return _.status === 'activated';
          } else {
            return _.status !== 'activated';
          }
        } else {
          return true;
        }
      })
      .filter(Boolean);
    if (list.length === 0) {
      return <p>No active banks!</p>;
    } else {
      return list.map(leafItem => {
        return <LeafListItem key={leafItem.name} instrument={leafItem} />;
      });
    }
  }
  return (
    <div class="level-3">
      {instrument.leafList.map(leafList => {
        return (
          <React.Fragment key={leafList.header}>
            <div class="heading">
              <strong style={{ fontSize: '14px' }}>{leafList.header} </strong>
              {leafList.docLink && (
                <span class="toggler-btn">
                  <a href={leafList.docLink} target="_blank" rel="noreferrer">
                    Documentation{' '}
                    <i
                      class="i i-external-link"
                      style={{ marginLeft: '5px' }}
                    />
                  </a>
                </span>
              )}
            </div>
            {intermediateInstrument &&
              intermediateInstrument.slug === 'netbanking' && (
                <div class="filter">
                  <button
                    class={`filter-btn ${
                      filter === 'active' ? 'filter-active' : ''
                    }`}
                    onClick={() => setFilter('active')}
                  >
                    Active Banks
                  </button>
                  <button
                    class={`filter-btn ${
                      filter === 'inactive' ? 'filter-active' : ''
                    }`}
                    onClick={() => setFilter('inactive')}
                  >
                    Add more Banks
                  </button>
                </div>
              )}
            <ul
              style={{ maxHeight: '500px', overflowY: 'auto', width: '420px' }}
            >
              {renderLeafList(leafList)}
            </ul>
            {leafList.footNote && (
              <div class="foot-note">
                <i class="i i-info-outline" />
                <p>{leafList.footNote}</p>
              </div>
            )}
            <br />
          </React.Fragment>
        );
      })}
    </div>
  );
};

const mapStateToProps = state => ({
  instrument: state.instrumentRequests.leafInstrument,
  intermediateInstrument: state.instrumentRequests.intermediateInstrument,
});

export default connect(mapStateToProps, {})(LeafList);
