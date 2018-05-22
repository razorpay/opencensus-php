import { Fragment } from 'react';

export default ({ stats }) => (
  <div class="stats-info equal-margin">
    <table class="table">
      <tbody>
        {chunk(stats).map(([item0, item1]) => (
          <tr>
            <StatItem {...item0} />
            <StatItem {...item1} />
          </tr>
        ))}
      </tbody>
    </table>
  </div>
);

function StatItem({ title, value }) {
  return (
    <td class="td-info">
      <span class="td-heading">{title}</span>
      <span class="td-value">{value}</span>
    </td>
  );
}

function chunk(array, length = 2) {
  let index = 0,
    result = [];
  while (index < array.length) {
    result.push(array.slice(index, (index += length)));
  }
  return result;
}
