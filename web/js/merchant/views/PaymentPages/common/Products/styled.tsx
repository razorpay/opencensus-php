import React from 'react';
import { Theme, Link, IconButton, PlusIcon, Text } from '@razorpay/blade/components';
import styled from 'styled-components';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import { formatTextAmountField } from './utils';
import PictureImage from 'assets/payment_pages/picture-icon.svg';

export const HeadingContainer = styled.div`
  display: flex;
  align-items: center;
`;

export const Heading = styled.p(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.gray.normal};
  font-size: ${theme.typography.fonts.size[400]}px;
  font-weight: ${theme.typography.fonts.weight.bold};
  line-height: ${theme.typography.lineHeights['3xl']}px;
  margin-bottom: ${theme.spacing[5]}px;
`,
);

export const HeadingInfo = styled.div(
  ({ theme }: { theme: Theme }) => `
  flex: 1;
  margin: 0 ${theme.spacing[4]}px;
  display: flex;
  align-items: center;
  justify-content: space-between;

  > button {
    color: ${theme.colors.feedback.text.negative.intense}
  }
`,
);

export const SubHeading = styled.p(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.gray.subtle};
  font-size: ${theme.typography.fonts.size[100]}px;
  font-weight: ${theme.typography.fonts.weight.regular};
  line-height: ${theme.typography.lineHeights[100]}px;
  margin-bottom: ${theme.spacing[7]}px;
  `,
);

export const ProductDrawerWrapper = styled(PaymentPagesDrawer)(
  ({ theme }: { theme: Theme }) => `
  .Modal-body {
    display: flex;
    flex-direction: column;
    gap: ${theme.spacing[7]}px;
    // unit field
    & > div:nth-child(4) {
      max-width: 210px; // (500 - 2 x 40px padding)/2
    }
  }

  .Modal-close {
    top: 26px;
  }
`,
);

export const PriceWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  gap: ${theme.spacing[7]}px;
  margin-bottom: 32px;
  position: relative;
  & div:first-child {
      input + div {
        // hide suffix (so that overlap input doesn't overlap with actual add discount button)
        p {
          visibility: hidden;
        }
    }
  }
`,
);

const PricePreviewWrapper = styled.p(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.gray.subtle};
  font-style: normal;
  color: ${theme.colors.surface.text.gray.subtle};
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[75]}px;
  line-height: ${theme.typography.lineHeights[50]}px;
  position: absolute;
  bottom: -26px;
`,
);

const PriceField = styled.span(
  ({ theme, strikethrough }: { theme: Theme; strikethrough?: boolean }) => `
  text-decoration: ${strikethrough ? 'line-through' : 'none'};
  padding-right: 8px;
  color: ${
    strikethrough ? theme.colors.surface.text.gray.muted : theme.colors.surface.text.gray.subtle
  };
`,
);

export const PricePreview = ({
  amount,
  discounted_amount,
}: {
  amount: string;
  discounted_amount: string;
}): React.ReactElement | null => {
  const price = (
    <PriceField>
      {discounted_amount ? (
        <>
          <PriceField strikethrough>{formatTextAmountField(amount)}</PriceField>
          <Text size="small" display="inline" color="surface.text.gray.subtle">
            {formatTextAmountField(discounted_amount)}
          </Text>
        </>
      ) : (
        formatTextAmountField(amount)
      )}
    </PriceField>
  );
  if (amount) return <PricePreviewWrapper>Customers will see price: {price}</PricePreviewWrapper>;
  return null;
};

const LinkTextWrapper = styled.p<any>(
  ({ theme, isRemove }: { theme: Theme; isRemove: boolean }) => `
  color: ${theme.colors.interactive.text.primary.normal};
  font-style: normal;
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[75]}px;
  line-height: ${theme.typography.lineHeights[50]}px;
  cursor: pointer;

  position: absolute;
  right: 4px;
  top: ${isRemove ? 'unset' : '35px'};
  background: ${isRemove ? '#fff' : 'transparent'};
  z-index: 1;
`,
);

