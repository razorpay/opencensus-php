import {
  Box,
  ChevronRightIcon,
  CopyIcon,
  InfoIcon,
  Link,
  Spinner,
  Text,
} from '@razorpay/blade/components';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import { titleCase } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  handleAnalytics,
  propertiesPayload,
} from 'merchant/views/Settlements/Settlements/analytics';
import Pagination from 'merchant/views/Settlements/v2/components/Pagination';
import {
  getSelfServeDetailForSettlementDetails,
  sanitizeTabName,
} from 'merchant/views/Settlements/v2/util';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { showNotification } from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { RouteComponentProps, withRouter } from 'react-router-dom';
// eslint-disable-next-line
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { BreakupDetailsInterface } from 'merchant/views/Settlements/v3/typings';
import { ColumnHeader, StyledSpinner, StyledTd } from './styled';
import { getEntityColumns, getNetValue } from './utils';
import { keys, tooltipConfig } from './constants';

const DEFAULT_SKIP = 0;
const DEFAULT_COUNT = 10;

const ListItem = ({
  item,
  source,
  user,
  terminalProviders,
  history,
  isMobileResolution,
  onIdCopied,
  onItemClick,
  sectionType,
  activeTab,
}) => {
  const { selfServeActionName, page, INIT_POINT, INIT_PAGE } =
    getSelfServeDetailForSettlementDetails(source);

  const selfServeInitiateData = {
    selfServeAction: selfServeActionName,
    screen: 'Settlements',
    page,
    props: {
      initiatePoint: INIT_POINT,
      sessionId: '',
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

  const highlightLink = (rowItem, key, idx, history, isMobileResolution) => {
    if (source === 'payment' || source === 'refund' || source === 'dispute') {
      if (isMobileResolution) {
        return (
          <Link
            variant="button"
            onClick={() => {
              const pathname =
                source === 'payment' || source === 'refund'
                  ? `/${source}s/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`
                  : `/${source}s/${rowItem[key]}`;

              history.push({
                pathname,
                state: { openedFrom: 'settlement-details', settlement_id: rowItem[key] },
              });

              if (['payment', 'refund'].includes(source)) {
                if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
                selfServeTrackInitiate(selfServeInitiateData);
                return analyticsHandler();
              }
              return true;
            }}
            icon={ChevronRightIcon}
            iconPosition="right"
          />
        );
      } else {
        return (
          <Link
            variant="button"
            onClick={() => {
              const pathname =
                source === 'payment' || source === 'refund'
                  ? `/${source}s/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`
                  : `/${source}s/${rowItem[key]}`;

              history.push({
                pathname,
                state: { openedFrom: 'settlement-details', settlement_id: rowItem[key] },
              });

              if (['payment', 'refund'].includes(source)) {
                if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
                selfServeTrackInitiate(selfServeInitiateData);
                return analyticsHandler();
              }
              return true;
            }}
            icon={ChevronRightIcon}
            iconPosition="right"
          >
            Details
          </Link>
        );
      }
    } else if (source === 'transfer' || source === 'reversal') {
      if (isMobileResolution) {
        return (
          <Link
            variant="button"
            icon={ChevronRightIcon}
            iconPosition="right"
            onClick={() => {
              history.push(
                `/route/${source}s/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`,
              );
            }}
          />
        );
      } else {
        return (
          <Link
            variant="button"
            icon={ChevronRightIcon}
            iconPosition="right"
            onClick={() => {
              history.push(
                `/route/${source}s/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`,
              );
            }}
          >
            Details
          </Link>
        );
      }
    } else if (source === 'payment_domestic' || source === 'payment_international') {
      if (isMobileResolution) {
        return (
          <Link
            variant="button"
            onClick={() => {
              history.push(
                `/payments/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`,
              );
              selfServeTrackInitiate(selfServeInitiateData);
            }}
            icon={ChevronRightIcon}
            iconPosition="right"
          />
        );
      } else {
        return (
          <Link
            variant="button"
            onClick={() => {
              history.push(
                `/payments/${rowItem[key]}?init_point=${INIT_POINT}&init_page=${INIT_PAGE}`,
              );
              selfServeTrackInitiate(selfServeInitiateData);
            }}
            icon={ChevronRightIcon}
            iconPosition="right"
          >
            Details
          </Link>
        );
      }
    } else {
      return <td key={idx} />;
    }
  };

  const { id, amount, fee, created_at, optimizer_provider, settled_by } = item;
  const currency = user.merchant.currency;
  const deductions = fee;
  // getting new value (amount or deduction) based on section type
  const netValue = getNetValue({ fee, amount, sectionType, activeTab });

  let columnKeys: Array<string>;

  if (isMobileResolution) {
    columnKeys = keys.filter((key) => {
      if (key === 'date' || key === 'net_value' || key === 'id') return true;
      else return false;
    });
  } else {
    columnKeys = keys;
  }

  return (
    <EntityItemRow id={id}>
      {columnKeys.map((key, idx) => {
        let row: any = null;

        switch (key) {
          case `entity_id`:
            row = (
              <StyledTd key={idx}>
                <Text type="subtle" size="medium">
                  {id}
                </Text>
                <CustomClipboard value={id} onCopy={onIdCopied.bind(null, id)}>
                  <CopyIcon size="medium" color="feedback.icon.neutral.lowContrast" />
                </CustomClipboard>
              </StyledTd>
            );
            break;
          case 'gross_amount':
            row = (
              <td key={idx}>
                <Text type="subtle" size="medium">
                  <Amount value={amount} currency={currency} />
                </Text>
              </td>
            );
            break;
          case 'deductions':
            row = (
              <td key={idx}>
                <Text weight="regular" color="surface.text.subtle.lowContrast" size="medium">
                  <Amount value={deductions} currency={currency} />
                </Text>
              </td>
            );
            break;
          case 'net_value':
            row = (
              <td key={idx}>
                <Text weight="regular" color="surface.text.subtle.lowContrast" size="medium">
                  <Amount value={netValue} currency={currency} />
                </Text>
              </td>
            );
            break;
          case 'date':
            row = (
              <td key={idx}>
                <Text type="subtle">
                  <Time value={created_at} format="DD MMM YYYY, hh:mm:ss a" />
                </Text>
                {isMobileResolution ? (
                  <Text type="subdued" size="small">
                    {id}
                  </Text>
                ) : null}
              </td>
            );
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
          default: {
            row = (
              <td onClick={onItemClick.bind(null, id)}>
                {highlightLink(item, key, idx, history, isMobileResolution)}
              </td>
            );
          }
        }

        return row;
      })}
    </EntityItemRow>
  );
};

type Props = {
  entityType: 'debit' | 'credit';
  breakupDetails: BreakupDetailsInterface;
  activeTab: string;
  settlementId: string;
  sectionType: string;
} & RouteComponentProps;

const EntityList = (props) => {
  const [listData, setlistData] = useState<null | []>(null);
  const [error, seterror] = useState(null);
  const [skip, setskip] = useState(DEFAULT_SKIP);
  const [count, setcount] = useState(DEFAULT_COUNT);

  const { settlementId, showNotification, activeTab, user, terminalProviders, sectionType } = props;

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

  // Effect runs whenever active tab is changed
  useEffect(() => {
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

    const tab = sanitizeTabName(activeTab);

    const entityColumns: any = getEntityColumns({
      tab,
      sectionType,
      isMobile: props.isMobileResolution,
    });

    return entityColumns.map((key, idx) => {
      return (
        <ColumnHeader key={idx}>
          <Box display="flex" gap={{ base: 'spacing.2', m: '5px' }} alignItems="center">
            <Text
              variant="body"
              size="medium"
              color="surface.text.subtle.lowContrast"
              weight="bold"
            >
              {titleCase(key)}
            </Text>
            {key === 'Net amount' || key === 'Net deduction' ? (
              <div>
                <InfoIcon size="small" color="surface.text.subtle.lowContrast" />
                <Popover theme="dark" align="top">
                  <PopoverBody>
                    <div>{tooltipConfig[key]}</div>
                  </PopoverBody>
                </Popover>
              </div>
            ) : null}
          </Box>
        </ColumnHeader>
      );
    });
  };

  const onItemClick = (entityId) => {
    const { settlement } = props;
    const entity = activeTab.split('_')[0].trim();
    const entityType = props.entityType === 'credit' ? 'Gross Settlements' : 'Deductions';

    analyticsTrackWithUserInfo({
      objectName: `${entityType} Details`,
      actionName: `Clicked`,
      screen: 'Settlements',
      properties: {
        page: 'Details View',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
        [`${entity}Id`]: entityId,
        component: `${titleCase(entity)}s`,
      },
    });
  };

  const onIdCopied = (entityId) => {
    const { settlement } = props;
    const entity = activeTab.split('_')[0].trim();
    const entityType = props.entityType === 'credit' ? 'Gross Settlements' : 'Deductions';

    analyticsTrackWithUserInfo({
      objectName: `${entityType} ${entity} ID`,
      actionName: `Copied`,
      screen: 'Settlements',
      properties: {
        page: 'Details View',
        settlements_experiment_name: 'v2',
        settlementId: settlement.id,
        settlementStatus: settlement.status,
        sessionId: window?.session_id ? window.session_id : undefined,
        [`${entity}Id`]: entityId,
        component: `${titleCase(entity)}s`,
      },
    });
  };

  return (
    <div className="content-wrapper" style={{ backgroundColor: '#FFFFFF' }}>
      {listData ? (
        <React.Fragment>
          <div className="table-responsive">
            <table className="table table-hover">
              <thead>
                <tr>{renderColumnsHeaders(listData)}</tr>
              </thead>
              <TableBody rows={listData} emptyTableMsg={`No ${sanitizeTabName(activeTab)} found`}>
                {listData &&
                  listData.map((item: any) => (
                    <ListItem
                      key={item.id}
                      item={item}
                      source={activeTab.split('_')[0].trim()}
                      user={user}
                      terminalProviders={terminalProviders}
                      history={props.history}
                      isMobileResolution={props.isMobileResolution}
                      onIdCopied={onIdCopied}
                      onItemClick={onItemClick}
                      sectionType={sectionType}
                      activeTab={activeTab}
                    />
                  ))}
              </TableBody>
            </table>
          </div>
          <Pagination next={next} prev={prev} listData={listData} skip={skip} count={count} />
        </React.Fragment>
      ) : error ? null : (
        <StyledSpinner>
          <Text>Loading {props.entityType === 'credit' ? 'gross settlements' : 'deductions'}</Text>
          <Spinner accessibilityLabel="Loading" size="medium" />
        </StyledSpinner>
      )}
    </div>
  );
};

const mapStateToProps = (state) => {
  const { session, navigator } = state;
  return {
    user: session.user,
    settlement: state.settlement.settlement,
    terminalProviders: navigator.terminalProviders,
    isMobileResolution: state.app.isMobileResolution,
  };
};

export default withRouter<Props, any>(connect(mapStateToProps, { showNotification })(EntityList));
