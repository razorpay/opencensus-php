import React, { useEffect, useState } from 'react';

export default ({ line_items }) => {
  const [isSkuCollapsed, setSkuCollapsed] = useState(false);
  const [skuList, setSkuList] = useState([]);

  /**
   * Updates the sku list rows which has to be rendered.
   * If collapsed -> Create array of arrays with 3 SKUs in each subarray
   * Else -> Create arrary with one subarray with first 3 SKUs
   */
  useEffect(() => {
    let list = [];
    if (isSkuCollapsed) {
      for (let index = 0; index < line_items.length; index += 3) {
        list.push(line_items.slice(index, index + 3));
      }
    } else {
      list = [line_items.slice(0, 3)];
    }
    setSkuList(list);
  }, [isSkuCollapsed, line_items]);

  return (
    <div class="sku-details-container">
      <div class="magic-checkout-sku-list">
        {skuList.length &&
          skuList.map((row, index) => (
            <div key={index}>{row.map((item) => item.sku).join(',')}</div>
          ))}
      </div>
      {line_items.length > 3 && (
        <button
          type="button"
          class="magic-checkout-collapse-btn"
          onClick={(_) => setSkuCollapsed((visible) => !visible)}
        >
          {isSkuCollapsed ? (
            <span>
              <i class="i i-chevron-up" />
            </span>
          ) : (
            <span>
              <i class="i i-chevron-down" />
            </span>
          )}
        </button>
      )}
    </div>
  );
};
