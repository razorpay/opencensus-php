import React from 'react';

import Legend, {
  LegendItem,
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'rzp/ui/Legend';

export default ({
  data,
  alignment = 'horizontal',
  valueTransformer = null,
}) => {
  if (!Array.isArray(data) || data.length === 0) {
    return null;
  }

  const total = data.reduce((sum, item) => {
    return sum + item.value;
  }, 0);

  if (!total) {
    return null;
  }

  return (
    <Legend alignment={alignment}>
      {data.map((item, key) => {
        return (
          <LegendItem key={key}>
            <LegendLabel color={item.color}>
              {(item.value / total * 100).toFixed(2)}%
            </LegendLabel>
            <LegendTitle>{item.label}</LegendTitle>
            <LegendContent>
              {typeof valueTransformer === 'function'
                ? valueTransformer(item.value)
                : item.value}
            </LegendContent>
          </LegendItem>
        );
      })}
    </Legend>
  );
};
