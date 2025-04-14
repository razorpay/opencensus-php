import React from 'react';
import { Button, SearchIcon } from '@razorpay/blade/components';
import { isMobileDevice } from 'merchant/components/Home/data';
import { POS_SALES_URL } from 'merchant/constants/urls';

const SearchButton = () => {
  const handleSearchButtonClick = () => {
    window.dispatchEvent(new Event('toggle-search'));
  };

  const showSearchButton = () => {
    return isMobileDevice() && window.location.pathname === POS_SALES_URL;
  };

  if (!showSearchButton()) return null;

  return (
    <Button
      icon={SearchIcon}
      variant="tertiary"
      color="white"
      size="small"
      marginRight="spacing.3"
      onClick={handleSearchButtonClick}
    >
      Search
    </Button>
  );
};

export default SearchButton;
