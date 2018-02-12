import { titleCase, colors } from 'rzp/utils/rzp-utils';
import LoaderDots from 'rzp/ui/LoaderDots';

export default ({ data, loading, error }) => {
  let methodBreakup = [null];
  const methods = ['CARD', 'EMI', 'NETBANKING', 'WALLET', 'UPI'].filter(
    method => data[method]
  );
  const total = methods.reduce((prev, cur) => {
    return prev + data[cur];
  }, 0);

  if (total) {
    methodBreakup = methods.map((method, index) => {
      return {
        bg: colors[index],
        title: titleCase(method),
        value: (100 * data[method] / total).toFixed(1).replace('.0', '') + '%',
      };
    });
  }

  return (
    <div class="Transaction__Types WidgetContainer">
      <div class="panel">
        <div class="panel-body">
          <h4 class="font-thin">Transaction Types</h4>
          {
            do {
              if (loading) {
                <div class="centered">
                  <LoaderDots />
                </div>;
              } else if (error) {
                <div class="centered">
                  <div class="text-danger">Error occurred in loading data!</div>
                </div>;
              } else {
                methodBreakup.map((methodData, index) => {
                  return (
                    <div key={index}>
                      {methodData ? (
                        <div>
                          <div>
                            <small class="pull-right">{methodData.value}</small>
                            <small>{titleCase(methodData.title)}</small>
                          </div>
                          <div class="progress-xs progress">
                            <div
                              class={
                                'progress-bar progress-bar-' + methodData.bg
                              }
                              style={{ width: methodData.value }}
                            />
                          </div>
                        </div>
                      ) : (
                        'No Data'
                      )}
                    </div>
                  );
                });
              }
            }
          }
        </div>
      </div>
    </div>
  );
};
