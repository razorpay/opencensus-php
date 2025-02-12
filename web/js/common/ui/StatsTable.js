export default ({ stats }) => (
  <div className="stats-info">
    <table className="table">
      <tbody>
        {stats.map((items, statIdx) => (
          <tr key={statIdx}>
            {items.map((item, itemIdx) => (
              <StatItem key={`${statIdx}-${itemIdx}`} {...item} />
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);

function StatItem({ title, value, colSpan = 1 }) {
  return (
    <td className="td-info" colSpan={colSpan}>
      <span className="td-heading">{title}</span>
      <span className="td-value">{value}</span>
    </td>
  );
}
