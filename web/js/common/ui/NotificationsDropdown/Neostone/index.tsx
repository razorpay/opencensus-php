import React, { useState } from 'react';
import NitroSelfServeDetails from './NitroSelfServeDetails';
import NitroSelfServeForms from './NitroSelfServeForms';
import XCAHeader from './common/XCAHeader';
import { XCAHeaderText, XCAheaderImage } from './constant/XCAConstant';
import { NSSCommonProps } from './TypeDeclare/XCATypeDeclare';

const NitroSelfServe = ({ user, handleClose, tracking }: NSSCommonProps): React.ReactElement => {
  const [showState, setShowState] = useState('details');
  const updateModalView = () => setShowState('form');
  const contentToShow =
    showState === 'details' ? (
      <NitroSelfServeDetails updateModalView={updateModalView} tracking={tracking} />
    ) : (
      <NitroSelfServeForms
        setShowState={setShowState}
        user={user}
        handleClose={handleClose}
        tracking={tracking}
      />
    );

  return (
    <div className="nitro-self-serve" id="nitro-self-serve">
      <XCAHeader
        XCAHeaderText={XCAHeaderText}
        imageArr={XCAheaderImage}
        handleClose={handleClose}
      />
      <div className="headerBottomBorder" />
      {contentToShow}
    </div>
  );
};

export default NitroSelfServe;
