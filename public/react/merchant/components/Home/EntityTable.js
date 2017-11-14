import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import LoaderDots from 'rzp/ui/LoaderDots';

import {
  PaymentStatusLabel,
  SettlementStatusLabel,
} from 'merchant/components/StatusLabel';

import { titleCase, formatFromNow } from 'rzp/utils/rzp-utils';

const StatusLabel = ({ status, entity, children, ...otherProps }) => {
  let Label =
    entity === 'settlement' ? SettlementStatusLabel : PaymentStatusLabel;
  status = status || 'refunded';

  return (
    <Label status={status} {...otherProps}>
      {children}
    </Label>
  );
};

export default ({ entity, data, loading }) => {
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
            >
              <i class="icon icon-arrow-forward" />
            </Link>

            <h4>Recent {titleCase(entity)}s</h4>
            {
              do {
                if (loading) {
                  <div class="centered">
                    <LoaderDots />
                  </div>;
                } else if (items.length) {
                  <div class="table-responsive EntityTable">
                    <table class="table table-hover table-noborder">
                      <tbody>
                        {items.slice(0, 5).map((item, index) => {
                          return (
                            <tr key={index}>
                              <td>
                                <Link to={`/${entity}s/${item.id}`}>
                                  <code>{item.id}</code>
                                  <div class="text-muted font-xs">
                                    {formatFromNow(item.created_at)}
                                  </div>
                                </Link>
                              </td>
                              <td>
                                <StatusLabel
                                  entity={entity}
                                  status={item.status}
                                  data-tip={titleCase(item.status) || null}
                                  data-place="bottom"
                                >
                                  <Amount
                                    value={item.amount}
                                    currency={item.currency}
                                  />
                                </StatusLabel>
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    </table>
                  </div>;
                } else {
                  <h5 style={{ marginTop: '30px' }}>
                    No Recent {titleCase(entity)}s
                  </h5>;
                }
              }
            }
          </div>
        </div>
      </div>
    </div>
  );
};
