import React, { useState, useEffect } from 'react';
import NavLinkItem from 'merchant/components/SidebarV2/components/NavLinkItem';
import Collapsible from 'common/components/Collapsible';
import { PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
import { ProductHeading, Items, Toggler } from './styled';
import NavGroupShimmer from 'merchant/components/SidebarV2/components/Shimmer';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { Products, NavLinkProductPropsInterface } from 'merchant/components/SidebarV2/typings';

const NavLinkProduct = ({
  heading,
  products,
  routes,
  activeTab,
  loading,
  user,
}: NavLinkProductPropsInterface): JSX.Element | null => {
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const [validProducts, setValidProducts] = useState<Products[] | []>();

  const handleToggle = (): void => setIsOpen((prevState) => !prevState);

  useEffect(() => {
    const productsToShow = products.filter(
      (each) =>
        PRODUCTS_DATA[each.product_id] &&
        showWhenUtil({
          additionalCondition: PRODUCTS_DATA[each.product_id].additionalCondition,
        }),
    );
    setValidProducts(productsToShow);
    if (productsToShow.slice(3).find((each) => each.product_id === activeTab)) {
      setIsOpen(true);
    }
  }, [products]);

  useEffect(() => {
    if (validProducts?.slice(3).find((each) => each.product_id === activeTab)) {
      setIsOpen(true);
    }
  }, [validProducts, activeTab]);

  const RenderNavLink = ({ product }) => (
    <NavLinkItem
      routes={routes}
      activeTab={activeTab}
      user={user}
      {...product}
      {...PRODUCTS_DATA[product.product_id]}
    />
  );

  return validProducts?.length ? (
    <>
      <ProductHeading>{heading}</ProductHeading>
      {loading ? (
        <NavGroupShimmer />
      ) : (
        <Items>
          {validProducts.slice(0, 3).map((each, index) => (
            <RenderNavLink key={`${each.title}_${index}`} product={each} />
          ))}
          <Collapsible open={isOpen}>
            <Items>
              {validProducts.slice(3).map((each, index) => (
                <RenderNavLink key={`${each.title}_${index}`} product={each} />
              ))}
            </Items>
          </Collapsible>
          {validProducts.length > 3 && (
            <Toggler onClick={handleToggle} type="button">
              {isOpen ? 'Show less' : `Show all (${validProducts.length})`}
            </Toggler>
          )}
        </Items>
      )}
    </>
  ) : null;
};

export default NavLinkProduct;
