import React, { useState, useRef, useEffect } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import LeafListItem from './LeafListItem';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import Paypal from './Paypal';
import International from './International';

const fircClickHandler = () => {
  analyticsTrack({
    objectName: 'FIRC Banner',
    actionName: 'clicked',
    screen: 'Instrument Dashboard',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toLumberjack: true,
  });
};

const LeafList = ({ instrument, intermediateInstrument, user }) => {
  const [filter, setFilter] = useState('active');
  const ulRef = useRef(null);
  const addShadow = () => {
    if (ulRef && ulRef.current) {
      if (ulRef.current.scrollHeight > ulRef.current.clientHeight) {
        ulRef.current.style.boxShadow =
          'inset 0px 20px 8px -10px rgba(227, 227, 227, 0.42), inset 0px -20px 8px -10px rgba(227, 227, 227, 0.42)';
      } else {
        ulRef.current.style.boxShadow = 'none';
      }
    }
  };
  useEffect(() => {
    addShadow();
  });
  if (!instrument) return null;
  function renderLeafList(leafList) {
    const analyticsList = {};
    if (leafList.list) {
      leafList.list.forEach((item) => {
        analyticsList[item.name] = item.status;
      });
    }
    analyticsTrack({
      objectName: 'method instruments',
      actionName: 'displayed',
      screen: 'settings',
      properties: {
        location: 'Payment Methods',
        methodName: instrument.name,
        ...analyticsList,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const list = leafList.list
      .filter((_) => {
        if (intermediateInstrument && intermediateInstrument.slug === 'netbanking') {
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
      if (filter === 'inactive') {
        return <p class="all-active">All banks are active on your account!</p>;
      } else if (filter === 'active') {
        return <p class="all-inactive">No banks active for you. Add more banks to catch up.</p>;
      }
    }
    return list.map((leafItem) => {
      if (leafItem.slug === 'internationalcards') return <International />;
      else if (leafItem.slug === 'paypal') return <Paypal instrument={leafItem} />;
      else if (
        ['itzcash', 'paycash', 'citibankrewards', 'cardless_emi.sezzle'].includes(leafItem.slug) &&
        leafItem.status !== 'activated'
      ) {
        return null;
      } else {
        return <LeafListItem key={leafItem.name} instrument={leafItem} />;
      }
    });
  }
  return (
    <div class={`level-3 ${instrument.leafList && instrument.leafList.length > 1 && 'overflowY'}`}>
      {instrument.leafList.map((leafList) => {
        return (
          <React.Fragment key={leafList.header}>
            <div class="heading">
              <strong style={{ fontSize: '14px' }}>{leafList.header} </strong>
              {leafList.docLink && (
                <span class="toggler-btn">
                  <a
                    href={leafList.docLink}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={() =>
                      analyticsTrack({
                        objectName: 'method documentation',
                        actionName: 'clicked',
                        screen: 'settings',
                        properties: {
                          location: 'Payment Methods',
                          methodName: instrument.name,
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      })
                    }
                  >
                    Documentation <i class="i i-external-link" style={{ marginLeft: '5px' }} />
                  </a>
                </span>
              )}
            </div>
            {intermediateInstrument && intermediateInstrument.slug === 'netbanking' && (
              <div class="filter">
                <button
                  class={`filter-btn ${filter === 'active' ? 'filter-active' : ''}`}
                  onClick={() => {
                    setFilter(() => {
                      addShadow();
                      return 'active';
                    });
                    analyticsTrack({
                      objectName: 'active banks',
                      actionName: 'clicked',
                      screen: 'settings',
                      properties: {
                        location: 'Payment Methods',
                        netbanking: instrument.name,
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                  }}
                >
                  Active Banks
                </button>
                <button
                  class={`filter-btn ${filter === 'inactive' ? 'filter-active' : ''}`}
                  onClick={() => {
                    setFilter(() => {
                      addShadow();
                      return 'inactive';
                    });
                    analyticsTrack({
                      objectName: 'add more banks',
                      actionName: 'clicked',
                      screen: 'settings',
                      properties: {
                        location: 'Payment Methods',
                        netbanking: instrument.name,
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                  }}
                >
                  Add more Banks
                </button>
              </div>
            )}
            <ul
              style={{
                maxHeight: `${!intermediateInstrument ? '420px' : '370px'}`,
                overflowY: 'auto',
                width: '420px',
              }}
              ref={ulRef}
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
      {/* FIRC banner for international merchants */}
      {instrument.slug === 'international' && user.international && (
        <div className="instrument-firc-banner">
          <div>
            <img
              src={`${window.cdnBaseUrl}/static/assets/firc/blue_vector.svg`}
              alt="FIRC Icon"
              height="24"
              width="24"
            />
          </div>
          <div className="instrument-firc-content">
            <div>
              Download monthly <b>e-FIRC </b> directly from the dashboard now!
            </div>
            <div>
              <u>
                <Link to="/profile/view_firc" onClick={fircClickHandler}>
                  View FIRC
                </Link>
              </u>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  instrument: state.instrumentRequests.leafInstrument,
  intermediateInstrument: state.instrumentRequests.intermediateInstrument,
});

export default connect(mapStateToProps, {})(LeafList);
