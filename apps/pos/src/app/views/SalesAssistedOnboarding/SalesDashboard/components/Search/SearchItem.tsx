import React from 'react';
import { Text, Box, HistoryIcon } from '@razorpay/blade/components';
import { SearchItemWrapper, HighlightedSpan } from './styles';
interface SearchItem {
  isRecentSearch?: boolean;
  primaryText: string;
  secondaryText: string;
  searchText: string;
  onClick: () => void;
}

const HighlightedText = (text: string, highlight: string) => {
  if (!highlight.trim() || !text.toLowerCase().includes(highlight.toLowerCase())) return text;

  const regex = new RegExp(`(${highlight})`, 'gi');
  const parts = text.split(regex);

  return (
    <>
      {parts.map((part, index) =>
        part.toLowerCase() === highlight.toLowerCase() ? (
          <HighlightedSpan key={index}>{part}</HighlightedSpan>
        ) : (
          part
        ),
      )}
    </>
  );
};

const SearchItem = ({
  isRecentSearch,
  primaryText,
  secondaryText,
  searchText,
  onClick,
}: SearchItem) => {
  return (
    <SearchItemWrapper onClick={onClick}>
      <Box
        display={'flex'}
        paddingY={'spacing.4'}
        justifyContent={'space-between'}
        alignItems={'center'}
        testID="search-item"
      >
        <Box display={'flex'} alignItems={'center'}>
          {isRecentSearch && <HistoryIcon marginRight={'spacing.3'} />}
          <Text size="medium" color="interactive.text.staticBlack.normal" weight="regular">
            {primaryText ? HighlightedText(primaryText, searchText) : ''}
          </Text>
        </Box>
        <Text size="small" color="surface.text.gray.muted">
          {secondaryText || ''}
        </Text>
      </Box>
    </SearchItemWrapper>
  );
};

export default SearchItem;
