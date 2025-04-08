import React, { useState, useEffect } from 'react';
import NavLinkItem from 'merchant/components/SidebarV2/components/NavLinkItem';
import Collapsible from 'common/components/Collapsible';
import { PRODUCTS_DATA } from 'merchant/components/SidebarV2/utils/Products';
import { ProductHeading, Items, Toggler, ShowMoreWrapper } from './styled';
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
import { withRouter } from 'common/deprecated/withRouter';
import { useSplitzService } from 'common/splitz';
import { useI18Service } from 'common/i18';
import { Box, Text } from '@razorpay/blade/components';
import { useIsRTUXHomepageEnabled } from 'merchant/containers/Home/RTUX/utils';
import { useIsFtuxV2Enabled } from '@dashboards/payments/containers/Home/FTUX/utils';

const NavLinkProduct = ({
  heading,
  products,
  routes,
  activeTab,
  loading,
  user,
  section_id,
  location,
  toggleMobileMenu,
}: NavLinkProductPropsInterface): JSX.Element | null => {
  const [sectionProducts, setSectionProducts] = useState<ProductsStateInterface>();
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const isRTUXHomepage = useIsRTUXHomepageEnabled();
  const isFtuxV2Enabled = useIsFtuxV2Enabled();

  const showNewHomePage = isRTUXHomepage || isFtuxV2Enabled;

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
            additionalCondition: (users) =>
              PRODUCTS_DATA[each.product_id].additionalCondition(users, {
                abExperiments,
                isConfigTagEnabled,
              }),
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
      toggleMobileMenu={toggleMobileMenu}
    />
  );

  return sectionProducts?.valid?.length ? (
    <>
      {showNewHomePage ? (
        <Text
          weight="semibold"
          marginX="spacing.7"
          marginY="spacing.3"
          size="small"
          color="surface.text.gray.subtle"
        >
          {heading}
        </Text>
      ) : (
        <ProductHeading>{heading}</ProductHeading>
      )}
      {loading ? (
        <NavGroupShimmer />
      ) : (
        <Items data-testid="navlink-product">
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
            <>
              {showNewHomePage ? (
                <ShowMoreWrapper data-testid="show-all-products" onClick={handleToggle}>
                  <Text weight="semibold" size="small" color="interactive.text.primary.subtle">
                    {isOpen ? 'Show less' : `Show all (${sectionProducts.valid.length})`}
                  </Text>
                </ShowMoreWrapper>
              ) : (
                <Toggler data-testid="show-all-products" onClick={handleToggle} type="button">
                  {isOpen ? 'Show less' : `Show all (${sectionProducts.valid.length})`}
                </Toggler>
              )}
            </>
          )}
        </Items>
      )}
      {showNewHomePage ? <Box marginBottom="spacing.6" /> : <Divider />}
    </>
  ) : null;
};

export default withRouter<any>(NavLinkProduct);
