import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import { sanitizeTabName } from '../util';
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
import { handleAnalytics, propertiesPayload } from '../../Settlements/analytics';

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

const ListItem = ({ item, source }) => {
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
              if (source === 'payment') return analyticsHandler();
              return true;
            }}
            to={`/${source}s/${rowItem[key]}`}
          >
            {rowItem.id}
          </Link>
        </td>
      );
    } else if (source === 'transfer' || source === 'reversal') {
      return (
        <td key={idx}>
          <Link to={`/route/${source}s/${rowItem[key]}`}>{rowItem.id}</Link>
        </td>
      );
    } else if (source === 'payment_domestic' || source === 'payment_international') {
      return (
        <td key={idx}>
          <Link to={`/payments/${rowItem[key]}`}>{rowItem.id}</Link>
        </td>
      );
    } else {
      return <td key={idx}>{rowItem.id}</td>;
    }
  };

  return (
    <EntityItemRow id={item.id}>
      {Object.keys(item).map((key, idx) => {
        let row = null;

        switch (key) {
          case `id`:
            row = highlightLink(item, key, idx);
            break;
          case 'amount':
            row = (
              <td key={idx}>
                <Amount value={item.amount} currency="INR" />
              </td>
            );
            break;
          case 'fee':
            row = (
              <td key={idx}>
                <Amount value={item.fee} currency="INR" />
              </td>
            );
            break;
          case 'tax':
            row = (
              <td key={idx}>
                <Amount value={item.tax} currency="INR" />
              </td>
            );
            break;
          case 'created_at':
            row = (
              <td key={idx}>
                <Time value={item.created_at} format="DD MMM YYYY, hh:mm:ss a" />
              </td>
            );
            break;
          case 'international':
            row = (
              <td key={idx}>
                <span class={`status-label label ${InternationalStatusMap[item.international]}`}>
                  {BooleanMap[item.international]}
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
                  <PaymentStatusLabel status={item.status} />
                </td>
              );
            } else if (
              source === 'refund' ||
              source === 'refund_domestic' ||
              source === 'refund_international'
            ) {
              row = (
                <td key={idx}>
                  <RefundStatusLabel status={item.status} />
                </td>
              );
            } else if (source === 'dispute') {
              row = (
                <td key={idx}>
                  <DisputeStatusLabel status={item.status} />
                </td>
              );
            } else if (source === 'transfer') {
              row = (
                <td key={idx}>
                  <span class={`status-label label ${TransfersMap[item.status]}`}>
                    {item.status}
                  </span>
                </td>
              );
            } else if (source === 'payout') {
              row = (
                <td key={idx}>
                  <span class={`status-label label ${PayoutsMap[item.status]}`}>{item.status}</span>
                </td>
              );
            } else if (source === 'ondemand settlement') {
              row = (
                <td key={idx}>
                  <span class={`status-label label ${OndemandMap[item.status]}`}>
                    {item.status}
                  </span>
                </td>
              );
            } else {
              row = <td key={idx}>{item.status}</td>;
            }
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

  const totalNoOfPayments = props.breakupDetails?.items?.find(
    (item) => item.component === 'payment',
  )?.count;

  const fetchData = (skipVal, countVal, type) => {
    const tab = sanitizeTabName(type);
    const source = tab === 'ondemand settlement' ? 'settlement.ondemand' : tab;

    return merchantFetch({
      url: `settlements/${props.settlementId}/transaction_source_details`,
      method: 'POST',
      data: {
        source_type: source,
        skip: skipVal,
        limit: countVal,
      },
    }).catch(({ errors }) => {
      seterror(errors.join(''));
      props.showNotification({
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

    fetchData(DEFAULT_SKIP, DEFAULT_COUNT, props.activeTab).then(({ data }) => {
      setlistData(data);
      setskip(DEFAULT_SKIP);
      setcount(DEFAULT_COUNT);
    });
  });

  const submit = (e) => {
    e.preventDefault();
    const searchId = e.target.elements.id.value.trim();
    const countValue = e.target.elements.count.value;
    const tab = sanitizeTabName(props.activeTab);
    const source = tab === 'ondemand settlement' ? 'settlement.ondemand' : tab;

    setlistData(null);
    seterror(null);

    // If search is on Id, count makes no difference here.
    if (searchId) {
      merchantFetch({
        url: `settlements/${props.settlementId}/transaction_source_details`,
        method: 'POST',
        data: {
          source_type: source,
          source_id: searchId,
          skip: 0,
          limit: 1,
        },
      })
        .then(({ data }) => {
          setlistData(data);
        })
        .catch(({ errors }) => {
          seterror(errors.join(''));
          props.showNotification({
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
      fetchData(DEFAULT_SKIP, countValue, props.activeTab).then(({ data }) => {
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
    fetchData(DEFAULT_SKIP, DEFAULT_COUNT, props.activeTab).then(({ data }) => {
      setlistData(data);
      setskip(DEFAULT_SKIP);
      setcount(DEFAULT_COUNT);
    });
  }, [props.activeTab]);

  // handles next page click
  const next = React.useCallback(() => {
    const skipValue = skip + count;
    fetchData(skipValue, count, props.activeTab).then(({ data }) => {
      setlistData(data);
      setskip(skipValue);
    });
  }, [props.activeTab, count, skip]);

  // handles previous page click
  const prev = React.useCallback(() => {
    const skipValue = skip - count;
    fetchData(skipValue, count, props.activeTab).then(({ data }) => {
      setlistData(data);
      setskip(skipValue);
    });
  }, [props.activeTab, count, skip]);

  const renderColumnsHeaders = (list) => {
    if (list.length === 0) return null;

    return Object.keys(list[0]).map((key, idx) => {
      return <th key={idx}>{titleCase(key)}</th>;
    });
  };

  return (
    <div class="content-wrapper">
      <ComponentListFilter
        activeTab={props.activeTab}
        ref={formRef}
        clear={clear}
        submit={submit}
        count={count}
      />

      {listData ? (
        <React.Fragment>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>{renderColumnsHeaders(listData)}</tr>
              </thead>
              <TableBody
                rows={listData}
                emptyTableMsg={`No ${sanitizeTabName(props.activeTab)} found`}
              >
                {listData.map((item) => (
                  <ListItem key={item.id} item={item} source={props.activeTab.trim()} />
                ))}
              </TableBody>
            </table>
          </div>
          <Pagination next={next} prev={prev} listData={listData} skip={skip} count={count} />
        </React.Fragment>
      ) : error ? null : (
        <div class="div--loading">
          <Spinner />
        </div>
      )}
    </div>
  );
};

export default connect(null, { showNotification })(EntityList);
