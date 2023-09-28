import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import {
  getSelfServeDetailForSettlementDetails,
  sanitizeTabName,
} from 'merchant/views/Settlements/v2/util';
import ComponentListFilter from './ComponentListFilter';
import Pagination from './Pagination';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import {
  PaymentStatusLabel,
  RefundStatusLabel,
  DisputeStatusLabel,
} from 'merchant/components/StatusLabel';
import { merchantFetch } from 'merchant/utils/ajax';
import { titleCase } from 'common/utils/rzp-utils';
import Spinner from 'common/ui/Spinner';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  handleAnalytics,
  propertiesPayload,
} from 'merchant/views/Settlements/Settlements/analytics';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const DEFAULT_SKIP = 0;
const DEFAULT_COUNT = 10;
const InternationalStatusMap = {
  true: 'label-success',
  false: 'label-warning',
  0: 'label-warning',
  1: 'label-success',
};
const TransfersMap = {
  processed: 'label-success',
  reversed: 'label-danger',
  partially_reversed: 'label-warning',
  failed: 'label-muted',
};
const PayoutsMap = {
  created: 'bg-primary',
  initiated: 'label-muted',
  reversed: 'label-danger',
  processed: 'label-success',
  failed: 'label-muted',
  partially_reversed: 'label-warning',
};
const OndemandMap = {
  processed: 'label-success',
  reversed: 'label-danger',
  partially_reversed: 'label-warning',
  failed: 'label-muted',
};
const BooleanMap = {
  true: `true`,
  false: `false`,
  0: `false`,
  1: `true`,
};

/**
 * Sort keys - adding optimizer_provider at 1st index for single recon, other keys remains same
 * @param {object} item object to sort the keys
 * @param {object} user object to check single recon enabled or not
 * @returns {object} array of keys from object
 */
const sortKeys = (item, user) => {
  const KEYS = Object.keys(item);
  if (user?.isSingleReconEnabled && user?.isOptimizerEnabled) {
    const INDEX = KEYS?.indexOf('optimizer_provider');
    const PROVIDER_KEY = INDEX !== -1 ? KEYS?.splice(INDEX, 1) : [];
    KEYS?.splice(1, 0, ...PROVIDER_KEY);
  }
  return KEYS;
};

