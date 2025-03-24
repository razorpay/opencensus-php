import React, { useEffect } from 'react';
import {
  Button,
  Text,
  ArrowRightIcon,
  Modal,
  ModalBody,
  ModalHeader,
  ModalFooter,
  Box,
  Card,
  CardBody,
} from '@razorpay/blade/components';
import {
  ChooseTitleTypeProps,
  SingleContentProps,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types/titleType';
import { connect } from 'react-redux';

import { DEFAULT_TITLE_TYPE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import {
  CHECKOUT_EDITOR_FIELDS,
  useCheckoutEditor,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

import { ImageWrapper } from './styled';
import track from './track';

const ChooseTitleType: React.FC<ChooseTitleTypeProps> = ({
  setShowTitleTypeModal,
  setShowEditModal,
  setSelectedTitleStyle,
  selectedTitleStyle,
}) => {
  const { values, handleTitleStyleChange } = useCheckoutEditor();

  function handleTitleStyleSelected() {
    const selectedTitleStyle = values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE];
    setShowEditModal(true);
    setShowTitleTypeModal(false);
    track.selectTitleStyle(selectedTitleStyle);
  }

  useEffect(() => {
    setSelectedTitleStyle(values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]);
  }, []);

  const SingleContent: React.FC<SingleContentProps> = ({ title, description, src, alt, value }) => {
    return (
      <Card
        elevation="none"
        key={value}
        onClick={() => {
          handleTitleStyleChange(value);
          setSelectedTitleStyle(value);
        }}
        accessibilityLabel={value}
        isSelected={value === selectedTitleStyle}
        testID={`title-style-${value}`}
      >
        <CardBody>
          <Text color="surface.text.gray.normal" weight="semibold" size="large" variant="body">
            {title}
          </Text>
          <Text color="surface.text.gray.subtle" weight="regular" size="medium" variant="body">
            {description}
          </Text>
          <ImageWrapper src={src} alt={alt} />
        </CardBody>
      </Card>
    );
  };

  return (
    <Modal isOpen={true} size="large" onDismiss={() => setShowTitleTypeModal(false)}>
      <ModalHeader
        title="Choose a title style"
        subtitle="Select how you want your logo to be displayed in checkout"
      />
      <ModalBody>
        <Box
          display="flex"
          gap="spacing.7"
          width="100%"
          padding="spacing.4"
          justifyContent="center"
          overflowX="scroll"
        >
          {DEFAULT_TITLE_TYPE.map((item) => (
            <SingleContent key={item.value} {...item} />
          ))}
        </Box>
      </ModalBody>
      <ModalFooter>
        <Box display="flex" width="100%" justifyContent="flex-end">
          <Button
            variant="primary"
            color="primary"
            size="medium"
            isFullWidth={false}
            icon={ArrowRightIcon}
            iconPosition="right"
            onClick={handleTitleStyleSelected}
            isDisabled={!values[CHECKOUT_EDITOR_FIELDS.TITLE_STYLE]}
          >
            Continue to upload
          </Button>
        </Box>
      </ModalFooter>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(ChooseTitleType);
