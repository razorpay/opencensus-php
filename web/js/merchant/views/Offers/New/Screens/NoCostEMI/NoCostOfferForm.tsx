import React from 'react';

import Input from 'common/new-ui/Input';
import EMITenureAction from 'merchant/views/Offers/New/Screens/NoCostEMI/NoCostOfferActionRow';
import { StyledActionRow } from 'merchant/views/Offers/New/Screens/NoCostEMI/Styled';
import { offerHeaders } from 'merchant/views/Offers/New/Screens/NoCostEMI/constants';
import { NoCostOfferFormProps } from 'merchant/views/Offers/New/Screens/NoCostEMI/types';

export const NoCostOfferForm = ({
  tenure,
  formData,
  onChange,
  onOffersChange,
  offersData,
}: NoCostOfferFormProps): JSX.Element => {
  return (
    <Input.Group label="Details" required>
      <div className="emi-options">
        <StyledActionRow className="emi-option heading">
          {offerHeaders.map((header) => (
            <div className={`offer_header ${header.key}`} key={header.key}>
              <p>{header.value}</p>
            </div>
          ))}
        </StyledActionRow>
        {tenure.map((plan, i) => (
          <EMITenureAction
            onOffersChange={onOffersChange}
            offersData={offersData}
            key={i}
            onChange={onChange}
            formData={formData}
            plan={plan}
          />
        ))}
      </div>
    </Input.Group>
  );
};
