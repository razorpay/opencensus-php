import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import LoaderDots from 'common/ui/LoaderDots';

import { PaymentStatusLabel, SettlementStatusLabel } from 'merchant/components/StatusLabel';

import { titleCase, formatFromNow } from 'common/utils/rzp-utils';

const StatusLabel = ({ status, entity, children, ...otherProps }) => {
  const Label = entity === 'settlement' ? SettlementStatusLabel : PaymentStatusLabel;
  status = status || 'reversed';

  return (
    <Label status={status} {...otherProps}>
      {children}
    </Label>
  );
};

export default ({ entity, data, loading, onSeeAll = () => {}, onOpenDetails = () => {} }) => {
  const items = data.items;

  const renderContent = () => {
    if (loading) {
      return (
        <div className="centered">
          <LoaderDots />
        </div>
      );
    }

    if (items.length) {
      return (
        <div className="table-responsive EntityTable">
          <table className="table table-hover table-noborder">
            <tbody>
              {items.slice(0, 5).map((item, index) => (
                <tr key={index}>
                  <td>
                    <Link to={`/${entity}s/${item.id}`} onClick={onOpenDetails}>
                      <code>{item.id}</code>
                      <div className="text-muted font-xs">{formatFromNow(item.created_at)}</div>
                    </Link>
                  </td>
                  <td>
                    <StatusLabel
                      entity={entity}
                      status={item.status}
                      data-tip={titleCase(item.status) || null}
                      data-place="bottom"
                    >
                      <Amount value={item.amount} currency={item.currency} />
                    </StatusLabel>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      );
    }

    return <h5 style={{ marginTop: '30px' }}>No Recent {titleCase(entity)}s</h5>;
  };

  return (
    <div className="col-md-4 col-sm-6 col-xs-12">
      <div className="WidgetContainer">
        <div className="panel">
          <div className="panel-body">
            <Link
              data-tip={`See All ${titleCase(entity)}s`}
              className="pull-right"
              to={`/${entity}s`}
              onClick={onSeeAll}
            >
              <i className="i i-arrow-forward" />
            </Link>

            <h4>Recent {titleCase(entity)}s</h4>
            {renderContent()}
          </div>
        </div>
      </div>
    </div>
  );
};
