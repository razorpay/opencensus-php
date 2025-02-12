import { titleCase, colors } from 'common/utils/rzp-utils';
import LoaderDots from 'common/ui/LoaderDots';

export default ({ data, loading, error }) => {
  let methodBreakup = [null];
  const methods = ['CARD', 'EMI', 'NETBANKING', 'WALLET', 'UPI', 'EMANDATE', 'AEPS'].filter(
    (method) => data[method],
  );
  const total = methods.reduce((prev, cur) => {
    return prev + data[cur];
  }, 0);

  if (total) {
    methodBreakup = methods.map((method, index) => {
      return {
        bg: colors[index],
        title: titleCase(method),
        value: ((100 * data[method]) / total).toFixed(1).replace('.0', '') + '%',
      };
    });
  }

  const renderContent = () => {
    if (loading) {
      return (
        <div className="centered">
          <LoaderDots />
        </div>
      );
    }

    if (error) {
      return (
        <div className="centered">
          <div className="text-danger">Error occurred in loading data!</div>
        </div>
      );
    }

    return methodBreakup.map((methodData, index) => (
      <div key={index}>
        {methodData ? (
          <div>
            <div>
              <small className="pull-right">{methodData.value}</small>
              <small>{titleCase(methodData.title)}</small>
            </div>
            <div className="progress-xs progress">
              <div
                className={'progress-bar progress-bar-' + methodData.bg}
                style={{ width: methodData.value }}
              />
            </div>
          </div>
        ) : (
          'No Data'
        )}
      </div>
    ));
  };

  return (
    <div className="Transaction__Types WidgetContainer">
      <div className="panel">
        <div className="panel-body">
          <h4 className="font-thin">Transaction Types</h4>
          {renderContent()}
        </div>
      </div>
    </div>
  );
};
