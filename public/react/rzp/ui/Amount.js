export default ({ value, currency, ...attrs }) => {
  return (
    // following regex formats in indian comma separated, i.e. 2,01,20,45,222.66
    (
      <span {...attrs}>
        ₹
        {' '}
        {(value / 100)
          .toFixed(2)
          .replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,')
          .replace('.00', '')}
      </span>
    )
  );
};
