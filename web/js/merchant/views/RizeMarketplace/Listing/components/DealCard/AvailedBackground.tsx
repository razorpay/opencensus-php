import React from 'react';
import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import DealAvailedBottomImage from 'assets/rize/marketplace/deal-availed-bottom.png';
import DealAvailedTopImage from 'assets/rize/marketplace/deal-availed-top.png';

const DealAvailedTopImg = styled.img(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  top: ${theme.spacing[0]}px;
  left: ${theme.spacing[0]}px;
  right: ${theme.spacing[0]}px;
  user-select: none;
  max-width: 100%;
`,
);

const DealAvailedBottomImg = styled.img(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  bottom: -${theme.spacing[3]}px;
  left: -${theme.spacing[3]}px;
  right: -${theme.spacing[3]}px;
  width: calc(100% + ${theme.spacing[5]}px);
  user-select: none;
`,
);

const AvailedBackground = (): JSX.Element => (
  <>
    <DealAvailedTopImg src={DealAvailedTopImage} alt="" />
    <DealAvailedBottomImg src={DealAvailedBottomImage} alt="" />
  </>
);

export default AvailedBackground;
