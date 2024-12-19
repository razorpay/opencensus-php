import React from 'react';
import { useTheme, Box, Card, Text, CardBody, Heading } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useNavigate } from 'react-router-dom';
import { productConfigMap } from './config';
import useConnectedProducts from 'merchant/components/NavigationLayout/hooks/useConnectedProducts';

const ProductCardContent = ({ title, description }) => {
  return (
    <React.Fragment>
      <Text weight="semibold">{title}</Text>
      <Text size="small" weight="regular">
        {description}
      </Text>
    </React.Fragment>
  );
};

const StyledCard = ({ children, ...props }) => {
  return (
    <Card
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      elevation="lowRaised"
      padding="spacing.3"
      minHeight={'94px'}
      {...props}
    >
      <CardBody>{children}</CardBody>
    </Card>
  );
};

const PaymentsCard = ({ title, description, imageSrc, onClick, productAlias }) => {
  return (
    <StyledCard
      onClick={() => {
        onClick(productAlias);
      }}
    >
      <Box display="flex" gap="8px" justifyContent={'space-between'}>
        <Box alignSelf="end">
          <ProductCardContent title={title} description={description} />
        </Box>
        {imageSrc && <img src={imageSrc} alt={`${title} Card`} />}
      </Box>
    </StyledCard>
  );
};

const ProductCard = ({ title, description, imageSrc, onClick, productAlias }) => {
  return (
    <StyledCard
      onClick={() => {
        onClick(productAlias);
      }}
    >
      <Box
        display="flex"
        flexDirection={
          productAlias === productConfigMap.payments_top_navigation_item.productAlias
            ? 'row'
            : 'column'
        }
        gap="16px"
      >
        {imageSrc && <img src={imageSrc} alt={`${title} Card`} />}
        <ProductCardContent title={title} description={description} />
      </Box>
    </StyledCard>
  );
};

const ConnectedMobileHome = () => {
  const { theme } = useTheme();
  const navigate = useNavigate();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  // TODO: fix esling issue post initial release
  // eslint-disable-next-line @typescript-eslint/naming-convention
  const { loading, getProductAction, listItemsByAlias } = useConnectedProducts();
  const isMobile = matchedDeviceType === 'mobile';

  //TODO: add loaders/shimmers if required post initial release
  if (!isMobile || loading) return null;

  const handleProductClick = (productConfig) => {
    const { defaultPath, productAlias } = productConfig;
    const { type, value } = getProductAction(productAlias);

    if (type == 'external') {
      window.open(value!, '_blank');
      return;
    }

    // We reached here which it is internal redirect, can be a growth_page or access denied page
    navigate(defaultPath);
  };

  return (
    <Box
      height={'100vh'}
      paddingRight="8px"
      paddingLeft="8px"
      paddingTop="32px"
      paddingBottom="8px"
      display="flex"
      flexDirection="column"
      gap="16px"
      backgroundColor={'surface.background.gray.subtle'}
    >
      <Box width="312px" alignSelf="center">
        <Text textAlign="center" color="surface.text.gray.muted" variant="body" weight="medium">
          Welcome to Razorpay!
        </Text>
        <Heading size="xlarge" textAlign="center" weight="semibold">
          What do you want to do today?
        </Heading>
      </Box>

      <Box display="grid" gap="8px" gridTemplateColumns="1fr 1fr">
        {Object.values(productConfigMap).map(
          ({ title, description, imageSrc, productAlias, ...rest }) => {
            if (!listItemsByAlias[productAlias]) return null;
            return productAlias === productConfigMap.payments_top_navigation_item.productAlias ? (
              <Box gridColumn="span 2">
                <PaymentsCard
                  key={productAlias}
                  title={title}
                  productAlias={productAlias}
                  description={description}
                  imageSrc={imageSrc}
                  onClick={() => handleProductClick({ productAlias, ...rest })}
                />
              </Box>
            ) : (
              <ProductCard
                key={productAlias}
                productAlias={productAlias}
                title={title}
                description={description}
                imageSrc={imageSrc}
                onClick={() => handleProductClick({ productAlias, ...rest })}
              />
            );
          },
        )}
      </Box>
    </Box>
  );
};

export default ConnectedMobileHome;
