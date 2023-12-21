import { Box, CloseIcon, Link, SearchIcon } from '@razorpay/blade/components';
import { CommonStateProps } from 'merchant/components/HeaderNav/UniversalSearch/typings';
import { trackSearchBarClicked } from 'merchant/components/HeaderNav/UniversalSearch/utils';
import React, { forwardRef } from 'react';
import { connect } from 'react-redux';
import { CloseButton, StyledBaseInput, StyledInputBox } from './styled';

const SearchBar = forwardRef(
  (
    {
      searchQuery,
      setSearch,
      setFocussed,
      isMobile,
      show,
      isDeviceInBreakpoint,
    }: CommonStateProps & { searchQuery: string; isMobile: boolean },
    ref,
  ): JSX.Element => {
    const handleChange = (type, e): void => {
      const { value = '' } = e.target;
      switch (type) {
        case 'close':
          setSearch('');
          setFocussed(false);
          break;
        case 'change':
          setSearch(value);
          break;
        case 'clear':
          setSearch('');
          break;
        /* istanbul ignore next */
        default:
          break;
      }
    };

    const handleFocus = (): void => {
      setFocussed(true);
    };

    return (
      <Box display="flex" alignItems="center" gap="spacing.5">
        <StyledInputBox isDeviceInBreakpoint={isDeviceInBreakpoint} isMobile={isMobile}>
          <SearchIcon color="feedback.icon.neutral.lowContrast" size="medium" />
          <StyledBaseInput
            ref={ref as React.RefObject<HTMLInputElement>}
            name="search"
            type="text"
            value={searchQuery}
            autoComplete="off"
            placeholder="Search payment products, settings, and more"
            onChange={handleChange.bind(null, 'change')}
            onFocus={handleFocus}
            onClick={trackSearchBarClicked}
          />
          {searchQuery.length ? (
            <CloseButton onClick={handleChange.bind(null, 'clear')} data-testid="search-close">
              <CloseIcon color="surface.action.icon.default.lowContrast" size="medium" />
            </CloseButton>
          ) : null}
        </StyledInputBox>
        {show && isMobile ? (
          <Link variant="button" onClick={handleChange.bind(null, 'close')} size="medium">
            Cancel
          </Link>
        ) : null}
      </Box>
    );
  },
);

const mapStateToProps = ({ app }) => ({
  isMobile: app.isMobileResolution,
});

export default connect(mapStateToProps, null)(SearchBar);
