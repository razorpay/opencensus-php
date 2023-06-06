import React, { useState, useEffect } from 'react';
import NavLinkItem from 'merchant/components/SidebarV2/components/NavLinkItem';
import Collapsible from 'common/components/Collapsible';
import { PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
import { ProductHeading, Items, Toggler } from './styled';
import NavGroupShimmer from 'merchant/components/SidebarV2/components/Shimmer';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  NavLinkProductPropsInterface,
  ProductsStateInterface,
  FilterProductInterface,
} from 'merchant/components/SidebarV2/typings';
import { PromotedReservationState } from 'merchant/components/SidebarV2/constants/constants';
import { swapElements } from 'merchant/components/SidebarV2/utils/utils';
import Divider from 'merchant/components/SidebarV2/components/Divider';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getActiveTab } from 'merchant/components/SidebarV2/utils/href';
import { withRouter } from 'react-router';

const NavLinkProduct = ({
  heading,
  products,
  routes,
  activeTab,
  loading,
  user,
  section_id,
  location,
}: NavLinkProductPropsInterface): JSX.Element | null => {
  const [sectionProducts, setSectionProducts] = useState<ProductsStateInterface>();
  const [isOpen, setIsOpen] = useState<boolean>(false);

  const handleToggle = (): void => {
    analyticsTrack({
      objectName: 'sidebar',
      actionName: 'clicked',
      screen: titleCase(getActiveTab(location)) || 'home page',
      toCleverTap: true,
      properties: {
        clickedElement: isOpen ? 'show less' : 'show all',
        section: titleCase(heading),
        location: 'sidebar',
        sidebar: 'v2',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    setIsOpen((prevState) => !prevState);
  };

  useEffect(() => {
    const reservationState = PromotedReservationState[section_id];
    const { valid } = products.reduce(
      (accumulator, each, index) => {
        if (
          PRODUCTS_DATA[each.product_id] &&
          showWhenUtil({
            additionalCondition: PRODUCTS_DATA[each.product_id].additionalCondition,
          })
        ) {
          accumulator.valid.push(each);
          if (
            each.category &&
            reservationState?.[each.category] &&
            accumulator[each.category] &&
            accumulator[each.category].length < reservationState[each.category].reservedPos.length
          ) {
            accumulator[each.category].push(accumulator.valid.length);
          }
        }
        if (index === products.length - 1 && reservationState) {
          const reservedArray = [...accumulator.valid];
          Object.keys(reservationState).forEach((eachCategory) => {
            if (accumulator[eachCategory]?.length) {
              accumulator[eachCategory].forEach((position, index) => {
                swapElements(
                  reservedArray,
                  position - 1,
                  reservationState[eachCategory].reservedPos[index] - 1,
                );
              });
            }
          });
          accumulator.valid = reservedArray;
        }
        return accumulator;
      },
      {
        valid: [],
        promoted: [],
      } as FilterProductInterface,
    );
    setSectionProducts({
      valid,
    });
    // user.tags is added as dependency to reevaluate the products again as ShowWhenUtil is dependent on user tags
  }, [products, section_id, user.tags]);

  useEffect(() => {
    if (sectionProducts?.valid?.slice(3).find((each) => each.product_id === activeTab)) {
      setIsOpen(true);
    }
  }, [sectionProducts, activeTab]);

  const RenderNavLink = ({ product }) => (
    <NavLinkItem
      routes={routes}
      activeTab={activeTab}
      user={user}
      {...product}
      {...PRODUCTS_DATA[product.product_id]}
    />
  );

  return sectionProducts?.valid?.length ? (
    <>
      <ProductHeading>{heading}</ProductHeading>
      {loading ? (
        <NavGroupShimmer />
      ) : (
        <Items>
          {sectionProducts.valid.slice(0, 3).map((each, index) => (
            <RenderNavLink key={`${each.title}_${index}`} product={each} />
          ))}
          <Collapsible open={isOpen}>
            <Items>
              {sectionProducts.valid.slice(3).map((each, index) => (
                <RenderNavLink key={`${each.title}_${index}`} product={each} />
              ))}
            </Items>
          </Collapsible>
          {sectionProducts.valid.length > 3 && (
            <Toggler onClick={handleToggle} type="button">
              {isOpen ? 'Show less' : `Show all (${sectionProducts.valid.length})`}
            </Toggler>
          )}
        </Items>
      )}
      <Divider />
    </>
  ) : null;
};

export default withRouter(NavLinkProduct);
