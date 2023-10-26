import React from 'react';
import styled from 'styled-components';

import { RemoveIcon } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

export const collectionName = {
  title: 'Collection name',
  value: (item: { title: any }) => {
    return item?.title || '';
  },
  columnClass: 'text-left',
};

export const productCount = {
  title: 'Product count',
  columnClass: 'text-left',
  value: (item: { products_count: number }) => item?.products_count,
};

export const collectionAction = (widgetsData, setWidgetsData, stateObject) => ({
  title: 'Action',
  columnClass: 'text-center',
  value: (item: { id: any }) => {
    return (
      <span
        onClick={() => {
          setWidgetsData({
            ...widgetsData,
            [stateObject]: {
              ...widgetsData[stateObject],
              discountedItemsDisplayList: widgetsData[
                stateObject
              ].discountedItemsDisplayList.filter(
                (prevItem: { id: any }) => prevItem.id !== item.id,
              ),
            },
          });
        }}
      >
        <RemoveIcon className="i-close" />
      </span>
    );
  },
});

export const TableWrapper = styled.div`
  table {
    border: 1px solid #97979747;
  }

  tr,
  tr:hover {
    background: #fff;
  }
`;
