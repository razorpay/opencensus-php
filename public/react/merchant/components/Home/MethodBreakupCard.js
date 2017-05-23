import { titleCase, colors } from 'rzp/utils/rzp-utils';

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
    <div
      class="col wrapper"
      style={{
        display: 'table-cell',
        float: 'none',
        height: '100%',
        verticalAlign: 'top',
        backgroundColor: '#e4eaec',
        width: '280px',
        borderRadius: '0 2px 2px 0',
        padding: '30px',
        color: '#58666e',
      }}
    >
      <h4 class="font-thin">Transaction Types</h4>
      {loading || error
        ? <div
            class={`text-thin ${loading ? 'h1' : 'text-danger'}`}
            style={{
              height: '280px',
              textAlign: 'center',
              lineHeight: '280px',
            }}
          >
            {loading ? '...' : 'Error occurred in loading data'}
          </div>
        : methodBreakup.map((methodData, index) => {
            return (
              <div key={index}>
                {methodData
                  ? <div>
                      <div class="text-center-folded">
                        <span class="pull-right">{methodData.value}</span>
                        <span>{titleCase(methodData.title)}</span>
                      </div>
                      <div class="progress-xs bg-white progress">
                        <div
                          class={'progress-bar progress-bar-' + methodData.bg}
                          role="progressbar"
                          style={{ width: methodData.value }}
                        />
                      </div>
                    </div>
                  : 'No Data'}
              </div>
            );
          })}
    </div>
  );
};
