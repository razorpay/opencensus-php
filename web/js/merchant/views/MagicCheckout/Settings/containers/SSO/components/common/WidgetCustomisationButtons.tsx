import {
  ActionList,
  ActionListItem,
  ArrowRightIcon,
  Box,
  EditIcon,
  IconButton,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  DotIcon,
} from '@razorpay/blade/components';
import React, { useState, useEffect, useRef } from 'react';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import { FONT_FAMILY_NAMES } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';
import {
  ColorButton,
  ButtonContainer,
  StyledSpan,
  CustomisationButtonsWrapper,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/styled';

type WidgetCustomisationButtonsProps = {
  isEditable: boolean;
  setIsEditable: (value: boolean) => void;
};

const WidgetCustomisationButtons: React.FC<WidgetCustomisationButtonsProps> = ({
  isEditable,
  setIsEditable,
}) => {
  const { ssoWidget } = useSSOContext();
  const [bgColor, setBgColor] = useState<string>(ssoWidget.backgroundColor);
  const [btnColor, setBtnColor] = useState<string>(ssoWidget.buttonColor);
  const [font, setFont] = useState<string>(ssoWidget.fontFamily);
  const [showFontSelector, setShowFontSelector] = useState<boolean>(false);

  const { updateSSOWidgetCss } = useSSOContext();

  // Set initial values for background color, button color, and font family from store
  useEffect(() => {
    if (ssoWidget.backgroundColor) {
      setBgColor(ssoWidget.backgroundColor);
    }
    if (ssoWidget.buttonColor) {
      setBtnColor(ssoWidget.buttonColor);
    }
    if (ssoWidget.fontFamily) {
      setFont(ssoWidget.fontFamily);
    }
  }, [ssoWidget]);

  // Update the CSS of the SSO widget when background color, button color, or font family changes
  useEffect(() => {
    updateSSOWidgetCss({
      backgroundColor: bgColor,
      buttonColor: btnColor,
      fontFamily: font,
    });
  }, [font, btnColor, bgColor]);

  const handleFontChange = (value: string) => {
    if (!value) {
      return;
    }
    setFont(value);
    setShowFontSelector(false);
  };

  return (
    <CustomisationButtonsWrapper>
      <ButtonContainer>
        <ColorButton color={bgColor}>
          <input type="color" onChange={(e) => setBgColor(e.target.value)} value={bgColor} />
        </ColorButton>
        <span>Change Background Colour</span>
      </ButtonContainer>

      <ButtonContainer>
        <ColorButton color={btnColor}>
          <input type="color" onChange={(e) => setBtnColor(e.target.value)} value={btnColor} />
        </ColorButton>
        <span>Change Button Color</span>
      </ButtonContainer>

      <ButtonContainer>
        <StyledSpan
          onClick={() => {
            setShowFontSelector(!showFontSelector);
          }}
        >
          Change Font
        </StyledSpan>
        <ArrowRightIcon />
        {showFontSelector && (
          <Box marginTop="100px" minWidth="160px" position={'absolute'}>
            <Dropdown selectionType="single">
              <SelectInput
                defaultValue={ssoWidget.fontFamily}
                value={font}
                placeholder="Change Font"
                name="select-font"
                accessibilityLabel="select font"
                onChange={({ values }) => {
                  handleFontChange(values?.[0]);
                }}
              />
              <DropdownOverlay>
                <ActionList>
                  {Object.values(FONT_FAMILY_NAMES).map((name, index) => (
                    <ActionListItem key={index} title={name} value={name} />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </Box>
        )}
      </ButtonContainer>

      <ButtonContainer>
        <IconButton
          onClick={() => setIsEditable(!isEditable)}
          icon={isEditable ? DotIcon : EditIcon}
          emphasis="intense"
          size="large"
          accessibilityLabel="edit mode"
        />
        <span>{isEditable ? 'Live Mode' : 'Edit Mode'}</span>
      </ButtonContainer>
    </CustomisationButtonsWrapper>
  );
};

export default WidgetCustomisationButtons;