const ListItem = ({ item, source, user, terminalProviders }) => {
  const { selfServeActionName, page, INIT_POINT, INIT_PAGE } =
    getSelfServeDetailForSettlementDetails(source);

  const selfServeInitiateData = {
    selfServeAction: selfServeActionName,
    screen: 'Settlements',
    page,
    props: {
      initiatePoint: INIT_POINT,
    },
  };
  // Render links
  const analyticsHandler = () => {
    const objectName = 'settlement details payment id';
    const actionName = 'clicked';
    const screen = 'settlement details';
    const properties = propertiesPayload('payment', item);
    handleAnalytics(objectName, actionName, properties, screen);
  };

  const highlightLink = (rowItem, key, idx) => {
    if (source === 'payment' || source === 'refund' || source === 'dispute') {
      return (
        <td key={idx}>
          <Link
            onClick={() => {
              if (['payment', 'refund'].includes(source)) {
                if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
                selfServeTrackInitiate(selfServeInitiateData);
                return analyticsHandler();
              }
              return true;
            }}
            to={
              source === 'payment' || source === 'refund'
                ? `/${source}s/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`
                : `/${source}s/${rowItem[key]}`
            }
            state={{ openedFrom: 'settlement-details', settlement_id: rowItem[key] }}
          >
            {rowItem.id}
          </Link>
        </td>
      );
    } else if (source === 'transfer' || source === 'reversal') {
      return (
        <td key={idx}>
          <Link
            to={`/route/${source}s/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`}
          >
            {rowItem.id}
          </Link>
        </td>
      );
    } else if (source === 'payment_domestic' || source === 'payment_international') {
      return (
        <td key={idx}>
          <Link
            to={`/payments/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`}
            onClick={() => {
              selfServeTrackInitiate(selfServeInitiateData);
            }}
          >
            {rowItem.id}
          </Link>
        </td>
      );
    } else {
      return <td key={idx}>{rowItem.id}</td>;
    }
  };

  const {
    id,
    amount,
    fee,
    tax,
    created_at,
    international,
    status,
    optimizer_provider,
    settled_by,
  } = item;

  const KEYS = sortKeys(item, user);
  const currency = user.merchant.currency;

  return (
    <EntityItemRow id={id}>
      {KEYS.map((key, idx) => {
        let row = null;

        switch (key) {
          case `id`:
            row = highlightLink(item, key, idx);
            break;
          case 'amount':
            row = (
              <td key={idx}>
                <Amount value={amount} currency={currency} />
              </td>
            );
            break;
          case 'fee':
            row = (
              <td key={idx}>
                <Amount value={fee} currency={currency} />
              </td>
            );
            break;
          case 'tax':
            row = (
              <td key={idx}>
                <Amount value={tax} currency={currency} />
              </td>
            );
            break;
          case 'created_at':
            row = (
              <td key={idx}>
                <Time value={created_at} format="DD MMM YYYY, hh:mm:ss a" />
              </td>
            );
            break;
          case 'international':
            row = (
              <td key={idx}>
                <span className={`status-label label ${InternationalStatusMap[international]}`}>
                  {BooleanMap[international]}
                </span>
              </td>
            );
            break;
          case 'status':
            if (
              source === 'payment' ||
              source === 'payment_domestic' ||
              source === 'payment_international'
            ) {
              row = (
                <td key={idx}>
                  <PaymentStatusLabel status={status} />
                </td>
              );
            } else if (
              source === 'refund' ||
              source === 'refund_domestic' ||
              source === 'refund_international'
            ) {
              row = (
                <td key={idx}>
                  <RefundStatusLabel status={status} />
                </td>
              );
            } else if (source === 'dispute') {
              row = (
                <td key={idx}>
                  <DisputeStatusLabel status={status} />
                </td>
              );
            } else if (source === 'transfer') {
              row = (
                <td key={idx}>
                  <span className={`status-label label ${TransfersMap[status]}`}>{status}</span>
                </td>
              );
            } else if (source === 'payout') {
              row = (
                <td key={idx}>
                  <span className={`status-label label ${PayoutsMap[status]}`}>{status}</span>
                </td>
              );
            } else if (source === 'ondemand settlement') {
              row = (
                <td key={idx}>
                  <span className={`status-label label ${OndemandMap[status]}`}>{status}</span>
                </td>
              );
            } else {
              row = <td key={idx}>{status}</td>;
            }
            break;
          case 'optimizer_provider':
            row = user?.isSingleReconEnabled && user?.isOptimizerEnabled && (
              <td key={idx}>
                <PaymentOptimizerProvider
                  terminal_id={optimizer_provider}
                  settled_by={settled_by}
                  terminalProviders={terminalProviders}
                  hideExternalLink={true}
                />
              </td>
            );
            break;
          case 'settled_by':
            /**
             * Added for single recon
             * No need to expose it's value on table, it will reflect with 'optimizer_provider' value
             */
            break;
          default:
            row = <td key={idx}>{item[key]}</td>;
        }

        return row;
      })}
    </EntityItemRow>
  );
};

