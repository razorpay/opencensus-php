import React, { useState, useRef, useEffect } from 'react';
import { Text, Link, Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Link as NavLink } from 'react-router-dom';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import lazy from 'merchant/routes/LazyLoader';
import {
  isInternationalLeafItemDisabled,
  isRecurringInstrumentEnabled,
} from 'merchant/views/AccountAndSettings/PaymentMethods/utils';
import LeafListItem from 'merchant/views/Settings/PaymentMethods/components/LeafListItem';
import {
  INSTRUMENT_SLUGS,
  RECURRING_METHOD_HEADERS,
} from 'merchant/views/Settings/PaymentMethods/constants';

const Paypal = lazy(() =>
  import(
    /* webpackChunkName: "Paypal" */ 'merchant/views/Settings/PaymentMethods/components/Paypal'
  ),
);

const International = lazy(() =>
  import(
    /* webpackChunkName: "International" */ 'merchant/views/Settings/PaymentMethods/components/International'
  ),
);

const LocalWireTransfer = lazy(() =>
  import(
    /* webpackChunkName: "LocalWireTransfer" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer'
  ),
);

const InstantBankTransfer = lazy(() =>
  import(
    /* webpackChunkName: "InstantBankTransfer" */ 'merchant/views/Settings/PaymentMethods/components/InstantBankTransfer'
  ),
);

const SwiftBankTransfer = lazy(() =>
  import(
    /* webpackChunkName: "SwiftBankTransfer" */ 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/SwiftBankTransfer'
  ),
);

const handleViewFirc = () => {
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

const LinkIcon = () => <i className="i i-external-link" />;

const DocumentLink = ({ link, method }) => {
  if (!link) return null;
  return (
    <Link
      href={link}
      iconPosition="right"
      rel="noreferrer noopener"
      target="_blank"
      variant="anchor"
      icon={LinkIcon}
      size="small"
      htmlTitle="Click to learn more"
      onClick={() =>
        analyticsTrack({
          objectName: 'method documentation',
          actionName: 'clicked',
          screen: 'settings',
          properties: {
            location: 'Payment Methods',
            methodName: method,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        })
      }
    >
      Documentation
    </Link>
  );
};

const LeafList = ({ instrument, intermediateInstrument, user }) => {
  const [filter, setFilter] = useState('active');
  const splitz = useSplitzService();

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
    if (Array.isArray(leafList.leafList)) {
      return leafList.leafList.map((leafListItem) => renderLeafList(leafListItem));
    }

    const list = leafList.list
      .filter((_) => {
        // for recurring, filter is not applicable
        if (instrument.slug === INSTRUMENT_SLUGS.RECURRING) return true;

        if (intermediateInstrument && intermediateInstrument.slug === INSTRUMENT_SLUGS.NETBANKING) {
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
        return <p className="all-active">All banks are active on your account!</p>;
      } else if (filter === 'active') {
        return <p className="all-inactive">No banks active for you. Add more banks to catch up.</p>;
      }
    }
    if (leafList?.slug === 'localcurrencytransfer')
      return (
        <SuspenseWithLoader>
          <Box marginBottom="spacing.4">
            <LocalWireTransfer leafList={leafList} />
          </Box>
        </SuspenseWithLoader>
      );
    if (leafList?.slug === 'instantbanktransfer') {
      return (
        <SuspenseWithLoader>
          <InstantBankTransfer leafList={leafList} />
        </SuspenseWithLoader>
      );
    }
    if (leafList?.slug === 'swiftbanktransfer') {
      return (
        <SuspenseWithLoader>
          <SwiftBankTransfer leafList={leafList} />
        </SuspenseWithLoader>
      );
    }
    return list.map((leafItem) => {
      if (leafItem.slug === 'internationalcards')
        return (
          // eslint-disable-next-line react/jsx-key
          <SuspenseWithLoader>
            <International />
          </SuspenseWithLoader>
        );
      else if (leafItem.slug === 'paypal')
        return (
          // eslint-disable-next-line react/jsx-key
          <SuspenseWithLoader>
            <Paypal instrument={leafItem} />
          </SuspenseWithLoader>
        );
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
    <div
      className={`level-3 ${instrument.leafList && instrument.leafList.length > 1 && 'overflowY'}`}
    >
      {instrument.leafList.map((leafList) => {
        if (isInternationalLeafItemDisabled({ leafList, user })) return null;
        if (
          RECURRING_METHOD_HEADERS.includes(leafList.header) &&
          !isRecurringInstrumentEnabled(splitz)
        )
          return null;

        function getDescription() {
          const description = leafList?.description;
          if (!description) return null;

          // in case of rzp merchant show a custom text for sodexo method
          if (leafList.header === 'Sodexo' && !user?.isOptimizerEnabled) {
            return (
              <Text>
                This is available for Optimizer merchants only. Please raise a request with our{' '}
                <Link
                  href="https://razorpay.com/support/#request"
                  htmlTitle="Click to learn more"
                  rel="noreferrer noopener"
                  target="_blank"
                  onClick={() =>
                    analyticsTrack({
                      objectName: 'support request',
                      actionName: 'clicked',
                      screen: 'settings',
                      properties: {
                        location: 'Payment Methods',
                        methodName: instrument?.name,
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    })
                  }
                >
                  support team
                </Link>{' '}
                to get this feature.
              </Text>
            );
          }

          return <Text className="description">{description}</Text>;
        }

        return (
          <React.Fragment key={leafList.header}>
            <div className="level-3--header">
              <div className="heading">
                <Text weight="semibold">{leafList?.header}</Text>
                <DocumentLink link={leafList?.docLink} method={instrument?.name} />
              </div>
              {getDescription()}
            </div>
            {intermediateInstrument &&
              intermediateInstrument.slug === INSTRUMENT_SLUGS.NETBANKING &&
              instrument.slug !== INSTRUMENT_SLUGS.RECURRING && (
                <div className="filter">
                  <button
                    className={`filter-btn${filter === 'active' ? ' filter-active' : ''}`}
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
                    className={`filter-btn${filter === 'inactive' ? ' filter-active' : ''}`}
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
                maxHeight: `${
                  !intermediateInstrument
                    ? instrument.slug === INSTRUMENT_SLUGS.CARDS
                      ? '300px'
                      : '420px'
                    : '370px'
                }`,
                overflowY: 'auto',
                width: '420px',
              }}
              ref={ulRef}
            >
              {renderLeafList(leafList)}
            </ul>
            {leafList.footNote && (
              <div className="foot-note">
                <i className="i i-info-outline" />
                <p>{leafList.footNote}</p>
              </div>
            )}
            <br />
          </React.Fragment>
        );
      })}
      {/* FIRC banner for international merchants */}
      {instrument.slug === INSTRUMENT_SLUGS.INTERNATIONAL && user.international && (
        <div className="instrument-firc-banner">
          <div>
            <img
              src={`${window.cdnBaseUrl}/static/assets/firc/blue_vector.svg`}
              alt="FIRS Icon"
              height="24"
              width="24"
            />
          </div>
          <div className="instrument-firc-content">
            <p>
              Download monthly <b>e-FIRS </b> directly from the dashboard now!
            </p>
            <NavLink to="/profile/view_firc" onClick={handleViewFirc}>
              View FIRS
            </NavLink>
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

export default connect(mapStateToProps)(LeafList);
