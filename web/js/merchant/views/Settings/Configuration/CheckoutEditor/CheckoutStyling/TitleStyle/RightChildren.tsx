import React, { useState } from 'react';

import { Link } from '@razorpay/blade/components';

import ChooseTitleType from './ChooseTitleType';
import EditLogoTitle from './EditLogoTitle';

const RightChildren = () => {
  const [isShowTitleTypeModal, setShowTitleTypeModal] = useState(false);
  const [isShowEditModal, setShowEditModal] = useState(false);

  return (
    <>
      <Link
        variant="button"
        color="primary"
        size="small"
        onClick={() => setShowTitleTypeModal(true)}
        testID="title-style-select-button"
      >
        Select
      </Link>
      {isShowTitleTypeModal ? (
        <ChooseTitleType
          setShowTitleTypeModal={setShowTitleTypeModal}
          setShowEditModal={setShowEditModal}
        />
      ) : null}
      {isShowEditModal ? (
        <EditLogoTitle
          setShowTitleTypeModal={setShowTitleTypeModal}
          setShowEditModal={setShowEditModal}
        />
      ) : null}
    </>
  );
};

export default RightChildren;