export const AddDiscountButton = ({ ...props }) => {
  return <LinkTextWrapper {...props}>Add discount</LinkTextWrapper>;
};

export const RemoveDiscountButton = ({ ...props }) => {
  return (
    <LinkTextWrapper {...props} isRemove>
      Remove
    </LinkTextWrapper>
  );
};

export const DiscountedPriceWrapper = styled.div`
  position: relative;
  top: 4px;
`;

export const SellingPriceWrapper = styled.div`
  position: relative;
  top: 0;
`;

export const ImageSelectorWrapper = styled.div(
  ({ theme }) => `
  background-color: ${theme.colors.surface.background.gray.moderate};
  border: 1px dashed #2A86F3;
  border-radius: ${theme.border.radius.small}px;
  height: 56px;
  padding: ${theme.spacing[3]}px ${theme.spacing[5]}px;
  display: flex;
  align-items: center;
  cursor: pointer;

  & > div {
    display: flex;
    flex-direction: column;
    padding-left: ${theme.spacing[4]}px;
  }
`,
);

export const ImageSelector = ({ onClick }: { onClick: () => void }): React.ReactElement => {
  return (
    <ImageSelectorWrapper onClick={onClick}>
      <img src={PictureImage} alt="picture icon" width={16} height={16} />
      <div>
        <Link variant="button">Upload product images</Link>
        click to add upto 5 images
      </div>
    </ImageSelectorWrapper>
  );
};

export const ImagesContainer = styled.div(
  ({ theme }) => `
  display: flex;
  gap: ${theme.spacing[7]}px;
`,
);

export const ImageButton = styled.div(
  ({ theme }) => `
  position: relative;
  & > button {
    position: absolute;
    top: -8px;
    right: -8px;
    background: white;
    border: 1px solid ${theme.colors.surface.text.gray.subtle};
    color: ${theme.colors.surface.text.gray.subtle};
    border-radius: ${theme.border.radius.large}px;
    &:hover {
      color: ${theme.colors.surface.text.gray.subtle};
    }
  };
  `,
);

export const AddImageButtonWrapper = styled.div(
  ({ theme }) => `
    width: 56px;
    height: 56px;
    background: ${theme.colors.surface.background.gray.moderate};
    border: 1px dashed #2A86F3;
    border-radius: ${theme.border.radius.small}px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    & > button {
      border: 1px solid ${theme.colors.interactive.icon.primary.normal};
      color: ${theme.colors.interactive.icon.primary.normal};
      border-radius: ${theme.border.radius.large}px;
      &:hover {
        color: ${theme.colors.interactive.icon.primary.normal};
      }
    }
  `,
);

export const AddImageButton = ({ onClick }: { onClick: () => void }): React.ReactElement => {
  return (
    <AddImageButtonWrapper onClick={onClick}>
      <IconButton icon={PlusIcon} accessibilityLabel="Add" onClick={onClick} />
    </AddImageButtonWrapper>
  );
};

export const ScrollableContent = styled.div`
  display: flex;
  flex-direction: column;
  gap: 24px;
  height: calc(100vh - 45px - 120px - 75px);
  overflow: auto;
`;

export const PriceInputField = styled.div`
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
  gap: 8px;
  align-items: end;
  margin-bottom: 32px;

  @media (max-width: 400px) {
    flex-direction: column;
  }
`;

export const TextInputContainer = styled.div`
  flex: 0.5;
  @media (max-width: 400px) {
    flex: 1;
    width: 100%;
  }
`;

export const PriceInfo = styled.div`
  position: absolute;
  left: 0;
  width: 100%;
  @media (max-width: 400px) {
    bottom: 0;
  }

  @media (max-width: 300px) {
    bottom: -8px;
  }
`;

export const StyledItalics = styled.i`
  font-weight: 400;
  font-size: '0.6875rem';
  color: #a2aebe;
`;