const EntityList = (props) => {
  const [listData, setlistData] = useState(null);
  const [error, seterror] = useState(null);
  const [skip, setskip] = useState(DEFAULT_SKIP);
  const [count, setcount] = useState(DEFAULT_COUNT);
  const formRef = React.createRef();

  // prettier-ignore
  const {
    breakupDetails,
    settlementId,
    showNotification,
    activeTab,
    user,
    terminalProviders,
  } = props;

  // prettier-ignore
  const totalNoOfPayments = breakupDetails?.items?.find((item) => item.component === 'payment')
    ?.count;

  const fetchData = (skipVal, countVal, type) => {
    const tab = sanitizeTabName(type);
    const source = tab === 'ondemand settlement' ? 'settlement.ondemand' : tab;

    return merchantFetch({
      url: `settlements/${settlementId}/transaction_source_details`,
      method: 'POST',
      data: {
        source_type: source,
        skip: skipVal,
        limit: countVal,
      },
    }).catch(({ errors }) => {
      seterror(errors.join(''));
      showNotification({
        type: 'error',
        message: errors.join(''),
      });
    });
  };

  const clear = React.useCallback(() => {
    // Set defaults
    formRef.current.reset();
    setlistData(null);
    seterror(null);

    fetchData(DEFAULT_SKIP, DEFAULT_COUNT, activeTab).then(({ data }) => {
      setlistData(data);
      setskip(DEFAULT_SKIP);
      setcount(DEFAULT_COUNT);
    });
  });

  const submit = (e) => {
    e.preventDefault();
    const searchId = e.target.elements.id.value.trim();
    const countValue = e.target.elements.count.value;
    const tab = sanitizeTabName(activeTab);
    const source = tab === 'ondemand settlement' ? 'settlement.ondemand' : tab;

    setlistData(null);
    seterror(null);

    // If search is on Id, count makes no difference here.
    if (searchId) {
      merchantFetch({
        url: `settlements/${settlementId}/transaction_source_details`,
        method: 'POST',
        data: {
          source_type: source,
          source_id: searchId,
          skip: 0,
          limit: 1,
        },
      })
        .then((res) => {
          /* istanbul ignore else */
          if (res?.data) {
            setlistData(res?.data);
          }
        })
        .catch(({ errors }) => {
          seterror(errors.join(''));
          showNotification({
            type: 'error',
            message: errors.join(''),
          });
        });
      if (searchId.includes('pay_')) {
        const objectName = 'settlement details search';
        const actionName = 'clicked';
        const screen = 'settlement details';
        const properties = {
          searchTerm: searchId,
          totalNoOfPayments,
        };
        handleAnalytics(objectName, actionName, properties, screen);
      }
    } else {
      fetchData(DEFAULT_SKIP, countValue, activeTab).then(({ data }) => {
        setlistData(data);
        setskip(DEFAULT_SKIP);
        setcount(parseInt(countValue, 10));
      });
    }
  };

  // Effect runs whenever active tab is changed
  useEffect(() => {
    // fetch new on tab change. Reset entire form.
    formRef.current.reset();
    // fetch with defaults
    fetchData(DEFAULT_SKIP, DEFAULT_COUNT, activeTab).then((res) => {
      /* istanbul ignore else */
      if (res?.data) {
        setlistData(res.data);
        setskip(DEFAULT_SKIP);
        setcount(DEFAULT_COUNT);
      }
    });
  }, [activeTab]);

  // handles next page click
  const next = React.useCallback(() => {
    const skipValue = skip + count;
    fetchData(skipValue, count, activeTab).then(({ data }) => {
      setlistData(data);
      setskip(skipValue);
    });
  }, [activeTab, count, skip]);

  // handles previous page click
  const prev = React.useCallback(() => {
    const skipValue = skip - count;
    fetchData(skipValue, count, activeTab).then(({ data }) => {
      setlistData(data);
      setskip(skipValue);
    });
  }, [activeTab, count, skip]);

  const renderColumnsHeaders = (list) => {
    if (list.length === 0) return null;

    const KEYS = sortKeys(list[0], user);

    return KEYS.map((key, idx) => {
      if (key === 'settled_by') {
        /**
         * Added for single recon
         * No need to expose this on table, it will reflect with 'optimizer_provider' value
         */
        return null;
      } else if (key === 'optimizer_provider') {
        // For single recon
        key = 'Payment Provider';
      }
      return <th key={idx}>{titleCase(key)}</th>;
    });
  };

  return (
    <div className="content-wrapper">
      <ComponentListFilter
        activeTab={activeTab}
        ref={formRef}
        clear={clear}
        submit={submit}
        count={count}
      />

      {listData ? (
        <React.Fragment>
          <div className="table-responsive">
            <table className="table table-hover">
              <thead>
                <tr>{renderColumnsHeaders(listData)}</tr>
              </thead>
              <TableBody rows={listData} emptyTableMsg={`No ${sanitizeTabName(activeTab)} found`}>
                {listData.map((item) => (
                  <ListItem
                    key={item.id}
                    item={item}
                    source={activeTab.trim()}
                    user={user}
                    terminalProviders={terminalProviders}
                  />
                ))}
              </TableBody>
            </table>
          </div>
          <Pagination next={next} prev={prev} listData={listData} skip={skip} count={count} />
        </React.Fragment>
      ) : error ? null : (
        <div className="div--loading">
          <Spinner />
        </div>
      )}
    </div>
  );
};

const mapStateToProps = (state) => {
  const { session, navigator } = state;
  return {
    user: session.user,
    terminalProviders: navigator.terminalProviders,
  };
};

export default connect(mapStateToProps, { showNotification })(EntityList);
