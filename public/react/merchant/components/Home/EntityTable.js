import Amount from "rzp/ui/Amount";
import {
  titleCase,
  paymentStausColor,
  formatFromNow,
} from "rzp/utils/rzp-utils";

export default ({ entity, data }) => {
  return (
    <div className="col-md-4 b-r b-light no-border-xs">
      <a
        data-tip="See All Payments"
        className="pull-right"
        href="#/app/payments/list"
      >
        <i className="icon-arrow-right" />
      </a>
      <h4 style={{ margin: "0 0 10px" }}>Recent {titleCase(entity)}s</h4>
      {data.count
        ? data.items.slice(0, 5).map((item, index) => {
            return (
              <div key={index} className="row" style={{ margin: "10px" }}>
                <a href={`#/app/payments/${item.id}`}>
                  <div
                    className={
                      "col-xs-4 col-md-3 label bg-" +
                        (paymentStausColor[item.status] || "light")
                    }
                    data-tip={titleCase(item.status)}
                    data-place="right"
                  >
                    <Amount value={item.amount} />
                  </div>
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
