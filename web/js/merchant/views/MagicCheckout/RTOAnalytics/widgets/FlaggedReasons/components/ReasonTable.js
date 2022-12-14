import { FLAGGEDREASONS_DOUGHNUT_COLORS } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import { FLAGGED_RULES_MAP } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/reasons';

const ReasonTable = ({ data, columns }) => {
  const otherReasonsPercentage = data.slice(4)?.reduce((acc, reason) => {
    return acc + reason.percentage;
  }, 0);

  return (
    <div className="reason-table-container">
      <table>
        <thead>
          <tr>
            {columns.map((col) => (
              <th key={col}>{col}</th>
            ))}
          </tr>
        </thead>
        <tbody>
          {data.map((reason, index) => (
            <>
              {index === 4 && (
                <tr key="all other reasons">
                  <td className="reason">All other reasons</td>
                  <td className="percent">
                    <div className="percent-bar-container">
                      <div className="percent-bar">
                        <div
                          style={{
                            backgroundColor: `${
                              FLAGGEDREASONS_DOUGHNUT_COLORS[index <= 4 ? index : 4]
                            }`,
                            width: `${reason.percentage}%`,
                          }}
                          className="colored-percent"
                        />
                      </div>
                      <span>
                        {typeof otherReasonsPercentage === 'number'
                          ? `${otherReasonsPercentage?.toFixed(2)}%`
                          : 'NA'}{' '}
                      </span>
                    </div>
                  </td>
                </tr>
              )}
              <tr key={reason.reason}>
                <td className="reason">{FLAGGED_RULES_MAP[reason.reason] || reason.reason}</td>
                <td className="percent">
                  <div className="percent-bar-container">
                    <div className="percent-bar">
                      <div
                        style={{
                          backgroundColor: `${
                            FLAGGEDREASONS_DOUGHNUT_COLORS[index <= 4 ? index : 4]
                          }`,
                          width: `${reason.percentage}%`,
                        }}
                        className="colored-percent"
                      />
                    </div>
                    <span>{reason.percentage}%</span>
                  </div>
                </td>
              </tr>
            </>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default ReasonTable;
