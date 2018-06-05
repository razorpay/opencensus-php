import { Fragment } from 'react';

export default ({ stats }) => (
  <div class="stats-info">
    <table class="table">
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
    <td class="td-info" colSpan={colSpan}>
      <span class="td-heading">{title}</span>
      <span class="td-value">{value}</span>
    </td>
  );
}
