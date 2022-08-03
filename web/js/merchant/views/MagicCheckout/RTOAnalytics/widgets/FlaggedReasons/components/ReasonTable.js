import { FLAGGEDREASONS_DOUGHNUT_COLORS } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';
import { FLAGGED_RULES_MAP } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/reasons';

const ReasonTable = ({ data }) => {
  return (
    <div className="reason-table-container">
      <table>
        <thead>
          <tr>
            <th>Reason</th>
            <th>Percentage of Risky orders</th>
          </tr>
        </thead>
        <tbody>
          {data.map((reason, index) => (
            <tr key={reason.reason}>
              <td className="reason">{FLAGGED_RULES_MAP[reason.reason]}</td>
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
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default ReasonTable;
