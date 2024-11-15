import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import LoaderDots from 'common/ui/LoaderDots';

import { PaymentStatusLabel, SettlementStatusLabel } from 'merchant/components/StatusLabel';

import { titleCase, formatFromNow } from 'common/utils/rzp-utils';

import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';

const StatusLabel = ({ status, entity, children, ...otherProps }) => {
  let Label = entity === 'settlement' ? SettlementStatusLabel : PaymentStatusLabel;
  status = status || 'refunded';

  return (
    <Label status={status} {...otherProps}>
      {children}
    </Label>
  );
};

export default ({ entity, data, loading, onSeeAll = () => {}, onOpenDetails = () => {} }) => {
  let items = data.items;
  return (
    <div class="col-md-4 col-sm-6 col-xs-12">
      <div class="WidgetContainer">
        <div class="panel">
          <div class="panel-body">
            <Link
              data-tip={`See All ${titleCase(entity)}s`}
              class="pull-right"
              to={`/${entity}s`}
              onClick={onSeeAll}
            >
              <i class="i i-arrow-forward" />
            </Link>

            <h4>Recent {titleCase(entity)}s</h4>
            {loading ? (
              <div class="centered">
                <LoaderDots />
              </div>
            ) : items.length ? (
              <div class="table-responsive EntityTable">
                <Table data={{ nodes: items.slice(0, 5) }}>
                  {(tableData) => (
                    <>
                      <TableBody>
                        {tableData.map((item, index) => (
                          <TableRow key={index} item={item}>
                            <TableCell>
                              <Link to={`/${entity}s/${item.id}`} onClick={onOpenDetails}>
                                <code>{item.id}</code>
                                <div class="text-muted font-xs">
                                  {formatFromNow(item.created_at)}
                                </div>
                              </Link>
                            </TableCell>
                            <TableCell>
                              <StatusLabel
                                entity={entity}
                                status={item.status}
                                data-tip={titleCase(item.status) || null}
                                data-place="bottom"
                              >
                                <Amount value={item.amount} currency={item.currency} />
                              </StatusLabel>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </>
                  )}
                </Table>
              </div>
            ) : (
              <h5 style={{ marginTop: '30px' }}>No Recent {titleCase(entity)}s</h5>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
