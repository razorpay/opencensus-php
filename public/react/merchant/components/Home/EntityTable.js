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

export default ({ entity, data }) => {
  return (
    <div className="col-md-4 b-light no-border-xs">
      <a
        data-tip="See All Payments"
        className="pull-right"
        href={`#/app/${entity}s/list`}
      >
        <i className="icon-arrow-right" />
      </a>
      <h4 style={{ margin: '0 0 10px' }}>Recent {titleCase(entity)}s</h4>
      {data.count
        ? data.items.slice(0, 5).map((item, index) => {
            return (
              <div key={index} className="row" style={{ margin: '10px' }}>
                <a href={`#/app/${entity}/${item.id}`}>
                  <StatusLabel
                    entity={entity}
                    status={item.status}
                    data-tip={titleCase(item.status) || null}
                    data-place="right"
                  >
                    <Amount value={item.amount} />
                  </StatusLabel>

                  <div className="col-xs-8 col-md-9">
                    <code className="hidden-xs">{item.id}</code>
                    <span className="pull-right">
                      {formatFromNow(item.created_at)}
                    </span>
                  </div>
                </a>
              </div>
            );
          })
        : <div>No Recent Payments</div>}
    </div>
  );
};
