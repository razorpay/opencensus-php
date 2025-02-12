import styled from 'styled-components';

import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import CatalogStatusLabel from 'merchant/views/PaymentPages/common/Products/CatalogStatusLabel';
import { analyticsTrack } from 'common/utils/analytics';
import { decodeHTMLEntities, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { shouldShowStrikethrough } from '../common/Products/utils';

const getQuantityInStock = ({ units, status }) => {
  if (status === 'unlimited') {
    return '';
  } else if (status === 'out_of_stock') {
    return <span className="text-danger">0 pieces</span>;
  } else {
    return (
      <span>
        {units} piece{units === 1 ? '' : 's'}
      </span>
    );
  }
};

const StyledList = styled.div`
  table > tbody > tr > td {
    vertical-align: middle;
  }
`;

const StyledStatusLabel = styled(CatalogStatusLabel)`
  min-width: 84px;
`;

export default ({ products, loading, handleEditProduct }) => {
  const onProductNameClick = (item) => {
    handleEditProduct(item);

    analyticsTrack({
      objectName: 'specific product',
      actionName: 'Clicked',
      screen: 'Products Screen',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { isStorefrontPage: true }),
        productId: item.id,
        product_page: 'Storefront Page',
      },
    });
  };

  return (
    <StyledList className="table-responsive Table--PaymentpagesV3">
      <table className="table table-hover table-striped">
        <thead>
          <tr>
            <th />
            <th>Product Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Quantity in stock</th>
            <th>Status</th>
          </tr>
        </thead>
        <TableBody isLoading={loading} colSpan={8} rows={products} emptyTableMsg="No data found!">
          {products.map((item) => {
            const productName = decodeHTMLEntities(item.product_name);
            const showStrikethrough = shouldShowStrikethrough(item.discounted_amount, item.amount);
            return (
              <EntityItemRow id={item.id} key={item.id}>
                <td className="text-right">
                  {item.images[0]?.original && (
                    <img
                      src={item.images[0].original}
                      height="28"
                      width="28"
                      alt={item.product_name}
                    />
                  )}
                </td>
                <td>
                  <a onClick={() => onProductNameClick(item)}>{productName}</a>
                </td>
                <td>
                  {item.categories.map((category, index) => {
                    return index === 0 ? category.name : `, ${category.name}`;
                  })}
                </td>
                <td>
                  {showStrikethrough ? (
                    <>
                      <strike>
                        <Amount value={item.amount} currency={item.currency} />
                      </strike>{' '}
                      <Amount value={item.discounted_amount} currency={item.currency} />
                    </>
                  ) : (
                    <Amount value={+item.amount} currency={item.currency} />
                  )}
                </td>
                <td>{getQuantityInStock(item)}</td>
                <td>
                  <StyledStatusLabel status={item.status} />
                </td>
              </EntityItemRow>
            );
          })}
        </TableBody>
      </table>
    </StyledList>
  );
};
