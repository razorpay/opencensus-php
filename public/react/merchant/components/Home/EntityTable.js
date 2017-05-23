import Amount from 'rzp/ui/Amount';
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
      <a
        data-tip={`See All ${titleCase(entity)}s`}
        class="pull-right"
        href={`#/app/${entity}s/list`}
      >
        <i class="icon-arrow-right" />
      </a>
      <h4 style={{ margin: '0 0 10px' }}>Recent {titleCase(entity)}s</h4>
      {
        do {
          if (loading) {
            <div
              class="text-thin h1"
              style={{
                height: '165px',
                textAlign: 'center',
                lineHeight: '165px',
              }}
            >
              ...
            </div>;
          } else if (data.count) {
            items.slice(0, 5).map((item, index) => {
              return (
                <div key={index} class="row" style={{ margin: '10px' }}>
                  <a href={`#/app/${entity}/${item.id}`}>
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
                  </a>
                </div>
              );
            });
          } else {
            <div>No Recent {titleCase(entity)}</div>;
          }
        }
      }
    </div>
  );
};
