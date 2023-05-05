import React from 'react';
import { Theme, Link, IconButton, PlusIcon } from '@razorpay/blade/components';
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
  color: ${theme.colors.surface.text.normal.lowContrast};
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
    color: ${theme.colors.feedback.text.negative.lowContrast}
  }
`,
);

export const SubHeading = styled.p(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.subtle.lowContrast};
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
  color: ${theme.colors.surface.text.subtle.lowContrast};
  font-style: normal;
  color: ${theme.colors.surface.text.subtle.lowContrast};
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[75]}px;
  line-height: ${theme.typography.lineHeights[50]}px;
  position: absolute;
  bottom: -16px;
`,
);

const PriceField = styled.span(
  ({ theme, strikethrough }: { theme: Theme; strikethrough?: boolean }) => `
  text-decoration: ${strikethrough ? 'line-through' : 'none'};
  color: ${
    strikethrough
      ? theme.colors.surface.text.muted.lowContrast
      : theme.colors.surface.text.subtle.lowContrast
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
          {formatTextAmountField(discounted_amount)}
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
  color: ${theme.colors.action.text.secondary.default};
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
  flex: 1;
  max-width: 210px;
`;

export const SellingPriceWrapper = styled.div`
  position: relative;
  flex: 1;
  max-width: 210px;
`;

export const ImageSelectorWrapper = styled.div(
  ({ theme }) => `
  background-color: ${theme.colors.surface.background.level3.lowContrast};
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
    border: 1px solid ${theme.colors.surface.text.subtle.lowContrast};
    color: ${theme.colors.surface.text.subtle.lowContrast};
    border-radius: ${theme.border.radius.large}px;
    &:hover {
      color: ${theme.colors.surface.text.subtle.lowContrast};
    }
  };
  `,
);

export const AddImageButtonWrapper = styled.div(
  ({ theme }) => `
    width: 56px;
    height: 56px;
    background: ${theme.colors.surface.background.level3.lowContrast};
    border: 1px dashed #2A86F3;
    border-radius: ${theme.border.radius.small}px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    & > button {
      border: 1px solid ${theme.colors.action.icon.secondary.default};
      color: ${theme.colors.action.icon.secondary.default};
      border-radius: ${theme.border.radius.large}px;
      &:hover {
        color: ${theme.colors.action.icon.secondary.default};
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
  height: calc(100vh - 45px - 120px - 75px); // taken from select all products drawer
  overflow: auto;
`;
