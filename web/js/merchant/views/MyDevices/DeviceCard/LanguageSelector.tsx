import React, { useState } from 'react';
import {
  ActionList,
  ActionListItem,
  BottomSheet,
  BottomSheetBody,
  BottomSheetHeader,
  Heading,
  Dropdown,
  Box,
  Text,
  DropdownLink,
  ChevronUpIcon,
  DropdownOverlay,
  ChevronDownIcon,
} from '@razorpay/blade/components';

import { useMobile } from 'common/hooks/useMobile';

const LanguageList: React.FC<{
  languages: string[];
  onLanguageSelect: (language: string) => void;
  selectedLanguage: string;
}> = ({ languages, onLanguageSelect, selectedLanguage }) => {
  return (
    <ActionList>
      {languages.map((language: string) => (
        <ActionListItem
          onClick={() => onLanguageSelect(language)}
          title={language}
          value={language}
          key={language}
          isSelected={selectedLanguage === language}
        />
      ))}
    </ActionList>
  );
};

const LanguageSelector: React.FC<{
  selectedLanguage: string;
  onLanguageSelect: (language: string) => void;
  availableLanguage: string[];
}> = ({ onLanguageSelect, selectedLanguage, availableLanguage }) => {
  // const { matchedDeviceType } = useBreakpoint({ breakpoints: theme.breakpoints });
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const [currentLang, setCurrentLang] = useState(selectedLanguage);

  const isMobile = useMobile();

  const handleDroddownToggle = (isOpen) => {
    if (!isOpen) {
      setIsDropdownOpen(false);
    }
  };

  const handleLanguageSelect = (language: string) => {
    setCurrentLang(language);
    onLanguageSelect(language);
  };

  return (
    <Box>
      <Text size="medium" color="surface.text.gray.subtle">
        Language
      </Text>
      <Dropdown onOpenChange={handleDroddownToggle}>
        <DropdownLink
          icon={isDropdownOpen ? ChevronUpIcon : ChevronDownIcon}
          iconPosition="right"
          onClick={() => setIsDropdownOpen(!isDropdownOpen)}
          testID="language-selector"
        >
          {currentLang}
        </DropdownLink>
        {isMobile ? (
          <BottomSheet
            isOpen={isDropdownOpen}
            onDismiss={() => {
              setIsDropdownOpen(false);
            }}
          >
            <BottomSheetHeader>
              <Heading color="surface.text.gray.normal">Language</Heading>
            </BottomSheetHeader>
            <BottomSheetBody>
              <LanguageList
                selectedLanguage={selectedLanguage}
                languages={availableLanguage}
                onLanguageSelect={handleLanguageSelect}
              />
            </BottomSheetBody>
          </BottomSheet>
        ) : (
          <DropdownOverlay>
            <LanguageList
              selectedLanguage={selectedLanguage}
              languages={availableLanguage}
              onLanguageSelect={handleLanguageSelect}
            />
          </DropdownOverlay>
        )}
      </Dropdown>
    </Box>
  );
};

export default LanguageSelector;
