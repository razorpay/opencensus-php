import isEmpty from 'lodash/isEmpty';
import React, { useContext, useEffect, useState } from 'react';

// UI imports
import Loader from 'common/ui/Loader';
import SearchItem from 'merchant/views/MagicCheckout/ShopifyOrderEditing/OrderEditingModal/components/SearchItem';
import {
  AddItemContainer,
  CtaContainer,
  ProductList,
  SearchBox,
  SearchInput,
  Title,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/styled';

// Util/constant imports
import { ORDER_EDITING_SUBTABS } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';
import { ModalContext } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/context';

// Type imports
import { ShowNotificationType } from 'common/typings';

// API imports
import {
  addNewLineItem,
  searchLineItems,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/api';

interface AddItemsProps {
  showNotification: ShowNotificationType;
}

const AddItems: React.FC<AddItemsProps> = ({ showNotification }) => {
  const [isLoading, setIsLoading] = useState(false);
  const [data, setData] = useState<any[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [itemsObject, setItemsObject] = useState<any>({});

  const { setView, edit_id, setOrder } = useContext(ModalContext);

  useEffect(() => {
    setData([]);
    setPage(1);
  }, [searchTerm]);

  const fetchData = async () => {
    try {
      setIsLoading(true);
      const limit = 10;
      const offset = (page - 1) * limit;
      const response = await searchLineItems(limit, offset, searchTerm);

      const formattedResponse = formatResponse(response.data.products);

      setData((prev) => [...prev, ...formattedResponse]);
      setHasMore(response.data.products.length > 0 && response.data.products.length === limit);
      setIsLoading(false);
    } catch (error: any) {
      showNotification({
        type: 'error',
        message: 'Something went wrong',
      });
    }
  };

  const handleScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const { scrollTop, clientHeight, scrollHeight } = e.currentTarget;

    if (scrollHeight - scrollTop === clientHeight && !isLoading && hasMore) {
      setPage((prevPage) => prevPage + 1);
    }
  };

  const handleReset = () => {
    setView(ORDER_EDITING_SUBTABS.DEFAULT);
  };

  const handleSubmit = async () => {
    setIsLoading(true);

    try {
      const transformedData = Object.entries(itemsObject).map(([variant_id, quantity]) => ({
        variant_id,
        quantity,
      }));

      const promises = transformedData.map((payload) => addNewLineItem(edit_id, payload));
      const promiseResponses = await Promise.allSettled(promises);

      const successfulOrders: any = [];

      promiseResponses.forEach((response) => {
        if (response.status === 'fulfilled') {
          successfulOrders.push(response.value.data.edited_order);
          showNotification({
            type: 'success',
            message: 'Product added successfully',
          });
        } else {
          showNotification({
            type: 'error',
            message: response.reason.errors || 'Something went wrong',
          });
        }
      });

      // This is needed because in the backend they are adding the line items in a parallel fashion, and sometimes whats happening is that some particular line item is taking longer to update and I am thhough waiting for the all the line items to be updated before updating the order, so in that case the order is getting updated with the old line items and not the new ones. So to fix this I am getting the order with the maximum line items and updating the order with that. Ideally this case should have been handled in the backend, but we mutually agreed that we will not be making any changes in the backend, because we are hitting the external shopify api and thus handling the case on FE
      const updatedOrder = getMaxLineItemsEntry(successfulOrders);

      setOrder(updatedOrder);
      showNotification({
        type: 'success',
        message: 'Product added successfully',
      });
      handleReset();
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Something went wrong',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const onSelectItem = (payload: any) => {
    setItemsObject((prev) => {
      const newItemsObject = { ...prev };

      if (payload.quantity > 0) {
        newItemsObject[payload.variant_id] = payload.quantity;
      } else {
        delete newItemsObject[payload.variant_id];
      }

      return newItemsObject;
    });
  };

  useEffect(() => {
    if (searchTerm === '') return;
    const debounceTimer = setTimeout(() => {
      fetchData();
    }, 1000);

    // eslint-disable-next-line consistent-return
    return () => {
      clearTimeout(debounceTimer);
    };
  }, [searchTerm, page]);

  return (
    <AddItemContainer>
      <Title>Add Product</Title>
      <SearchBox>
        <SearchInput
          type="text"
          placeholder="Search or add products"
          value={searchTerm}
          onChange={(e) => {
            setSearchTerm(e.target.value);
          }}
        />
      </SearchBox>

      <ProductList className={`${data.length > 3 ? 'scroll' : ''}`} onScroll={handleScroll}>
        {data.map((item) => (
          <SearchItem data={item} key={item.variant_id} onSelectItem={onSelectItem} />
        ))}

        {isLoading && <Loader />}
      </ProductList>
      <CtaContainer>
        <button className="secondary-cta" onClick={handleReset}>
          Cancel
        </button>
        <button
          className="primary-cta"
          onClick={handleSubmit}
          disabled={isLoading || isEmpty(itemsObject)}
        >
          Add items
        </button>
      </CtaContainer>
    </AddItemContainer>
  );
};

// this is needed because currently the api returns products and its variants separately
function formatResponse(products) {
  return products.flatMap((product) => {
    const { variants, ...others } = product;

    return variants.map((variant) => ({
      variant_id: variant.id,
      variant_image_url: variant.image_url,
      variant_sku: variant.sku,
      variant_title: variant.title,
      variant_inventory: variant.inventory,
      variant_price: variant.price,
      ...others,
    }));
  });
}

function getMaxLineItemsEntry(entries) {
  let maxLineItemsEntry = null;
  let maxLineItemsCount = 0;

  for (let i = 0; i < entries.length; i++) {
    const entry = entries[i];
    const lineItemsCount = entry.line_items.length;

    if (lineItemsCount > maxLineItemsCount) {
      maxLineItemsEntry = entry;
      maxLineItemsCount = lineItemsCount;
    }
  }

  return maxLineItemsEntry;
}

export default AddItems;
