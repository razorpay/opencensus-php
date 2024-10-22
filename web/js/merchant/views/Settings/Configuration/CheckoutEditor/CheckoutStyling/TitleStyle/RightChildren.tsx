import React, { useState } from 'react';
import { Link } from '@razorpay/blade/components';

import { AVAILABLE_TITLE_STYLE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

import ChooseTitleType from './ChooseTitleType';
import EditLogoTitle from './EditLogoTitle';
import track from './track';

const RightChildren = () => {
  const [isShowTitleTypeModal, setShowTitleTypeModal] = useState(false);
  const [isShowEditModal, setShowEditModal] = useState(false);
  const [selectedTitleStyle, setSelectedTitleStyle] = useState(AVAILABLE_TITLE_STYLE.LOGO_TEXT);

  function handleTitleStyleEditClicked() {
    setShowTitleTypeModal(true);
    track.editTitleStyle();
  }

  return (
    <>
      <Link
        variant="button"
        color="primary"
        size="small"
        onClick={handleTitleStyleEditClicked}
        testID="title-style-select-button"
      >
        Edit
      </Link>
      {isShowTitleTypeModal ? (
        <ChooseTitleType
          setShowTitleTypeModal={setShowTitleTypeModal}
          setShowEditModal={setShowEditModal}
          selectedTitleStyle={selectedTitleStyle}
          setSelectedTitleStyle={setSelectedTitleStyle}
        />
      ) : null}
      {isShowEditModal ? (
        <EditLogoTitle
          setShowTitleTypeModal={setShowTitleTypeModal}
          setShowEditModal={setShowEditModal}
          selectedTitleStyle={selectedTitleStyle}
          setSelectedTitleStyle={setSelectedTitleStyle}
        />
      ) : null}
    </>
  );
};

export default RightChildren;
