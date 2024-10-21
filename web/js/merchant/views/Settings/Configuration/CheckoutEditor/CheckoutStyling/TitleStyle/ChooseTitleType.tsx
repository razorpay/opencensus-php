import React, { useEffect } from 'react';
import { connect } from 'react-redux';

import { Modal, ModalContent, ModalMask } from 'common/new-ui/Modal';
import { Button, Text, ArrowRightIcon } from '@razorpay/blade/components';

import {
  ContentWrapper,
  FooterWrapper,
  HeadingWrapper,
  ImageWrapper,
  SingleContentWrapper,
} from './styled';

import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import {
  ChooseTitleTypeProps,
  SingleContentProps,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';

import { DEFAULT_TITLE_TYPE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

const ChooseTitleType: React.FC<ChooseTitleTypeProps> = ({
  setShowTitleTypeModal,
  setShowEditModal,
  setSelectedTitleStyle,
  selectedTitleStyle,
}) => {
  const { values, handleTitleStyleChange } = useCheckoutEditor();

  useEffect(() => {
    setSelectedTitleStyle(values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]);
  }, []);

  const SingleContent: React.FC<SingleContentProps> = ({ title, description, src, alt, value }) => {
    return (
      <SingleContentWrapper
        key={value}
        onClick={() => {
          handleTitleStyleChange(value);
          setSelectedTitleStyle(value);
        }}
        isSelected={value === selectedTitleStyle}
        testID={`title-style-${value}`}
      >
        <Text color="surface.text.gray.normal" weight="semibold" size="large" variant="body">
          {title}
        </Text>
        <Text color="surface.text.gray.subtle" weight="regular" size="medium" variant="body">
          {description}
        </Text>
        <ImageWrapper src={src} alt={alt} />
      </SingleContentWrapper>
    );
  };

  const content: any = (
    <>
      <HeadingWrapper>
        <Text color="surface.text.gray.normal" weight="semibold" size="large" variant="body">
          Choose a title style
        </Text>
        <Text color="surface.text.gray.muted" weight="regular" size="small" variant="body">
          Select how you want your logo to be displayed in checkout
        </Text>
      </HeadingWrapper>
      <ContentWrapper>
        {DEFAULT_TITLE_TYPE.map((item) => (
          <SingleContent key={item.value} {...item} />
        ))}
      </ContentWrapper>
      <FooterWrapper>
        <Button
          variant="primary"
          color="primary"
          size="medium"
          isFullWidth={false}
          icon={ArrowRightIcon}
          iconPosition="right"
          onClick={() => {
            setShowEditModal(true);
            setShowTitleTypeModal(false);
          }}
          isDisabled={!values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]}
        >
          Continue to upload
        </Button>
      </FooterWrapper>
    </>
  );
  return (
    <ModalMask>
      <Modal className="non3dsCardsModal" onClose={() => setShowTitleTypeModal(false)}>
        <ModalContent header={undefined} banner={undefined}>
          {content}
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(ChooseTitleType);
