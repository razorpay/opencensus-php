import React from 'react';
import { Box, Text, ChevronRightIcon, Button, ArrowLeftIcon } from '@razorpay/blade/components';
import OptimizerIcon from 'assets/reconciliations/payment-optimizer-icon.svg';
import POSIcon from 'assets/reconciliations/pos-icon.svg';
import { useNavigate } from 'react-router-dom';
import styled from 'styled-components';

import OnboardingView from './OnboardingView';
import { selectProductMeta } from './constants';

const ProcuctCard = styled.div`
  cursor: pointer;
`;

export default function SelectProduct({ selectProduct, merchantMeta }) {
  const products = Object.keys(merchantMeta.products);
  const navigate = useNavigate();

  const getIcon = (name) => {
    return name.toLowerCase().includes('optimizer') ? OptimizerIcon : POSIcon;
  };

  return (
    <OnboardingView
      title={selectProductMeta.title}
      question={selectProductMeta.question}
      questionSubText={selectProductMeta.questionSubText}
    >
      {products.map((item) => {
        const product = merchantMeta.products[item];
        return (
          <ProcuctCard key={item} onClick={() => selectProduct(item)}>
            <Box
              display="flex"
              alignItems="center"
              justifyContent="space-between"
              width="480px"
              borderWidth={1}
              borderColor="surface.border.gray.muted"
              padding="spacing.4"
              borderRadius="medium"
              marginBottom="spacing.4"
            >
              <Box display="flex">
                <img src={getIcon(product.header || product.description)} alt="icon" />
                <Box marginLeft="spacing.4">
                  <Text weight="semibold" size="large">
                    {product.header}
                  </Text>
                  <Text color="surface.text.gray.normal">{product.description}</Text>
                </Box>
              </Box>
              <ChevronRightIcon />
            </Box>
          </ProcuctCard>
        );
      })}
      <Box
        width="480px"
        display="flex"
        justifyContent="flex-end"
        alignItems="center"
        marginTop="spacing.6"
      >
        <Button variant="secondary" icon={ArrowLeftIcon} onClick={() => navigate(-1)}>
          Back
        </Button>
      </Box>
    </OnboardingView>
  );
}
