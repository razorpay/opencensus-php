import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import LoaderDots from 'rzp/ui/LoaderDots';

import {
  PaymentStatusLabel,
  SettlementStatusLabel,
} from 'merchant/components/StatusLabel';

import { titleCase, formatFromNow } from 'rzp/utils/rzp-utils';

const StatusLabel = ({ status, entity, children, ...otherProps }) => {
  let Label = entity === 'settlement'
    ? SettlementStatusLabel
    : PaymentStatusLabel;
  status = status || 'refunded';

  return (
    <Label status={status} {...otherProps}>
      {children}
    </Label>
  );
};

export default ({ entity, data, loading }) => {
  let items = data[`${entity}s`];
  return (
    <div class="col-md-4">
      <div class="WidgetContainer">
        <div class="panel">
          <div class="panel-body">
            <Link
              data-tip={`See All ${titleCase(entity)}s`}
              class="pull-right"
              to={`/${entity}s/`}
            >
              <i class="icon icon-arrow-forward" />
            </Link>

            <h4>Recent {titleCase(entity)}s</h4>
            {
              do {
                if (loading) {
                  <div class="centered"><LoaderDots /></div>;
                } else if (data.count) {
                  items.slice(0, 5).map((item, index) => {
                    return (
                      <div key={index} class="row" style={{ margin: '10px' }}>
                        <Link to={`/${entity}s/${item.id}`}>
                          <StatusLabel
                            entity={entity}
                            status={item.status}
                            data-tip={titleCase(item.status) || null}
                            data-place="right"
                          >
                            <Amount value={item.amount} />
                          </StatusLabel>

                          <div class="col-xs-8 col-md-9">
                            <code class="hidden-xs">{item.id}</code>
                            <span class="pull-right">
                              {formatFromNow(item.created_at)}
                            </span>
                          </div>
                        </Link>
                      </div>
                    );
                  });
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
